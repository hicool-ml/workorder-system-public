<?php

namespace App\Http\Middleware;

use App\Models\SystemSetting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * 动态应用登录会话有效期（分钟）
 *
 * 默认会话有效期来自 config('session.lifetime')（即 .env 的 SESSION_LIFETIME）。
 * 本中间件改为优先读取 system_settings 表中的 session_lifetime（系统设置页可配置），
 * 用于解决用户在微信内置浏览器等环境下频繁掉线、需反复登录的问题。
 *
 * 必须在 StartSession 中间件之前执行，这样本请求创建的会话处理器
 * （SessionManager 懒加载）才会使用新的 lifetime。
 */
class ApplySessionLifetime
{
    public function handle(Request $request, Closure $next): Response
    {
        $minutes = (int) SystemSetting::get('session_lifetime', 120);

        // 下限保护：至少 5 分钟，避免误配为 0/负数导致每次请求都掉线
        if ($minutes < 5) {
            $minutes = 5;
        }

        config(['session.lifetime' => $minutes]);

        return $next($request);
    }
}
