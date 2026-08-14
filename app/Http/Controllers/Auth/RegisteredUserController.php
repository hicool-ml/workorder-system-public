<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class RegisteredUserController extends Controller
{
    /**
     * 显示注册页面
     */
    public function create()
    {
        // 检查是否开放注册
        if (!SystemSetting::isRegistrationEnabled()) {
            return redirect()->route('login')
                ->with('error', '当前未开放用户注册');
        }

        // 获取部门列表供选择
        $departments = \App\Models\Department::where('status', 'active')->get();

        return view('auth.register', compact('departments'));
    }

    /**
     * 处理注册请求
     */
    public function store(Request $request)
    {
        // 检查是否开放注册
        if (!SystemSetting::isRegistrationEnabled()) {
            return back()->with('error', '当前未开放用户注册');
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'username' => 'required|string|max:50|unique:users,username',
            'email' => 'required|email|max:100|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
            'phone' => 'nullable|string|max:20',
            'employee_id' => 'nullable|string|max:50|unique:users,employee_id',
            'department_id' => 'nullable|exists:departments,id',
            'location' => 'nullable|string|max:255',
            'account_type' => 'required|in:staff,student,external',
        ], [
            'name.required' => '姓名不能为空',
            'name.max' => '姓名不能超过100个字符',
            'username.required' => '用户名不能为空',
            'username.unique' => '用户名已存在',
            'email.required' => '邮箱不能为空',
            'email.email' => '请输入有效的邮箱地址',
            'email.unique' => '邮箱已存在',
            'password.required' => '密码不能为空',
            'password.min' => '密码至少需要6个字符',
            'password.confirmed' => '两次输入的密码不一致',
            'phone.max' => '电话号码不能超过20个字符',
            'employee_id.unique' => '员工号已存在',
            'department_id.exists' => '选择的部门不存在',
            'location.max' => '位置信息不能超过255个字符',
            'account_type.required' => '账户类型不能为空',
            'account_type.in' => '账户类型无效',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        DB::beginTransaction();
        try {
            // 白名单取值，防止注入 SSO 绑定字段（wechat_openid/oidc_sub 等）
            $data = $request->only([
                'name', 'username', 'email', 'password', 'phone',
                'employee_id', 'department_id', 'location', 'account_type',
            ]);
            $data['password'] = Hash::make($data['password']);
            $data['role'] = SystemSetting::getDefaultUserRole();
            $data['status'] = 'active';

            User::create($data);

            DB::commit();

            return redirect()->route('login')
                ->with('success', '注册成功，请使用您的账户登录');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', '注册失败：' . $e->getMessage())->withInput();
        }
    }

    /**
     * 检查注册状态（API）
     */
    public function checkRegistrationStatus()
    {
        return response()->json([
            'enabled' => SystemSetting::isRegistrationEnabled(),
            'default_role' => SystemSetting::getDefaultUserRole(),
            'require_email_verification' => SystemSetting::get('require_email_verification', false),
        ]);
    }
}