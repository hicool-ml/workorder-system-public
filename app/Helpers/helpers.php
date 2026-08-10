<?php

if (!function_exists('pg_bin_path')) {
    /**
     * 定位 PostgreSQL 命令行工具（pg_dump / psql 等）的完整路径。
     *
     * 优先查系统 PATH，找不到时再探测常见安装目录（Windows / macOS / Linux），
     * 避免因 PostgreSQL 未加入 PATH 导致备份退化为不含表结构的纯 PHP 导出。
     *
     * @param string $binary 工具名，如 pg_dump / psql
     * @return string 完整路径；未找到返回空字符串
     */
    function pg_bin_path(string $binary): string
    {
        $name = DIRECTORY_SEPARATOR === '\\' ? $binary . '.exe' : $binary;

        // 1) 系统 PATH
        $found = trim((string) (DIRECTORY_SEPARATOR === '\\'
            ? shell_exec('where ' . escapeshellarg($binary) . ' 2>NUL') ?? ''
            : shell_exec('command -v ' . escapeshellarg($binary) . ' 2>/dev/null') ?? ''));
        if ($found !== '' && stripos($found, 'INFO') === false && stripos($found, 'could not find') === false) {
            $first = explode("\n", $found)[0];
            if (is_file($first)) {
                return $first;
            }
        }

        // 2) 常见安装目录
        $candidates = [
            // Windows
            'C:\\Program Files\\PostgreSQL\\*\\bin\\' . $name,
            'C:\\Program Files (x86)\\PostgreSQL\\*\\bin\\' . $name,
            // macOS
            '/Applications/Postgres.app/Contents/Versions/*/bin/' . $name,
            '/opt/homebrew/opt/postgresql*/bin/' . $name,
            '/usr/local/opt/postgresql*/bin/' . $name,
            // Linux
            '/usr/lib/postgresql/*/bin/' . $name,
            '/usr/pgsql-*/bin/' . $name,
            '/usr/bin/' . $name,
        ];

        $found = [];
        foreach ($candidates as $pattern) {
            foreach (glob($pattern) ?: [] as $path) {
                $found[$path] = pg_bin_version($path);
            }
        }
        if (!$found) {
            return '';
        }

        // 取版本号最高者：pg_dump 向后兼容，新版本可导出旧版本服务器；
        // 老版本 pg_dump 无法连接更高版本的服务器，会直接失败。
        uasort($found, function ($a, $b) {
            if ($a === $b) {
                return 0;
            }
            if ($a === null) {
                return 1;
            }
            if ($b === null) {
                return -1;
            }
            return version_compare($b, $a);
        });

        return (string) array_key_first($found);
    }
}

if (!function_exists('pg_bin_version')) {
    /**
     * 从 pg_dump / psql 可执行文件路径中推断 PostgreSQL 版本号。
     *
     * 例如 C:\Program Files\PostgreSQL\18\bin\pg_dump.exe -> 18；
     * 无法推断时返回 null（作为最低优先级处理）。
     */
    function pg_bin_version(string $path): ?string
    {
        if (preg_match('~[\\\\/]PostgreSQL[\\\\/](\d+)~i', $path, $m)) {
            return $m[1];
        }
        if (preg_match('~postgres(?:ql)?@?/?(\d+)~i', $path, $m)) {
            return $m[1];
        }
        if (preg_match('~postgresql[\\\\/](\d+)~i', $path, $m)) {
            return $m[1];
        }
        if (preg_match('~pgsql-(\d+)~i', $path, $m)) {
            return $m[1];
        }
        return null;
    }
}

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