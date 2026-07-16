<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Illuminate\Http\Request;
use Closure;

class TrustProxies extends Middleware
{
    protected $proxies = '*';

    protected $headers =
        Request::HEADER_X_FORWARDED_FOR |
        Request::HEADER_X_FORWARDED_HOST |
        Request::HEADER_X_FORWARDED_PROTO |
        Request::HEADER_X_FORWARDED_PORT;

    public function handle(Request $request, Closure $next)
    {
        // 测试环境跳过代理信任，避免清空 HOST 导致 Symfony Request 报错
        if (app()->environment('testing')) {
            return $next($request);
        }

        return parent::handle($request, $next);
    }
}