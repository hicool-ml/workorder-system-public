# 校园网工单系统 - 部署与维护指南

## 指南概述

本指南为系统管理员和运维人员提供完整的部署、配置、监控和维护指导，涵盖从环境准备到生产运维的全流程，确保系统稳定可靠运行。

## 目录

1. [系统要求](#系统要求)
2. [环境准备](#环境准备)
3. [部署流程](#部署流程)
4. [配置管理](#配置管理)
5. [监控与日志](#监控与日志)
6. [备份与恢复](#备份与恢复)
7. [性能优化](#性能优化)
8. [安全配置](#安全配置)
9. [故障排除](#故障排除)
10. [升级与维护](#升级与维护)
11. [运维最佳实践](#运维最佳实践)

## 系统要求

### 硬件要求

#### 最小配置
- **CPU**：2核心
- **内存**：4GB RAM
- **存储**：50GB 可用空间
- **网络**：100Mbps 带宽

#### 推荐配置
- **CPU**：4核心或更多
- **内存**：8GB RAM 或更多
- **存储**：100GB+ SSD
- **网络**：1Gbps 带宽

#### 生产环境配置
- **CPU**：8核心或更多
- **内存**：16GB RAM 或更多
- **存储**：500GB+ SSD（系统）+ 1TB+ HDD（数据）
- **网络**：1Gbps+ 带宽
- **负载均衡**：多台服务器部署

### 软件要求

#### 操作系统
- **Linux**：Ubuntu 20.04+ / CentOS 8+ / RHEL 8+
- **Windows**：Windows Server 2019+（不推荐）

#### Web服务器
- **Nginx**：1.18+（推荐）
- **Apache**：2.4+（备选）

#### 数据库
- **MySQL**：8.0+（推荐）
- **MariaDB**：10.5+（兼容）
- **PostgreSQL**：13+（实验性支持）

#### PHP环境
- **PHP版本**：8.2+（推荐8.3）
- **必需扩展**：
  ```bash
  php-mbstring
  php-tokenizer
  php-xml
  php-ctype
  php-fileinfo
  php-json
  php-bcmath
  php-openssl
  php-pdo_mysql
  php-gd
  php-curl
  php-zip
  php-intl
  ```

#### 其他软件
- **Node.js**：18.0+（前端构建）
- **NPM**：9.0+
- **Composer**：2.0+
- **Redis**：6.0+（缓存，可选）
- **Git**：2.0+

## 环境准备

### 1. 系统初始化

#### Ubuntu/Debian
```bash
# 更新系统
sudo apt update && sudo apt upgrade -y

# 安装基础软件
sudo apt install -y curl wget git unzip vim htop

# 配置时区
sudo timedatectl set-timezone Asia/Shanghai

# 配置防火墙
sudo ufw enable
sudo ufw allow ssh
sudo ufw allow 80
sudo ufw allow 443
```

#### CentOS/RHEL
```bash
# 更新系统
sudo yum update -y

# 安装基础软件
sudo yum install -y curl wget git unzip vim htop

# 配置时区
sudo timedatectl set-timezone Asia/Shanghai

# 配置防火墙
sudo firewall-cmd --permanent --add-service=ssh
sudo firewall-cmd --permanent --add-service=http
sudo firewall-cmd --permanent --add-service=https
sudo firewall-cmd --reload
```

### 2. 安装PHP环境

#### Ubuntu/Debian
```bash
# 添加PHP PPA
sudo apt install -y software-properties-common
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update

# 安装PHP 8.3
sudo apt install -y php8.3 php8.3-fpm php8.3-mysql \
    php8.3-mbstring php8.3-tokenizer php8.3-xml \
    php8.3-ctype php8.3-fileinfo php8.3-json \
    php8.3-bcmath php8.3-openssl php8.3-gd \
    php8.3-curl php8.3-zip php8.3-intl

# 启动PHP-FPM
sudo systemctl start php8.3-fpm
sudo systemctl enable php8.3-fpm
```

#### CentOS/RHEL
```bash
# 安装REMI仓库
sudo yum install -y https://rpms.remirepo.net/enterprise/remi-release-8.rpm

# 启用PHP 8.3模块
sudo yum module enable php:remi-8.3 -y
sudo yum update -y

# 安装PHP 8.3
sudo yum install -y php php-fpm php-mysql \
    php-mbstring php-tokenizer php-xml \
    php-ctype php-fileinfo php-json \
    php-bcmath php-openssl php-gd \
    php-curl php-zip php-intl

# 启动PHP-FPM
sudo systemctl start php-fpm
sudo systemctl enable php-fpm
```

### 3. 安装数据库

#### MySQL 8.0
```bash
# Ubuntu/Debian
sudo apt install -y mysql-server

# CentOS/RHEL
sudo yum install -y mysql-server

# 安全配置
sudo mysql_secure_installation

# 启动服务
sudo systemctl start mysql
sudo systemctl enable mysql

# 创建数据库和用户
sudo mysql -u root -p
```

```sql
CREATE DATABASE workorder_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'workorder_user'@'localhost' IDENTIFIED BY 'strong_password';
GRANT ALL PRIVILEGES ON workorder_db.* TO 'workorder_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### 4. 安装Web服务器

#### Nginx（推荐）
```bash
# Ubuntu/Debian
sudo apt install -y nginx

# CentOS/RHEL
sudo yum install -y nginx

# 启动服务
sudo systemctl start nginx
sudo systemctl enable nginx
```

#### Nginx配置
```nginx
# /etc/nginx/sites-available/workorder
server {
    listen 80;
    server_name your-domain.com;
    root /var/www/workorder/public;
    index index.php index.html;

    # 日志配置
    access_log /var/log/nginx/workorder_access.log;
    error_log /var/log/nginx/workorder_error.log;

    # 安全配置
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
    add_header Content-Security-Policy "default-src 'self' http: https: data: blob: 'unsafe-inline'" always;

    # 静态文件缓存
    location ~* \.(jpg|jpeg|png|gif|ico|css|js)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    # PHP处理
    location ~ \.php$ {
        try_files $uri =404;
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_param PATH_INFO $fastcgi_path_info;
    }

    # Laravel路由
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # 禁止访问敏感文件
    location ~ /\. {
        deny all;
    }

    location ~ /(storage|bootstrap/cache)/ {
        deny all;
    }
}

# HTTPS配置（使用Let's Encrypt）
server {
    listen 443 ssl http2;
    server_name your-domain.com;
    root /var/www/workorder/public;

    ssl_certificate /etc/letsencrypt/live/your-domain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/your-domain.com/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-RSA-AES256-GCM-SHA512:DHE-RSA-AES256-GCM-SHA512:ECDHE-RSA-AES256-GCM-SHA384:DHE-RSA-AES256-GCM-SHA384;
    ssl_prefer_server_ciphers off;

    # 其他配置同上...
}

# HTTP重定向到HTTPS
server {
    listen 80;
    server_name your-domain.com;
    return 301 https://$server_name$request_uri;
}
```

### 5. 安装Node.js和Composer

#### Node.js
```bash
# 使用NodeSource仓库
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt-get install -y nodejs

# 或使用NVM
curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.39.0/install.sh | bash
source ~/.bashrc
nvm install 20
nvm use 20
```

#### Composer
```bash
# 下载并安装Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
sudo chmod +x /usr/local/bin/composer

# 验证安装
composer --version
```

## 部署流程

### 1. 自动化部署（推荐）

使用项目提供的自动化部署脚本：

```bash
# 1. 下载部署包
wget https://your-domain.com/packages/workorder-system_v1.0.0.tar.gz

# 2. 解压部署包
tar -xzf workorder-system_v1.0.0.tar.gz
cd workorder-system_v1.0.0

# 3. 运行自动部署脚本
chmod +x auto_deploy.sh
./auto_deploy.sh -e production -v
```

#### 自动部署脚本功能
- 环境检查
- 依赖安装
- 数据库迁移
- 前端资源编译
- 权限设置
- 健康检查

### 2. 手动部署

#### 步骤1：获取源码
```bash
# 克隆仓库
git clone https://github.com/your-org/workorder-system.git /var/www/workorder
cd /var/www/workorder

# 或下载发布包
wget https://your-domain.com/workorder-system_v1.0.0.tar.gz
tar -xzf workorder-system_v1.0.0.tar.gz -C /var/www/
mv /var/www/workorder-system_v1.0.0 /var/www/workorder
```

#### 步骤2：安装依赖
```bash
# PHP依赖
composer install --no-dev --optimize-autoloader

# 前端依赖
npm install --production
```

#### 步骤3：环境配置
```bash
# 复制环境配置文件
cp .env.example .env

# 生成应用密钥
php artisan key:generate

# 编辑环境配置
vim .env
```

#### 步骤4：数据库设置
```bash
# 运行迁移
php artisan migrate --force

# 填充初始数据
php artisan db:seed --force
```

#### 步骤5：前端资源编译
```bash
# 编译前端资源
npm run build

# 优化缓存
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

#### 步骤6：设置权限
```bash
# 设置目录权限
sudo chown -R www-data:www-data /var/www/workorder
sudo chmod -R 755 /var/www/workorder
sudo chmod -R 775 /var/www/workorder/storage
sudo chmod -R 775 /var/www/workorder/bootstrap/cache
```

#### 步骤7：配置Web服务器
```bash
# 启用站点
sudo ln -s /etc/nginx/sites-available/workorder /etc/nginx/sites-enabled/

# 测试配置
sudo nginx -t

# 重启服务
sudo systemctl restart nginx
sudo systemctl restart php8.3-fpm
```

### 3. Docker部署

#### Dockerfile
```dockerfile
FROM php:8.3-fpm

# 安装系统依赖
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# 安装Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 复制应用代码
COPY . /var/www/html

# 设置工作目录
WORKDIR /var/www/html

# 安装PHP依赖
RUN composer install --no-dev --optimize-autoloader

# 安装Node.js和前端依赖
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && npm install \
    && npm run build

# 设置权限
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 775 /var/www/html/storage \
    && chmod -R 775 /var/www/html/bootstrap/cache

EXPOSE 9000

CMD ["php-fpm"]
```

#### Docker Compose
```yaml
version: '3.8'

services:
  app:
    build: .
    container_name: workorder-app
    restart: unless-stopped
    working_dir: /var/www/html
    volumes:
      - ./:/var/www/html
      - ./php/local.ini:/usr/local/etc/php/conf.d/local.ini
    networks:
      - workorder-network
    depends_on:
      - mysql
      - redis

  webserver:
    image: nginx:alpine
    container_name: workorder-webserver
    restart: unless-stopped
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./:/var/www/html
      - ./nginx/conf.d/:/etc/nginx/conf.d/
      - ./nginx/ssl/:/etc/nginx/ssl/
    networks:
      - workorder-network
    depends_on:
      - app

  mysql:
    image: mysql:8.0
    container_name: workorder-mysql
    restart: unless-stopped
    environment:
      MYSQL_DATABASE: workorder_db
      MYSQL_USER: workorder_user
      MYSQL_PASSWORD: strong_password
      MYSQL_ROOT_PASSWORD: root_password
    volumes:
      - mysql_data:/var/lib/mysql
    ports:
      - "3306:3306"
    networks:
      - workorder-network

  redis:
    image: redis:6.2-alpine
    container_name: workorder-redis
    restart: unless-stopped
    ports:
      - "6379:6379"
    volumes:
      - redis_data:/data
    networks:
      - workorder-network

networks:
  workorder-network:
    driver: bridge

volumes:
  mysql_data:
    driver: local
  redis_data:
    driver: local
```

#### Docker部署命令
```bash
# 构建并启动服务
docker-compose up -d --build

# 查看日志
docker-compose logs -f

# 运行迁移
docker-compose exec app php artisan migrate

# 填充数据
docker-compose exec app php artisan db:seed
```

## 配置管理

### 1. 环境配置

#### .env文件配置
```env
# 应用配置
APP_NAME="校园网工单系统"
APP_ENV=production
APP_KEY=base64:your_app_key_here
APP_DEBUG=false
APP_URL=https://your-domain.com

# 数据库配置
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=workorder_db
DB_USERNAME=workorder_user
DB_PASSWORD=your_database_password

# 缓存配置
CACHE_DRIVER=redis
CACHE_PREFIX=workorder
CACHE_STORE=redis

# 会话配置
SESSION_DRIVER=redis
SESSION_LIFETIME=120

# 队列配置
QUEUE_CONNECTION=redis

# Redis配置
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# 邮件配置
MAIL_MAILER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=your_email@example.com
MAIL_PASSWORD=your_email_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@example.com
MAIL_FROM_NAME="${APP_NAME}"

# 文件存储
FILESYSTEM_DISK=public

# 日志配置
LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

# 安全配置
BCRYPT_ROUNDS=12
```

### 2. PHP配置

#### php.ini优化
```ini
# /etc/php/8.3/fpm/php.ini

# 内存限制
memory_limit = 256M

# 执行时间限制
max_execution_time = 300

# 上传限制
upload_max_filesize = 10M
post_max_size = 12M

# 会话配置
session.gc_maxlifetime = 7200

# 错误报告
display_errors = Off
log_errors = On
error_log = /var/log/php_errors.log

# OPcache配置
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=4000
opcache.revalidate_freq=2
opcache.fast_shutdown=1
```

#### PHP-FPM配置
```ini
# /etc/php/8.3/fpm/pool.d/www.conf

[www]
user = www-data
group = www-data
listen = /run/php/php8.3-fpm.sock
listen.owner = www-data
listen.group = www-data
listen.mode = 0660

# 进程管理
pm = dynamic
pm.max_children = 50
pm.start_servers = 5
pm.min_spare_servers = 5
pm.max_spare_servers = 35
pm.max_requests = 500

# 进程限制
process.priority = -19
```

### 3. Nginx配置优化

#### 性能优化
```nginx
# 工作进程数
worker_processes auto;
worker_connections 1024;

# 缓存配置
fastcgi_cache_path /var/cache/nginx levels=1:2 keys_zone=workorder_cache:100m inactive=60m;
fastcgi_cache_key "$scheme$request_method$host$request_uri";

# Gzip压缩
gzip on;
gzip_vary on;
gzip_min_length 1024;
gzip_types text/plain text/css application/json application/javascript text/xml application/xml application/xml+rss text/javascript;

# 连接配置
keepalive_timeout 65;
keepalive_requests 100;
```

## 监控与日志

### 1. 应用监控

#### Laravel Telescope（开发环境）
```bash
# 安装Telescope
composer require laravel/telescope --dev
php artisan telescope:install
php artisan migrate
```

#### 生产监控配置
```php
// config/logging.php
'channels' => [
    'stack' => [
        'driver' => 'stack',
        'channels' => ['single', 'slack'],
        'ignore_exceptions' => false,
    ],
    
    'daily' => [
        'driver' => 'daily',
        'path' => storage_path('logs/laravel.log'),
        'level' => env('LOG_LEVEL', 'debug'),
        'days' => 14,
    ],
    
    'slack' => [
        'driver' => 'slack',
        'url' => env('LOG_SLACK_WEBHOOK_URL'),
        'username' => 'Laravel Log',
        'emoji' => ':boom:',
        'level' => 'critical',
    ],
],
```

### 2. 系统监控

#### 安装监控工具
```bash
# 安装htop
sudo apt install htop

# 安装iotop
sudo apt install iotop

# 安装netstat
sudo apt install net-tools

# 安装nethogs
sudo apt install nethogs
```

#### 监控脚本
```bash
#!/bin/bash
# /usr/local/bin/monitor.sh

# 系统负载
echo "=== 系统负载 ==="
uptime

# 内存使用
echo "=== 内存使用 ==="
free -h

# 磁盘使用
echo "=== 磁盘使用 ==="
df -h

# 网络连接
echo "=== 网络连接 ==="
netstat -an | grep :80 | wc -l

# PHP-FPM进程
echo "=== PHP-FPM进程 ==="
ps aux | grep php-fpm | wc -l

# MySQL进程
echo "=== MySQL进程 ==="
ps aux | grep mysql | wc -l
```

### 3. 日志管理

#### 日志轮转配置
```bash
# /etc/logrotate.d/workorder
/var/www/workorder/storage/logs/*.log {
    daily
    missingok
    rotate 30
    compress
    delaycompress
    notifempty
    create 644 www-data www-data
    postrotate
        systemctl reload php8.3-fpm
    endscript
}
```

#### 日志分析脚本
```bash
#!/bin/bash
# /usr/local/bin/analyze_logs.sh

LOG_DIR="/var/www/workorder/storage/logs"
TODAY=$(date +%Y-m-d)

# 分析错误日志
if [ -f "$LOG_DIR/laravel-$TODAY.log" ]; then
    echo "=== 今日错误统计 ==="
    grep -i "error" "$LOG_DIR/laravel-$TODAY.log" | wc -l
    
    echo "=== 今日警告统计 ==="
    grep -i "warning" "$LOG_DIR/laravel-$TODAY.log" | wc -l
fi

# 分析Nginx访问日志
if [ -f "/var/log/nginx/workorder_access.log" ]; then
    echo "=== 今日访问统计 ==="
    awk '{print $1}' /var/log/nginx/workorder_access.log | sort | uniq -c | sort -nr | head -10
fi
```

## 备份与恢复

### 1. 数据库备份

#### 自动备份脚本
```bash
#!/bin/bash
# /usr/local/bin/backup_database.sh

DB_NAME="workorder_db"
DB_USER="workorder_user"
DB_PASS="your_password"
BACKUP_DIR="/var/backups/mysql"
DATE=$(date +%Y%m%d_%H%M%S)

# 创建备份目录
mkdir -p $BACKUP_DIR

# 执行备份
mysqldump -u $DB_USER -p$DB_PASS $DB_NAME | gzip > $BACKUP_DIR/workorder_$DATE.sql.gz

# 删除7天前的备份
find $BACKUP_DIR -name "workorder_*.sql.gz" -mtime +7 -delete

# 记录日志
echo "Database backup completed: workorder_$DATE.sql.gz" >> /var/log/backup.log
```

#### 设置定时备份
```bash
# 添加到crontab
crontab -e

# 每天凌晨2点备份
0 2 * * * /usr/local/bin/backup_database.sh
```

### 2. 文件备份

#### 应用文件备份
```bash
#!/bin/bash
# /usr/local/bin/backup_files.sh

APP_DIR="/var/www/workorder"
BACKUP_DIR="/var/backups/files"
DATE=$(date +%Y%m%d_%H%M%S)

# 创建备份目录
mkdir -p $BACKUP_DIR

# 备份应用文件
tar -czf $BACKUP_DIR/workorder_files_$DATE.tar.gz \
    --exclude=node_modules \
    --exclude=storage/logs \
    --exclude=storage/framework/cache \
    $APP_DIR

# 删除30天前的备份
find $BACKUP_DIR -name "workorder_files_*.tar.gz" -mtime +30 -delete

echo "Files backup completed: workorder_files_$DATE.tar.gz" >> /var/log/backup.log
```

### 3. 恢复流程

#### 数据库恢复
```bash
#!/bin/bash
# /usr/local/bin/restore_database.sh

BACKUP_FILE=$1
DB_NAME="workorder_db"
DB_USER="workorder_user"
DB_PASS="your_password"

if [ -z "$BACKUP_FILE" ]; then
    echo "Usage: $0 <backup_file>"
    exit 1
fi

# 停止应用
sudo systemctl stop nginx

# 恢复数据库
gunzip < $BACKUP_FILE | mysql -u $DB_USER -p$DB_PASS $DB_NAME

# 启动应用
sudo systemctl start nginx

echo "Database restored from: $BACKUP_FILE"
```

## 性能优化

### 1. 数据库优化

#### MySQL配置优化
```ini
# /etc/mysql/mysql.conf.d/mysqld.cnf

[mysqld]
# 内存配置
innodb_buffer_pool_size = 2G
innodb_log_file_size = 256M
innodb_log_buffer_size = 16M

# 连接配置
max_connections = 200
max_connect_errors = 1000

# 查询缓存
query_cache_type = 1
query_cache_size = 64M
query_cache_limit = 2M

# 慢查询日志
slow_query_log = 1
slow_query_log_file = /var/log/mysql/slow.log
long_query_time = 2

# 二进制日志
log_bin = /var/log/mysql/mysql-bin.log
binlog_format = ROW
expire_logs_days = 7
```

#### 数据库维护
```bash
#!/bin/bash
# /usr/local/bin/mysql_maintenance.sh

# 优化表
mysql -u root -p -e "OPTIMIZE TABLE workorders, workorder_logs, workorder_attachments;"

# 分析表
mysql -u root -p -e "ANALYZE TABLE workorders, workorder_logs, workorder_attachments;"

# 检查表
mysql -u root -p -e "CHECK TABLE workorders, workorder_logs, workorder_attachments;"

echo "MySQL maintenance completed" >> /var/log/maintenance.log
```

### 2. 应用缓存优化

#### Redis配置
```conf
# /etc/redis/redis.conf

# 内存配置
maxmemory 512mb
maxmemory-policy allkeys-lru

# 持久化配置
save 900 1
save 300 10
save 60 10000

# 网络配置
tcp-keepalive 300
timeout 0
```

#### Laravel缓存优化
```bash
# 清理所有缓存
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# 优化缓存
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 3. 前端优化

#### 静态资源优化
```javascript
// vite.config.js
export default defineConfig({
    build: {
        rollupOptions: {
            output: {
                manualChunks: {
                    vendor: ['jquery', 'bootstrap'],
                    app: ['./resources/js/app.js']
                }
            }
        },
        minify: 'terser',
        sourcemap: false,
        chunkSizeWarningLimit: 1000
    }
});
```

## 安全配置

### 1. 系统安全

#### SSH安全配置
```bash
# /etc/ssh/sshd_config

# 禁用root登录
PermitRootLogin no

# 使用密钥认证
PasswordAuthentication no
PubkeyAuthentication yes

# 更改默认端口
Port 2222

# 限制用户
AllowUsers workorder_user

# 重启SSH服务
sudo systemctl restart ssh
```

#### 防火墙配置
```bash
# UFW配置
sudo ufw default deny incoming
sudo ufw default allow outgoing
sudo ufw allow ssh
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable
```

### 2. 应用安全

#### SSL证书配置
```bash
# 安装Certbot
sudo apt install certbot python3-certbot-nginx

# 获取SSL证书
sudo certbot --nginx -d your-domain.com

# 自动续期
sudo crontab -e
# 添加：0 12 * * * /usr/bin/certbot renew --quiet
```

#### 安全头配置
```nginx
# 在Nginx配置中添加安全头
add_header X-Frame-Options "SAMEORIGIN" always;
add_header X-XSS-Protection "1; mode=block" always;
add_header X-Content-Type-Options "nosniff" always;
add_header Referrer-Policy "no-referrer-when-downgrade" always;
add_header Content-Security-Policy "default-src 'self' http: https: data: blob: 'unsafe-inline'" always;
add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
```

### 3. 数据安全

#### 数据加密
```php
// config/app.php
'key' => env('APP_KEY'),
'cipher' => 'AES-256-CBC',
```

#### 敏感信息保护
```bash
# 设置文件权限
sudo chmod 600 .env
sudo chmod 755 storage/
sudo chmod 755 bootstrap/cache/
```

## 故障排除

### 1. 常见问题

#### 应用无法访问
```bash
# 检查Nginx状态
sudo systemctl status nginx

# 检查配置文件
sudo nginx -t

# 查看错误日志
sudo tail -f /var/log/nginx/error.log

# 检查PHP-FPM状态
sudo systemctl status php8.3-fpm
```

#### 数据库连接失败
```bash
# 检查MySQL状态
sudo systemctl status mysql

# 测试连接
mysql -u workorder_user -p workorder_db

# 检查权限
mysql -u root -p -e "SHOW GRANTS FOR 'workorder_user'@'localhost';"
```

#### 权限问题
```bash
# 修复文件权限
sudo chown -R www-data:www-data /var/www/workorder
sudo chmod -R 755 /var/www/workorder
sudo chmod -R 775 /var/www/workorder/storage
sudo chmod -R 775 /var/www/workorder/bootstrap/cache
```

### 2. 性能问题

#### 内存不足
```bash
# 检查内存使用
free -h
htop

# 优化PHP配置
# 调整memory_limit和pm设置
```

#### 数据库慢查询
```bash
# 启用慢查询日志
# 分析慢查询日志
mysqldumpslow /var/log/mysql/slow.log

# 优化查询
# 添加索引，重写查询
```

### 3. 磁盘空间不足
```bash
# 检查磁盘使用
df -h

# 清理日志
sudo find /var/log -name "*.log" -mtime +30 -delete

# 清理缓存
php artisan cache:clear
```

## 升级与维护

### 1. 版本升级

#### 升级前准备
```bash
# 1. 备份数据
/usr/local/bin/backup_database.sh
/usr/local/bin/backup_files.sh

# 2. 检查当前版本
php artisan --version

# 3. 查看升级日志
cat CHANGELOG.md
```

#### 升级流程
```bash
# 1. 下载新版本
git fetch origin
git checkout v1.1.0

# 2. 更新依赖
composer update --no-dev
npm install

# 3. 运行迁移
php artisan migrate --force

# 4. 清理缓存
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# 5. 重新优化
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 6. 编译前端资源
npm run build
```

### 2. 定期维护

#### 每日维护
```bash
#!/bin/bash
# /usr/local/bin/daily_maintenance.sh

# 清理临时文件
find /tmp -name "laravel*" -mtime +1 -delete

# 清理过期缓存
php artisan cache:clear

# 备份数据库
/usr/local/bin/backup_database.sh

# 检查磁盘空间
df -h | mail -s "Disk Usage Report" admin@example.com
```

#### 每周维护
```bash
#!/bin/bash
# /usr/local/bin/weekly_maintenance.sh

# 优化数据库
/usr/local/bin/mysql_maintenance.sh

# 清理旧日志
find /var/www/workorder/storage/logs -name "*.log" -mtime +7 -delete

# 更新系统
sudo apt update && sudo apt upgrade -y

# 重启服务
sudo systemctl restart nginx
sudo systemctl restart php8.3-fpm
```

#### 每月维护
```bash
#!/bin/bash
# /usr/local/bin/monthly_maintenance.sh

# 全面备份
/usr/local/bin/backup_files.sh

# 安全更新
sudo unattended-upgrade

# 检查SSL证书
sudo certbot certificates

# 性能报告
/usr/local/bin/generate_performance_report.sh
```

## 运维最佳实践

### 1. 监控策略

#### 关键指标监控
- **系统负载**：CPU、内存、磁盘使用率
- **应用性能**：响应时间、错误率、吞吐量
- **数据库性能**：查询时间、连接数、慢查询
- **网络状态**：带宽使用、连接数、延迟

#### 告警配置
```bash
# 设置监控告警
# CPU使用率超过80%告警
# 内存使用率超过90%告警
# 磁盘使用率超过85%告警
# 应用响应时间超过5秒告警
# 错误率超过5%告警
```

### 2. 文档管理

#### 运维文档
- **系统架构图**：网络拓扑、服务器配置
- **操作手册**：日常操作、故障处理
- **应急预案**：系统故障、数据恢复
- **变更记录**：配置修改、版本升级

#### 文档更新
- 定期更新文档内容
- 记录所有变更操作
- 建立文档版本控制
- 定期审查文档准确性

### 3. 团队协作

#### 值班制度
- 建立7x24小时值班制度
- 制定值班交接流程
- 建立紧急联系机制
- 定期进行应急演练

#### 知识分享
- 定期技术分享会
- 建立知识库系统
- 记录典型故障案例
- 持续改进运维流程

---

**文档版本**：v1.0.0  
**最后更新**：2025-11-21  
**适用系统版本**：v1.0.0+  
**维护团队**：校园网工单系统运维团队