<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Illuminate\Http\Request;
use Closure;

class TrustProxies extends Middleware
{
    /**
     * 仅信任显式配置的代理网段（TRUSTED_PROXIES，逗号分隔 CIDR/IP）。
     *
     * 安全背景：此前为 '*'（信任所有代理），$request->ip() 完全由攻击者
     * 通过 X-Forwarded-For 头控制——可伪造短信回调 IP 白名单、伪造审计日志。
     * 部署在 Cloudflare 隧道/内网反代后，应把真实代理网段写入 TRUSTED_PROXIES。
     */
    protected $proxies;

    protected $headers =
        Request::HEADER_X_FORWARDED_FOR |
        Request::HEADER_X_FORWARDED_HOST |
        Request::HEADER_X_FORWARDED_PROTO |
        Request::HEADER_X_FORWARDED_PORT;

    public function handle(Request $request, Closure $next)
    {
        // 从环境变量读取可信代理网段；未配置则不信任任何代理（直连地址判定）
        $this->proxies = (string) env('TRUSTED_PROXIES', '');

        // 测试环境跳过代理信任，避免清空 HOST 导致 Symfony Request 报错
        if (app()->environment('testing')) {
            return $next($request);
        }

        return parent::handle($request, $next);
    }
}