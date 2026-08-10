<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * 用户列表页面
     */
    public function index(Request $request)
    {
        $query = User::with('department')->orderBy('created_at', 'desc');

        // 搜索条件
        if ($request->filled('keyword')) {
            $keyword = $request->input('keyword');
            $query->where(function($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                  ->orWhere('username', 'like', "%{$keyword}%")
                  ->orWhere('email', 'like', "%{$keyword}%")
                  ->orWhere('phone', 'like', "%{$keyword}%")
                  ->orWhere('employee_id', 'like', "%{$keyword}%");
            });
        }

        // 角色筛选
        if ($request->filled('role')) {
            $query->where('role', $request->input('role'));
        }

        // 状态筛选
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // 部门筛选
        if ($request->filled('department_id')) {
            $query->where('department_id', $request->input('department_id'));
        }

        $users = $query->paginate(15);
        $departments = Department::where('status', 'active')->get();
        
        return view('users.index', compact('users', 'departments'));
    }

    /**
     * 创建用户页面
     */
    public function create()
    {
        $departments = Department::where('status', 'active')->get();
        $roles = User::getRoleOptions();
        $statuses = User::getStatusOptions();
        
        return view('users.create', compact('departments', 'roles', 'statuses'));
    }

    /**
     * 保存用户
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'username' => 'required|string|max:50|unique:users,username',
            'email' => 'required|email|max:100|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
            'phone' => 'nullable|string|max:20',
            'employee_id' => 'nullable|string|max:50|unique:users,employee_id',
            'wecom_userid' => 'nullable|string|max:100',
            'dingtalk_userid' => 'nullable|string|max:100',
            'feishu_user_id' => 'nullable|string|max:100',
            'wechat_openid' => 'nullable|string|max:128',
            'department_id' => 'nullable|exists:departments,id',
            'role' => 'required|in:admin,workorder_manager,engineer,user',
            'status' => 'required|in:active,inactive',
            'location' => 'nullable|string|max:255',
            'remarks' => 'nullable|string|max:500',
            'account_type' => 'required|in:staff,student,external',
        ]);

        DB::beginTransaction();
        try {
            $data = $request->all();
            $data['password'] = Hash::make($data['password']);
            
            User::create($data);
            
            DB::commit();
            
            return redirect()->route('users.index', $request->query())
                ->with('success', '用户创建成功');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', '用户创建失败：' . $e->getMessage());
        }
    }

    /**
     * 用户详情页面
     */
    public function show(User $user)
    {
        $user->load([
            'department',
            'createdWorkorders' => function($query) {
                $query->latest()->limit(10);
            },
            'assignedWorkorders' => function($query) {
                $query->latest()->limit(10);
            }
        ]);
        
        // 获取最近工单
        $recentWorkorders = $user->assignedWorkorders()
            ->latest()
            ->limit(10)
            ->get();
        
        return view('users.show', compact('user', 'recentWorkorders'));
    }

    /**
     * 编辑用户页面
     */
    public function edit(User $user)
    {
        $departments = Department::where('status', 'active')->get();
        $roles = User::getRoleOptions();
        $statuses = User::getStatusOptions();
        
        return view('users.edit', compact('user', 'departments', 'roles', 'statuses'));
    }

    /**
     * 更新用户
     */
    public function update(Request $request, User $user)
    {
        $rules = [
            'name' => 'required|string|max:100',
            'username' => 'required|string|max:50|unique:users,username,' . $user->id,
            'email' => 'required|email|max:100|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
            'employee_id' => 'nullable|string|max:50|unique:users,employee_id,' . $user->id,
            'wecom_userid' => 'nullable|string|max:100',
            'dingtalk_userid' => 'nullable|string|max:100',
            'feishu_user_id' => 'nullable|string|max:100',
            'wechat_openid' => 'nullable|string|max:128',
            'department_id' => 'nullable|exists:departments,id',
            'role' => 'required|in:admin,workorder_manager,engineer,user',
            'status' => 'required|in:active,inactive',
            'location' => 'nullable|string|max:255',
            'remarks' => 'nullable|string|max:500',
            'account_type' => 'required|in:staff,student,external',
        ];

        // 如果提供了密码，则添加密码验证规则
        if ($request->filled('password')) {
            $rules['password'] = 'required|string|min:6|confirmed';
        }

        $request->validate($rules);

        DB::beginTransaction();
        try {
            $data = $request->all();
            
            // 如果提供了新密码，则加密
            if ($request->filled('password')) {
                $data['password'] = Hash::make($data['password']);
            } else {
                unset($data['password']);
            }
            
            $user->update($data);
            
            DB::commit();
            
            return redirect()->route('users.index', $request->query())
                ->with('success', '用户更新成功');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', '用户更新失败：' . $e->getMessage());
        }
    }

    /**
     * 删除用户
     */
    public function destroy(Request $request, User $user)
    {
        // 防止删除自己
        if ($user->id === auth()->id()) {
            return back()->with('error', '不能删除自己的账户');
        }

        // 检查是否有未完成的工单
        if ($user->assignedWorkorders()->whereIn('status', ['pending', 'assigned', 'processing'])->count() > 0) {
            return back()->with('error', '该用户还有未处理的工单，无法删除');
        }

        try {
            $user->delete();
            return redirect()->route('users.index', $request->query())
                ->with('success', '用户删除成功');
        } catch (\Exception $e) {
            return back()->with('error', '用户删除失败：' . $e->getMessage());
        }
    }

    /**
     * 重置用户密码
     */
    public function resetPassword(Request $request, User $user)
    {
        $request->validate([
            'password' => 'required|string|min:6|confirmed',
        ]);

        try {
            $user->update([
                'password' => Hash::make($request->input('password')),
                'password_changed_at' => null,
            ]);
            
            return back()->with('success', '密码重置成功');
        } catch (\Exception $e) {
            return back()->with('error', '密码重置失败：' . $e->getMessage());
        }
    }

    /**
     * 切换用户状态
     */
    public function toggleStatus(User $user)
    {
        // 防止禁用自己
        if ($user->id === auth()->id()) {
            return back()->with('error', '不能禁用自己的账户');
        }

        try {
            $newStatus = $user->status === 'active' ? 'inactive' : 'active';
            $user->update(['status' => $newStatus]);
            
            $message = $newStatus === 'active' ? '用户已启用' : '用户已禁用';
            return back()->with('success', $message);
        } catch (\Exception $e) {
            return back()->with('error', '状态切换失败：' . $e->getMessage());
        }
    }

    /**
     * 获取用户统计信息
     */
    public function statistics(User $user)
    {
        $stats = [
            'created_workorders_count' => $user->createdWorkorders()->count(),
            'assigned_workorders_count' => $user->assignedWorkorders()->count(),
            'pending_workorders_count' => $user->assignedWorkorders()
                ->whereIn('status', ['pending', 'assigned', 'processing'])
                ->count(),
            'completed_workorders_count' => $user->assignedWorkorders()
                ->whereIn('status', ['resolved', 'closed'])
                ->count(),
            'avg_processing_time' => $user->assignedWorkorders()
                ->whereNotNull('processing_duration')
                ->avg('processing_duration'),
            'attachments_count' => $user->attachments()->count(),
        ];
        
        return response()->json($stats);
    }

    /**
     * 获取工程师选项（用于API）
     */
    public function engineers(Request $request)
    {
        $engineers = User::getAssignableEngineers();
        
        if ($request->filled('department_id')) {
            $engineers = $engineers->where('department_id', $request->input('department_id'));
        }
        
        return response()->json($engineers->map(function($engineer) {
            return [
                'id' => $engineer->id,
                'name' => $engineer->name,
                'email' => $engineer->email,
                'phone' => $engineer->phone,
                'department' => $engineer->department ? $engineer->department->name : null,
            ];
        }));
    }

    /**
     * 批量操作用户
     */
    public function batchOperation(Request $request)
    {
        $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
            'operation' => 'required|in:activate,deactivate,delete',
        ]);

        $userIds = $request->input('user_ids');
        
        // 防止操作自己
        if (in_array(auth()->id(), $userIds)) {
            return back()->with('error', '不能对自己的账户进行批量操作');
        }

        DB::beginTransaction();
        try {
            switch ($request->input('operation')) {
                case 'activate':
                    User::whereIn('id', $userIds)->update(['status' => 'active']);
                    $message = '用户批量启用成功';
                    break;
                    
                case 'deactivate':
                    // 检查是否有未完成的工单
                    $hasPendingWorkorders = User::whereIn('id', $userIds)
                        ->whereHas('assignedWorkorders', function($query) {
                            $query->whereIn('status', ['pending', 'assigned', 'processing']);
                        })
                        ->exists();
                    
                    if ($hasPendingWorkorders) {
                        throw new \Exception('选中的用户中还有未处理的工单，无法禁用');
                    }
                    
                    User::whereIn('id', $userIds)->update(['status' => 'inactive']);
                    $message = '用户批量禁用成功';
                    break;
                    
                case 'delete':
                    // 检查是否有未完成的工单
                    $hasPendingWorkorders = User::whereIn('id', $userIds)
                        ->whereHas('assignedWorkorders', function($query) {
                            $query->whereIn('status', ['pending', 'assigned', 'processing']);
                        })
                        ->exists();
                    
                    if ($hasPendingWorkorders) {
                        throw new \Exception('选中的用户中还有未处理的工单，无法删除');
                    }
                    
                    User::whereIn('id', $userIds)->delete();
                    $message = '用户批量删除成功';
                    break;
            }
            
            DB::commit();
            
            return back()->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', '批量操作失败：' . $e->getMessage());
        }
    }
}
