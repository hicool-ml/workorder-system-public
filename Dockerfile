# 工单系统 Docker 镜像
# 多阶段构建：先安装依赖，再生成精简运行镜像

# ---- Stage 1: 构建前端资源 ----
FROM node:20-alpine AS frontend
WORKDIR /build
COPY package.json package-lock.json* ./
RUN npm ci --prefer-offline 2>/dev/null || npm install
COPY resources/ ./resources/
COPY tsconfig.json vite.config.ts tailwind.config.js postcss.config.js* ./
COPY public/ ./public/
RUN npm run build

# ---- Stage 2: PHP 依赖 ----
FROM composer:2 AS backend
WORKDIR /build
COPY composer.json composer.lock* ./
RUN composer install --no-dev --no-autoloader --no-scripts --prefer-dist
COPY . .
RUN composer dump-autoload --no-dev --optimize

# ---- Stage 3: 运行时 ----
FROM php:8.4-fpm-alpine AS runtime

# 安装 PHP 扩展依赖
RUN apk add --no-cache \
    nginx \
    supervisor \
    mysql-client \
    libzip-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    oniguruma-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        mysqli \
        gd \
        zip \
        mbstring \
        bcmath \
        opcache \
        pcntl

# 配置 OPcache（生产优化）
RUN echo "[opcache]\nopcache.enable=1\nopcache.memory_consumption=256\nopcache.max_accelerated_files=20000\nopcache.validate_timestamps=0" \
    > /usr/local/etc/php/conf.d/opcache-recommended.ini

WORKDIR /var/www/html

# 复制应用代码
COPY --from=backend /build .
# 复制前端构建产物
COPY --from=frontend /build/public/build ./public/build

# 创建 storage 目录结构
RUN mkdir -p storage/app/public storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

# Nginx 配置
COPY <<'NGINX' /etc/nginx/http.d/default.conf
server {
    listen 80;
    server_name _;
    root /var/www/html/public;
    index index.php;

    client_max_body_size 25M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ ^/(storage|public)/ {
        try_files $uri /index.php?$query_string;
    }

    location ~ /\.(?!well-known).* { deny all; }
}
NGINX

# Supervisor 配置（PHP-FPM + Nginx + Queue Worker）
COPY <<'SUP' /etc/supervisor.d/app.ini
[supervisord]
nodaemon=true
logfile=/dev/stdout
pidfile=/var/run/supervisord.pid

[program:php-fpm]
command=php-fpm -F
stdout_logfile=/dev/stdout
stdout_logfile_maxbytes=0
stderr_logfile=/dev/stderr

[program:nginx]
command=nginx -g 'daemon off;'
stdout_logfile=/dev/stdout
stdout_logfile_maxbytes=0
stderr_logfile=/dev/stderr

[program:queue]
command=php artisan queue:work --tries=3 --sleep=3
directory=/var/www/html
stdout_logfile=/dev/stdout
stdout_logfile_maxbytes=0
stderr_logfile=/dev/stderr
autostart=true
autorestart=true

# Laravel 调度器：每分钟触发 schedule:run，让注册在 routes/console.php 的定时任务
# （如每日 02:00 的 backup:system）真正生效。Alpine 无 cron 服务，用循环替代。
[program:scheduler]
command=/bin/sh -c "while true; do php artisan schedule:run --no-ansi >> /dev/null 2>&1; sleep 60; done"
directory=/var/www/html
stdout_logfile=/dev/stdout
stdout_logfile_maxbytes=0
stderr_logfile=/dev/stderr
autostart=true
autorestart=true
SUP

EXPOSE 80
CMD ["supervisord", "-c", "/etc/supervisord.conf"]
