<?php

namespace App\Http\Controllers;

use App\Models\Workorder;
use App\Models\WorkorderType;
use App\Models\WorkorderCategorySimplified;
use App\Models\WorkorderAttachment;
use App\Models\WorkorderVisit;
use App\Models\WorkorderSource;
use App\Models\WorkorderTemplate;
use App\Models\User;
use App\Models\Department;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Traits\HandlesReport;

class WorkorderController extends Controller
{
    use HandlesReport;

    /**
     * 工单列表页面
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // 预热地址全表内存映射：campus_name/address_full 等祖先链 accessor 由此零 SQL 解析（消除每行 N+1）
        \App\Models\Location::allNodesCached();

        // 根据用户角色获取不同的查询范围
        // 直接 with('locationInfo') 即可，location_id 是标准 bigint 外键，不再需要 attachBuildingLocations hack
        $query = $user->getWorkorderQueryScope()
            ->with(['category', 'creator', 'assignee', 'department', 'locationInfo'])
            ->orderBy('created_at', 'desc');

        
        // ---- 状态过滤（集中处理，后续筛选全部为 AND） ----
        // 优先级：明确选择某状态 > 勾选"显示已解决" > 有关键词搜索时全部状态 > 默认仅未完结
        $status = $request->input('status');
        $showClosed = $request->boolean('show_closed');

        if ($status && $status !== 'all') {
            // 用户明确选择了某个状态，精确匹配
            $query->where('status', $status);
        } elseif ($status !== 'all' && !$showClosed && !$request->filled('keyword')) {
            // 默认：只显示未完结工单（待处理、已分配、处理中）
            // 但当有关键词搜索时，自动扩大到全部状态——搜索历史工单是主要用途
            $query->whereIn('status', ['pending', 'assigned', 'processing']);
        }

        // 搜索条件
        if ($request->filled('keyword')) {
            $keyword = trim($request->input('keyword'));

            // 预先查出关键词匹配到的 location 节点 id，用于工单 location_id 反查
            $matchedLocationIds = \App\Models\Location::where('name', 'like', "%{$keyword}%")
                ->orWhere('code', 'like', "%{$keyword}%")
                ->pluck('id')
                ->all();

            $query->where(function($q) use ($keyword, $matchedLocationIds) {
                $q->where('ticket_no', 'like', "%{$keyword}%")
                  ->orWhere('description', 'like', "%{$keyword}%")
                  ->orWhere('contact_name', 'like', "%{$keyword}%")
                  ->orWhere('contact_phone', 'like', "%{$keyword}%")
                  ->orWhere('location_detail', 'like', "%{$keyword}%")
                  ->orWhere('solution', 'like', "%{$keyword}%")
                  ->orWhere('custom_source', 'like', "%{$keyword}%")
                  ->orWhere('department_name', 'like', "%{$keyword}%")
                  ->when(! empty($matchedLocationIds), function ($qq) use ($matchedLocationIds) {
                      $qq->orWhereIn('location_id', $matchedLocationIds);
                  })
                  // 关联：创建人姓名
                  ->orWhereHas('creator', function($uq) use ($keyword) {
                      $uq->where('name', 'like', "%{$keyword}%");
                  })
                  // 关联：处理人姓名
                  ->orWhereHas('assignee', function($uq) use ($keyword) {
                      $uq->where('name', 'like', "%{$keyword}%");
                  });
            });
        }

        // 优先级筛选
        if ($request->filled('priority')) {
            $query->where('priority', $request->input('priority'));
        }

        // 分类筛选（层级：工单大类 → 故障分类，与创建页一致）
        // 优先使用级联参数 category_main / category_sub，兼容旧参数 category_id
        if ($request->filled('category_sub')) {
            // 选了具体子分类，精确匹配
            $query->where('category_id', $request->input('category_sub'));
        } elseif ($request->filled('category_main')) {
            // 只选了大类，匹配该大类下所有子分类
            $subCategoryIds = WorkorderCategorySimplified::where('parent_id', $request->input('category_main'))
                ->where('status', true)
                ->pluck('id')
                ->toArray();
            if (!empty($subCategoryIds)) {
                $query->whereIn('category_id', $subCategoryIds);
            } else {
                $query->where('category_id', $request->input('category_main'));
            }
        } elseif ($request->filled('category_id')) {
            $categoryId = $request->input('category_id');
            $category = WorkorderCategorySimplified::find($categoryId);
            if ($category) {
                if ($category->parent_id === null) {
                    $subCategoryIds = WorkorderCategorySimplified::where('parent_id', $categoryId)
                        ->where('status', true)
                        ->pluck('id')
                        ->toArray();
                    if (!empty($subCategoryIds)) {
                        $query->whereIn('category_id', $subCategoryIds);
                    } else {
                        $query->where('category_id', $categoryId);
                    }
                } else {
                    $query->where('category_id', $categoryId);
                }
            }
        }

        // 处理人筛选
        if ($request->filled('assignee_id')) {
            $query->where('assignee_id', $request->input('assignee_id'));
        }

        // 区域筛选：campus_id 入参实际是 level=6 的 location 节点 id，
        // 该校区下的所有楼栋都是它的子节点；这里转译为 location_id IN (该校区及其所有子孙 id)
        if ($request->filled('campus_id')) {
            $campusLocationId = (int) $request->input('campus_id');
            $scope = array_merge([$campusLocationId], \App\Models\Location::getDescendantIds($campusLocationId));
            $query->whereIn('location_id', $scope);
        }

        // 来源筛选
        if ($request->filled('source')) {
            $query->where('source', $request->input('source'));
        }

        // 紧急标记筛选
        if ($request->filled('is_emergency')) {
            $query->where('is_emergency', $request->input('is_emergency') === '1');
        }

        // 电话协助筛选
        if ($request->filled('phone_assisted')) {
            $query->where('phone_assisted', $request->input('phone_assisted') === '1');
        }

        // 创建时间筛选
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        // 特殊筛选条件
        if ($request->filled('show_overdue') && $request->input('show_overdue') === '1') {
            $query->whereHas('category', function($q) {
                $q->whereNotNull('default_hours');
            })->whereNotNull('expected_complete_at')
              ->where('expected_complete_at', '<', now())
              ->whereNotIn('status', ['resolved']);
        }

        if ($request->filled('show_emergency') && $request->input('show_emergency') === '1') {
            $query->where('is_emergency', true)
                  ->whereNotIn('status', ['resolved']);
        }

        // 地址异常筛选：location_id 为空 或 指向"未分类/未分类校区"子树下的节点
        // 用于运维诊断历史工单的地址迁移结果
        if ($request->filled('address_anomaly') && $request->input('address_anomaly') === '1') {
            $unclassifiedIds = \App\Models\Location::query()
                ->whereIn('name', ['未分类', '未分类校区'])
                ->pluck('id')
                ->all();
            // 取所有"未分类"节点的子孙 id（含其本身）
            $descendants = [];
            foreach ($unclassifiedIds as $uid) {
                $descendants = array_merge($descendants, [$uid], \App\Models\Location::getDescendantIds($uid));
            }
            $query->where(function ($q) use ($descendants) {
                $q->whereNull('location_id');
                if (! empty($descendants)) {
                    $q->orWhereIn('location_id', $descendants);
                }
            });
        }

        // 权限控制已经在查询范围中处理，这里不需要额外的权限过滤

        $workorders = $query->paginate(15);

        // 获取分类数据
        $mainCategories = WorkorderCategorySimplified::getTopLevelCategories();
        $subCategories = [];

        foreach ($mainCategories as $category) {
            $subCategories[$category->id] = WorkorderCategorySimplified::getSubCategories($category->id);
        }

        $categories = [
            'main' => $mainCategories,
            'sub' => $subCategories,
        ];

        // 工单列表"区域筛选"下拉：用 Location 树 level=6 节点替代 campuses 表
        $campusOptions = \App\Models\Location::getCampusOptionsForWorkorder();

        $engineers = User::getAssignableEngineers();

        return view('workorders.index', compact('workorders', 'categories', 'engineers', 'campusOptions'));
    }

    /**
     * 创建工单页面
     */
    public function create(Request $request)
    {
        // 普通用户只能通过简化报修表单提交
        if (Auth::user()->isUser()) {
            return redirect()->route('workorders.report.create');
        }

        // 获取简化的工单分类
        $mainCategories = WorkorderCategorySimplified::getTopLevelCategories();
        $subCategories = [];

        foreach ($mainCategories as $category) {
            $subCategories[$category->id] = WorkorderCategorySimplified::getSubCategories($category->id);
        }

        $categories = [
            'main' => $mainCategories,
            'sub' => $subCategories,
        ];

        // 地址两段式：数据源改为 Location 树（前缀根下的 level=6 校区 + level=7 楼栋）
        $campusOptions = \App\Models\Location::getCampusOptionsForWorkorder();
        $campusBuildings = \App\Models\Location::getCampusBuildingTree();
        $addressPrefix = \App\Models\Location::getPrefixLabel();

        // 检查是否从模板创建
        $template = null;
        $templateCustomFields = [];
        if ($request->filled('template')) {
            $template = WorkorderTemplate::find($request->input('template'));
            if ($template) {
                // 预填充模板数据（必要 + 建议字段）
                $templateData = $template->toWorkorderData();
                $request->session()->flashInput($templateData);
                // 自定义字段传给视图，用于显示额外信息
                $templateCustomFields = $template->getCustomFields();
            }
        }

        return view('workorders.create', compact('categories', 'template', 'campusOptions', 'campusBuildings', 'addressPrefix', 'templateCustomFields'));
    }

    /**
     * 保存工单
     */
    public function store(Request $request)
    {
        // 普通用户只能通过简化报修表单提交
        if (Auth::user()->isUser()) {
            return redirect()->route('workorders.report.create');
        }

        // 获取所有有效的工单来源代码
        $validSources = WorkorderSource::getActiveSourceCodes();
        $validSources[] = 'custom'; // 允许自定义来源
        
        $request->validate([
            'description' => 'required|string',
            'category_main' => 'required|exists:workorder_categories_simplified,id',
            'category_sub' => 'required|exists:workorder_categories_simplified,id',
            'contact_name' => 'required|string|max:100',
            'contact_phone' => 'required|string|max:20',
            'contact_email' => 'nullable|email|max:100',
            'campus_id' => 'required|exists:locations,id',
            'building' => 'required|exists:locations,id|different:campus_id',
            'location_detail' => 'nullable|string|max:500',
            'appointment_time_start' => 'nullable|date',
            'appointment_time_end' => 'nullable|date|after_or_equal:appointment_time_start',
            'appointment_time' => 'nullable|string|max:200', // 保持兼容性，存储时间段描述
            'time_limit_hours' => 'nullable|integer|min:1|max:168', // 最大7天
            'priority' => 'required|in:high,medium,low',
            'source' => 'required|in:' . implode(',', $validSources),
            'other_source' => 'nullable|string|max:50|required_if:source,其他来源',
            'department_name' => 'nullable|string|max:100',
            'need_visit' => 'boolean',
            'is_emergency' => 'boolean',
            'phone_assisted' => 'boolean',
            'phone_solution' => 'nullable|string|max:2000',
            'assignee_id' => 'nullable|integer|exclude_unless:assignee_id,other|exists:users,id',
            'other_reason' => 'nullable|string|max:500',
            'requires_signature' => 'boolean',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|max:10240|mimes:jpg,jpeg,png,gif,bmp,webp,pdf,doc,docx,xls,xlsx,ppt,pptx,txt,md,mp4,mov,avi,wmv,mkv,mp3,wav,flac,aac,ogg,zip,rar,7z', // 最大10MB，白名单扩展名
        ]);

        DB::beginTransaction();
        try {
            // 白名单取值，防止 mass assignment 写入签单/满意度/完结时间等敏感字段
            $data = $request->only([
                'description', 'category_main', 'category_sub', 'contact_name', 'contact_phone',
                'contact_email', 'location_detail',
                'appointment_time_start', 'appointment_time_end', 'appointment_time',
                'time_limit_hours', 'priority', 'source', 'other_source', 'department_name',
                'need_visit', 'is_emergency', 'phone_assisted', 'phone_solution',
                'other_reason', 'requires_signature', 'assignee_id',
            ]);

            // 表单字段 building（楼栋 location id）→ 工单 location_id
            $data['location_id'] = (int) $request->input('building');
            
            // 获取工单分类信息
            $mainCategory = WorkorderCategorySimplified::find($data['category_main']);
            $subCategory = WorkorderCategorySimplified::find($data['category_sub']);
            
            // 设置工单编号前缀和编号
            $ticketPrefix = $mainCategory ? $mainCategory->getTicketPrefix() : 'WO';
            $data['ticket_no'] = Workorder::generateTicketNoByPrefix($ticketPrefix);
            $data['ticket_prefix'] = $ticketPrefix;
            $data['creator_id'] = Auth::id();
            
            // 处理指定工程师
            if ($request->filled('assignee_id')) {
                if ($request->input('assignee_id') === 'other') {
                    // 如果选择其他，则不分配工程师
                    $data['assignee_id'] = null;
                    // 验证other_reason是否为空
                    if (!$request->filled('other_reason')) {
                        DB::rollBack();
                        return back()->withInput()->with('error', '选择其他部门时，必须填写原因说明');
                    }
                    $data['other_reason'] = $request->input('other_reason');
                } else {
                    // 分配给指定工程师
                    $data['assignee_id'] = $request->input('assignee_id');
                    $data['status'] = 'assigned';
                    $data['assigned_at'] = now();
                }
            }
            
            // 处理电话协助完成
            if ($request->boolean('phone_assisted')) {
                // 验证电话协助权限
                if (!Auth::user()->canUsePhoneAssist()) {
                    DB::rollBack();
                    return back()->withInput()->with('error', '您没有权限使用电话协助功能');
                }

                $data['status'] = 'resolved';
                $data['resolved_at'] = now();
                $data['solution'] = $request->input('phone_solution', '通过电话协助完成');
                // 电话协助完成的工单，处理人设置为创建人
                $data['assignee_id'] = Auth::id();
                $data['assigned_at'] = now();
            } elseif (empty($data['assignee_id'])) {
                // 未指派且非电话协助 → 待处理；已指派的保持上面的 assigned 状态
                $data['status'] = 'pending';
            }
            
            // 设置预计完成时间
            $timeLimitHours = $data['time_limit_hours'] ?? ($mainCategory ? $mainCategory->getDefaultHours() : 24);
            
            // 如果有预约开始时间，从预约开始时间计算；否则从现在开始计算
            $startTime = now();
            if (!empty($data['appointment_time_start'])) {
                try {
                    $appointmentStartTime = \Carbon\Carbon::parse($data['appointment_time_start']);
                    
                    // 只有预约开始时间在未来时才使用，否则使用当前时间
                    if ($appointmentStartTime->isFuture()) {
                        $startTime = $appointmentStartTime;
                    }
                } catch (\Exception $e) {
                    // 解析失败，使用当前时间
                }
            }
            
            $data['expected_complete_at'] = $startTime->addHours((int)$timeLimitHours);
            
            // 生成预约时间段描述
            if (!empty($data['appointment_time_start']) && !empty($data['appointment_time_end'])) {
                try {
                    $start = \Carbon\Carbon::parse($data['appointment_time_start']);
                    $end = \Carbon\Carbon::parse($data['appointment_time_end']);
                    $data['appointment_time'] = $start->format('m月d日 H:i') . ' - ' . $end->format('m月d日 H:i');
                } catch (\Exception $e) {
                    $data['appointment_time'] = null;
                }
            } else {
                $data['appointment_time'] = null;
            }
            
            // 设置分类ID
            $data['category_id'] = $data['category_sub'];
            // 设置type_id为null，因为我们现在使用简化的分类系统
            $data['type_id'] = null;

            // 注：表单字段 building（楼栋 location id）已在 $data 准备阶段映射到 location_id，
            // campus（text）/campus_id/building/location 列已 drop，不再写入。
            // campus_name/building_name 通过 location_id 沿父链 accessor 解析。

            // 设置电话协助完成标记
            $data['phone_assisted'] = $request->boolean('phone_assisted');
            
            // 处理自定义来源
            if ($request->input('source') === '其他来源') {
                $data['source'] = '其他来源';
                $data['custom_source'] = $request->input('other_source');
            }

            $workorder = Workorder::create($data);

            // 电话协助完成：resolved_at 已从 $fillable 移除（防 mass assignment），用 forceFill 单独写入
            if ($request->boolean('phone_assisted')) {
                $workorder->forceFill(['resolved_at' => now()])->save();
            }
            
            // 发送通知
            if ($request->boolean('phone_assisted')) {
                $workorder->sendNotification('closed');
            } else {
                // 接收者由 NotificationDispatcher 按事件统一决定
                $workorder->sendNotification('created');
            }
            
            // 如果是电话协助完成，记录日志
            if ($request->boolean('phone_assisted')) {
                $workorder->addLog('phone_assisted', '通过电话协助完成：' . $request->input('phone_solution'));
            }

            // 处理附件上传 - 使用简化的附件模型避免卡顿
            if ($request->hasFile('attachments')) {
                $files = $request->file('attachments');
                $descriptions = $request->input('attachment_descriptions', []);
                
                foreach ($files as $index => $file) {
                    $description = $descriptions[$index] ?? null;
                    WorkorderAttachment::uploadFile($file, $workorder->id, $description);
                }
            }

            // 记录创建日志
            $workorder->addLog('created', '工单创建成功');

            DB::commit();
            
            return redirect(\App\Helpers\UrlHelper::relative_url("/workorders/{$workorder->id}"))
                ->with('success', '工单创建成功，工单编号：' . $workorder->ticket_no);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', '工单创建失败：' . $e->getMessage());
        }
    }

    /**
     * 工单详情页面
     */
    public function show(Workorder $workorder)
    {
        // 权限检查
        $this->authorizeView($workorder);
        
        $workorder->load([
            'type',
            'creator',
            'assignee',
            'department',
            'logs.user',
            'attachments.user',
            'visits.visitor',
            'collaborations.inviter',
            'collaborations.collaborator'
        ]);

        return view('workorders.show', compact('workorder'));
    }

    /**
     * 编辑工单页面
     */
    public function edit(Workorder $workorder)
    {
        // 权限检查
        $this->authorizeEdit($workorder);

        $departments = Department::where('status', 'active')->get();
        $engineers = User::getAssignableEngineers();

        // 获取简化的工单分类
        $mainCategories = WorkorderCategorySimplified::getTopLevelCategories();
        $subCategories = [];

        foreach ($mainCategories as $category) {
            $subCategories[$category->id] = WorkorderCategorySimplified::getSubCategories($category->id);
        }

        $categories = [
            'main' => $mainCategories,
            'sub' => $subCategories,
        ];

        // 地址两段式：数据源改为 Location 树
        $campusOptions = \App\Models\Location::getCampusOptionsForWorkorder();
        $campusBuildings = \App\Models\Location::getCampusBuildingTree();
        $addressPrefix = \App\Models\Location::getPrefixLabel();

        return view('workorders.edit', compact('workorder', 'departments', 'engineers', 'categories', 'campusOptions', 'campusBuildings', 'addressPrefix'));
    }

    /**
     * 更新工单
     */
    public function update(Request $request, Workorder $workorder)
    {
        // 权限检查
        $this->authorizeEdit($workorder);
        
        // 获取所有有效的工单来源代码
        $validSources = WorkorderSource::getActiveSourceCodes();
        $validSources[] = 'custom'; // 允许自定义来源
        
        $rules = [
            'description' => 'required|string',
            'category_main' => 'required|exists:workorder_categories_simplified,id',
            'category_sub' => 'required|exists:workorder_categories_simplified,id',
            'contact_name' => 'required|string|max:100',
            'contact_phone' => 'required|string|max:20',
            'contact_email' => 'nullable|email|max:100',
            'campus_id' => 'required|exists:locations,id',
            'building' => 'required|exists:locations,id|different:campus_id',
            'location_detail' => 'nullable|string|max:500',
            'appointment_time_start' => 'nullable|date',
            'appointment_time_end' => 'nullable|date|after_or_equal:appointment_time_start',
            'appointment_time' => 'nullable|string|max:200', // 保持兼容性，存储时间段描述
            'time_limit_hours' => 'nullable|integer|min:1|max:168',
            'priority' => 'required|in:high,medium,low',
            'source' => 'required|in:' . implode(',', $validSources),
            'other_source' => 'nullable|string|max:50|required_if:source,其他来源',
            'department_id' => 'nullable|exists:departments,id',
            'need_visit' => 'boolean',
            'is_emergency' => 'boolean',
            'remarks' => 'nullable|string|max:1000',
            'assignee_id' => 'nullable|integer|exists:users,id',
        ];
        
        // 根据工单状态添加额外验证规则
        if (in_array($workorder->status, ['processing', 'resolved', 'completed', 'closed'])) {
            $rules['materials_usage'] = 'nullable|string|max:2000';
        }
        
        if (in_array($workorder->status, ['resolved', 'completed', 'closed'])) {
            $rules['solution'] = 'nullable|string|max:2000';
        }
        
        // 管理员可以修改创建时间
        if (Auth::user()->isAdmin()) {
            $rules['created_at'] = 'nullable|date';
        }
        
        $request->validate($rules);

        DB::beginTransaction();
        try {
            $oldStatus = $workorder->status;
            $originalCreatedAt = $workorder->created_at;
            // 白名单取值，防止 mass assignment 写入签单/满意度/完结时间等敏感字段
            $allowedFields = [
                'description', 'category_main', 'category_sub', 'contact_name', 'contact_phone',
                'contact_email', 'location_detail',
                'appointment_time_start', 'appointment_time_end', 'appointment_time',
                'time_limit_hours', 'priority', 'source', 'other_source', 'department_id',
                'need_visit', 'is_emergency', 'remarks',
            ];
            // solution/materials_usage 属于处理结果：仅处理侧（处理人/协作者/管理员）可改，
            // 且仅已进入处理后期（processing 及之后）开放；普通创建人不可篡改工程师的处理记录
            if ($workorder->canBeOperatedBy(Auth::user(), 'resolve')
                && in_array($workorder->status, ['processing', 'resolved', 'completed', 'closed'])) {
                $allowedFields[] = 'materials_usage';
                if (in_array($workorder->status, ['resolved', 'completed', 'closed'])) {
                    $allowedFields[] = 'solution';
                }
            }
            // 仅管理员和工单管理员可通过编辑表单改派 assignee_id
            if (Auth::user()->canAssignWorkorders()) {
                $allowedFields[] = 'assignee_id';
            }
            // 仅管理员可修改 created_at
            if (Auth::user()->isAdmin()) {
                $allowedFields[] = 'created_at';
            }
            $data = $request->only($allowedFields);

            // 表单字段 building（楼栋 location id）→ 工单 location_id
            $data['location_id'] = (int) $request->input('building');

            // 设置分类ID为子分类ID
            $data['category_id'] = $data['category_sub'];
            // 设置type_id为null，因为我们现在使用简化的分类系统
            $data['type_id'] = null;
            
            // 设置预计完成时间
            $timeLimitHours = $data['time_limit_hours'] ?? null;
            if ($timeLimitHours) {
                // 如果有预约开始时间，从预约开始时间计算；否则从现在开始计算
                $startTime = now();
                if (!empty($data['appointment_time_start'])) {
                    try {
                        $appointmentStartTime = \Carbon\Carbon::parse($data['appointment_time_start']);
                        
                        // 只有预约开始时间在未来时才使用，否则使用当前时间
                        if ($appointmentStartTime->isFuture()) {
                            $startTime = $appointmentStartTime;
                        }
                    } catch (\Exception $e) {
                        // 解析失败，使用当前时间
                    }
                }
                
                $data['expected_complete_at'] = $startTime->addHours((int)$timeLimitHours);
            }
            
            // 更新预约时间段描述
            if (!empty($data['appointment_time_start']) && !empty($data['appointment_time_end'])) {
                try {
                    $start = \Carbon\Carbon::parse($data['appointment_time_start']);
                    $end = \Carbon\Carbon::parse($data['appointment_time_end']);
                    $data['appointment_time'] = $start->format('m月d日 H:i') . ' - ' . $end->format('m月d日 H:i');
                } catch (\Exception $e) {
                    $data['appointment_time'] = null;
                }
            } else {
                $data['appointment_time'] = null;
            }
            
           // 处理自定义来源
           if ($request->input('source') === '其他来源') {
                $data['custom_source'] = $request->input('other_source');
           } else {
                $data['custom_source'] = null;
           }

           // 如果分配了处理人但没有设置状态，自动设置为assigned
            if (isset($data['assignee_id']) && $data['assignee_id'] && $workorder->status === 'pending') {
                $data['status'] = 'assigned';
                $data['assigned_at'] = $data['assigned_at'] ?? now();
            }

            // created_at 由 Laravel 管理、不在 $fillable 中；管理员单独写入
            $submittedCreatedAt = $data['created_at'] ?? null;
            unset($data['created_at']);

            $workorder->update($data);

            // 管理员显式修改创建时间（绕过 $fillable）
            if (Auth::user()->isAdmin() && $submittedCreatedAt) {
                try {
                    $newCreatedAt = \Carbon\Carbon::parse($submittedCreatedAt);
                    if (!$originalCreatedAt || !$newCreatedAt->equalTo($originalCreatedAt)) {
                        $workorder->forceFill(['created_at' => $newCreatedAt])->save();
                    }
                } catch (\Exception $e) {
                    // 解析失败忽略
                }
            }
            
            // 如果分配了处理人，发送通知
            if ($workorder->wasChanged('assignee_id') && $workorder->assignee_id) {
                $workorder->sendNotification('assigned');
            }
            
            // 记录更新日志
            $logContent = '工单信息已更新';
            if (Auth::user()->isAdmin() && $submittedCreatedAt) {
                try {
                    $parsedSubmitted = \Carbon\Carbon::parse($submittedCreatedAt);
                    if (!$originalCreatedAt || !$parsedSubmitted->equalTo($originalCreatedAt)) {
                        $logContent .= '（创建时间已修改）';
                    }
                } catch (\Exception $e) {
                    // 忽略解析错误
                }
            }
            $workorder->addLog('comment', $logContent);
            
            DB::commit();
            
            return redirect(\App\Helpers\UrlHelper::relative_url("/workorders/{$workorder->id}"))
                ->with('success', '工单更新成功');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', '工单更新失败：' . $e->getMessage());
        }
    }

    /**
     * 分配工单
     */
    public function assign(Request $request, Workorder $workorder)
    {
        // 权限检查：只有管理员和工单管理员可以分配工单给任何人
        if (!Auth::user()->canAssignWorkorders()) {
            $message = '您没有权限分配工单';
            if ($request->isMethod('get')) {
                return redirect(\App\Helpers\UrlHelper::relative_url("/workorders/{$workorder->id}"))->with('error', $message);
            }
            return back()->with('error', $message);
        }

        if (!$workorder->canBeAssigned()) {
            $message = '当前工单状态不允许分配';
            if ($request->isMethod('get')) {
                return redirect(\App\Helpers\UrlHelper::relative_url("/workorders/{$workorder->id}"))->with('error', $message);
            }
            return back()->with('error', $message);
        }

        // 如果是GET请求，直接返回到工单详情页面
        if ($request->isMethod('get')) {
            return redirect(\App\Helpers\UrlHelper::relative_url("/workorders/{$workorder->id}"));
        }

        $request->validate([
            'assignee_id' => 'required|exists:users,id',
        ]);

        if ($workorder->assign($request->input('assignee_id'))) {
            return back()->with('success', '工单分配成功');
        }
        
        return back()->with('error', '工单分配失败');
    }

    /**
     * 工程师接单
     */
    public function claim(Request $request, Workorder $workorder)
    {
        // 权限检查：只有工程师、工单管理员和系统管理员可以接单
        if (!Auth::user()->canAcceptWorkorders()) {
            $message = '您没有权限接单';
            if ($request->isMethod('get')) {
                return redirect(\App\Helpers\UrlHelper::relative_url('/workorders'))->with('error', $message);
            }
            return back()->with('error', $message);
        }
        
        if (!$workorder->canBeAssigned()) {
            $message = '当前工单状态不允许接单';
            if ($request->isMethod('get')) {
                return redirect(\App\Helpers\UrlHelper::relative_url('/workorders'))->with('error', $message);
            }
            return back()->with('error', $message);
        }

        if ($workorder->assign(Auth::id())) {
            $message = '接单成功，工单已分配给您';
            if ($request->isMethod('get')) {
                return redirect(\App\Helpers\UrlHelper::relative_url('/workorders'))->with('success', $message);
            }
            return back()->with('success', $message);
        }
        
        $message = '接单失败';
        if ($request->isMethod('get')) {
            return redirect(\App\Helpers\UrlHelper::relative_url('/workorders'))->with('error', $message);
        }
        return back()->with('error', $message);
    }

    /**
     * 开始处理工单
     */
    public function start(Request $request, Workorder $workorder)
    {
        // 权限检查：工单负责人、协作工程师或管理员可以开始处理
        if (!$workorder->canBeOperatedBy(Auth::user(), 'start')) {
            $message = '您没有权限开始处理此工单';
            if ($request->isMethod('get')) {
                return redirect(\App\Helpers\UrlHelper::relative_url('/workorders'))->with('error', $message);
            }
            return back()->with('error', $message);
        }
        
        if ($workorder->start()) {
            // 发送通知
            $workorder->sendNotification('started');
            $message = '工单处理已开始';
            if ($request->isMethod('get')) {
                return redirect(\App\Helpers\UrlHelper::relative_url('/workorders'))->with('success', $message);
            }
            return back()->with('success', $message);
        }
        
        $message = '工单开始处理失败';
        if ($request->isMethod('get')) {
            return redirect(\App\Helpers\UrlHelper::relative_url('/workorders'))->with('error', $message);
        }
        return back()->with('error', $message);
    }

    /**
     * 解决工单
     */
    public function resolve(Request $request, Workorder $workorder)
    {
        // 权限检查：工单负责人、协作工程师或管理员可以解决工单
        if (!$workorder->canBeOperatedBy(Auth::user(), 'resolve')) {
            return back()->with('error', '您没有权限解决此工单');
        }
        
        $request->validate([
            'solution' => 'required|string|max:2000',
            'no_materials' => 'boolean',
            // 复选框未勾选时字段缺失而非字符串 'false'，故用 required_unless 表达"未勾选无备件则必填"
            'materials_usage' => 'required_unless:no_materials,1|nullable|string|max:2000',
        ]);

        // 注释掉签单检查，允许工单先解决再签单
        // 根据用户反馈的流程：创建→分配→处理→解决→签单→完结
        // if ($workorder->requires_signature && !$workorder->hasSignature()) {
        //     // 如果需要签单但尚未签单，重定向到签单页面
        //     return redirect()->route('workorders.signature.create', $workorder->id)
        //         ->with('info', '此工单需要签单确认后才能解决，请先完成签单流程');
        // }

        // 处理备件耗材使用情况
        $materialsUsage = null;
        if ($request->boolean('no_materials')) {
            $materialsUsage = '无备件耗材使用';
        } else {
            $materialsUsage = $request->input('materials_usage');
        }

        if ($workorder->resolve($request->input('solution'))) {
            // 更新备件耗材使用情况
            $workorder->materials_usage = $materialsUsage;
            $workorder->save();
            
            // 记录日志
            $workorder->addLog('materials_updated', '更新了备件耗材使用情况');
            
            // 系统设置不需要用户确认完结时，工程师解决后自动完结（签单工单除外）
            $requireConfirm = \App\Models\SystemSetting::get('require_user_completion_confirm', '0');
            if ($requireConfirm !== '1' && !$workorder->requires_signature) {
                $workorder->complete();
                $workorder->sendNotification('completed');
                return back()->with('success', '工单已解决并自动完结');
            }

            // 非自动完结（需用户确认或签单）才停在"已解决"，此时发 resolved 通知；
            // 自动完结分支只发 completed，避免同一时刻连发两条近似通知
            $workorder->sendNotification('resolved');

            return back()->with('success', '工单已标记为解决');
        }
        
        return back()->with('error', '工单解决失败');
    }

    /**
     * 关闭工单
     */
    public function close(Request $request, Workorder $workorder)
    {
        // 权限检查
        if (!Auth::user()->canCloseWorkorders()) {
            $message = '您没有权限关闭工单';
            if ($request->isMethod('get')) {
                return redirect(\App\Helpers\UrlHelper::relative_url('/workorders'))->with('error', $message);
            }
            return back()->with('error', $message);
        }
        
        if ($workorder->close()) {
            // 发送通知
            $workorder->sendNotification('closed');
            $message = '工单已关闭';
            if ($request->isMethod('get')) {
                return redirect(\App\Helpers\UrlHelper::relative_url('/workorders'))->with('success', $message);
            }
            return back()->with('success', $message);
        }
        
        $message = '工单关闭失败';
        if ($request->isMethod('get')) {
            return redirect(\App\Helpers\UrlHelper::relative_url('/workorders'))->with('error', $message);
        }
        return back()->with('error', $message);
    }

    /**
     * 完结工单
     */
    public function complete(Request $request, Workorder $workorder)
    {
        // 权限检查：工单负责人或管理员可以完结工单
        if (!$workorder->canBeOperatedBy(Auth::user(), 'resolve')) {
            $message = '您没有权限完结此工单';
            if ($request->isMethod('get')) {
                return redirect(\App\Helpers\UrlHelper::relative_url('/workorders'))->with('error', $message);
            }
            return back()->with('error', $message);
        }
        
        // 如果是GET请求，跳过验证，直接尝试完结
        if ($request->isMethod('post')) {
            $request->validate([
                'completion_note' => 'required|string|max:1000',
            ]);
        }


        // 签单阻断：需要签单但尚未签名的工单不能完结
        // 用户必须先填写故障处理记录单并签字，然后才能完结此工单
        if ($workorder->requires_signature && !$workorder->hasSignature()) {
            return redirect()->route('workorders.signature.create', $workorder->id)
                ->with('warning', '此工单需要报障人签单确认后才能完结，请先完成故障处理记录单签单流程');
        }
        if ($workorder->complete()) {
            // 发送通知
            $workorder->sendNotification('completed');
            
            // 如果有完结备注，添加到日志
            if ($request->filled('completion_note')) {
                $workorder->addLog('completed', $request->input('completion_note'));
            }
            
            $message = '工单已完结';
            if ($request->isMethod('get')) {
                return redirect(\App\Helpers\UrlHelper::relative_url('/workorders'))->with('success', $message);
            }
            return back()->with('success', $message);
        }
        
        $message = '工单完结失败';
        if ($request->isMethod('get')) {
            return redirect(\App\Helpers\UrlHelper::relative_url('/workorders'))->with('error', $message);
        }
        return back()->with('error', $message);
    }

    /**
     * 添加处理记录
     */
    public function addLog(Request $request, Workorder $workorder)
    {
        // 权限检查：必须能查看该工单，且有添加处理记录的权限
        if (!Auth::user()->canViewWorkorder($workorder)) {
            abort(403, '您没有权限查看此工单');
        }
        if (!Auth::user()->canAddWorkorderNotes()) {
            abort(403, '您没有权限添加处理记录');
        }

        // 如果是GET请求，直接返回到工单详情页面
        if ($request->isMethod('get')) {
            return redirect(\App\Helpers\UrlHelper::relative_url("/workorders/{$workorder->id}"));
        }

        $request->validate([
            'content' => 'required|string|max:1000',
        ]);

        // 用户提交的备注显式加前缀，避免与系统日志（如"工单已关闭"）混淆
        $content = '【用户备注】' . $request->input('content');
        $workorder->addLog('comment', $content, Auth::id());

        // 发送通知
        $workorder->sendCommentNotification($request->input('content'), Auth::user());
        
        return back()->with('success', '处理记录添加成功');
    }

    /**
     * 更新备件耗材使用情况
     */
    public function updateMaterials(Request $request, Workorder $workorder)
    {
        // 权限检查：只有工单的分配处理人、协作工程师、工单管理员或管理员可以编辑备件耗材
        // （canBeOperatedBy 'add_materials' 不含创建人分支——报修人不应篡改备件记录）
        if (!$workorder->canBeOperatedBy(auth()->user(), 'add_materials')) {
            $message = '您没有权限编辑备件耗材使用情况';
            if ($request->isMethod('get')) {
                return redirect(\App\Helpers\UrlHelper::relative_url("/workorders/{$workorder->id}"))->with('error', $message);
            }
            return back()->with('error', $message);
        }
        
        // 只有处理中或已解决的工单可以编辑备件耗材
        if (!in_array($workorder->status, ['processing', 'resolved', 'completed'])) {
            $message = '当前工单状态不允许编辑备件耗材使用情况';
            if ($request->isMethod('get')) {
                return redirect(\App\Helpers\UrlHelper::relative_url("/workorders/{$workorder->id}"))->with('error', $message);
            }
            return back()->with('error', $message);
        }

        // 如果是GET请求，直接返回到工单详情页面
        if ($request->isMethod('get')) {
            return redirect(\App\Helpers\UrlHelper::relative_url("/workorders/{$workorder->id}"));
        }
        
        $request->validate([
            'materials_usage' => 'required|string|max:2000',
        ]);
        
        try {
            $workorder->materials_usage = $request->input('materials_usage');
            $workorder->save();
            
            // 记录日志
            $workorder->addLog('materials_updated', '更新了备件耗材使用情况');
            
            return back()->with('success', '备件耗材使用情况更新成功');
        } catch (\Exception $e) {
            return back()->with('error', '备件耗材使用情况更新失败：' . $e->getMessage());
        }
    }

    /**
     * 上传附件
     */
   public function uploadAttachments(Request $request, Workorder $workorder)
   {
        $isAjax = $request->expectsJson() || $request->ajax();
       // 权限检查：只有工单的分配处理人、工单管理员、管理员或协作工程师可以上传附件
       if (!auth()->user()->canUploadAttachment($workorder)) {
           $message = '您没有权限上传附件';
            if ($isAjax) {
                return response()->json(['success' => false, 'message' => $message], 403);
            }
           if ($request->isMethod('get')) {
               return redirect(\App\Helpers\UrlHelper::relative_url("/workorders/{$workorder->id}"))->with('error', $message);
           }
           return back()->with('error', $message);
       }
       
       // 只有待处理或处理中的工单可以上传附件
       if (!in_array($workorder->status, ['pending', 'processing'])) {
           $message = '当前工单状态不允许上传附件';
            if ($isAjax) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }
           if ($request->isMethod('get')) {
               return redirect(\App\Helpers\UrlHelper::relative_url("/workorders/{$workorder->id}"))->with('error', $message);
           }
           return back()->with('error', $message);
       }
       
       // 如果是GET请求，直接返回到工单详情页面
       if ($request->isMethod('get')) {
           return redirect(\App\Helpers\UrlHelper::relative_url("/workorders/{$workorder->id}"));
       }
       
        try {
             $request->validate([
                 'attachments' => 'required|array',
                 'attachments.*' => 'file|max:10240|mimes:jpg,jpeg,png,gif,bmp,webp,pdf,doc,docx,xls,xlsx,ppt,pptx,txt,md,mp4,mov,avi,wmv,mkv,mp3,wav,flac,aac,ogg,zip,rar,7z', // 白名单扩展名
                 'description' => 'nullable|string|max:500',
             ]);
        } catch (\Illuminate\Validation\ValidationException $ve) {
            if ($isAjax) {
                return response()->json(['success' => false, 'message' => implode(' ', $ve->validator->errors()->all())], 422);
            }
            throw $ve;
        }
       
       try {
           $uploadedCount = 0;
           $files = $request->file('attachments');
           $descriptions = $request->input('attachment_descriptions', []);
           
           foreach ($files as $index => $file) {
               $description = $descriptions[$index] ?? null;
               WorkorderAttachment::uploadFile($file, $workorder->id, $description);
               $uploadedCount++;
           }
           
           // 记录日志
           $workorder->addLog('attachment_uploaded', "上传了 {$uploadedCount} 个附件");
           
            $msg = "成功上传 {$uploadedCount} 个附件";
            if ($isAjax) {
                return response()->json(['success' => true, 'message' => $msg]);
            }
            return back()->with('success', $msg);
       } catch (\Exception $e) {
            if ($isAjax) {
                return response()->json(['success' => false, 'message' => '附件上传失败：' . $e->getMessage()], 500);
            }
            return back()->with('error', '附件上传失败：' . $e->getMessage());
       }
   }
    
    /**
     * 添加工单回访记录
     */
    public function storeVisit(Request $request, Workorder $workorder)
    {
        // 权限检查：只有管理员、工单管理员或工单处理人可以添加回访记录
        if (!$workorder->canBeOperatedBy(auth()->user(), 'resolve')) {
            $message = '您没有权限添加回访记录';
            if ($request->isMethod('get')) {
                return redirect(\App\Helpers\UrlHelper::relative_url("/workorders/{$workorder->id}"))->with('error', $message);
            }
            return back()->with('error', $message);
        }
        
        // 只有已解决的工单才能添加回访记录
        if ($workorder->status !== 'resolved') {
            $message = '只有已解决的工单才能添加回访记录';
            if ($request->isMethod('get')) {
                return redirect(\App\Helpers\UrlHelper::relative_url("/workorders/{$workorder->id}"))->with('error', $message);
            }
            return back()->with('error', $message);
        }
        
        // 检查是否已经有回访记录
        if ($workorder->visits()->exists()) {
            $message = '该工单已有回访记录';
            if ($request->isMethod('get')) {
                return redirect(\App\Helpers\UrlHelper::relative_url("/workorders/{$workorder->id}"))->with('error', $message);
            }
            return back()->with('error', $message);
        }

        // 如果是GET请求，直接返回到工单详情页面
        if ($request->isMethod('get')) {
            return redirect(\App\Helpers\UrlHelper::relative_url("/workorders/{$workorder->id}"));
        }
        
        $request->validate([
            'visit_method' => 'required|in:phone,sms,email,online,scene',
            'visit_time' => 'required|date',
            'visit_content' => 'required|string|max:1000',
            'satisfaction_score' => 'nullable|integer|min:1|max:5',
            'service_quality_score' => 'nullable|integer|min:1|max:5',
            'professional_score' => 'nullable|integer|min:1|max:5',
            'overall_score' => 'nullable|integer|min:1|max:5',
            'feedback' => 'nullable|string|max:1000',
            'need_follow_up' => 'boolean',
            'follow_up_note' => 'nullable|string|max:500',
        ]);
        
        try {
            $visitData = [
                'workorder_id' => $workorder->id,
                'visitor_id' => auth()->id(),
                'visit_method' => $request->input('visit_method'),
                'visit_time' => $request->input('visit_time'),
                'visit_content' => $request->input('visit_content'),
                'feedback' => $request->input('feedback'),
                'satisfaction_score' => $request->input('satisfaction_score'),
                'response_speed_score' => $request->input('satisfaction_score'), // 使用satisfaction_score作为响应速度评分
                'service_quality_score' => $request->input('service_quality_score'),
                'professional_score' => $request->input('professional_score'),
                'overall_score' => $request->input('overall_score'),
                'need_follow_up' => $request->boolean('need_follow_up'),
                'follow_up_note' => $request->input('follow_up_note'),
                'status' => 'completed',
            ];
            
            $visit = WorkorderVisit::create($visitData);
            
            // 发送通知
            $workorder->sendVisitCompletedNotification($visit);
            
            // 记录日志
            $workorder->addLog('visit_completed', "完成了工单回访，回访方式：{$visit->visit_method_text}");
            
            return back()->with('success', '回访记录添加成功');
        } catch (\Exception $e) {
            return back()->with('error', '回访记录添加失败：' . $e->getMessage());
        }
    }

    /**
     * 回滚工单状态（工单管理员 / 系统管理员专用）
     *
     * 注意：这与"修改状态"不同。回滚会把目标节点之后产生的派生数据一并清理
     * （处理人、处理时间、解决方案、协作邀请等），并写入带原因的审计日志。
     */
    public function rollback(Request $request, Workorder $workorder)
    {
        // 权限检查：仅工单管理员和系统管理员可回滚
        if (!Auth::user()->canRollbackWorkorder()) {
            return back()->with('error', '您没有权限回滚工单状态');
        }

        $request->validate([
            'target_status' => 'required|string',
            'reason' => 'nullable|string|max:500',
        ], [
            'target_status.required' => '请选择回滚目标节点',
        ]);

        $target = $request->input('target_status');
        $reason = $request->input('reason');

        if (!$workorder->canRollbackTo($target)) {
            return back()->with('error', '该节点不是当前工单状态可回滚的目标');
        }

        if ($workorder->rollback($target, $reason, Auth::id())) {
            return redirect(\App\Helpers\UrlHelper::relative_url("/workorders/{$workorder->id}"))
                ->with('success', "工单已回滚到「{$workorder->status_text}」");
        }

        return back()->with('error', '工单回滚失败');
    }

   /**
     * 权限检查：查看工单
     */
    private function authorizeView(Workorder $workorder)
    {
        if (!Auth::user()->canViewWorkorder($workorder)) {
            abort(403, '您没有权限查看此工单');
        }
    }

    /**
     * 权限检查：编辑工单
     */
    private function authorizeEdit(Workorder $workorder)
    {
        if (!Auth::user()->canEditWorkorder($workorder)) {
            abort(403, '您没有权限编辑此工单');
        }
    }

    /**
     * 获取工单的备品耗材使用情况（API）
     */
    public function getMaterialsUsage(Workorder $workorder)
    {
        // 权限检查：工单负责人、协作工程师或管理员可以查看备品耗材使用情况
        if (!$workorder->canBeOperatedBy(Auth::user(), 'view')) {
            return response()->json(['error' => '您没有权限查看此工单的备品耗材使用情况'], 403);
        }
        
        return response()->json([
            'materials_usage' => $workorder->materials_usage,
            'has_materials' => !empty($workorder->materials_usage) && $workorder->materials_usage !== '无备件耗材使用'
        ]);
    }

    /**
     * 删除工单（软删除，仅管理员和工单管理员）
     */
    public function destroy(Request $request, Workorder $workorder)
    {
        if (!Auth::user()->canDeleteWorkorders()) {
            abort(403, '您没有权限删除工单');
        }

        $workorder->delete();

        return redirect(\App\Helpers\UrlHelper::relative_url('/workorders'))
            ->with('success', '工单已删除');
    }
}
