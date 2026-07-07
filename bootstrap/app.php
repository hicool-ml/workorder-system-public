<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
        ]);
        
        // 全局注册TrustProxies中间件
        $middleware->append(\App\Http\Middleware\TrustProxies::class);
        
        // 完全移除ForceHttps中间件，因为使用Cloudflare隧道不需要本地HTTPS
        // Cloudflare隧道已经提供了加密：[用户浏览器] ←HTTPS→ [Cloudflare隧道] ←HTTP→ [内网服务器]
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
