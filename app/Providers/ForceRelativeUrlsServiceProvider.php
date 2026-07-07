<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

class ForceRelativeUrlsServiceProvider extends ServiceProvider
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
        // 在最早的阶段强制所有URL生成都使用相对路径
        URL::forceRootUrl(null);
        URL::forceScheme(null);
        
        // 强制清除所有可能影响URL生成的服务器变量
        $_SERVER['HTTPS'] = 'off';
        $_SERVER['REQUEST_SCHEME'] = 'http';
        $_SERVER['HTTP_X_FORWARDED_PROTO'] = null;
        $_SERVER['HTTP_X_FORWARDED_HOST'] = null;
        $_SERVER['HTTP_X_FORWARDED_SSL'] = null;
        $_SERVER['SERVER_PORT'] = '80';
        
        // 重写URL生成器的核心方法
        $this->overrideUrlGenerator();
    }
    
    /**
     * 重写URL生成器的核心方法
     */
    private function overrideUrlGenerator(): void
    {
        $urlGenerator = app('url');
        
        // 重写to方法
        app('url')->macro('to', function($path, $extra = [], $secure = null) {
            // 直接返回相对路径，忽略所有其他参数
            return '/' . ltrim($path, '/');
        });
        
        // 重写route方法
        app('url')->macro('route', function($name, $parameters = [], $absolute = false) {
            $url = $urlGenerator->toRoute($name, $parameters, $absolute);
            
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
        
        // 重写asset方法
        app('url')->macro('asset', function($path, $secure = null) {
            // 直接返回相对路径
            return '/' . ltrim($path, '/');
        });
    }
}