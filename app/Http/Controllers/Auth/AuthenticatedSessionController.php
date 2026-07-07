<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\AppServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create()
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(Request $request)
    {
        $credentials = $request->validate([
            'name' => ['required', 'string'],
            'password' => ['required'],
        ]);

        // 尝试使用用户名登录
        $usernameCredentials = [
            'username' => $credentials['name'],
            'password' => $credentials['password']
        ];
        
        if (Auth::attempt($usernameCredentials)) {
            $request->session()->regenerate();
            return redirect()->intended('/workorders');
        }

        // 如果用户名登录失败，尝试使用邮箱登录
        $emailCredentials = [
            'email' => $credentials['name'],
            'password' => $credentials['password']
        ];

        if (Auth::attempt($emailCredentials)) {
            $request->session()->regenerate();
            return redirect()->intended('/workorders');
        }

        return back()->withErrors([
            'name' => '用户名或密码错误。',
        ])->onlyInput('name');
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        // 使用相对URL，让浏览器自动处理协议
        return redirect('/workorders');
    }
}