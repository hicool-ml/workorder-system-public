<?php

namespace App\Helpers;

use App\Models\SystemSetting;

class SystemHelper
{
    /**
     * 获取系统名称
     */
    public static function getSystemName(): string
    {
        // 优先从系统设置中获取
        $systemName = SystemSetting::get('system_name');
        if ($systemName) {
            return $systemName;
        }
        
        // 如果没有设置，使用配置文件中的值
        return config('app.name', '校园网工单系统');
    }
    
    /**
     * 获取系统名称（用于显示）
     */
    public static function getSystemNameDisplay(): string
    {
        return self::getSystemName();
    }
    
    /**
     * 获取系统名称（用于页面标题）
     */
    public static function getSystemNameTitle(string $pageTitle = ''): string
    {
        $systemName = self::getSystemName();
        if (empty($pageTitle)) {
            return $systemName;
        }
        
        return $pageTitle . ' - ' . $systemName;
    }
    
    /**
     * 获取系统版本
     */
    public static function getSystemVersion(): string
    {
        // 优先从系统设置中获取
        $version = SystemSetting::get('system_version');
        if ($version) {
            return 'v' . $version;
        }

        // 默认版本号
        return 'v2.0.0';
    }

    /**
     * 获取系统发布日期
     */
    public static function getSystemReleaseDate(): string
    {
        // 优先从系统设置中获取
        $releaseDate = SystemSetting::get('system_release_date');
        if ($releaseDate) {
            return $releaseDate;
        }

        // 默认发布日期
        return '2025-12-16';
    }
    
    /**
     * 获取系统版权信息
     */
    public static function getSystemCopyright(): string
    {
        $year = date('Y');
        $systemName = self::getSystemName();
        return "© {$year} {$systemName} - " . self::getSystemVersion();
    }
}