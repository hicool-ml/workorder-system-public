#!/usr/bin/env bash
# ============================================================
# 工单系统一键部署脚本（Ubuntu 20.04/22.04/24.04 + PostgreSQL >=14）
# 特点：
#   - 自动系统更新（apt update/upgrade）
#   - 依赖版本检查，过旧自动升级（PHP>=8.2、Node>=20.19、PG>=14）
#   - 国内镜像加速（Composer 阿里云、npm 淘宝）
#   - 时区统一 Asia/Shanghai
#   - 幂等：可重复执行
#   - 【不迁移生产数据】，仅空库初始化（migrate+seed），供测试
# 用法：bash deploy_ubuntu_pg.sh
# ============================================================
set -euo pipefail

# ---------- 可修改变量 ----------
APP_DIR="/var/www/workorder"
DB_NAME="workorder_db"
DB_USER="workorder"
APP_URL="http://192.168.1.4"
GIT_REPO="https://github.com/hicool-ml/workorder-system-public.git"
# --------------------------------

# 数据库密码：优先从环境变量 DB_PASSWORD 读取；未设置则自动生成随机密码，
# 并持久化到 ~/.workorder_db_password，保证重复执行（幂等）时密码一致。
DB_PASSWORD="${DB_PASSWORD:-}"
DB_PASSWORD_FILE="${HOME}/.workorder_db_password"
if [ -z "$DB_PASSWORD" ]; then
    if [ -f "$DB_PASSWORD_FILE" ]; then
        DB_PASSWORD="$(cat "$DB_PASSWORD_FILE")"
    else
        DB_PASSWORD="$(tr -dc 'A-Za-z0-9' < /dev/urandom | head -c 20)"
        printf '%s' "$DB_PASSWORD" > "$DB_PASSWORD_FILE"
        chmod 600 "$DB_PASSWORD_FILE"
    fi
fi

# 输出辅助
info()  { echo -e "\033[1;32m[✓]\033[0m $*"; }
warn()  { echo -e "\033[1;33m[!]\033[0m $*"; }
fail()  { echo -e "\033[1;31m[✗]\033[0m $*"; exit 1; }

# 版本比较：version_ge 实际 要求  =>  实际 >= 要求 时为真
version_ge() {
    [ "$(printf '%s\n%s\n' "$2" "$1" | sort -V | tail -1)" = "$1" ]
}

echo "=============================================="
echo "  工单系统一键部署"
echo "  时间: $(date '+%Y-%m-%d %H:%M:%S')"
echo "=============================================="

# ============ [1/10] 系统更新 ============
info "[1/10] 更新系统包"
sudo apt-get update -y
sudo apt-get upgrade -y

# ============ [2/10] PHP（>=8.2） ============
info "[2/10] 检查/安装 PHP"
PHP_VER=$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;' 2>/dev/null || echo "0.0")
if ! version_ge "$PHP_VER" "8.2"; then
    warn "当前 PHP ${PHP_VER} 过旧（需 >= 8.2），添加 ondrej/php PPA 安装 8.3"
    sudo apt-get install -y software-properties-common
    sudo add-apt-repository -y ppa:ondrej/php
    sudo apt-get update -y
fi
sudo apt-get install -y \
    php8.3-cli php8.3-fpm php8.3-pgsql php8.3-gd php8.3-mbstring \
    php8.3-xml php8.3-curl php8.3-zip php8.3-bcmath
info "PHP $(php -v | head -1)"

# ============ [3/10] Node.js（>=20.19，vite 7 要求） ============
info "[3/10] 检查/安装 Node.js"
NODE_VER=$(node -v 2>/dev/null | sed 's/^v//' || echo "0.0.0")
if ! version_ge "$NODE_VER" "20.19.0"; then
    warn "当前 Node ${NODE_VER} 过旧（vite 7 需 >= 20.19），升级到 22 LTS"
    curl -fsSL https://deb.nodesource.com/setup_22.x | sudo -E bash -
    sudo apt-get install -y nodejs
fi
info "Node $(node -v) / npm $(npm -v)"

# ============ [4/10] PostgreSQL（>=14，已手动安装则跳过） ============
info "[4/10] 检查 PostgreSQL"
if ! command -v psql >/dev/null 2>&1; then
    fail "未检测到 PostgreSQL，请先手动安装 >= 14（pgdg 官方源）"
fi
PG_MAJOR=$(psql --version | awk '{print $3}' | cut -d. -f1)
if [ "$PG_MAJOR" -lt 14 ]; then
    fail "PostgreSQL ${PG_MAJOR} 过旧（需 >= 14），请手动升级"
fi
info "PostgreSQL $(psql --version)"

# ============ [5/10] 其他工具 + Composer ============
info "[5/10] 安装 Nginx/Git/Composer"
sudo apt-get install -y nginx git unzip curl

if ! command -v composer >/dev/null 2>&1; then
    info "安装 Composer（阿里云镜像）"
    sudo curl -sS https://mirrors.aliyun.com/composer/composer.phar -o /usr/local/bin/composer
    sudo chmod +x /usr/local/bin/composer
fi
info "Composer $(composer --version | head -1)"

# ============ [6/10] 时区 ============
info "[6/10] 设置时区（系统 + PHP + PG）"
sudo timedatectl set-timezone Asia/Shanghai
sudo sed -i "s/^;date.timezone.*/date.timezone = Asia\/Shanghai/" /etc/php/8.3/cli/php.ini
sudo sed -i "s/^;date.timezone.*/date.timezone = Asia\/Shanghai/" /etc/php/8.3/fpm/php.ini
grep -q "^date.timezone" /etc/php/8.3/cli/php.ini || echo "date.timezone = Asia/Shanghai" | sudo tee -a /etc/php/8.3/cli/php.ini >/dev/null
grep -q "^date.timezone" /etc/php/8.3/fpm/php.ini || echo "date.timezone = Asia/Shanghai" | sudo tee -a /etc/php/8.3/fpm/php.ini >/dev/null
sudo systemctl restart php8.3-fpm
info "时区已统一为 Asia/Shanghai"

# ============ [7/10] 数据库 ============
info "[7/10] 配置 PostgreSQL 数据库"
sudo -u postgres psql -tc "SELECT 1 FROM pg_roles WHERE rolname='${DB_USER}'" | grep -q 1 || \
    sudo -u postgres psql -c "CREATE USER ${DB_USER} WITH PASSWORD '${DB_PASSWORD}';"
sudo -u postgres psql -tc "SELECT 1 FROM pg_database WHERE datname='${DB_NAME}'" | grep -q 1 || \
    sudo -u postgres psql -c "CREATE DATABASE ${DB_NAME} OWNER ${DB_USER};"
sudo -u postgres psql -c "ALTER DATABASE ${DB_NAME} SET timezone TO 'Asia/Shanghai';"
info "数据库 ${DB_NAME} / 用户 ${DB_USER} 就绪"

# ============ [8/10] 部署代码 ============
info "[8/10] 部署代码"
sudo mkdir -p /var/www
sudo chown -R "$USER":"$USER" /var/www
if [ ! -d "${APP_DIR}/.git" ]; then
    git clone "${GIT_REPO}" "${APP_DIR}"
else
    cd "${APP_DIR}" && git pull
fi

# ============ [9/10] 配置 + 依赖 + 构建 ============
info "[9/10] 配置 .env + 安装依赖 + 构建前端"
cd "${APP_DIR}"
[ -f .env ] || cp .env.example .env

sed -i "s|^APP_URL=.*|APP_URL=${APP_URL}|" .env
# APP_URL 是 http 时关闭 Secure Cookie，否则浏览器在 http 下不发送会话 Cookie 导致登录后掉线
if [[ "${APP_URL}" == https* ]]; then
    sed -i "s|^SESSION_SECURE_COOKIE=.*|SESSION_SECURE_COOKIE=true|" .env
else
    sed -i "s|^SESSION_SECURE_COOKIE=.*|SESSION_SECURE_COOKIE=false|" .env
fi
sed -i "s|^APP_ENV=.*|APP_ENV=production|" .env
sed -i "s|^APP_DEBUG=.*|APP_DEBUG=false|" .env
sed -i "s|^DB_CONNECTION=.*|DB_CONNECTION=pgsql|" .env
sed -i "s|^DB_HOST=.*|DB_HOST=127.0.0.1|" .env
sed -i "s|^DB_PORT=.*|DB_PORT=5432|" .env
sed -i "s|^DB_DATABASE=.*|DB_DATABASE=${DB_NAME}|" .env
sed -i "s|^DB_USERNAME=.*|DB_USERNAME=${DB_USER}|" .env
sed -i "s|^DB_PASSWORD=.*|DB_PASSWORD=${DB_PASSWORD}|" .env
sed -i "s|^SESSION_DRIVER=.*|SESSION_DRIVER=database|" .env
sed -i "s|^QUEUE_CONNECTION=.*|QUEUE_CONNECTION=database|" .env
sed -i "s|^CACHE_STORE=.*|CACHE_STORE=database|" .env

# 国内镜像加速
composer config -g repo.packagist composer https://mirrors.aliyun.com/composer/ 2>/dev/null || true
npm config set registry https://registry.npmmirror.com 2>/dev/null || true

composer install --no-dev --optimize-autoloader
php artisan key:generate --force
npm install
npm run build

# ============ [10/10] 空库部署 + Nginx ============
info "[10/10] 空库部署（migrate+seed）+ Nginx"
php artisan migrate --force
php artisan db:seed --force

sudo tee /etc/nginx/sites-available/workorder >/dev/null <<EOF
server {
    listen 80;
    server_name _;
    root ${APP_DIR}/public;
    index index.php;

    client_max_body_size 25M;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php\$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    }

    location ~ /\.(?!well-known).* { deny all; }
}
EOF
sudo ln -sf /etc/nginx/sites-available/workorder /etc/nginx/sites-enabled/
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t
sudo systemctl reload nginx

sudo chown -R www-data:www-data "${APP_DIR}/storage" "${APP_DIR}/bootstrap/cache"

echo ""
echo "=============================================="
echo "  部署完成！"
echo "  访问地址：${APP_URL}"
echo "  管理员账号：用户名 admin（密码见上方 db:seed 输出）"
echo "  数据库密码：保存在 ${DB_PASSWORD_FILE}（本机文件，勿提交）"
echo "  注意：尚未迁移生产数据，可先测试地址初始化等操作"
echo "=============================================="
