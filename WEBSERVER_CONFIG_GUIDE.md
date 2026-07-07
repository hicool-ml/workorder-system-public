# 🔧 Web服务器配置指南

## ❌ 问题描述
访问 `http://117.176.215.210:14580/` 显示的是Apache2默认首页，而不是工单系统。

## 🎯 解决方案

### 方法1：配置Apache虚拟主机

#### 1.1 创建虚拟主机配置文件
```bash
# 创建配置文件
sudo nano /etc/apache2/sites-available/workorder.conf
```

#### 1.2 配置内容
```apache
<VirtualHost *:14580>
    ServerName 117.176.215.210
    DocumentRoot /home/waverjiang/campus-workorder-system-v1.0.0_20251121_183153/public
    
    <Directory /home/waverjiang/campus-workorder-system-v1.0.0_20251121_183153/public>
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/workorder_error.log
    CustomLog ${APACHE_LOG_DIR}/workorder_access.log combined
</VirtualHost>
```

#### 1.3 启用站点
```bash
# 启用新站点
sudo a2ensite workorder.conf

# 禁用默认站点
sudo a2dissite 000-default.conf

# 启用重写模块
sudo a2enmod rewrite

# 重新加载Apache配置
sudo systemctl reload apache2
```

### 方法2：修改Apache默认配置

#### 2.1 编辑默认配置
```bash
sudo nano /etc/apache2/sites-available/000-default.conf
```

#### 2.2 修改DocumentRoot
```apache
<VirtualHost *:14580>
    # 将原来的 DocumentRoot /var/www/html
    # 修改为：
    DocumentRoot /home/waverjiang/campus-workorder-system-v1.0.0_20251121_183153/public
    
    <Directory /home/waverjiang/campus-workorder-system-v1.0.0_20251121_183153/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

#### 2.3 重新加载配置
```bash
sudo systemctl reload apache2
```

### 方法3：配置Nginx（如果使用Nginx）

#### 3.1 创建站点配置
```bash
sudo nano /etc/nginx/sites-available/workorder
```

#### 3.2 配置内容
```nginx
server {
    listen 14580;
    server_name 117.176.215.210;
    root /home/waverjiang/campus-workorder-system-v1.0.0_20251121_183153/public;
    index index.php index.html;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
    
    location ~ /\.ht {
        deny all;
    }
    
    error_log /var/log/nginx/workorder_error.log;
    access_log /var/log/nginx/workorder_access.log;
}
```

#### 3.3 启用站点
```bash
# 创建符号链接
sudo ln -s /etc/nginx/sites-available/workorder /etc/nginx/sites-enabled/

# 删除默认配置
sudo rm /etc/nginx/sites-enabled/default

# 测试配置
sudo nginx -t

# 重新加载Nginx
sudo systemctl reload nginx
```

## 🔍 验证配置

### 1. 检查Apache配置
```bash
# 检查配置语法
sudo apache2ctl configtest

# 检查启用的站点
sudo a2query -s

# 检查监听端口
sudo netstat -tlnp | grep :14580
```

### 2. 检查文件权限
```bash
# 确保项目目录权限正确
ls -la /home/waverjiang/campus-workorder-system-v1.0.0_20251121_183153/public/

# 设置正确的所有者和权限
sudo chown -R www-data:www-data /home/waverjiang/campus-workorder-system-v1.0.0_20251121_183153
sudo chmod -R 755 /home/waverjiang/campus-workorder-system-v1.0.0_20251121_183153
```

### 3. 检查Laravel应用
```bash
# 进入项目目录
cd /home/waverjiang/campus-workorder-system-v1.0.0_20251121_183153

# 检查应用状态
php artisan about

# 检查路由
php artisan route:list

# 测试PHP文件
php -r "echo 'PHP works!';"
```

## 🚨 常见问题

### 1. 端口14580未监听
```bash
# 检查Apache端口配置
sudo nano /etc/apache2/ports.conf

# 确保包含：
Listen 14580
Listen 80

# 重启Apache
sudo systemctl restart apache2
```

### 2. 权限被拒绝
```bash
# 检查Apache用户
ps aux | grep apache2

# 设置正确的文件权限
sudo chown -R www-data:www-data /home/waverjiang/campus-workorder-system-v1.0.0_20251121_183153
sudo chmod -R 755 /home/waverjiang/campus-workorder-system-v1.0.0_20251121_183153
```

### 3. .htaccess不工作
```bash
# 确保AllowOverride设置为All
# 在Apache配置中：
<Directory /home/waverjiang/campus-workorder-system-v1.0.0_20251121_183153/public>
    AllowOverride All
    Require all granted
</Directory>

# 启用重写模块
sudo a2enmod rewrite
sudo systemctl reload apache2
```

### 4. PHP文件不执行
```bash
# 检查PHP-FPM状态
sudo systemctl status php8.1-fpm

# 启用PHP模块
sudo a2enmod php8.1
sudo systemctl reload apache2
```

## 📋 完整配置检查清单

### Apache配置
- [ ] 虚拟主机配置已创建
- [ ] DocumentRoot指向正确的public目录
- [ ] 端口14580已配置
- [ ] 重写模块已启用
- [ ] 默认站点已禁用
- [ ] 配置已重新加载

### 文件系统
- [ ] 项目目录存在
- [ ] public目录包含index.php
- [ ] 文件权限设置正确
- [ ] 所有者设置为www-data

### Laravel应用
- [ ] .env文件已配置
- [ ] 数据库迁移已完成
- [ ] 缓存已清除
- [ ] 存储链接已创建

## 🎯 快速修复命令

如果需要快速修复，可以运行以下命令：

```bash
# 1. 创建Apache配置
sudo bash -c 'cat > /etc/apache2/sites-available/workorder.conf << EOF
<VirtualHost *:14580>
    ServerName 117.176.215.210
    DocumentRoot /home/waverjiang/campus-workorder-system-v1.0.0_20251121_183153/public
    
    <Directory /home/waverjiang/campus-workorder-system-v1.0.0_20251121_183153/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
EOF'

# 2. 启用站点和模块
sudo a2ensite workorder.conf
sudo a2dissite 000-default.conf
sudo a2enmod rewrite

# 3. 设置权限
sudo chown -R www-data:www-data /home/waverjiang/campus-workorder-system-v1.0.0_20251121_183153
sudo chmod -R 755 /home/waverjiang/campus-workorder-system-v1.0.0_20251121_183153

# 4. 重新加载Apache
sudo systemctl reload apache2

# 5. 验证配置
sudo apache2ctl configtest
```

## 📞 获取帮助

如果配置后仍然有问题，请检查：

1. **Apache错误日志**：`sudo tail -f /var/log/apache2/error.log`
2. **访问日志**：`sudo tail -f /var/log/apache2/access.log`
3. **Laravel日志**：`tail -f /home/waverjiang/campus-workorder-system-v1.0.0_20251121_183153/storage/logs/laravel.log`

---

**💡 提示**：配置完成后，访问 `http://117.176.215.210:14580/` 应该显示工单系统的登录页面，而不是Apache默认页面。