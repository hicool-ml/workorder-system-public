<?php

namespace App\Helpers;

class UrlHelper
{
    /**
     * 生成相对路径的路由URL
     *
     * @param string $name 路由名称
     * @param array $parameters 路由参数
     * @return string 相对路径URL
     */
    public static function relative_route(string $name, array $parameters = []): string
    {
        return route($name, $parameters, false);
    }

    /**
     * 生成相对路径的URL
     *
     * @param string $path 路径
     * @return string 相对路径URL
     */
    public static function relative_url(string $path): string
    {
        // 去掉前后空格和重复斜杠
        $path = '/' . ltrim($path, '/');
        return $path;
    }

    /**
     * 生成相对路径的资源URL
     *
     * @param string $path 路径
     * @return string 相对路径URL
     */
    public static function relative_asset(string $path): string
    {
        // 直接返回相对路径，不依赖Laravel的asset函数
        return '/' . ltrim($path, '/');
    }

    /**
     * 校验登录后跳转目标是否为本站相对路径（防开放重定向钓鱼）。
     *
     * 仅接受以单个 "/" 开头、且不以 "//" 或 "/\" 开头的路径；
     * 任何绝对 URL（http://、https://、协议相对 //evil.com）一律拒绝。
     */
    public static function safeRedirectTarget(?string $url, string $fallback = '/workorders'): string
    {
        if ($url === null || $url === '') {
            return $fallback;
        }

        $url = trim($url);

        // 必须以单个 / 开头；排除 // 与 /\（协议相对/反斜杠绕过）
        if (!str_starts_with($url, '/') || str_starts_with($url, '//') || str_starts_with($url, '/\\')) {
            return $fallback;
        }

        // 解析出的 host 非空说明携带了 scheme 或协议相对形态
        $parts = parse_url($url);
        if (isset($parts['scheme']) || isset($parts['host'])) {
            return $fallback;
        }

        return $url;
    }
}