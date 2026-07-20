<?php

namespace App\Http\Controllers;

use App\Models\Workorder;
use App\Models\WorkorderType;
use App\Models\WorkorderCategory;
use App\Models\WorkorderCategorySimplified;
use App\Models\WorkorderAttachment;
use App\Models\WorkorderVisit;
use App\Models\WorkorderCollaboration;
use App\Models\WorkorderSource;
use App\Models\WorkorderTemplate;
use App\Models\User;
use App\Models\Department;
use App\Models\Location;
use App\Models\Campus;
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
        
        // 根据用户角色获取不同的查询范围
        $query = $user->getWorkorderQueryScope()
            ->with(['category', 'creator', 'assignee', 'department'])
            ->orderBy('created_at', 'desc');
        
        // ---- 状态过滤（集中处理，后续筛选全部为 AND） ----
        // 优先级：明确选择某状态 > 勾选"显示已解决" > 默认仅未完结
        $status = $request->input('status');
        $showClosed = $request->boolean('show_closed');

        if ($status && $status !== 'all') {
            // 用户明确选择了某个状态，精确匹配
            $query->where('status', $status);
        } elseif ($status !== 'all' && !$showClosed) {
            // 默认：只显示未完结工单（待处理、已分配、处理中）
            $query->whereIn('status', ['pending', 'assigned', 'processing']);
        }

        // 搜索条件
        if ($request->filled('keyword')) {
            $keyword = $request->input('keyword');
            $query->where(function($q) use ($keyword) {
                $q->where('ticket_no', 'like', "%{$keyword}%")
                  ->orWhere('description', 'like', "%{$keyword}%")
                  ->orWhere('contact_name', 'like', "%{$keyword}%")
                  ->orWhere('contact_phone', 'like', "%{$keyword}%");
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

        // 校区筛选
        if ($request->filled('campus_id')) {
            $query->where('campus_id', $request->input('campus_id'));
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
        
        $engineers = User::getAssignableEngineers();

        return view('workorders.index', compact('workorders', 'categories', 'engineers'));
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
        
        // 检查是否从模板创建
        $template = null;
        if ($request->filled('template')) {
            $template = WorkorderTemplate::find($request->input('template'));
            if ($template) {
                // 预填充模板数据
                $templateData = $template->toWorkorderData();
                $request->session()->flashInput($templateData);
            }
        }
        
        return view('workorders.create', compact('categories', 'template'));
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
            'campus_id' => 'required|exists:campuses,id',
            'building' => 'required|string',
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
            'assignee_id' => 'nullable|string',
            'other_reason' => 'nullable|string|max:500',
            'requires_signature' => 'boolean',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|max:10240', // 最大10MB
        ]);

        DB::beginTransaction();
        try {
            $data = $request->all();
            
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
                    return back()->withInput()->with('error', '您没有权限使用电话协助功能');
                }
                
                $data['status'] = 'resolved';
                $data['resolved_at'] = now();
                $data['solution'] = $request->input('phone_solution', '通过电话协助完成');
                // 电话协助完成的工单，处理人设置为创建人
                $data['assignee_id'] = Auth::id();
                $data['assigned_at'] = now();
            } else {
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
            
            // 设置位置信息
            $campus = Campus::find($data['campus_id']);
            $data['location'] = ($campus ? $campus->name : '') . ' - ' . $data['building'];
            $data['campus'] = $campus ? $campus->name : '';
            // 同时保存校区ID
            $data['campus_id'] = $data['campus_id'];
            
            // 设置电话协助完成标记
            $data['phone_assisted'] = $request->boolean('phone_assisted');
            
            // 处理自定义来源
            if ($request->input('source') === '其他来源') {
                $data['source'] = '其他来源';
                $data['custom_source'] = $request->input('other_source');
            }

            $workorder = Workorder::create($data);
            
            // 发送通知
            if ($request->boolean('phone_assisted')) {
                $workorder->sendNotification('closed');
            } else {
                // 如果有分配处理人，只发送给处理人；否则发送给创建人
                if ($workorder->assignee_id) {
                    $workorder->sendNotification('created', [], [$workorder->assignee_id]);
                } else {
                    $workorder->sendNotification('created', [], [$workorder->creator_id]);
                }
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
        
        return view('workorders.edit', compact('workorder', 'departments', 'engineers', 'categories'));
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
            'campus_id' => 'required|exists:campuses,id',
            'building' => 'required|string',
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
            $data = $request->all();
            
            // 设置分类ID为子分类ID
            $data['category_id'] = $data['category_sub'];
            // 设置type_id为null，因为我们现在使用简化的分类系统
            $data['type_id'] = null;
            
            // 设置位置信息
            if (isset($data['campus_id']) && isset($data['building'])) {
                $campus = Campus::find($data['campus_id']);
                $data['location'] = ($campus ? $campus->name : '') . ' - ' . $data['building'];
                $data['campus'] = $campus ? $campus->name : '';
            }
            
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
            
            $workorder->update($data);
            
            // 如果分配了处理人，发送通知
            if ($workorder->wasChanged('assignee_id') && $workorder->assignee_id) {
                $workorder->sendNotification('assigned', [], [$workorder->assignee_id]);
            }
            
            // 记录更新日志
            $logContent = '工单信息已更新';
            if (Auth::user()->isAdmin() && isset($data['created_at']) && $data['created_at'] !== $workorder->created_at->format('Y-m-d H:i:s')) {
                $logContent .= '（创建时间已修改）';
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
            'materials_usage' => 'required_if:no_materials,false|nullable|string|max:2000',
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
            
            // 发送通知
            $workorder->sendNotification('resolved');

            // 系统设置不需要用户确认完结时，工程师解决后自动完结（签单工单除外）
            $requireConfirm = \App\Models\SystemSetting::get('require_user_completion_confirm', '0');
            if ($requireConfirm !== '1' && !$workorder->requires_signature) {
                $workorder->complete();
                $workorder->sendNotification('completed');
                return back()->with('success', '工单已解决并自动完结');
            }

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

        $workorder->addLog('comment', $request->input('content'));
        
        // 发送通知
        $workorder->sendCommentNotification($request->input('content'), Auth::user());
        
        return back()->with('success', '处理记录添加成功');
    }

    /**
     * 更新备件耗材使用情况
     */
    public function updateMaterials(Request $request, Workorder $workorder)
    {
        // 权限检查：只有工单的分配处理人、工单管理员或管理员可以编辑备件耗材
        if (!auth()->user()->canUploadAttachment($workorder)) {
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
                'attachments.*' => 'file|max:10240', // 最大10MB
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
     * 邀请协作
     */
    public function inviteCollaborator(Request $request, Workorder $workorder)
    {
        // 权限检查：只有工单的分配处理人、工单管理员或管理员可以邀请协作
        if (!$workorder->canBeOperatedBy(auth()->user(), 'resolve')) {
            $message = '您没有权限邀请协作';
            if ($request->isMethod('get')) {
                return redirect(\App\Helpers\UrlHelper::relative_url("/workorders/{$workorder->id}"))->with('error', $message);
            }
            return back()->with('error', $message);
        }
        
        // 只有处理中的工单可以邀请协作
        if (!in_array($workorder->status, ['processing', 'assigned'])) {
            $message = '当前工单状态不允许邀请协作';
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
            'collaborator_id' => 'required|exists:users,id|different:' . auth()->id(),
            'invitation_reason' => 'nullable|string|max:500',
        ]);
        
       try {
            // 走模型封装：它会建邀请记录、写工单日志，并发送协作邀请通知给被邀请人，
            // 否则被邀请人无从知晓（此前 controller 直接调 createInvitation 绕过了通知）。
            if ($workorder->inviteCollaborator(
                $request->input('collaborator_id'),
                $request->input('invitation_reason'),
                auth()->id()
            )) {
                return back()->with('success', '协作邀请发送成功，已通知被邀请人');
            } else {
                return back()->with('error', '协作邀请发送失败，可能已经存在待处理的邀请');
            }
        } catch (\Exception $e) {
           return back()->with('error', '协作邀请发送失败：' . $e->getMessage());
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
     * 接受协作邀请
     */
    public function acceptCollaboration(Request $request, WorkorderCollaboration $collaboration)
    {
        if (!$collaboration->canBeAccepted()) {
            $message = '无法接受此邀请';
            if ($request->isMethod('get')) {
                return redirect(\App\Helpers\UrlHelper::relative_url("/workorders"))->with('error', $message);
            }
            return back()->with('error', $message);
        }

        // 如果是GET请求，直接返回到工单详情页面
        if ($request->isMethod('get')) {
            return redirect(\App\Helpers\UrlHelper::relative_url("/workorders/{$collaboration->workorder_id}"));
        }
        
        try {
           if ($collaboration->accept()) {
               // 记录日志
               $collaboration->workorder->addLog('collaboration_accepted', "接受了 {$collaboration->inviter->name} 的协作邀请");
                // 通知发起邀请的人：对方已接受
                if ($collaboration->inviter) {
                    \App\Models\Notification::createWorkorderCollaborationAccepted(
                        $collaboration->workorder,
                        User::find($collaboration->collaborator_id) ?? auth()->user(),
                        $collaboration->inviter
                    );
                }
               
               // 接受邀请后，重定向到工单详情页面
                return redirect(\App\Helpers\UrlHelper::relative_url("/workorders/{$collaboration->workorder_id}"))->with('success', '协作邀请接受成功');
            } else {
                return back()->with('error', '协作邀请接受失败');
            }
        } catch (\Exception $e) {
            return back()->with('error', '协作邀请接受失败：' . $e->getMessage());
        }
    }

    /**
     * 拒绝协作邀请
     */
    public function rejectCollaboration(Request $request, WorkorderCollaboration $collaboration)
    {
        if (!$collaboration->canBeRejected()) {
            $message = '无法拒绝此邀请';
            // 提供更具体的错误信息
            if ($collaboration->collaborator_id !== auth()->id()) {
                $message = '您没有权限拒绝此邀请';
            } elseif ($collaboration->status !== 'pending') {
                $message = '此邀请已被处理，无法拒绝';
            }
            
            if ($request->isMethod('get')) {
                return redirect(\App\Helpers\UrlHelper::relative_url("/workorders"))->with('error', $message);
            }
            return back()->with('error', $message);
        }

        // 如果是GET请求，直接返回到工单详情页面
        if ($request->isMethod('get')) {
            return redirect(\App\Helpers\UrlHelper::relative_url("/workorders/{$collaboration->workorder_id}"));
        }
        
        $request->validate([
            'response_note' => 'nullable|string|max:500',
        ]);
        
        try {
           if ($collaboration->reject($request->input('response_note'))) {
               // 记录日志
               $collaboration->workorder->addLog('collaboration_rejected', "拒绝了 {$collaboration->inviter->name} 的协作邀请");
                // 通知发起邀请的人：对方已拒绝
                if ($collaboration->inviter) {
                    \App\Models\Notification::createWorkorderCollaborationRejected(
                        $collaboration->workorder,
                        User::find($collaboration->collaborator_id) ?? auth()->user(),
                        $collaboration->inviter,
                        $request->input('response_note')
                    );
                }
               
               // 拒绝邀请后，重定向到工单列表而不是工单详情页面
                // 因为用户拒绝后可能不再有权限查看该工单
                return redirect(\App\Helpers\UrlHelper::relative_url("/workorders"))->with('success', '协作邀请拒绝成功');
            } else {
                return back()->with('error', '协作邀请拒绝失败');
            }
       } catch (\Exception $e) {
           return back()->with('error', '协作邀请拒绝失败：' . $e->getMessage());
       }
   }
 
    /**
     * 取消协作邀请
     * 规则：仅工单负责人、工单管理员、系统管理员可取消；且仅当邀请仍为「待接受」时可取消。
     */
    public function cancelCollaboration(Request $request, WorkorderCollaboration $collaboration)
    {
        if (!$collaboration->canBeCancelledBy()) {
            $message = '无法取消此邀请';
            if ($collaboration->status !== 'pending') {
                $message = '对方已接受邀请，无法取消';
            } else {
                $message = '您没有权限取消此邀请';
            }

            if ($request->isMethod('get')) {
                return redirect(\App\Helpers\UrlHelper::relative_url("/workorders/{$collaboration->workorder_id}"))->with('error', $message);
            }
            return back()->with('error', $message);
        }

        $collaboratorName = $collaboration->collaborator?->name ?? '未知用户';
        $workorderId = $collaboration->workorder_id;

        try {
            if ($collaboration->cancel()) {
                $collaboration->workorder->addLog('collaboration_cancelled', "取消了对 {$collaboratorName} 的协作邀请");
                return redirect(\App\Helpers\UrlHelper::relative_url("/workorders/{$workorderId}"))
                    ->with('success', '协作邀请已取消');
            }
            return back()->with('error', '取消邀请失败');
        } catch (\Exception $e) {
            return back()->with('error', '取消邀请失败：' . $e->getMessage());
        }
    }
   
   /**
    * 批量分配工单
     */
    public function batchAssign(Request $request)
    {
        // 权限检查
        if (!auth()->user()->canAssignWorkorders()) {
            return response()->json(['success' => false, 'message' => '您没有权限分配工单'], 403);
        }
        
        $request->validate([
            'workorder_ids' => 'required|string',
            'assignee_id' => 'required|exists:users,id',
            'note' => 'nullable|string|max:500',
        ]);
        
        $workorderIds = $this->parseWorkorderIds($request);
        $assigneeId = $request->input('assignee_id');
        $note = $request->input('note');
        
        try {
            $successCount = 0;
            $failedCount = 0;
            $failedWorkorders = [];
            
            foreach ($workorderIds as $workorderId) {
                $workorder = Workorder::find($workorderId);
                
                if (!$workorder || !$workorder->canBeAssigned()) {
                    $failedCount++;
                    $failedWorkorders[] = $workorder->ticket_no ?? 'Unknown';
                    continue;
                }
                
                if ($workorder->assign($assigneeId, $note)) {
                    $successCount++;
                } else {
                    $failedCount++;
                    $failedWorkorders[] = $workorder->ticket_no;
                }
            }
            
            $message = "成功分配 {$successCount} 个工单";
            if ($failedCount > 0) {
                $message .= "，失败 {$failedCount} 个工单：" . implode(', ', $failedWorkorders);
            }
            
            return response()->json(['success' => true, 'message' => $message]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => '批量分配失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 批量开始处理工单
     */
    public function batchStart(Request $request)
    {
        // 权限检查
        if (!auth()->user()->canHandleWorkorders()) {
            return response()->json(['success' => false, 'message' => '您没有权限处理工单'], 403);
        }
        
        $request->validate([
            'workorder_ids' => 'required|string',
        ]);
        
        $workorderIds = $this->parseWorkorderIds($request);
        
        try {
            $successCount = 0;
            $failedCount = 0;
            $failedWorkorders = [];
            
            foreach ($workorderIds as $workorderId) {
                $workorder = Workorder::find($workorderId);
                
                // 权限检查：只能处理分配给自己的工单，或者管理员/工单管理员可以处理所有工单
                // 工单不存在时直接跳过，避免空值解引用
                if (!$workorder) {
                    $failedCount++;
                    $failedWorkorders[] = 'Unknown';
                    continue;
                }
                // 权限检查：只能处理分配给自己的工单，或者管理员/工单管理员可以处理所有工单
                if (!$workorder->canBeOperatedBy(auth()->user(), 'resolve')) {
                    $failedCount++;
                    $failedWorkorders[] = $workorder->ticket_no ?? 'Unknown';
                    continue;
                }
                if (!$workorder->canBeStarted()) {
                    $failedCount++;
                    $failedWorkorders[] = $workorder->ticket_no ?? 'Unknown';
                    continue;
                }
                if ($workorder->start()) {
                    $successCount++;
                } else {
                    $failedCount++;
                    $failedWorkorders[] = $workorder->ticket_no;
                }
            }
            
            $message = "成功开始处理 {$successCount} 个工单";
            if ($failedCount > 0) {
                $message .= "，失败 {$failedCount} 个工单：" . implode(', ', $failedWorkorders);
            }
            
            return response()->json(['success' => true, 'message' => $message]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => '批量开始处理失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 批量解决工单
     */
    public function batchResolve(Request $request)
    {
        // 权限检查
        if (!auth()->user()->canHandleWorkorders()) {
            return response()->json(['success' => false, 'message' => '您没有权限处理工单'], 403);
        }

        $request->validate([
            'workorder_ids' => 'required|string',
            'solution_type' => 'required|in:common,individual',
        ]);
        
        $workorderIds = $this->parseWorkorderIds($request);
        $solutionType = $request->input('solution_type');
        
        try {
            $successCount = 0;
            $failedCount = 0;
            $failedWorkorders = [];
            
            if ($solutionType === 'common') {
                // 通用解决方案模式验证
                $request->validate([
                    'solution' => 'required|string|max:2000',
                ]);
                
                $solution = $request->input('solution');
                $noMaterials = $request->boolean('no_materials');
                $materialsUsage = $noMaterials ? '无备件耗材使用' : $request->input('materials_usage');
                
                foreach ($workorderIds as $workorderId) {
                    $workorder = Workorder::find($workorderId);
                    
                    // 权限检查：只能处理分配给自己的工单，或者管理员/工单管理员可以处理所有工单
                    if (!$workorder->canBeOperatedBy(auth()->user(), 'resolve')) {
                        $failedCount++;
                        $failedWorkorders[] = $workorder->ticket_no ?? 'Unknown';
                        continue;
                    }
                    
                    if (!$workorder || !$workorder->canBeResolved()) {
                        $failedCount++;
                        $failedWorkorders[] = $workorder->ticket_no ?? 'Unknown';
                        continue;
                    }
                    
                    if ($workorder->resolve($solution)) {
                        // 更新备件耗材使用情况
                        $workorder->materials_usage = $materialsUsage;
                        $workorder->save();
                        
                        // 记录日志
                        $workorder->addLog('materials_updated', '更新了备件耗材使用情况');
                        
                        $successCount++;
                    } else {
                        $failedCount++;
                        $failedWorkorders[] = $workorder->ticket_no;
                    }
                }
            } else {
                // 单独设置模式验证
                $solutions = $request->input('solutions', []);
                $noMaterialsArray = $request->input('no_materials_array', []);
                $materialsUsageArray = $request->input('materials_usage_array', []);
                
                foreach ($workorderIds as $workorderId) {
                    $workorder = Workorder::find($workorderId);
                    
                    // 权限检查：只能处理分配给自己的工单，或者管理员/工单管理员可以处理所有工单
                    if (!$workorder->canBeOperatedBy(auth()->user(), 'resolve')) {
                        $failedCount++;
                        $failedWorkorders[] = $workorder->ticket_no ?? 'Unknown';
                        continue;
                    }
                    
                    if (!$workorder || !$workorder->canBeResolved()) {
                        $failedCount++;
                        $failedWorkorders[] = $workorder->ticket_no ?? 'Unknown';
                        continue;
                    }
                    
                    $solution = $solutions[$workorderId] ?? '';
                    $noMaterials = $noMaterialsArray[$workorderId] ?? false;
                    $materialsUsage = $noMaterials ? '无备件耗材使用' : ($materialsUsageArray[$workorderId] ?? '');
                    
                    if ($workorder->resolve($solution)) {
                        // 更新备件耗材使用情况
                        $workorder->materials_usage = $materialsUsage;
                        $workorder->save();
                        
                        // 记录日志
                        $workorder->addLog('materials_updated', '更新了备件耗材使用情况');
                        
                        $successCount++;
                    } else {
                        $failedCount++;
                        $failedWorkorders[] = $workorder->ticket_no;
                    }
                }
            }
            
            $message = "成功解决 {$successCount} 个工单";
            if ($failedCount > 0) {
                $message .= "，失败 {$failedCount} 个工单：" . implode(', ', $failedWorkorders);
            }
            
            return response()->json(['success' => true, 'message' => $message]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => '批量解决失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 批量关闭工单
     */
    public function batchClose(Request $request)
    {
        // 权限检查
        if (!auth()->user()->canCloseWorkorders()) {
            return response()->json(['success' => false, 'message' => '您没有权限关闭工单'], 403);
        }
        
        $request->validate([
            'workorder_ids' => 'required|string',
        ]);
        
        $workorderIds = $this->parseWorkorderIds($request);
        
        try {
            $successCount = 0;
            $failedCount = 0;
            $failedWorkorders = [];
            
            foreach ($workorderIds as $workorderId) {
                $workorder = Workorder::find($workorderId);
                
                if (!$workorder || !$workorder->canBeClosed()) {
                    $failedCount++;
                    $failedWorkorders[] = $workorder->ticket_no ?? 'Unknown';
                    continue;
                }
                
                if ($workorder->close()) {
                    $successCount++;
                } else {
                    $failedCount++;
                    $failedWorkorders[] = $workorder->ticket_no;
                }
            }
            
            $message = "成功关闭 {$successCount} 个工单";
            if ($failedCount > 0) {
                $message .= "，失败 {$failedCount} 个工单：" . implode(', ', $failedWorkorders);
            }
            
            return response()->json(['success' => true, 'message' => $message]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => '批量关闭失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 批量完结工单
     */
    public function batchComplete(Request $request)
    {
        // 权限检查
        if (!auth()->user()->canHandleWorkorders()) {
            return response()->json(['success' => false, 'message' => '您没有权限处理工单'], 403);
        }

        $request->validate([
            'workorder_ids' => 'required|string',
            'completion_note' => 'nullable|string|max:1000',
        ]);
        
        $workorderIds = $this->parseWorkorderIds($request);
        $completionNote = $request->input('completion_note', '批量完结工单');
        
        try {
            $successCount = 0;
            $failedCount = 0;
            $failedWorkorders = [];
            
            foreach ($workorderIds as $workorderId) {
                $workorder = Workorder::find($workorderId);
                
                // 权限检查：只能处理分配给自己的工单，或者管理员/工单管理员可以处理所有工单
                // 工单不存在时直接跳过，避免空值解引用
                if (!$workorder) {
                    $failedCount++;
                    $failedWorkorders[] = 'Unknown';
                    continue;
                }
                // 权限检查：只能处理分配给自己的工单，或者管理员/工单管理员可以处理所有工单
                if (!$workorder->canBeOperatedBy(auth()->user(), 'resolve')) {
                    $failedCount++;
                    $failedWorkorders[] = $workorder->ticket_no ?? 'Unknown';
                    continue;
                }
                if (!$workorder->canBeCompleted()) {
                    $failedCount++;
                    $failedWorkorders[] = $workorder->ticket_no ?? 'Unknown';
                    continue;
                }
                if ($workorder->complete()) {
                    // 如果有完结备注，添加到日志
                    if (!empty($completionNote)) {
                        $workorder->addLog('completed', $completionNote);
                    }
                    
                    $successCount++;
                } else {
                    $failedCount++;
                    $failedWorkorders[] = $workorder->ticket_no;
                }
            }
            
            $message = "成功完结 {$successCount} 个工单";
            if ($failedCount > 0) {
                $message .= "，失败 {$failedCount} 个工单：" . implode(', ', $failedWorkorders);
            }
            
            return response()->json(['success' => true, 'message' => $message]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => '批量完结失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 将逗号分隔的工单 ID 字符串解析为去重的正整数数组
     */
    private function parseWorkorderIds(Request $request): array
    {
        $ids = explode(',', $request->input('workorder_ids', ''));
        $ids = array_filter($ids, fn($id) => is_numeric($id) && $id > 0);
        return array_map('intval', $ids);
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
     * 获取子分类API
     */
    public function getSubCategories(Request $request)
    {
        $parentId = $request->input('parent_id');
        
        if (!$parentId) {
            return response()->json([]);
        }
        
        $categories = WorkorderType::where('parent_id', $parentId)
            ->where('status', 'active')
            ->orderBy('sort_order')
            ->get(['id', 'name']);
            
        return response()->json($categories);
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
}
