<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Vite;
use Carbon\Carbon;

class AppServiceProvider extends ServiceProvider
{
    public const HOME = '/workorders';

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // 凭据类接口限流：登录/SSO 回调/微信绑定按 IP 限 10 次/分钟，
        // 注册按 IP 限 5 次/分钟（防在线爆破与批量注册）
        // 注意：必须定义在 ServiceProvider 中——bootstrap/app.php 的 withMiddleware
        // 闭包执行时 Facade root 尚未绑定，在那里调用会抛 "A facade root has not been set"
        RateLimiter::for('auth', function ($request) {
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(10)->by($request->ip());
        });
        RateLimiter::for('register', function ($request) {
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(5)->by($request->ip());
        });

        // Vite 资源强制使用相对路径，确保 IP/域名/HTTP/HTTPS 访问都能正确加载
        Vite::createAssetPathsUsing(fn ($path) => '/'.ltrim($path, '/'));

        // 设置Carbon中文本地化
        Carbon::setLocale('zh');

        // 历史说明：此处曾用 $_SERVER 强改 + url 宏全局强制相对 URL。
        // 经核验为死代码（Request 在 provider boot 前已捕获；UrlGenerator 的
        // to()/route() 是真实方法不触发宏），且若被"修活"会破坏 SSO 绝对回调地址
        // 与 HTTPS 反代协议识别，已移除。协议/主机识别统一交给 TRUSTED_PROXIES。

        // 自定义时间差格式化函数
        if (!function_exists('App\Providers\diffForHumansCN')) {
            function diffForHumansCN($datetime) {
                $now = now();
                $diff = $now->diffInMinutes($datetime);
                
                if ($diff < 1) {
                    return '刚刚';
                } elseif ($diff < 60) {
                    return $diff . '分钟前';
                } elseif ($diff < 1440) { // 24小时
                    $hours = floor($diff / 60);
                    return $hours . '小时前';
                } elseif ($diff < 10080) { // 7天
                    $days = floor($diff / 1440);
                    return $days . '天前';
                } else {
                    return $datetime->format('m-d H:i');
                }
            }
        }
    }
}
