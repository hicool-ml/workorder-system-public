<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * 让 HTML 响应不被浏览器强缓存。
 *
 * 背景：Vite 产物文件名带 hash（如 app-AbCd1234.css），只要 HTML 指向最新的 hash，
 * 浏览器就会自动拉取新版本的 CSS/JS。但如果浏览器强缓存了旧 HTML，它还会继续引用
 * 旧的 hash 文件名——一旦旧文件在部署时被清理掉，旧 HTML 加载旧 CSS 失败，
 * Tailwind 的尺寸类（w-4 h-4 等）不生效，SVG 图标就会显示成原始巨大尺寸。
 *
 * 本中间件只给 HTML 响应加协商缓存头（no-cache, must-revalidate）：
 *   - 浏览器每次请求 HTML 都会向服务器确认是否变化（轻量 304，不重传整个页面）
 *   - 带 hash 的静态资源仍可被浏览器长期缓存
 */
class PreventHtmlCache
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $contentType = $response->headers->get('Content-Type', '');
        if (str_contains($contentType, 'text/html')) {
            $response->headers->set('Cache-Control', 'no-cache, must-revalidate');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', '0');
        }

        return $response;
    }
}
