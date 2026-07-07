<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Workorder;
use Illuminate\Http\Request;

class UserManagementController extends Controller
{
    /**
     * 用户管理列表（安全删除功能）
     */
    public function index(Request $request)
    {
        $query = User::with('department');

        if ($request->filled('keyword')) {
            $keyword = $request->input('keyword');
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                  ->orWhere('username', 'like', "%{$keyword}%")
                  ->orWhere('email', 'like', "%{$keyword}%")
                  ->orWhere('employee_id', 'like', "%{$keyword}%");
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->input('role'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(15);

        return view('users-management.index', compact('users'));
    }

    /**
     * 删除确认页面
     */
    public function deleteConfirm($id)
    {
        $user = User::with('department')->findOrFail($id);
        $stats = $this->getUserStatsData($user);

        return view('users-management.delete-confirm', compact('user', 'stats'));
    }

    /**
     * 安全删除用户
     */
    public function destroy($id)
    {
        $user = User::findOrFail($id);

        if ($user->isAdmin() && User::where('role', 'admin')->count() <= 1) {
            return back()->with('error', '不能删除最后一个管理员账户');
        }

        $user->delete();

        return redirect()->route('users.management')
            ->with('success', "用户 {$user->name} 已删除");
    }

    /**
     * 批量操作
     */
    public function batchAction(Request $request)
    {
        $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'integer|exists:users,id',
            'action' => 'required|in:delete,activate,deactivate',
        ]);

        $userIds = $request->input('user_ids');
        $action = $request->input('action');

        if ($action === 'delete') {
            $adminCount = User::whereIn('id', $userIds)->where('role', 'admin')->count();
            $totalAdmins = User::where('role', 'admin')->count();
            if ($adminCount >= $totalAdmins) {
                return response()->json([
                    'success' => false,
                    'message' => '不能删除所有管理员账户',
                ], 403);
            }

            User::whereIn('id', $userIds)->delete();
            $message = "成功删除 " . count($userIds) . " 个用户";
        } elseif ($action === 'activate') {
            User::whereIn('id', $userIds)->update(['status' => 'active']);
            $message = "成功启用 " . count($userIds) . " 个用户";
        } else {
            User::whereIn('id', $userIds)->update(['status' => 'inactive']);
            $message = "成功禁用 " . count($userIds) . " 个用户";
        }

        return response()->json(['success' => true, 'message' => $message]);
    }

    /**
     * 获取用户统计数据（API）
     */
    public function getUserStats($id)
    {
        $user = User::findOrFail($id);
        return response()->json($this->getUserStatsData($user));
    }

    /**
     * 获取用户的关联统计数据
     */
    private function getUserStatsData(User $user): array
    {
        return [
            'created_count' => Workorder::where('creator_id', $user->id)->count(),
            'assigned_count' => Workorder::where('assignee_id', $user->id)->count(),
            'pending_count' => Workorder::where('assignee_id', $user->id)
                ->whereIn('status', ['pending', 'assigned', 'processing'])
                ->count(),
            'resolved_count' => Workorder::where('assignee_id', $user->id)
                ->where('status', 'resolved')
                ->count(),
        ];
    }
}
