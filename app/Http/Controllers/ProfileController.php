<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    /**
     * 仪表板
     */
    public function dashboard()
    {
        return view('dashboard');
    }

    /**
     * 个人资料页
     */
    public function index()
    {
        return view('profile');
    }

    /**
     * 更新个人资料
     */
    public function update(Request $request)
    {
        if (auth()->user()->isSsoUser()) {
            return back()->with('error', '统一身份认证用户的个人信息由身份认证服务方管理，无法在此修改');
        }

        $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:100|unique:users,email,' . auth()->id(),
            'phone' => 'nullable|string|max:20',
            'employee_id' => 'nullable|string|max:50',
            'department_id' => 'nullable|exists:departments,id',
            'location' => 'nullable|string|max:255',
            'remarks' => 'nullable|string|max:500',
        ]);

        auth()->user()->update($request->only([
            'name', 'email', 'phone', 'employee_id',
            'department_id', 'location', 'remarks',
        ]));

        return back()->with('success', '个人信息更新成功');
    }

    /**
     * 修改密码
     */
    public function updatePassword(Request $request)
    {
        if (auth()->user()->isSsoUser()) {
            return back()->with('error', '统一身份认证用户的密码由身份认证服务方管理，无法在此修改');
        }

        $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:6|confirmed',
        ]);

        if (!Hash::check($request->current_password, auth()->user()->password)) {
            return back()->withErrors(['current_password' => '当前密码不正确']);
        }

        auth()->user()->update([
            'password' => Hash::make($request->password),
        ]);

        return back()->with('success', '密码修改成功');
    }

    /**
     * 强制修改默认密码页
     */
    public function passwordChange()
    {
        return view('auth.passwords.change');
    }

    /**
     * 强制修改默认密码提交
     */
    public function passwordUpdate(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:6|confirmed',
        ]);

        if (!Hash::check($request->current_password, auth()->user()->password)) {
            return back()->withErrors(['current_password' => '当前密码不正确']);
        }

        auth()->user()->update([
            'password' => $request->password,
            'password_changed_at' => now(),
        ]);

        return redirect()->route('dashboard')->with('success', '密码修改成功，欢迎使用系统');
    }
}
