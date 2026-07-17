<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
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
        // Vite 资源强制使用相对路径，确保 IP/域名/HTTP/HTTPS 访问都能正确加载
        Vite::createAssetPathsUsing(fn ($path) => '/'.ltrim($path, '/'));

        // 设置Carbon中文本地化
        Carbon::setLocale('zh');
        
        // 强制清除所有可能的HTTPS设置，确保不会生成https://192.168.1.19的URL
        $_SERVER['HTTPS'] = 'off';
        $_SERVER['REQUEST_SCHEME'] = 'http';
        $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'http';
        $_SERVER['HTTP_X_FORWARDED_HOST'] = null;
        $_SERVER['HTTP_X_FORWARDED_SSL'] = null;
        $_SERVER['SERVER_PORT'] = '80';
        
        // 强制生成相对路径，避免地址重复
        URL::forceRootUrl(null);
        URL::forceScheme(null);
        
        // 确保所有URL生成都使用相对路径，让Cloudflare自动处理协议转换
        
        // 重写URL生成器，确保所有URL都是相对路径
        app('url')->macro('to', function($path, $extra = [], $secure = null) {
            // 确保返回相对路径，避免地址重复
            return '/' . ltrim($path, '/');
        });
        
        // 重写URL生成器的route方法，确保始终返回相对路径
        app('url')->macro('route', function($name, $parameters = [], $absolute = false) {
            $originalUrl = app('url')->toRoute($name, $parameters, $absolute);
            
            // 强制转换为相对路径
            if (preg_match('/^https?:\/\/[^\/]+(.+)$/', $originalUrl, $matches)) {
                return '/' . ltrim($matches[1], '/');
            }
            
            // 如果已经是相对路径，直接返回
            if (strpos($originalUrl, '/') === 0) {
                return $originalUrl;
            }
            
            // 否则强制转换为相对路径
            return '/' . ltrim($originalUrl, '/');
        });
        
        // 全局覆盖route函数，确保所有route调用都返回相对路径
        if (!function_exists('route')) {
            function route($name, $parameters = [], $absolute = false) {
                $url = app('url')->route($name, $parameters, false);
                // 强制返回相对路径，以/开头
                if (preg_match('/^https?:\/\/[^\/]+(.+)$/', $url, $matches)) {
                    return '/' . ltrim($matches[1], '/');
                }
                // 如果已经是相对路径，直接返回
                if (strpos($url, '/') === 0) {
                    return $url;
                }
                // 否则强制转换为相对路径
                return '/' . ltrim($url, '/');
            }
        }
        
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
