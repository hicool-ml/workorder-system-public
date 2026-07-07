<?php

if (!function_exists('relative_route')) {
    /**
     * 生成相对路径的路由URL
     * 
     * @param string $name 路由名称
     * @param array $parameters 路由参数
     * @return string 相对路径URL
     */
    function relative_route($name, $parameters = []) {
        $url = app('url')->route($name, $parameters, false);
        
        // 确保返回的是相对路径
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

if (!function_exists('safe_route')) {
    /**
     * 安全的路由生成函数，根据上下文决定使用相对路径还是绝对路径
     * 对于表单action和链接，使用相对路径
     * 
     * @param string $name 路由名称
     * @param array $parameters 路由参数
     * @param bool $absolute 是否生成绝对路径
     * @return string URL
     */
    function safe_route($name, $parameters = [], $absolute = false) {
        // 默认使用相对路径，除非明确要求绝对路径
        if (!$absolute) {
            return relative_route($name, $parameters);
        }
        
        return route($name, $parameters, $absolute);
    }
}