<?php

namespace App\Http\Controllers\Traits;

use App\Models\Workorder;
use App\Models\WorkorderCategorySimplified;
use App\Models\WorkorderAttachment;
use App\Models\Campus;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * CAS 用户简化报修表单逻辑
 * CAS 工单提交后进入 pending 工单池，工程师可就近自行接单。
 */
trait HandlesReport
{
    /**
     * CAS 用户简化报修表单
     */
    public function reportCreate(Request $request)
    {
        if (!in_array(Auth::user()->role, ['user', 'admin', 'workorder_manager'])) {
            return redirect()->route('workorders.create');
        }

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

        return view('workorders.report', compact('categories', 'campusOptions', 'campusBuildings', 'addressPrefix'));
    }

    /**
     * CAS 用户简化报修提交
     * 工单进入 pending 工单池，等待工程师接单或管理员分配。
     */
    public function reportStore(Request $request)
    {
        $request->validate([
            'description' => 'required|string|max:2000',
            'category_main' => 'required|exists:workorder_categories_simplified,id',
            'category_sub' => 'required|exists:workorder_categories_simplified,id',
            'campus_id' => 'required|exists:locations,id',
            'building' => 'required|exists:locations,id|different:campus_id',
            'location_detail' => 'nullable|string|max:500',
            'other_reason' => 'nullable|string|max:500',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|max:10240|mimes:jpg,jpeg,png,gif,bmp,webp,pdf,doc,docx,xls,xlsx,ppt,pptx,txt,md,mp4,mov,avi,wmv,mkv,mp3,wav,flac,aac,ogg,zip,rar,7z',
        ]);

        DB::beginTransaction();
        try {
            $user = Auth::user();

            $mainCategory = WorkorderCategorySimplified::find($request->input('category_main'));
            $subCategory = WorkorderCategorySimplified::find($request->input('category_sub'));
            $buildingLocation = \App\Models\Location::find($request->input('building'));

            $ticketPrefix = $mainCategory ? $mainCategory->getTicketPrefix() : 'WO';

            $workorder = Workorder::create([
                'ticket_no'      => Workorder::generateTicketNoByPrefix($ticketPrefix),
                'ticket_prefix'  => $ticketPrefix,
                'title'          => mb_substr($request->input('description'), 0, 50),
                'description'    => $request->input('description'),
                'category_id'    => $request->input('category_sub'),
                'type_id'        => null,
                'creator_id'     => $user->id,
                'contact_name'   => $user->name,
                'contact_phone'  => $user->phone ?? '',
                'contact_email'  => $user->email,
                'location_id'    => $buildingLocation ? $buildingLocation->id : null,
                'location_detail'=> $request->input('location_detail'),
                'source'         => '本台',
                'priority'       => 'medium',
                'status'         => 'pending',
                'department_name'=> $this->resolveDepartmentName($user),
                'other_reason'   => $request->input('other_reason'),
                'need_visit'        => false,
                'is_emergency'      => false,
                'phone_assisted'    => false,
                'requires_signature'=> false,
            ]);

            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    if ($file->isValid()) {
                        // 统一走 uploadFile：私有盘 + UUID 文件名 + 危险扩展名兜底拦截
                        WorkorderAttachment::uploadFile($file, $workorder->id);
                    }
                }
            }

            $workorder->addLog('created', '用户通过统一身份认证自助申报', $user->id);
            $workorder->sendNotification('created');

            DB::commit();

            return redirect()
                ->route('workorders.show', $workorder)
                ->with('success', '故障申报提交成功，工程师将尽快处理');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->with('error', '提交失败：' . $e->getMessage());
        }
    }

    /**
     * 解析申报人所属部门名称
     * 优先取 users.department_id -> departments.name；若取不到则从 remarks 中剥掉「部门：」前缀。
     */
    private function resolveDepartmentName($user): ?string
    {
        if ($user && $user->department) {
            return $user->department->name;
        }
        if ($user && $user->remarks) {
            return trim(str_replace('部门：', '', (string) $user->remarks));
        }
        return null;
    }
}
