<?php

namespace App\Http\Controllers\Traits;

use App\Models\Workorder;
use App\Models\WorkorderCategorySimplified;
use App\Models\Campus;
use App\Models\Building;
use App\Models\WorkorderAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * CAS 用户简化报修表单逻辑
 * 从 WorkorderController 调用，避免修改过大的控制器文件。
 * CAS 工单提交后进入 pending 状态形成工单池，工程师可就近自行接单。
 */
trait HandlesReport
{
    /**
     * CAS 用户简化报修表单
     */
    public function reportCreate(Request $request)
    {
        if (!in_array(Auth::user()->role, ['user'])) {
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

        $campuses = Campus::orderBy('sort_order')->orderBy('name')->get();
        $buildings = Building::orderBy('name')->get();

        return view('workorders.report', compact('categories', 'campuses', 'buildings'));
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
            'campus_id' => 'required|exists:campuses,id',
            'building' => 'required|string|max:200',
            'location_detail' => 'nullable|string|max:500',
            'other_reason' => 'nullable|string|max:500',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|max:10240',
        ]);

        DB::beginTransaction();
        try {
            $user = Auth::user();

            $mainCategory = WorkorderCategorySimplified::find($request->input('category_main'));
            $subCategory = WorkorderCategorySimplified::find($request->input('category_sub'));
            $campus = Campus::find($request->input('campus_id'));

            $ticketPrefix = $mainCategory ? $mainCategory->getTicketPrefix() : 'WO';

            // 字段对齐数据库实际结构
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
                'campus'         => $campus ? $campus->name : '',
                'campus_id'      => $request->input('campus_id'),
                'building'       => $request->input('building'),
                'location'       => ($campus ? $campus->name : '') . ' - ' . $request->input('building'),
                'location_detail'=> $request->input('location_detail'),
                'source'         => '本台',
                'priority'       => 'medium',
                'status'         => 'pending',
                'department_name'=> $user->remarks,
                'other_reason'   => $request->input('other_reason'),
                'need_visit'        => false,
                'is_emergency'      => false,
                'phone_assisted'    => false,
                'requires_signature'=> false,
            ]);

            // 处理附件
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    if ($file->isValid()) {
                        $path = $file->store('workorder-attachments', 'public');
                        WorkorderAttachment::create([
                            'workorder_id' => $workorder->id,
                            'file_name'    => $file->getClientOriginalName(),
                            'file_path'    => $path,
                            'file_size'    => $file->getSize(),
                            'mime_type'    => $file->getMimeType(),
                            'uploaded_by'  => $user->id,
                        ]);
                    }
                }
            }

            $workorder->addLog('created', '用户通过统一身份认证自助申报', $user->id);

            // 发送通知（工单池通知所有工程师和管理员）
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
}
