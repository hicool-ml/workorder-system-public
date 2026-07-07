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
     * @param string $path 资源路径
     * @return string 相对路径URL
     */
    public static function relative_asset(string $path): string
    {
        // 直接返回相对路径，不依赖Laravel的asset函数
        return '/' . ltrim($path, '/');
    }
}