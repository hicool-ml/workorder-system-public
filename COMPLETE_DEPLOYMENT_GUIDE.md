# Laravel工单系统完整部署指南

## 目录

1. [系统概述](#系统概述)
2. [系统要求](#系统要求)
3. [快速部署](#快速部署)
4. [详细部署步骤](#详细部署步骤)
5. [环境配置](#环境配置)
6. [数据库配置](#数据库配置)
7. [Web服务器配置](#web服务器配置)
8. [SSL证书配置](#ssl证书配置)
9. [性能优化](#性能优化)
10. [监控和日志](#监控和日志)
11. [备份策略](#备份策略)
12. [故障排除](#故障排除)
13. [升级指南](#升级指南)
14. [安全建议](#安全建议)

## 系统概述

Laravel工单系统是一个基于Laravel 12框架开发的企业级工单管理系统，包含以下核心功能：

- **工单管理**: 工单创建、分配、处理、关闭
- **部门管理**: 多级部门结构和权限管理
- **用户管理**: 用户角色和权限控制
- **通知系统**: 实时通知和消息推送
- **文件管理**: 工单附件和文档管理
- **报表统计**: 工单数据分析和报表

## 系统要求

### 最低要求

- **操作系统**: Linux (Ubuntu 20.04+, CentOS 8+, Debian 11+) 或 Windows Server 2019+
- **PHP**: 8.2 或更高版本
- **数据库**: MySQL 5.7+ / MariaDB 10.3+ / PostgreSQL 9.6+ / SQLite 3.8+
- **Web服务器**: Nginx 1.18+ / Apache 2.4+ / Caddy 2.0+
- **内存**: 最低 2GB RAM
- **存储**: 最低 10GB 可用空间
- **网络**: 稳定的互联网连接

### 推荐配置

- **操作系统**: Ubuntu 22.04 LTS
- **PHP**: 8.3
- **数据库**: MySQL 8.0 / MariaDB 10.11
- **Web服务器**: Nginx 1.24
- **内存**: 4GB+ RAM
- **存储**: 50GB+ SSD
- **缓存**: Redis 6.0+

### PHP扩展要求

```bash
php-mbstring
php-pdo_mysql
php-tokenizer
php-xml
php-ctype
php-fileinfo
php-json
php-bcmath
php-openssl
php-gd
php-curl
php-zip
```

## 快速部署

### 使用自动化脚本（推荐）

1. **下载项目包**
   ```bash
   wget https://your-domain.com/workorder-system_latest.tar.gz
   tar -xzf workorder-system_latest.tar.gz
   cd workorder-system_v*
   ```

2. **运行自动部署脚本**
   ```bash
   chmod +x auto_deploy.sh
   ./auto_deploy.sh -e production -v
   ```

3. **配置数据库连接**
   ```bash
   nano .env
   ```

4. **导入数据库**
   ```bash
   mysql -u username -p database_name < database.sql
   ```

5. **启动服务**
   ```bash
   php artisan serve --host=0.0.0.0 --port=8000
   ```

### 手动快速部署

```bash
# 1. 安装依赖
composer install --no-dev --optimize-autoloader
npm install --production && npm run build

# 2. 配置环境
cp .env.example .env
php artisan key:generate

# 3. 配置数据库
# 编辑 .env 文件

# 4. 数据库迁移
php artisan migrate --force
php artisan db:seed --force

# 5. 设置权限
chmod -R 775 storage bootstrap/cache

# 6. 优化应用
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan storage:link
```

## 详细部署步骤

### 步骤1: 环境准备

#### Ubuntu/Debian

```bash
# 更新系统
sudo apt update && sudo apt upgrade -y

# 安装PHP和扩展
sudo apt install -y php8.3 php8.3-fpm php8.3-mysql php8.3-pgsql \
    php8.3-mbstring php8.3-tokenizer php8.3-xml php8.3-ctype \
    php8.3-fileinfo php8.3-json php8.3-bcmath php8.3-openssl \
    php8.3-gd php8.3-curl php8.3-zip

# 安装Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# 安装Node.js
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs

# 安装Nginx
sudo apt install -y nginx

# 安装MySQL
sudo apt install -y mysql-server

# 安装Redis（可选）
sudo apt install -y redis-server
```

#### CentOS/RHEL

```bash
# 安装EPEL仓库
sudo yum install -y epel-release

# 安装PHP和扩展
sudo yum install -y php83 php83-php-fpm php83-php-mysqlnd php83-php-pgsql \
    php83-php-mbstring php83-php-tokenizer php83-php-xml php83-php-ctype \
    php83-php-fileinfo php83-php-json php83-php-bcmath php83-php-openssl \
    php83-php-gd php83-php-curl php83-php-zip

# 安装Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# 安装Node.js
curl -fsSL https://rpm.nodesource.com/setup_20.x | sudo bash -
sudo yum install -y nodejs

# 安装Nginx
sudo yum install -y nginx

# 安装MySQL
sudo yum install -y mysql-server

# 安装Redis
sudo yum install -y redis
```

### 步骤2: 数据库设置

#### MySQL/MariaDB

```bash
# 安全安装
sudo mysql_secure_installation

# 创建数据库和用户
sudo mysql -u root -p
```

```sql
CREATE DATABASE workorder CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'workorder_user'@'localhost' IDENTIFIED BY 'strong_password';
GRANT ALL PRIVILEGES ON workorder.* TO 'workorder_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

#### PostgreSQL

```bash
# 切换到postgres用户
sudo -u postgres psql
```

```sql
CREATE DATABASE workorder;
CREATE USER workorder_user WITH PASSWORD 'strong_password';
GRANT ALL PRIVILEGES ON DATABASE workorder TO workorder_user;
\q
```

### 步骤3: 项目部署

```bash
# 创建项目目录
sudo mkdir -p /var/www/workorder
sudo chown $USER:$USER /var/www/workorder
cd /var/www/workorder

# 下载项目
wget https://your-domain.com/workorder-system_latest.tar.gz
tar -xzf workorder-system_latest.tar.gz
cd workorder-system_v*

# 安装依赖
composer install --no-dev --optimize-autoloader --no-interaction
npm install --production
npm run build

# 配置环境
cp .env.example .env
php artisan key:generate

# 设置权限
sudo chown -R www-data:www-data /var/www/workorder
chmod -R 775 storage bootstrap/cache
```

### 步骤4: 环境配置

编辑 `.env` 文件：

```env
APP_NAME="工单管理系统"
APP_ENV=production
APP_KEY=base64:your_generated_key_here
APP_DEBUG=false
APP_URL=https://your-domain.com

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=workorder
DB_USERNAME=workorder_user
DB_PASSWORD=strong_password

BROADCAST_DRIVER=log
CACHE_DRIVER=redis
FILESYSTEM_DISK=local
QUEUE_CONNECTION=redis
SESSION_DRIVER=database
SESSION_LIFETIME=120

MEMCACHED_HOST=127.0.0.1

REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@workorder.com"
MAIL_FROM_NAME="${APP_NAME}"
```

### 步骤5: 数据库初始化

```bash
# 导入数据库（如果有备份文件）
mysql -u workorder_user -p workorder < database.sql

# 运行迁移
php artisan migrate --force

# 导入种子数据
php artisan db:seed --force

# 创建管理员用户
php artisan tinker
```

```php
User::create([
    'name' => '系统管理员',
    'email' => 'admin@your-domain.com',
    'password' => Hash::make('admin_password'),
    'role' => 'admin',
    'status' => 'active',
]);
```

## 环境配置

### 生产环境配置

```env
APP_ENV=production
APP_DEBUG=false
LOG_LEVEL=error
CACHE_DRIVER=redis
SESSION_DRIVER=database
QUEUE_CONNECTION=redis
```

### 测试环境配置

```env
APP_ENV=staging
APP_DEBUG=true
LOG_LEVEL=warning
CACHE_DRIVER=redis
SESSION_DRIVER=database
QUEUE_CONNECTION=database
```

### 开发环境配置

```env
APP_ENV=local
APP_DEBUG=true
LOG_LEVEL=debug
CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync
```

## 数据库配置

### MySQL配置优化

编辑 `/etc/mysql/mysql.conf.d/mysqld.cnf`：

```ini
[mysqld]
# 基本设置
datadir = /var/lib/mysql
tmpdir = /tmp
socket = /var/run/mysqld/mysqld.sock
pid-file = /var/run/mysqld/mysqld.pid
user = mysql
bind-address = 127.0.0.1

# 性能优化
innodb_buffer_pool_size = 2G
innodb_log_file_size = 256M
innodb_flush_log_at_trx_commit = 2
innodb_flush_method = O_DIRECT

# 连接设置
max_connections = 200
max_connect_errors = 1000

# 字符集
character-set-server = utf8mb4
collation-server = utf8mb4_unicode_ci
```

### PostgreSQL配置优化

编辑 `/etc/postgresql/15/main/postgresql.conf`：

```ini
# 连接设置
listen_addresses = 'localhost'
port = 5432
max_connections = 200

# 内存设置
shared_buffers = 256MB
effective_cache_size = 1GB
work_mem = 4MB
maintenance_work_mem = 64MB

# WAL设置
wal_buffers = 16MB
checkpoint_completion_target = 0.9

# 字符集
lc_messages = 'en_US.UTF-8'
lc_monetary = 'en_US.UTF-8'
lc_numeric = 'en_US.UTF-8'
lc_time = 'en_US.UTF-8'
default_text_search_config = 'pg_catalog.english'
```

## Web服务器配置

### Nginx配置

创建 `/etc/nginx/sites-available/workorder`：

```nginx
server {
    listen 80;
    server_name your-domain.com www.your-domain.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name your-domain.com www.your-domain.com;

    root /var/www/workorder/public;
    index index.php index.html index.htm;

    # SSL配置
    ssl_certificate /etc/ssl/certs/your-domain.crt;
    ssl_certificate_key /etc/ssl/private/your-domain.key;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-RSA-AES256-GCM-SHA512:DHE-RSA-AES256-GCM-SHA512:ECDHE-RSA-AES256-GCM-SHA384:DHE-RSA-AES256-GCM-SHA384;
    ssl_prefer_server_ciphers off;
    ssl_session_cache shared:SSL:10m;
    ssl_session_timeout 10m;

    # 安全头
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
    add_header Content-Security-Policy "default-src 'self' http: https: data: blob: 'unsafe-inline'" always;

    # Gzip压缩
    gzip on;
    gzip_vary on;
    gzip_proxied any;
    gzip_comp_level 6;
    gzip_types text/plain text/css text/xml text/javascript application/json application/javascript application/xml+rss application/rss+xml application/atom+xml image/svg+xml;

    # 静态文件缓存
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    # Laravel路由
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # PHP处理
    location ~ \.php$ {
        try_files $uri =404;
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_param PATH_INFO $fastcgi_path_info;
        fastcgi_read_timeout 300;
    }

    # 拒绝访问隐藏文件
    location ~ /\.ht {
        deny all;
    }
}
```

启用站点：

```bash
sudo ln -s /etc/nginx/sites-available/workorder /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

### Apache配置

创建 `/etc/apache2/sites-available/workorder.conf`：

```apache
<VirtualHost *:80>
    ServerName your-domain.com
    Redirect permanent / https://your-domain.com/
</VirtualHost>

<VirtualHost *:443>
    ServerName your-domain.com
    DocumentRoot /var/www/workorder/public

    # SSL配置
    SSLEngine on
    SSLCertificateFile /etc/ssl/certs/your-domain.crt
    SSLCertificateKeyFile /etc/ssl/private/your-domain.key
    SSLProtocol all -SSLv3 -TLSv1 -TLSv1.1
    SSLCipherSuite ECDHE-RSA-AES256-GCM-SHA512:DHE-RSA-AES256-GCM-SHA512:ECDHE-RSA-AES256-GCM-SHA384:DHE-RSA-AES256-GCM-SHA384
    SSLHonorCipherOrder off
    SSLSessionTickets off

    # 安全头
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set X-XSS-Protection "1; mode=block"
    Header always set X-Content-Type-Options "nosniff"
    Header always set Referrer-Policy "no-referrer-when-downgrade"
    Header always set Content-Security-Policy "default-src 'self' http: https: data: blob: 'unsafe-inline'"

    <Directory /var/www/workorder>
        AllowOverride All
        Require all granted
    </Directory>

    # 启用压缩
    <IfModule mod_deflate.c>
        AddOutputFilterByType DEFLATE text/plain
        AddOutputFilterByType DEFLATE text/html
        AddOutputFilterByType DEFLATE text/xml
        AddOutputFilterByType DEFLATE text/css
        AddOutputFilterByType DEFLATE application/xml
        AddOutputFilterByType DEFLATE application/xhtml+xml
        AddOutputFilterByType DEFLATE application/rss+xml
        AddOutputFilterByType DEFLATE application/javascript
        AddOutputFilterByType DEFLATE application/x-javascript
    </IfModule>
</VirtualHost>
```

启用站点：

```bash
sudo a2ensite workorder.conf
sudo a2enmod rewrite
sudo a2enmod headers
sudo a2enmod ssl
sudo apache2ctl configtest
sudo systemctl reload apache2
```

## SSL证书配置

### 使用Let's Encrypt

```bash
# 安装Certbot
sudo apt install -y certbot python3-certbot-nginx

# 获取证书
sudo certbot --nginx -d your-domain.com -d www.your-domain.com

# 自动续期
sudo crontab -e
# 添加以下行：
# 0 12 * * * /usr/bin/certbot renew --quiet
```

### 使用自签名证书（开发环境）

```bash
# 创建证书目录
sudo mkdir -p /etc/ssl/private

# 生成私钥
sudo openssl genrsa -out /etc/ssl/private/your-domain.key 2048

# 生成证书
sudo openssl req -new -x509 -key /etc/ssl/private/your-domain.key -out /etc/ssl/certs/your-domain.crt -days 365
```

## 性能优化

### PHP优化

编辑 `/etc/php/8.3/fpm/php.ini`：

```ini
# 内存限制
memory_limit = 256M

# 执行时间
max_execution_time = 300
max_input_time = 300

# 文件上传
upload_max_filesize = 50M
post_max_size = 50M
max_file_uploads = 20

# OPcache配置
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=4000
opcache.revalidate_freq=60
opcache.fast_shutdown=1
opcache.enable_cli=1

# 会话设置
session.gc_maxlifetime = 7200
session.cookie_lifetime = 7200
```

### Redis配置

编辑 `/etc/redis/redis.conf`：

```ini
# 内存设置
maxmemory 256mb
maxmemory-policy allkeys-lru

# 持久化
save 900 1
save 300 10
save 60 10000

# 安全设置
requirepass your_redis_password
```

### 数据库连接池

使用Laravel的数据库连接池：

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=workorder
DB_USERNAME=workorder_user
DB_PASSWORD=strong_password

# 连接池设置
DB_POOL_SIZE=10
DB_MAX_CONNECTIONS=20
```

## 监控和日志

### 应用监控

安装Laravel Telescope：

```bash
composer require laravel/telescope
php artisan telescope:install
php artisan migrate
```

### 日志配置

```env
LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug
```

### 系统监控

使用Prometheus和Grafana：

```yaml
# prometheus.yml
global:
  scrape_interval: 15s

scrape_configs:
  - job_name: 'laravel'
    static_configs:
      - targets: ['localhost:8000']
```

## 备份策略

### 数据库备份

创建备份脚本 `/usr/local/bin/backup_workorder.sh`：

```bash
#!/bin/bash

BACKUP_DIR="/var/backups/workorder"
DATE=$(date +%Y%m%d_%H%M%S)
DB_NAME="workorder"
DB_USER="workorder_user"
DB_PASS="strong_password"

# 创建备份目录
mkdir -p $BACKUP_DIR

# 备份数据库
mysqldump -u$DB_USER -p$DB_PASS $DB_NAME | gzip > $BACKUP_DIR/db_backup_$DATE.sql.gz

# 备份文件
tar -czf $BACKUP_DIR/files_backup_$DATE.tar.gz /var/www/workorder/storage/app/public

# 清理旧备份（保留7天）
find $BACKUP_DIR -name "*.gz" -mtime +7 -delete

echo "备份完成: $DATE"
```

设置定时备份：

```bash
sudo crontab -e
# 添加以下行：
# 0 2 * * * /usr/local/bin/backup_workorder.sh
```

### 应用备份

```bash
# 创建应用备份
tar -czf workorder_backup_$(date +%Y%m%d_%H%M%S).tar.gz \
    --exclude=node_modules \
    --exclude=storage/logs \
    --exclude=storage/framework/cache \
    --exclude=storage/framework/sessions \
    --exclude=storage/framework/views \
    /var/www/workorder
```

## 故障排除

### 常见问题

#### 1. 500内部服务器错误

```bash
# 检查Laravel日志
tail -f /var/www/workorder/storage/logs/laravel.log

# 检查PHP错误日志
tail -f /var/log/php8.3-fpm.log

# 检查Nginx错误日志
tail -f /var/log/nginx/error.log

# 检查权限
sudo chown -R www-data:www-data /var/www/workorder
chmod -R 775 storage bootstrap/cache
```

#### 2. 数据库连接失败

```bash
# 测试数据库连接
php artisan tinker
>>> DB::connection()->getPdo();

# 检查数据库服务
sudo systemctl status mysql

# 检查数据库配置
cat .env | grep DB_
```

#### 3. 文件上传失败

```bash
# 检查上传目录权限
ls -la storage/app/public

# 检查PHP上传配置
php -i | grep upload

# 检查磁盘空间
df -h
```

#### 4. 队列不工作

```bash
# 检查队列服务
php artisan queue:failed

# 重启队列工作进程
php artisan queue:restart

# 手动处理队列
php artisan queue:work --timeout=60
```

### 性能问题诊断

```bash
# 检查系统资源
top
htop
iotop

# 检查数据库性能
mysql -u root -p -e "SHOW PROCESSLIST;"

# 检查PHP性能
php -v
php -m | grep opcache
```

## 升级指南

### 版本升级步骤

1. **备份当前系统**
   ```bash
   ./backup_workorder.sh
   ```

2. **下载新版本**
   ```bash
   wget https://your-domain.com/workorder-system_v2.0.0.tar.gz
   ```

3. **更新依赖**
   ```bash
   composer update --no-dev --optimize-autoloader
   npm install --production
   npm run build
   ```

4. **运行迁移**
   ```bash
   php artisan migrate --force
   ```

5. **清理缓存**
   ```bash
   php artisan config:clear
   php artisan route:clear
   php artisan view:clear
   php artisan cache:clear
   ```

6. **重新优化**
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

## 安全建议

### 基本安全措施

1. **定期更新**
   ```bash
   sudo apt update && sudo apt upgrade
   composer update
   npm update
   ```

2. **防火墙配置**
   ```bash
   sudo ufw enable
   sudo ufw allow ssh
   sudo ufw allow 'Nginx Full'
   ```

3. **SSH安全**
   ```bash
   # 编辑 /etc/ssh/sshd_config
   PermitRootLogin no
   PasswordAuthentication no
   ```

4. **应用安全**
   ```env
   APP_ENV=production
   APP_DEBUG=false
   ```

### 高级安全配置

1. **fail2ban配置**
   ```bash
   sudo apt install fail2ban
   sudo systemctl enable fail2ban
   ```

2. **入侵检测**
   ```bash
   sudo apt install aide
   sudo aide --init
   sudo mv /var/lib/aide/aide.db.new /var/lib/aide/aide.db
   ```

3. **安全扫描**
   ```bash
   # 使用ClamAV扫描恶意软件
   sudo apt install clamav
   sudo freshclam
   sudo clamscan -r /var/www/workorder
   ```

---

## 联系支持

如果在部署过程中遇到问题，请：

1. 查看系统日志：`/var/www/workorder/storage/logs/laravel.log`
2. 检查Web服务器日志：`/var/log/nginx/error.log` 或 `/var/log/apache2/error.log`
3. 联系技术支持：support@your-domain.com

---

**最后更新**: 2025-11-21
**版本**: 1.0.0