# 🔧 Apache最终修复方案

## ❌ 日志分析

从Apache错误日志可以看到：
```
AH00558: apache2: Could not reliably determine the server's fully qualified domain name, using 127.0.1.1. Set the 'ServerName' directive globally to suppress this message
[php:error] [client 182.151.223.17:48794] script '/var/www/html/login.php' not found or unable to stat
```

**问题分析**：
1. Apache仍在使用默认配置，访问 `/var/www/html/` 而不是项目目录
2. ServerName配置不正确
3. 虚拟主机配置没有生效

## 🎯 最终解决方案

### 方案1：完全重置Apache配置（推荐）

```bash
#!/bin/bash
echo "=== Apache最终修复脚本 ==="

# 1. 停止Apache服务
echo "停止Apache服务..."
sudo systemctl stop apache2

# 2. 删除所有现有配置
echo "清理现有配置..."
sudo rm -f /etc/apache2/sites-enabled/*
sudo rm -f /etc/apache2/sites-available/000-default.conf

# 3. 创建新的主配置
echo "创建新的Apache配置..."
sudo bash -c 'cat > /etc/apache2/sites-available/000-default.conf << EOF
<VirtualHost *:80>
    ServerName 117.176.215.210
    DocumentRoot /home/waverjiang/campus-workorder-system-v1.0.0_20251121_191018/public
    
    <Directory /home/waverjiang/campus-workorder-system-v1.0.0_20251121_191018/public>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog \${APACHE_LOG_DIR}/workorder_error.log
    CustomLog \${APACHE_LOG_DIR}/workorder_access.log combined
</VirtualHost>

<VirtualHost *:14580>
    ServerName 117.176.215.210
    DocumentRoot /home/waverjiang/campus-workorder-system-v1.0.0_20251121_191018/public
    
    <Directory /home/waverjiang/campus-workorder-system-v1.0.0_20251121_191018/public>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog \${APACHE_LOG_DIR}/workorder_14580_error.log
    CustomLog \${APACHE_LOG_DIR}/workorder_14580_access.log combined
</VirtualHost>
EOF'

# 4. 启用配置
echo "启用新配置..."
sudo a2ensite 000-default.conf
sudo a2enmod rewrite
sudo a2enmod dir

# 5. 更新主配置文件
echo "更新主配置文件..."
sudo bash -c 'cat > /etc/apache2/apache2.conf << EOF
Mutex file:\${APACHE_LOCK_DIR} default
PidFile \${APACHE_PID_FILE}
Timeout 300
KeepAlive On
MaxKeepAliveRequests 100
KeepAliveTimeout 5
User \${APACHE_RUN_USER}
Group \${APACHE_RUN_GROUP}
HostnameLookups Off
ErrorLog \${APACHE_LOG_DIR}/error.log
LogLevel warn
IncludeOptional mods-enabled/*.load
IncludeOptional mods-enabled/*.conf
Include ports.conf
IncludeOptional conf-enabled/*.conf
IncludeOptional sites-enabled/*.conf
ServerName 117.176.215.210
EOF'

# 6. 更新端口配置
echo "更新端口配置..."
sudo bash -c 'cat > /etc/apache2/ports.conf << EOF
Listen 80
Listen 14580
<IfModule ssl_module>
        Listen 443
</IfModule>
<IfModule mod_gnutls.c>
        Listen 443
</IfModule>
EOF'

# 7. 设置文件权限
echo "设置文件权限..."
sudo chown -R www-data:www-data /home/waverjiang/campus-workorder-system-v1.0.0_20251121_191018
sudo chmod -R 755 /home/waverjiang/campus-workorder-system-v1.0.0_20251121_191018

# 8. 清除Apache缓存
echo "清除Apache缓存..."
sudo rm -rf /var/lib/apache2/fastcgi_cache/*
sudo rm -rf /var/cache/apache2/mod_cache_disk/*

# 9. 测试配置
echo "测试Apache配置..."
sudo apache2ctl configtest

if [ $? -eq 0 ]; then
    echo "✅ Apache配置测试通过"
else
    echo "❌ Apache配置测试失败"
    exit 1
fi

# 10. 启动Apache
echo "启动Apache服务..."
sudo systemctl start apache2

# 11. 检查状态
echo "检查Apache状态..."
sudo systemctl status apache2 --no-pager -l

# 12. 验证配置
echo "验证虚拟主机配置..."
sudo apache2ctl -S | grep -E "(14580|workorder)"

echo "=== Apache修复完成 ==="
echo "请访问：http://117.176.215.210:14580"
```

### 方案2：直接修改默认配置（简单）

```bash
# 1. 直接编辑默认配置文件
sudo nano /etc/apache2/sites-available/000-default.conf

# 2. 将内容完全替换为：
<VirtualHost *:80>
    ServerName 117.176.215.210
    DocumentRoot /home/waverjiang/campus-workorder-system-v1.0.0_20251121_191018/public
    
    <Directory /home/waverjiang/campus-workorder-system-v1.0.0_20251121_191018/public>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>

<VirtualHost *:14580>
    ServerName 117.176.215.210
    DocumentRoot /home/waverjiang/campus-workorder-system-v1.0.0_20251121_191018/public
    
    <Directory /home/waverjiang/campus-workorder-system-v1.0.0_20251121_191018/public>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>

# 3. 重启Apache
sudo systemctl restart apache2
```

### 方案3：使用IP地址配置

```bash
# 创建基于IP的虚拟主机配置
sudo bash -c 'cat > /etc/apache2/sites-available/workorder.conf << EOF
<VirtualHost 117.176.215.210:80>
    ServerName 117.176.215.210
    DocumentRoot /home/waverjiang/campus-workorder-system-v1.0.0_20251121_191018/public
    
    <Directory /home/waverjiang/campus-workorder-system-v1.0.0_20251121_191018/public>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>

<VirtualHost 117.176.215.210:14580>
    ServerName 117.176.215.210
    DocumentRoot /home/waverjiang/campus-workorder-system-v1.0.0_20251121_191018/public
    
    <Directory /home/waverjiang/campus-workorder-system-v1.0.0_20251121_191018/public>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
EOF'

sudo a2ensite workorder.conf
sudo a2dissite 000-default.conf
sudo systemctl restart apache2
```

## 🔍 验证修复

### 检查Apache配置
```bash
# 1. 检查启用的站点
sudo a2query -s

# 2. 检查虚拟主机配置
sudo apache2ctl -S

# 3. 检查端口监听
sudo netstat -tlnp | grep -E ":(80|14580)"

# 4. 检查Apache状态
sudo systemctl status apache2
```

### 测试网站访问
```bash
# 1. 本地测试
curl -I http://127.0.0.1:14580/
curl -I http://127.0.0.1:80/

# 2. 远程测试
curl -I http://117.176.215.210:14580/

# 3. 检查日志
sudo tail -f /var/log/apache2/access.log
sudo tail -f /var/log/apache2/error.log
```

## 🆘 常见问题解决

### 1. "script not found" 错误
```bash
# 检查DocumentRoot是否正确
sudo grep -r "DocumentRoot" /etc/apache2/sites-enabled/

# 检查项目目录是否存在
ls -la /home/waverjiang/campus-workorder-system-v1.0.0_20251121_191018/public/
```

### 2. "Permission denied" 错误
```bash
# 设置正确的文件权限
sudo chown -R www-data:www-data /home/waverjiang/campus-workorder-system-v1.0.0_20251121_191018
sudo chmod -R 755 /home/waverjiang/campus-workorder-system-v1.0.0_20251121_191018
```

### 3. Apache无法启动
```bash
# 检查配置语法
sudo apache2ctl configtest

# 检查错误日志
sudo journalctl -u apache2 -f
```

## 📋 完整验证清单

- [ ] Apache配置语法正确：`sudo apache2ctl configtest`
- [ ] 虚拟主机配置生效：`sudo a2query -s` 显示正确配置
- [ ] 端口14580正在监听：`sudo netstat -tlnp | grep :14580`
- [ ] 文件权限正确：`ls -la /home/waverjiang/campus-workorder-system-v1.0.0_20251121_191018/public/`
- [ ] 可以访问网站：`curl -I http://117.176.215.210:14580/`
- [ ] Apache日志无错误：`sudo tail -20 /var/log/apache2/error.log`

## 🎯 预期结果

修复完成后：
- ✅ 访问 `http://117.176.215.210:14580/` 应该显示工单系统
- ✅ Apache错误日志应该不再显示 "script not found" 错误
- ✅ 虚拟主机配置应该指向正确的项目目录

---

**💡 提示**：如果问题仍然存在，请检查是否有其他Web服务器（如Nginx）同时运行，可能需要停止或重新配置。