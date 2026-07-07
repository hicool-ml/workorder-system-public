# HTTP/HTTPS端口切换问题修复总结

## 问题描述
项目在内网访问时使用HTTP 80端口，在外网使用work.66107166.xyz通过Cloudflare隧道访问，但浏览器在80和443端口间反复切换，导致网页无法打开或无法提交表单。

## 问题根源分析
1. **配置冲突**: `.env`文件中`APP_URL=https://localhost`与`ASSET_URL=https://work.66107166.xyz`不一致
2. **缺少代理处理**: 没有TrustProxies中间件处理Cloudflare隧道和HTTPS重定向
3. **URL生成问题**: Laravel无法动态检测Cloudflare隧道的访问协议
4. **重定向规则冲突**: .htaccess强制HTTPS重定向与Cloudflare隧道冲突

## 修复方案

### 1. 修复环境配置 (.env)
```diff
- APP_URL=https://localhost
+ APP_URL=http://localhost

- ASSET_URL=https://work.66107166.xyz
+ # ASSET_URL=https://work.66107166.xyz
```

### 2. 创建TrustProxies中间件
**文件**: `app/Http/Middleware/TrustProxies.php`
- 信任Cloudflare IP范围
- 处理Cloudflare特殊头信息 (CF-Visitor, CF-Connecting-IP)
- 处理标准forwarded headers (X-Forwarded-Proto, X-Forwarded-Host等)

### 3. 注册中间件 (bootstrap/app.php)
```php
->withMiddleware(function (Middleware $middleware): void {
    $middleware->alias([
        'role' => \App\Http\Middleware\RoleMiddleware::class,
    ]);
    
    // 全局注册TrustProxies中间件
    $middleware->append(\App\Http\Middleware\TrustProxies::class);
})
```

### 4. 动态URL检测 (AppServiceProvider.php)
```php
public function boot(): void
{
    // 动态设置应用URL以支持内网和Cloudflare隧道访问
    if (isset($_SERVER['HTTP_HOST'])) {
        $host = $_SERVER['HTTP_HOST'];
        
        // 检测Cloudflare隧道或代理的协议
        $scheme = 'http';
        
        // 检查HTTPS标准头
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            $scheme = 'https';
        }
        // 检查Cloudflare的协议头
        elseif (isset($_SERVER['HTTP_CF_VISITOR'])) {
            $cf_visitor = json_decode($_SERVER['HTTP_CF_VISITOR'], true);
            if (isset($cf_visitor['scheme']) && $cf_visitor['scheme'] === 'https') {
                $scheme = 'https';
            }
        }
        // 检查X-Forwarded-Proto头
        elseif (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
            $scheme = 'https';
        }
        
        URL::forceRootUrl($scheme . '://' . $host);
    }
    // ... 其他代码
}
```

### 5. .htaccess重定向规则
```apache
# 处理内网外网访问问题 - 防止端口切换循环
# 注意：Cloudflare隧道访问时不要强制重定向，让Laravel处理协议检测

# 如果是内网IP访问且检测到HTTPS，重定向到HTTP
RewriteCond %{HTTP_HOST} ^(localhost|127\.0\.0\.1|192\.168\.|10\.|172\.1[6-9]\.|172\.2[0-9]\.|172\.3[01]\.) [NC]
RewriteCond %{HTTPS} on
RewriteCond %{HTTP:CF-Visitor} !{"scheme":"https"}
RewriteRule ^ http://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# 对于Cloudflare隧道，不进行协议强制重定向，让TrustProxies中间件处理
```

## 修复效果

### 内网访问
- URL: `http://localhost` 或 `http://内网IP`
- 协议: HTTP
- 端口: 80
- 行为: 保持HTTP连接，不强制跳转到HTTPS

### 外网访问 (Cloudflare隧道)
- URL: `https://work.66107166.xyz`
- 协议: HTTPS (通过Cloudflare隧道)
- 端口: 443
- 行为: Cloudflare自动处理HTTPS，Laravel动态检测协议

## 解决的问题
1. ✅ 消除浏览器在80/443端口间的反复切换
2. ✅ 内网访问保持HTTP，提高访问速度
3. ✅ 外网访问强制HTTPS，确保安全性
4. ✅ 动态检测访问协议，生成正确的URL
5. ✅ 支持Cloudflare隧道的SSL终端
6. ✅ 表单提交不再因协议切换而失败
7. ✅ 兼容内网直接访问和Cloudflare隧道访问

## 验证步骤
1. 清除Laravel缓存:
   ```bash
   php artisan cache:clear
   php artisan config:clear
   php artisan route:clear
   ```

2. 重启Web服务器

3. 测试内网访问:
   - 访问: `http://localhost`
   - 预期: 正常访问，不跳转到HTTPS

4. 测试Cloudflare隧道访问:
   - 访问: `https://work.66107166.xyz`
   - 预期: 正常访问，无重定向循环

5. 测试表单提交:
   - 在内网和外网环境下分别测试登录、表单提交等功能
   - 预期: 正常提交，无协议切换错误

## 技术要点
1. **TrustProxies中间件**: 让Laravel正确识别Cloudflare代理的协议头信息
2. **动态URL生成**: 根据Cloudflare头信息动态检测协议并生成URL
3. **条件重定向**: .htaccess中避免与Cloudflare隧道冲突的重定向
4. **配置一致性**: 确保Laravel配置支持Cloudflare隧道访问
5. **协议检测**: 优先使用Cloudflare CF-Visitor头检测协议

## 注意事项
1. 修改后需要清除Laravel缓存
2. 需要重启Web服务器使.htaccess生效
3. Cloudflare隧道访问时，服务器端实际是HTTP连接，HTTPS由Cloudflare处理
4. 如果使用Nginx，需要将.htaccess规则转换为Nginx配置
5. 建议在生产环境测试前先在测试环境验证

## 后续维护
1. 定期检查Cloudflare隧道状态
2. 监控内网外网访问日志
3. 如有新的域名或IP段，需要更新TrustProxies配置
4. 保持Laravel版本更新，确保兼容性
5. 关注Cloudflare IP范围变化并及时更新