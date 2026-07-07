# Apache Forbidden 错误修复指南

## 🚨 问题诊断

**错误信息**：`Forbidden You don't have permission to access this resource. Apache/2.4.58 (Ubuntu) Server at 117.176.215.210 Port 14580`

**问题分析**：
1. Apache 服务器正在运行（端口14580可访问）
2. 但返回 403 Forbidden 错误
3. 通常是文件权限或目录权限问题
4. 可能是 Apache 配置问题

## 🔧 快速修复方案

### 方案1：修复文件权限（推荐）

```bash
# 1. 检查当前文件权限
ls -la /home/waverjiang/campus-workorder-system-v1.0.0_20251121_191018/

# 2. 修复文件所有者
sudo chown -R www-data:www-data /home/waverjiang/campus-workorder-system-v1.0.0_20251121_191018/

# 3. 修复目录权限
sudo find /home/waverjiang/campus-workorder-system-v1.0.0_20251121_191018/ -type d -exec chmod 755 {} \;

# 4. 修复文件权限
sudo find /home/waverjiang/campus-workorder-system-v1.0.0_20251121_191018/ -type f -exec chmod 644 {} \;

# 5. 特殊权限修复
sudo chmod -R 775 /home/waverjiang/campus-workorder-system-v1.0.0_20251121_191018/storage
sudo chmod -R 775 /home/waverjiang/campus-workorder-system-v1.0.0_20251121_191018/bootstrap/cache
```

### 方案2：修复Apache配置

```bash
# 1. 检查Apache配置文件
sudo nano /etc/apache2/sites-available/workorder.conf

# 2. 确保配置正确
<VirtualHost *:14580>
    ServerName 117.176.215.210
    DocumentRoot "/home/waverjiang/campus-workorder-system-v1.0.0_20251121_191018/public"
    
    <Directory "/home/waverjiang/campus-workorder-system-v1.0.0_20251121_191018/public">
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/workorder_error.log
    CustomLog ${APACHE_LOG_DIR}/workorder_access.log combined
</VirtualHost>

# 3. 重新加载Apache配置
sudo systemctl reload apache2

# 4. 重启Apache服务
sudo systemctl restart apache2
```

### 方案3：检查Apache模块

```bash
# 1. 启用必要的Apache模块
sudo a2enmod rewrite
sudo a2enmod headers
sudo a2enmod expires

# 2. 重启Apache
sudo systemctl restart apache2

# 3. 检查模块状态
sudo apache2ctl -M
```

### 方案4：检查SELinux（如果启用）

```bash
# 1. 检查SELinux状态
sestatus

# 2. 如果SELinux启用，设置正确的上下文
sudo setsebool -P httpd_can_network_connect 1
sudo setsebool -P httpd_can_network_relay 1
sudo chcon -R -t httpd_sys_rw_content_t /home/waverjiang/campus-workorder-system-v1.0.0_20251121_191018/storage
sudo chcon -R -t httpd_sys_rw_content_t /home/waverjiang/campus-workorder-system-v1.0.0_20251121_191018/bootstrap/cache
```

## 🔍 诊断步骤

### 1. 检查Apache错误日志

```bash
# 查看最新的错误日志
sudo tail -20 /var/log/apache2/error.log

# 查看工单站点专用日志
sudo tail -20 /var/log/apache2/workorder_error.log
```

### 2. 检查Apache状态

```bash
# 检查Apache服务状态
sudo systemctl status apache2

# 检查端口监听
sudo ss -tlnp | grep :14580

# 检查Apache配置语法
sudo apache2ctl configtest
```

### 3. 测试文件访问

```bash
# 创建测试文件
echo "<?php phpinfo(); ?>" > /home/waverjiang/campus-workorder-system-v1.0.0_20251121_191018/public/test.php

# 测试访问
curl http://127.0.0.1:14580/test.php

# 测试Laravel路由
curl http://127.0.0.1:14580/
```

## 🚀 完整修复脚本

```bash
#!/bin/bash

# Apache Forbidden 错误修复脚本
echo "🔧 开始修复Apache Forbidden错误..."

# 设置变量
PROJECT_PATH="/home/waverjiang/campus-workorder-system-v1.0.0_20251121_191018"
APACHE_CONFIG="/etc/apache2/sites-available/workorder.conf"

# 1. 修复文件权限
echo "📁 修复文件权限..."
sudo chown -R www-data:www-data "$PROJECT_PATH"
sudo find "$PROJECT_PATH" -type d -exec chmod 755 {} \;
sudo find "$PROJECT_PATH" -type f -exec chmod 644 {} \;
sudo chmod -R 775 "$PROJECT_PATH/storage"
sudo chmod -R 775 "$PROJECT_PATH/bootstrap/cache"

# 2. 创建Apache配置
echo "⚙️ 配置Apache虚拟主机..."
sudo tee "$APACHE_CONFIG" > /dev/null <<EOF
<VirtualHost *:14580>
    ServerName 117.176.215.210
    DocumentRoot "$PROJECT_PATH/public"
    
    <Directory "$PROJECT_PATH/public">
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog \${APACHE_LOG_DIR}/workorder_error.log
    CustomLog \${APACHE_LOG_DIR}/workorder_access.log combined
</VirtualHost>
EOF

# 3. 启用站点和模块
echo "🔧 启用Apache站点和模块..."
sudo a2ensite workorder.conf
sudo a2dissite 000-default.conf
sudo a2enmod rewrite
sudo a2enmod headers
sudo a2enmod expires

# 4. 配置端口监听
echo "🌐 配置端口监听..."
if ! grep -q "Listen 14580" /etc/apache2/ports.conf; then
    echo "Listen 14580" | sudo tee -a /etc/apache2/ports.conf
fi

# 5. 测试配置
echo "🧪 测试Apache配置..."
if sudo apache2ctl configtest; then
    echo "✅ Apache配置正确"
    sudo systemctl restart apache2
else
    echo "❌ Apache配置错误"
    exit 1
fi

# 6. 验证服务状态
echo "🔍 验证服务状态..."
sudo systemctl status apache2 --no-pager
sudo ss -tlnp | grep :14580

# 7. 测试访问
echo "🌐 测试网站访问..."
sleep 3
if curl -s -o /dev/null -w "%{http_code}" http://127.0.0.1:14580/ | grep -q "200\|302"; then
    echo "✅ 网站访问正常"
else
    echo "❌ 网站访问异常"
    echo "📋 查看错误日志："
    sudo tail -10 /var/log/apache2/workorder_error.log
fi

echo "🎉 修复完成！"
```

## 📋 检查清单

修复完成后，请验证以下项目：

- [ ] Apache服务正在运行
- [ ] 端口14580正在监听
- [ ] 文件权限正确设置
- [ ] Apache配置语法正确
- [ ] 网站可以正常访问
- [ ] Laravel路由正常工作
- [ ] 没有错误日志

## 🆘 如果问题仍然存在

1. **检查防火墙**：
   ```bash
   sudo ufw status
   sudo ufw allow 14580/tcp
   ```

2. **检查目录索引**：
   ```bash
   # 确保public目录有index.php
   ls -la /home/waverjiang/campus-workorder-system-v1.0.0_20251121_191018/public/
   ```

3. **检查PHP错误**：
   ```bash
   # 启用PHP错误显示
   sudo nano /etc/php/8.1/apache2/php.ini
   # 设置 display_errors = On
   ```

4. **重新部署Laravel**：
   ```bash
   cd /home/waverjiang/campus-workorder-system-v1.0.0_20251121_191018
   php artisan cache:clear
   php artisan config:clear
   php artisan route:clear
   php artisan view:clear
   ```

## 🎯 最终验证

```bash
# 完整验证脚本
curl -I http://127.0.0.1:14580/
curl -I http://127.0.0.1:14580/login
sudo tail -5 /var/log/apache2/workorder_error.log
sudo tail -5 /var/log/apache2/workorder_access.log
```

这个修复指南应该能解决Apache Forbidden错误。如果问题仍然存在，请提供错误日志的详细内容以便进一步诊断。