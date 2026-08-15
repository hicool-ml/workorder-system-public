<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'password.changed' => \App\Http\Middleware\ForcePasswordChange::class,
        ]);

        // 短信上行回复回调由服务商发起，无法携带 CSRF token，需排除校验
        $middleware->validateCsrfTokens(except: [
            'sms/reply',
        ]);

        // 全局注册TrustProxies中间件
        $middleware->append(\App\Http\Middleware\TrustProxies::class);

        // 动态会话有效期：必须在 StartSession 之前执行，否则本次请求的会话已按
        // config('session.lifetime') 创建，新的 session_lifetime 只能下次请求生效
        $middleware->web(prepend: [
            \App\Http\Middleware\ApplySessionLifetime::class,
        ]);

        // 强制修改默认密码：所有 web 请求都会经过，仅对已登录且未修改密码的用户生效
        $middleware->web(append: [
            \App\Http\Middleware\ForcePasswordChange::class,
        ]);

        // 防止浏览器强缓存 HTML：保证前端总是引用最新 hash 的 CSS/JS
        // 避免"旧 HTML 引用已删除的旧 CSS 导致图标巨大"的问题
        $middleware->web(append: [
            \App\Http\Middleware\PreventHtmlCache::class,
        ]);

        // 完全移除ForceHttps中间件，因为使用Cloudflare隧道不需要本地HTTPS
        // Cloudflare隧道已经提供了加密：[用户浏览器] <-HTTPS-> [Cloudflare隧道] <-HTTP-> [内网服务器>
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->respond(function ($response, $exception, $request) {
            if ($response->getStatusCode() === 419) {
                return redirect()->route('login')->with('message', '会话已过期，请重新登录');
            }
            return $response;
        });
    })->create();
