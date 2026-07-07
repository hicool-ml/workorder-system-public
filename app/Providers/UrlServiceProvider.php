<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

class UrlServiceProvider extends ServiceProvider
{
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
        // 强制所有URL生成都使用相对路径
        URL::forceRootUrl(null);
        URL::forceScheme(null);
        
        // 重写URL生成器的to方法
        app('url')->macro('to', function($path, $extra = [], $secure = null) {
            // 直接返回相对路径
            return '/' . ltrim($path, '/');
        });
        
        // 重写URL生成器的route方法
        app('url')->macro('route', function($name, $parameters = [], $absolute = false) {
            $url = app('url')->toRoute($name, $parameters, $absolute);
            
            // 强制转换为相对路径
            if (preg_match('/^https?:\/\/[^\/]+(.+)$/', $url, $matches)) {
                return '/' . ltrim($matches[1], '/');
            }
            
            // 如果已经是相对路径，直接返回
            if (strpos($url, '/') === 0) {
                return $url;
            }
            
            // 否则强制转换为相对路径
            return '/' . ltrim($url, '/');
        });
    }
}