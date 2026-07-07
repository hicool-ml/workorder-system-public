# Cloudflare隧道配置指南

## 架构概述

本系统使用Cloudflare隧道提供安全的HTTPS访问，内部服务器保持HTTP协议：

```
[用户浏览器] ←HTTPS→ [Cloudflare隧道] ←HTTP→ [内网服务器]
```

这种架构的优势：
- 无需在内网服务器配置SSL证书
- Cloudflare提供端到端加密
- 简化服务器配置和维护
- 自动处理HTTPS证书续期

## 系统配置

### 1. Laravel应用配置

应用已配置为始终使用HTTP协议，因为Cloudflare隧道已经提供了加密。

#### 关键配置文件修改：

**`.env` 文件：**
```env
# HTTPS配置 - 禁用HTTPS，因为使用Cloudflare隧道
FORCE_HTTPS=false
DETECT_CLOUDFLARE=false
FORCE_HTTPS_ON_API=false
```

**`bootstrap/app.php` 文件：**
- 已注释掉ForceHttps中间件注册
- 移除了对web路由组的HTTPS强制重定向

**`app/Providers/AppServiceProvider.php` 文件：**
- 简化了URL生成逻辑，始终使用HTTP协议
- 移除了复杂的协议检测逻辑

**`config/https.php` 文件：**
- 默认禁用所有HTTPS相关功能
- 禁用Cloudflare检测（因为不需要HTTPS重定向）

### 2. 服务器配置

#### Web服务器配置

确保Web服务器（Apache/Nginx）监听HTTP端口（通常是80或8000）：

**Apache示例配置：**
```apache
<VirtualHost *:8000>
    ServerName localhost
    DocumentRoot /var/www/workorder/public
    
    <Directory /var/www/workorder/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

**Nginx示例配置：**
```nginx
server {
    listen 8000;
    server_name localhost;
    root /var/www/workorder/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### 3. Cloudflare隧道配置

#### 安装Cloudflare隧道客户端

```bash
# Ubuntu/Debian
wget -q https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-linux-amd64.deb
sudo dpkg -i cloudflared-linux-amd64.deb

# 或者使用包管理器
sudo apt update
sudo apt install cloudflared
```

#### 创建隧道

```bash
# 登录Cloudflare
cloudflared tunnel login

# 创建隧道
cloudflared tunnel create workorder-tunnel

# 创建配置文件
sudo mkdir -p /etc/cloudflared
sudo nano /etc/cloudflared/config.yml
```

#### 隧道配置文件示例

`/etc/cloudflared/config.yml`:
```yaml
tunnel: workorder-tunnel
credentials-file: /etc/cloudflared/.workorder-tunnel.json

ingress:
  # 将您的域名映射到本地HTTP服务
  - hostname: workorder.yourdomain.com
    service: http://localhost:8000
  
  # 或者使用子路径
  - hostname: yourdomain.com
    path: /workorder/*
    service: http://localhost:8000
  
  # 默认规则（必须放在最后）
  - service: http_status:404
```

#### 设置DNS记录

```bash
# 将域名指向隧道
cloudflared tunnel route dns workorder-tunnel workorder.yourdomain.com
```

#### 启动隧道服务

```bash
# 创建systemd服务
sudo cloudflared service install

# 启动服务
sudo systemctl start cloudflared
sudo systemctl enable cloudflared

# 检查状态
sudo systemctl status cloudflared
```

## 安全考虑

### 1. 网络安全

- 确保内网服务器只监听本地地址（127.0.0.1或localhost）
- 不要将HTTP服务直接暴露到公网
- 使用防火墙限制对服务器的访问

### 2. Cloudflare安全设置

在Cloudflare仪表板中配置：

1. **SSL/TLS设置**
   - 设置为"Full (strict)"模式
   - 启用HSTS
   - 配置最小TLS版本

2. **防火墙规则**
   - 配置WAF规则
   - 设置访问规则（如限制特定国家/地区）
   - 启用Bot Fight Mode

3. **DDoS保护**
   - 确保DDoS保护已启用
   - 配置速率限制

### 3. 应用安全

- 定期更新Laravel框架和依赖包
- 启用Laravel的安全特性（CSRF保护、XSS防护等）
- 使用强密码和双因素认证

## 故障排除

### 1. 常见问题

**问题：访问网站时显示502错误**
- 检查本地HTTP服务是否正常运行
- 确认cloudflared服务状态
- 检查隧道配置文件语法

**问题：静态资源加载失败**
- 确认APP_URL配置正确
- 检查资源链接是否使用相对路径
- 验证web服务器配置

**问题：重定向循环**
- 确认已禁用所有HTTPS强制重定向
- 检查Laravel的URL生成配置
- 验证Cloudflare的SSL设置

### 2. 调试命令

```bash
# 检查隧道状态
cloudflared tunnel list

# 测试隧道连接
cloudflared tunnel run workorder-tunnel --url http://localhost:8000

# 查看日志
sudo journalctl -u cloudflared -f

# 检查本地服务
curl -I http://localhost:8000
```

### 3. 性能优化

1. **启用缓存**
   - 配置Cloudflare缓存规则
   - 启用Laravel的页面缓存

2. **优化资源加载**
   - 使用Cloudflare的CDN功能
   - 压缩静态资源
   - 启用Brotli压缩

3. **数据库优化**
   - 配置查询缓存
   - 优化数据库连接池

## 维护和监控

### 1. 监控设置

- 配置Cloudflare Analytics
- 设置服务器监控（如Prometheus + Grafana）
- 配置告警通知

### 2. 备份策略

- 定期备份数据库
- 备份应用配置文件
- 备份Cloudflare配置

### 3. 更新维护

- 定期更新cloudflared
- 更新Laravel和依赖包
- 更新服务器系统

## 总结

通过Cloudflare隧道，我们实现了：

1. **简化的架构**：内网服务器只需运行HTTP服务
2. **增强的安全性**：Cloudflare提供端到端加密和DDoS保护
3. **易于维护**：无需管理SSL证书
4. **高可用性**：Cloudflare的全球网络提供可靠的服务

这种架构特别适合内网应用需要通过互联网安全访问的场景。