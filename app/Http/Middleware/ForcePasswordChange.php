<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * 强制修改默认密码
 *
 * 首次登录或管理员重置密码后，password_changed_at 为 null，
 * 用户必须先修改密码才能使用系统。
 */
class ForcePasswordChange
{
    /** 白名单路由：允许在未修改密码时访问 */
    private array $whitelist = [
        'password.change',
        'password.update',
        'logout',
        'logout.get',
        'cas.logout',
        'profile.password',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (!$user) {
            return $next($request);
        }

        if ($user->password_changed_at === null) {
            $routeName = $request->route()?->getName();

            if ($routeName && in_array($routeName, $this->whitelist)) {
                return $next($request);
            }

            if ($request->expectsJson()) {
                return response()->json(['message' => '首次登录请先修改密码'], 403);
            }

            return redirect()->route('password.change')
                ->with('warning', '检测到您使用的是默认密码，请先修改密码后再使用系统');
        }

        return $next($request);
    }
}