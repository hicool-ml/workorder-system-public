<?php

namespace App\Http\Controllers;

use App\Models\Workorder;
use App\Models\WorkorderCollaboration;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * 工单协作：邀请 / 接受 / 拒绝 / 取消
 */
class WorkorderCollaborationController extends Controller
{
    /**
     * 邀请协作
     */
    public function invite(Request $request, Workorder $workorder)
    {
        if (!$workorder->canBeOperatedBy(auth()->user(), 'resolve')) {
            return $this->backWithError($request, $workorder->id, '您没有权限邀请协作');
        }

        if (!in_array($workorder->status, ['processing', 'assigned'])) {
            return $this->backWithError($request, $workorder->id, '当前工单状态不允许邀请协作');
        }

        if ($request->isMethod('get')) {
            return redirect(\App\Helpers\UrlHelper::relative_url("/workorders/{$workorder->id}"));
        }

        $request->validate([
            'collaborator_id' => 'required|exists:users,id|different:' . auth()->id(),
            'invitation_reason' => 'nullable|string|max:500',
        ]);

        try {
            // 走模型封装：建邀请记录、写工单日志、发送协作邀请通知给被邀请人
            if ($workorder->inviteCollaborator(
                $request->input('collaborator_id'),
                $request->input('invitation_reason'),
                auth()->id()
            )) {
                return back()->with('success', '协作邀请发送成功，已通知被邀请人');
            }
            return back()->with('error', '协作邀请发送失败，可能已经存在待处理的邀请');
        } catch (\Exception $e) {
            return back()->with('error', '协作邀请发送失败：' . $e->getMessage());
        }
    }

    /**
     * 接受协作邀请
     */
    public function accept(Request $request, WorkorderCollaboration $collaboration)
    {
        if (!$collaboration->canBeAccepted()) {
            return $this->listWithError($request, '无法接受此邀请');
        }

        if ($request->isMethod('get')) {
            return redirect(\App\Helpers\UrlHelper::relative_url("/workorders/{$collaboration->workorder_id}"));
        }

        try {
            if ($collaboration->accept()) {
                $collaboration->workorder->addLog('collaboration_accepted', "接受了 {$collaboration->inviter->name} 的协作邀请");
                if ($collaboration->inviter) {
                    \App\Models\Notification::createWorkorderCollaborationAccepted(
                        $collaboration->workorder,
                        User::find($collaboration->collaborator_id) ?? auth()->user(),
                        $collaboration->inviter
                    );
                }
                return redirect(\App\Helpers\UrlHelper::relative_url("/workorders/{$collaboration->workorder_id}"))
                    ->with('success', '协作邀请接受成功');
            }
            return back()->with('error', '协作邀请接受失败');
        } catch (\Exception $e) {
            return back()->with('error', '协作邀请接受失败：' . $e->getMessage());
        }
    }

    /**
     * 拒绝协作邀请
     */
    public function reject(Request $request, WorkorderCollaboration $collaboration)
    {
        if (!$collaboration->canBeRejected()) {
            $message = '无法拒绝此邀请';
            if ($collaboration->collaborator_id !== auth()->id()) {
                $message = '您没有权限拒绝此邀请';
            } elseif ($collaboration->status !== 'pending') {
                $message = '此邀请已被处理，无法拒绝';
            }
            return $this->listWithError($request, $message);
        }

        if ($request->isMethod('get')) {
            return redirect(\App\Helpers\UrlHelper::relative_url("/workorders/{$collaboration->workorder_id}"));
        }

        $request->validate([
            'response_note' => 'nullable|string|max:500',
        ]);

        try {
            if ($collaboration->reject($request->input('response_note'))) {
                $collaboration->workorder->addLog('collaboration_rejected', "拒绝了 {$collaboration->inviter->name} 的协作邀请");
                if ($collaboration->inviter) {
                    \App\Models\Notification::createWorkorderCollaborationRejected(
                        $collaboration->workorder,
                        User::find($collaboration->collaborator_id) ?? auth()->user(),
                        $collaboration->inviter,
                        $request->input('response_note')
                    );
                }
                // 拒绝后重定向到列表（用户可能已无权限查看该工单）
                return redirect(\App\Helpers\UrlHelper::relative_url("/workorders"))->with('success', '协作邀请拒绝成功');
            }
            return back()->with('error', '协作邀请拒绝失败');
        } catch (\Exception $e) {
            return back()->with('error', '协作邀请拒绝失败：' . $e->getMessage());
        }
    }

    /**
     * 取消协作邀请（仅工单负责人、工单管理员、系统管理员；邀请仍为待接受时可取消）
     */
    public function cancel(Request $request, WorkorderCollaboration $collaboration)
    {
        if (!$collaboration->canBeCancelledBy()) {
            $message = $collaboration->status !== 'pending' ? '对方已接受邀请，无法取消' : '您没有权限取消此邀请';
            return $this->backWithError($request, $collaboration->workorder_id, $message);
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

    // ===== 辅助 =====

    private function backWithError(Request $request, int $workorderId, string $message)
    {
        if ($request->isMethod('get')) {
            return redirect(\App\Helpers\UrlHelper::relative_url("/workorders/{$workorderId}"))->with('error', $message);
        }
        return back()->with('error', $message);
    }

    private function listWithError(Request $request, string $message)
    {
        if ($request->isMethod('get')) {
            return redirect(\App\Helpers\UrlHelper::relative_url("/workorders"))->with('error', $message);
        }
        return back()->with('error', $message);
    }
}
