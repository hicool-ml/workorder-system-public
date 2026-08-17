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
        return config('app.name', '工单管理系统');
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
     * 获取项目名称（固定，用于版权信息；与用户可改的系统名称解耦）
     */
    public const PROJECT_NAME = '通用工单系统';

    /**
     * 获取系统版权信息：© 年份 系统名称
     * 显示用户可配置的系统名称（system_name），不再显示版本号。
     */
    public static function getSystemCopyright(): string
    {
        $year = date('Y');
        return '© ' . $year . ' ' . self::getSystemName();
    }

    /**
     * 获取项目版本号
     * 优先读版本管理页写入的 DB 值；DB 缺失时回退到 VERSION 文件。
     */
    public static function getProjectVersion(): string
    {
        $version = SystemSetting::get('system_version');
        if ($version) {
            return 'v' . $version;
        }

        $versionFile = base_path('VERSION');
        if (file_exists($versionFile)) {
            $v = trim((string) file_get_contents($versionFile));
            if ($v !== '') {
                return 'v' . $v;
            }
        }
        return 'v1.0.0';
    }

    /**
     * 获取系统对外访问的基础地址（不含末尾斜杠）。
     *
     * 统一口径：通知链接、CAS/OIDC/微信回调地址等所有「对外可见 URL」
     * 都以同一个「系统访问地址」（system_settings.system_url）为准，
     * 未配置时回退到 .env 的 APP_URL。
     */
    public static function getSystemBaseUrl(): string
    {
        return rtrim((string) SystemSetting::get('system_url', config('app.url', '')), '/');
    }

    /**
     * 基于系统访问地址拼接一个绝对 URL。
     * 例：absoluteUrl('/cas/callback') => http://域名/cas/callback
     */
    public static function absoluteUrl(string $path): string
    {
        return self::getSystemBaseUrl() . '/' . ltrim($path, '/');
    }
}