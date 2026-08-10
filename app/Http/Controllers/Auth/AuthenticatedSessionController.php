<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\AppServiceProvider;
use App\Models\SystemSetting;
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
            'login' => ['required', 'string'],
            'password' => ['required'],
        ]);

        // 尝试使用用户名登录
        $usernameCredentials = [
            'username' => $credentials['login'],
            'password' => $credentials['password']
        ];
        
        if (Auth::attempt($usernameCredentials)) {
            if (Auth::user()->status !== 'active') {
                Auth::logout();
                return back()->withErrors(['login' => '该账号已被禁用，请联系管理员。'])->onlyInput('login');
            }
            $request->session()->regenerate();
            return redirect()->intended('/workorders');
        }

        // 如果用户名登录失败，尝试使用邮箱登录
        $emailCredentials = [
            'email' => $credentials['login'],
            'password' => $credentials['password']
        ];

        if (Auth::attempt($emailCredentials)) {
            if (Auth::user()->status !== 'active') {
                Auth::logout();
                return back()->withErrors(['login' => '该账号已被禁用，请联系管理员。'])->onlyInput('login');
            }
            $request->session()->regenerate();
            return redirect()->intended('/workorders');
        }

        return back()->withErrors([
            'login' => '用户名或密码错误。',
        ])->onlyInput('login');
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request)
    {
        // 记录用户类型，登出后用于判断是否需要跳转 SSO 登出
        $user = Auth::guard('web')->user();
        $accountType = $user?->account_type;

        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        // SSO 用户登出后跳转到对应 IdP 的登出端点
        if ($accountType === 'cas' && SystemSetting::get('cas_enabled', false)) {
            $casBaseUrl = rtrim(SystemSetting::get('cas_base_url', ''), '/');
            if ($casBaseUrl) {
                return redirect("{$casBaseUrl}/logout?service=" . urlencode(route('login')));
            }
        }

        if ($accountType === 'oidc' && SystemSetting::get('oidc_enabled', false)) {
            $endSessionEndpoint = SystemSetting::get('oidc_end_session_endpoint', '');
            if ($endSessionEndpoint) {
                return redirect($endSessionEndpoint . '?post_logout_redirect_uri=' . urlencode(route('login')));
            }
        }

        // 使用相对URL，让浏览器自动处理协议
        return redirect('/workorders');
    }
}
