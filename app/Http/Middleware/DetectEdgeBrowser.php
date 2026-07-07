<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class DetectEdgeBrowser
{
    public function handle(Request $request, Closure $next)
    {
        $userAgent = $request->header('User-Agent', '');
        
        // 检测Edge浏览器
        if (strpos($userAgent, 'Edge/') !== false || strpos($userAgent, 'Edg/') !== false) {
            // 为Edge浏览器使用兼容性布局
            view()->share('useEdgeLayout', true);
        } else {
            view()->share('useEdgeLayout', false);
        }
        
        return $next($request);
    }
}
