# HTTPS配置指南

本指南详细说明如何配置校园网工单系统的HTTPS设置，以支持本地开发和Cloudflare生产环境。

## 概述

系统现在支持智能的HTTPS协议检测和重定向，可以处理以下场景：
- 本地开发环境（localhost）使用HTTP协议
- 生产环境通过域名访问时强制使用HTTPS
- Cloudflare代理环境的协议检测
- 混合环境下的协议自动切换

## 配置文件

### 1. 环境变量配置 (.env)

```env
# HTTPS配置
FORCE_HTTPS=false          # 是否强制HTTPS（本地环境设为false，生产环境设为true）
DETECT_CLOUDFLARE=true     # 是否检测Cloudflare代理
FORCE_HTTPS_ON_API=true    # 是否在API请求中也强制HTTPS
```

### 2. HTTPS配置文件 (config/https.php)

```php
return [
    // 是否强制HTTPS（生产环境默认启用）
    'force' => env('FORCE_HTTPS', !app()->environment('local')),

    // 需要强制HTTPS的主机列表
    'hosts' => [
        // 可以在这里添加特定的域名
        // 'example.com',
        // 'www.example.com',
    ],

    // 是否检测Cloudflare代理
    'detect_cloudflare' => env('DETECT_CLOUDFLARE', true),

    // Cloudflare相关的HTTP头
    'cloudflare_headers' => [
        'HTTP_CF_VISITOR',
        'HTTP_CF_RAY',
        'HTTP_CF_CONNECTING_IP',
    ],

    // 本地开发环境的主机模式
    'local_hosts' => [
        'localhost',
        '127.0.0.1',
        '0.0.0.0',
    ],

    // 是否在API请求中也强制HTTPS
    'force_on_api' => env('FORCE_HTTPS_ON_API', true),

    // HTTPS重定向状态码
    'redirect_status' => 301,
];
```

## 部署配置

### 本地开发环境

1. 确保`.env`文件中的配置：
```env
APP_ENV=local
FORCE_HTTPS=false
```

2. 本地访问使用HTTP：
```bash
php artisan serve
# 访问 http://localhost:8000
```

### 生产环境部署

#### 方法1：使用脚本自动配置

```bash
# 给脚本执行权限
chmod +x scripts/enable_https.sh

# 运行配置脚本
./scripts/enable_https.sh
```

#### 方法2：手动配置

1. 更新`.env`文件：
```env
APP_ENV=production
FORCE_HTTPS=true
```

2. 编辑`config/https.php`，添加您的域名：
```php
'hosts' => [
    'yourdomain.com',
    'www.yourdomain.com',
],
```

3. 清除缓存：
```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

4. 重启Web服务器：
```bash
# Apache
sudo systemctl restart apache2

# Nginx
sudo systemctl restart nginx

# PHP-FPM
sudo systemctl restart php8.2-fpm
```

## Cloudflare配置

### 1. SSL/TLS设置

在Cloudflare控制台中：
1. 进入SSL/TLS → Overview
2. 选择"Full (strict)"模式
3. 确保Always Use HTTPS已启用

### 2. Page Rules设置

可以创建页面规则强制HTTPS：
1. 进入Page Rules
2. 创建规则：`http://*yourdomain.com/*`
3. 设置：Always Use HTTPS

## 中间件说明

### ForceHttps中间件

这个中间件负责处理HTTPS重定向逻辑：
- 检测当前请求协议
- 根据环境配置决定是否强制HTTPS
- 处理Cloudflare代理的特殊情况
- 支持AJAX请求的JSON响应

### AppServiceProvider中的协议检测

在`AppServiceProvider.php`中，系统会自动检测请求协议：
- 检查HTTPS标准头
- 检查Cloudflare的协议头
- 检查X-Forwarded-Proto头
- 在生产环境中对域名访问默认使用HTTPS

## 常见问题

### 1. 本地环境仍然重定向到HTTPS

检查以下几点：
- 确保`APP_ENV=local`
- 确保`FORCE_HTTPS=false`
- 检查是否在`config/https.php`的`hosts`列表中添加了本地域名

### 2. 生产环境HTTP访问不重定向

检查以下几点：
- 确保`APP_ENV=production`
- 确保`FORCE_HTTPS=true`
- 检查域名是否在`config/https.php`的`hosts`列表中
- 确保中间件已正确注册

### 3. Cloudflare环境下混合内容警告

确保：
- 所有资源URL使用相对路径或协议相对URL
- 检查CSS和JavaScript文件中的URL
- 使用`asset()`辅助函数生成资源URL

## 测试方法

### 本地环境测试

```bash
# 启动服务器
php artisan serve

# 测试HTTP访问
curl -I http://localhost:8000

# 应该返回200 OK，不重定向
```

### 生产环境测试

```bash
# 测试HTTP重定向
curl -I http://yourdomain.com

# 应该返回301重定向到HTTPS
```

## 安全建议

1. 始终在生产环境中使用HTTPS
2. 定期更新SSL证书
3. 使用HSTS头部增强安全性
4. 确保所有敏感数据传输都通过HTTPS

## 故障排除

如果遇到HTTPS相关问题，可以：

1. 检查Laravel日志：`storage/logs/laravel.log`
2. 启用调试模式：`APP_DEBUG=true`
3. 检查Web服务器错误日志
4. 使用浏览器开发者工具查看网络请求

## 联系支持

如果遇到配置问题，请：
1. 检查本指南的常见问题部分
2. 查看项目文档
3. 提交Issue到项目仓库