# 部署到其他服务器完整指南

## 🎯 概述

本指南详细说明如何将校园网工单系统部署到新的服务器环境中，包括环境准备、依赖安装、数据库配置和应用部署的完整流程。

## 📋 部署前准备

### 1. 系统要求

**最低配置**：
- CPU: 2核心
- 内存: 4GB RAM
- 存储: 20GB可用空间
- 网络: 稳定的互联网连接

**推荐配置**：
- CPU: 4核心或更多
- 内存: 8GB RAM或更多
- 存储: 50GB SSD
- 网络: 100Mbps带宽

**操作系统支持**：
- Ubuntu 20.04/22.04 LTS
- CentOS 7/8/9
- Debian 10/11
- Red Hat Enterprise Linux 8/9

### 2. 软件要求

**必需软件**：
- PHP >= 8.1
- MySQL >= 8.0 或 MariaDB >= 10.3
- Apache >= 2.4 或 Nginx >= 1.18
- Composer >= 2.0

**可选软件**：
- Node.js >= 16.0 (前端资源编译)
- Redis >= 6.0 (缓存和会话)
- Git >= 2.0 (版本控制)

## 🚀 快速部署流程

### 步骤1: 获取部署包

从源服务器下载最新的部署包：

```bash
# 方法1: 使用wget下载
wget https://your-source-server.com/packages/campus-workorder-system-v1.0.0_*.tar.gz

# 方法2: 使用scp复制
scp user@source-server:/var/www/workorder/packages/campus-workorder-system-v1.0.0_*.tar.gz ./

# 方法3: 使用rsync同步
rsync -avz user@source-server:/var/www/workorder/packages/campus-workorder-system-v1.0.0_*.tar.gz ./
```

### 步骤2: 解压部署包

```bash
# 解压到目标目录
tar -xzf campus-workorder-system-v1.0.0_*.tar.gz

# 进入项目目录
cd campus-workorder-system-v1.0.0_*/
```

### 步骤3: 环境检查和准备

```bash
# 检查系统依赖
./check_dependencies.sh

# 如果需要，运行环境准备脚本
sudo ./setup_server.sh
```

### 步骤4: 自动部署

```bash
# 运行自动部署脚本
./auto_deploy.sh -e production -v

# 或者使用简单部署脚本
./deploy.sh
```

## 📝 详细部署步骤

### 1. 环境准备

#### 1.1 安装PHP和扩展

**Ubuntu/Debian**:
```bash
sudo apt update
sudo apt install -y php8.1 php8.1-fpm php8.1-mysql php8.1-xml php8.1-mbstring \
    php8.1-curl php8.1-zip php8.1-gd php8.1-intl php8.1-bcmath php8.1-tokenizer \
    php8.1-ctype php8.1-json php8.1-fileinfo php8.1-pdo php8.1-pdo-mysql
```

**CentOS/RHEL**:
```bash
sudo dnf install -y php81 php81-php-fpm php81-php-mysqlnd php81-php-xml \
    php81-php-mbstring php81-php-curl php81-php-zip php81-php-gd php81-php-intl \
    php81-php-bcmath php81-php-tokenizer php81-php-ctype php81-php-json \
    php81-php-fileinfo php81-php-pdo php81-php-pdo-mysql
```

#### 1.2 安装数据库

**MySQL**:
```bash
# Ubuntu/Debian
sudo apt install -y mysql-server
sudo mysql_secure_installation

# CentOS/RHEL
sudo dnf install -y mysql-server
sudo systemctl enable --now mysqld
sudo mysql_secure_installation
```

**MariaDB**:
```bash
# Ubuntu/Debian
sudo apt install -y mariadb-server
sudo mysql_secure_installation

# CentOS/RHEL
sudo dnf install -y mariadb-server
sudo systemctl enable --now mariadb
sudo mysql_secure_installation
```

#### 1.3 安装Web服务器

**Apache**:
```bash
# Ubuntu/Debian
sudo apt install -y apache2 libapache2-mod-php8.1
sudo a2enmod rewrite
sudo systemctl enable --now apache2

# CentOS/RHEL
sudo dnf install -y httpd
sudo systemctl enable --now httpd
```

**Nginx**:
```bash
# Ubuntu/Debian
sudo apt install -y nginx php8.1-fpm
sudo systemctl enable --now nginx php8.1-fpm

# CentOS/RHEL
sudo dnf install -y nginx php81-php-fpm
sudo systemctl enable --now nginx php81-php-fpm
```

#### 1.4 安装Composer

```bash
# 下载并安装Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
sudo chmod +x /usr/local/bin/composer

# 验证安装
composer --version
```

### 2. 数据库配置

#### 2.1 创建数据库和用户

```sql
-- 登录MySQL
mysql -u root -p

-- 创建数据库
CREATE DATABASE workorder_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 创建用户并授权
CREATE USER 'workorder_user'@'localhost' IDENTIFIED BY 'your_strong_password';
GRANT ALL PRIVILEGES ON workorder_system.* TO 'workorder_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

#### 2.2 配置数据库连接

编辑 `.env` 文件：
```bash
cp .env.example .env
nano .env
```

修改数据库配置：
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=workorder_system
DB_USERNAME=workorder_user
DB_PASSWORD=your_strong_password
```

### 3. 应用部署

#### 3.1 安装PHP依赖

```bash
# 安装生产依赖
composer install --no-dev --optimize-autoloader

# 如果需要开发依赖
composer install
```

#### 3.2 安装前端依赖（可选）

```bash
# 如果存在package.json
npm install
npm run build
```

#### 3.3 配置应用

```bash
# 生成应用密钥
php artisan key:generate

# 创建符号链接
php artisan storage:link

# 设置缓存配置
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

#### 3.4 运行数据库迁移

```bash
# 运行迁移
php artisan migrate --force

# 导入种子数据
php artisan db:seed --force

# 或者一次性运行迁移和种子
php artisan migrate:fresh --seed --force
```

#### 3.5 设置文件权限

```bash
# 设置存储目录权限
chmod -R 755 storage/
chmod -R 755 bootstrap/cache/

# 设置所有者（根据Web服务器配置调整）
sudo chown -R www-data:www-data storage/
sudo chown -R www-data:www-data bootstrap/cache/

# 或者使用nginx用户
sudo chown -R nginx:nginx storage/
sudo chown -R nginx:nginx bootstrap/cache/
```

### 4. Web服务器配置

#### 4.1 Apache配置

创建虚拟主机配置文件 `/etc/apache2/sites-available/workorder.conf`：
```apache
<VirtualHost *:80>
    ServerName your-domain.com
    DocumentRoot /var/www/workorder/public
    
    <Directory /var/www/workorder/public>
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/workorder_error.log
    CustomLog ${APACHE_LOG_DIR}/workorder_access.log combined
</VirtualHost>
```

启用站点：
```bash
sudo a2ensite workorder.conf
sudo a2dissite 000-default.conf
sudo systemctl reload apache2
```

#### 4.2 Nginx配置

创建站点配置文件 `/etc/nginx/sites-available/workorder`：
```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /var/www/workorder/public;
    index index.php index.html index.htm;
    
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

启用站点：
```bash
sudo ln -s /etc/nginx/sites-available/workorder /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

### 5. SSL证书配置（推荐）

#### 5.1 使用Let's Encrypt

```bash
# 安装Certbot
sudo apt install -y certbot python3-certbot-apache  # Apache
# 或
sudo apt install -y certbot python3-certbot-nginx   # Nginx

# 获取SSL证书
sudo certbot --apache -d your-domain.com  # Apache
# 或
sudo certbot --nginx -d your-domain.com    # Nginx

# 设置自动续期
sudo crontab -e
# 添加以下行
0 12 * * * /usr/bin/certbot renew --quiet
```

#### 5.2 手动SSL配置

如果您有自己的SSL证书，请将证书文件放置在适当位置并更新Web服务器配置。

### 6. 防火墙配置

```bash
# Ubuntu/Debian (UFW)
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable

# CentOS/RHEL (firewalld)
sudo firewall-cmd --permanent --add-service=ssh
sudo firewall-cmd --permanent --add-service=http
sudo firewall-cmd --permanent --add-service=https
sudo firewall-cmd --reload
```

## 🔧 高级配置

### 1. 性能优化

#### 1.1 PHP配置优化

编辑 `php.ini` 文件：
```ini
# 内存限制
memory_limit = 256M

# 执行时间
max_execution_time = 300

# 上传限制
upload_max_filesize = 50M
post_max_size = 50M

# OPcache配置
opcache.enable=1
opcache.memory_consumption=128
opcache.max_accelerated_files=4000
opcache.revalidate_freq=60
```

#### 1.2 数据库优化

MySQL配置优化 `/etc/mysql/mysql.conf.d/mysqld.cnf`：
```ini
[mysqld]
# 内存设置
innodb_buffer_pool_size = 1G
innodb_log_file_size = 256M

# 连接设置
max_connections = 200
max_connect_errors = 1000

# 查询缓存
query_cache_type = 1
query_cache_size = 64M
```

#### 1.3 Web服务器优化

**Apache优化**：
```apache
# 启用压缩
LoadModule deflate_module modules/mod_deflate.so
<Location />
    SetOutputFilter DEFLATE
</Location>

# 启用缓存
LoadModule expires_module modules/mod_expires.so
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
    ExpiresByType image/png "access plus 1 month"
    ExpiresByType image/jpg "access plus 1 month"
    ExpiresByType image/jpeg "access plus 1 month"
</IfModule>
```

**Nginx优化**：
```nginx
# 启用压缩
gzip on;
gzip_vary on;
gzip_min_length 1024;
gzip_types text/plain text/css text/xml text/javascript application/javascript application/xml+rss application/json;

# 启用缓存
location ~* \.(css|js|png|jpg|jpeg|gif|ico|svg)$ {
    expires 1y;
    add_header Cache-Control "public, immutable";
}
```

### 2. 安全配置

#### 2.1 文件权限

```bash
# 设置严格的文件权限
find /var/www/workorder -type f -exec chmod 644 {} \;
find /var/www/workorder -type d -exec chmod 755 {} \;

# 设置敏感文件权限
chmod 600 .env
chmod 600 storage/oauth-*.key
```

#### 2.2 隐藏敏感信息

在 `.env` 文件中设置：
```env
APP_ENV=production
APP_DEBUG=false
```

#### 2.3 安全头配置

**Apache**：
```apache
<IfModule mod_headers.c>
    Header always set X-Content-Type-Options nosniff
    Header always set X-Frame-Options DENY
    Header always set X-XSS-Protection "1; mode=block"
    Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"
</IfModule>
```

**Nginx**：
```nginx
add_header X-Content-Type-Options nosniff;
add_header X-Frame-Options DENY;
add_header X-XSS-Protection "1; mode=block";
add_header Strict-Transport-Security "max-age=31536000; includeSubDomains";
```

### 3. 监控和日志

#### 3.1 应用监控

```bash
# 创建日志轮转配置
sudo nano /etc/logrotate.d/workorder
```

```
/var/www/workorder/storage/logs/*.log {
    daily
    missingok
    rotate 52
    compress
    delaycompress
    notifempty
    create 644 www-data www-data
    postrotate
        systemctl reload apache2
    endscript
}
```

#### 3.2 系统监控

安装监控工具：
```bash
# 安装htop
sudo apt install -y htop

# 安装iotop
sudo apt install -y iotop

# 安装netstat
sudo apt install -y net-tools
```

## 🚨 故障排除

### 常见问题及解决方案

#### 1. 500内部服务器错误

**可能原因**：
- 文件权限不正确
- .env文件配置错误
- PHP扩展缺失

**解决方案**：
```bash
# 检查文件权限
ls -la /var/www/workorder/
ls -la /var/www/workorder/storage/

# 检查.env文件
cat /var/www/workorder/.env

# 检查PHP错误日志
tail -f /var/log/php8.1-fpm.log
```

#### 2. 数据库连接失败

**可能原因**：
- 数据库服务未启动
- 数据库用户权限不足
- 防火墙阻止连接

**解决方案**：
```bash
# 检查数据库状态
sudo systemctl status mysql

# 测试数据库连接
mysql -u workorder_user -p workorder_system

# 检查防火墙
sudo ufw status
```

#### 3. 文件上传失败

**可能原因**：
- 上传目录权限不足
- PHP上传限制过小
- 磁盘空间不足

**解决方案**：
```bash
# 检查上传目录权限
ls -la /var/www/workorder/storage/app/public/

# 检查PHP上传配置
php -i | grep upload

# 检查磁盘空间
df -h
```

#### 4. 性能问题

**可能原因**：
- 数据库查询慢
- 内存不足
- 缓存未配置

**解决方案**：
```bash
# 检查系统资源
htop
free -h

# 检查数据库性能
mysql -e "SHOW PROCESSLIST;"

# 启用缓存
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## 📋 部署检查清单

### 部署前检查
- [ ] 服务器配置满足最低要求
- [ ] 所有必需软件已安装
- [ ] 数据库已创建并配置
- [ ] 防火墙规则已设置
- [ ] SSL证书已获取（如需要）

### 部署后检查
- [ ] 应用可以正常访问
- [ ] 数据库连接正常
- [ ] 文件上传功能正常
- [ ] 用户登录功能正常
- [ ] 邮件发送功能正常（如配置）
- [ ] 日志文件正常写入
- [ ] 定时任务正常运行
- [ ] 备份策略已实施

### 性能检查
- [ ] 页面加载时间合理
- [ ] 数据库查询性能良好
- [ ] 内存使用率正常
- [ ] 磁盘空间充足
- [ ] 网络带宽充足

## 📞 技术支持

如果在部署过程中遇到问题，请参考以下资源：

1. **项目文档**：
   - 部署维护指南：`DEPLOYMENT_MAINTENANCE_GUIDE.md`
   - 用户手册：`USER_MANUAL.md`
   - 开发者指南：`DEVELOPER_GUIDE.md`

2. **日志文件**：
   - 应用日志：`storage/logs/laravel.log`
   - Web服务器日志：`/var/log/apache2/` 或 `/var/log/nginx/`
   - PHP错误日志：`/var/log/php8.1-fpm.log`

3. **常用命令**：
   ```bash
   # 查看应用状态
   php artisan about
   
   # 检查路由
   php artisan route:list
   
   # 查看缓存状态
   php artisan cache:status
   
   # 运行测试
   php artisan test
   ```

## 🔄 更新和维护

### 系统更新

```bash
# 更新系统包
sudo apt update && sudo apt upgrade  # Ubuntu/Debian
sudo dnf update                      # CentOS/RHEL

# 更新Composer依赖
composer update

# 更新前端依赖
npm update
```

### 备份策略

```bash
# 创建备份脚本
cat > backup.sh << 'EOF'
#!/bin/bash
BACKUP_DIR="/var/backups/workorder"
DATE=$(date +%Y%m%d_%H%M%S)

# 创建备份目录
mkdir -p $BACKUP_DIR

# 备份数据库
mysqldump -u workorder_user -p workorder_system > $BACKUP_DIR/database_$DATE.sql

# 备份应用文件
tar -czf $BACKUP_DIR/application_$DATE.tar.gz /var/www/workorder

# 清理旧备份（保留30天）
find $BACKUP_DIR -name "*.sql" -mtime +30 -delete
find $BACKUP_DIR -name "*.tar.gz" -mtime +30 -delete
EOF

chmod +x backup.sh

# 设置定时备份
echo "0 2 * * * /path/to/backup.sh" | crontab -
```

---

**注意**：本指南涵盖了从基础到高级的完整部署流程。根据您的具体需求和环境，可能需要调整某些配置和步骤。