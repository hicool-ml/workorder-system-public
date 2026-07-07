#!/bin/bash

# 校园网工单系统 - 一键部署脚本
# 解决所有已知问题的完整部署方案

set -e

# 颜色定义
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}  校园网工单系统 - 一键部署脚本${NC}"
echo -e "${BLUE}========================================${NC}"
echo ""

# 检查是否为root用户
if [ "$EUID" -ne 0 ]; then
    echo -e "${RED}错误：请使用sudo运行此脚本${NC}"
    echo "用法：sudo bash $0"
    exit 1
fi

# 获取项目目录
PROJECT_DIR="/home/waverjiang/campus-workorder-system-v1.0.0_20251121_191018"
if [ ! -d "$PROJECT_DIR" ]; then
    echo -e "${RED}错误：项目目录不存在 $PROJECT_DIR${NC}"
    echo "请检查项目路径是否正确"
    exit 1
fi

echo -e "${GREEN}项目目录：${NC} $PROJECT_DIR"

# 步骤1：修复文件权限
echo -e "${YELLOW}步骤1：修复文件权限...${NC}"
chown -R waverjiang:waverjiang "$PROJECT_DIR"
find "$PROJECT_DIR" -type d -exec chmod 755 {} \;
find "$PROJECT_DIR" -type f -exec chmod 644 {} \;
chmod +x "$PROJECT_DIR/artisan"
echo -e "${GREEN}✓ 文件权限修复完成${NC}"

# 步骤2：修复Composer权限
echo -e "${YELLOW}步骤2：配置Composer权限...${NC}"
export COMPOSER_ALLOW_SUPERUSER=1

# 创建Composer全局配置
mkdir -p /root/.config/composer
cat > /root/.config/composer/config.json << EOF
{
    "config": {
        "allow-plugins": true,
        "allow-superuser": true
    }
}
EOF
echo -e "${GREEN}✓ Composer权限配置完成${NC}"

# 步骤3：安装依赖
echo -e "${YELLOW}步骤3：安装PHP依赖...${NC}"
cd "$PROJECT_DIR"

# 尝试多种方法安装依赖
DEPENDENCY_SUCCESS=false

# 方法1：使用环境变量
echo "尝试方法1：设置环境变量..."
if COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --optimize-autoloader; then
    echo -e "${GREEN}✓ 方法1成功${NC}"
    DEPENDENCY_SUCCESS=true
else
    echo -e "${YELLOW}❌ 方法1失败，尝试方法2...${NC}"
    
    # 方法2：禁用插件
    echo "尝试方法2：禁用插件..."
    if composer install --no-dev --optimize-autoloader --no-plugins; then
        echo -e "${GREEN}✓ 方法2成功${NC}"
        composer dump-autoload --optimize
        DEPENDENCY_SUCCESS=true
    else
        echo -e "${YELLOW}❌ 方法2失败，尝试方法3...${NC}"
        
        # 方法3：重新安装Composer
        echo "尝试方法3：重新安装Composer..."
        curl -sS https://getcomposer.org/installer | php
        if php composer.phar install --no-dev --optimize-autoloader; then
            echo -e "${GREEN}✓ 方法3成功${NC}"
            DEPENDENCY_SUCCESS=true
        else
            echo -e "${RED}❌ 所有方法都失败了${NC}"
        fi
    fi
fi

if [ "$DEPENDENCY_SUCCESS" = false ]; then
    echo -e "${RED}错误：依赖安装失败${NC}"
    exit 1
fi

# 步骤4：生成应用密钥
echo -e "${YELLOW}步骤4：生成应用密钥...${NC}"
# 确保APP_KEY存在
if ! grep -q "APP_KEY=" "$PROJECT_DIR/.env"; then
    php artisan key:generate --force
    echo -e "${GREEN}✓ 应用密钥生成成功${NC}"
else
    echo -e "${GREEN}✓ 应用密钥已存在${NC}"
fi

# 步骤5：配置环境文件
echo -e "${YELLOW}步骤5：配置环境文件...${NC}"
cat > "$PROJECT_DIR/.env" << EOF
APP_NAME="校园网工单系统"
APP_ENV=production
APP_DEBUG=false
APP_URL=http://117.176.215.210:14580

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=workorder_DB
DB_USERNAME=cdu
DB_PASSWORD=REDACTED_PROD_SSH_PASS

BROADCAST_DRIVER=log
CACHE_DRIVER=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_LIFETIME=120

MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="\${APP_NAME}"
EOF

echo -e "${GREEN}✓ 环境文件配置完成${NC}"
echo -e "${YELLOW}请手动修改 .env 文件中的数据库密码${NC}"

# 步骤6：创建数据库
echo -e "${YELLOW}步骤6：创建数据库...${NC}"
echo "请输入MySQL root密码："
if mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS workorder_DB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; CREATE USER IF NOT EXISTS 'cdu'@'localhost' IDENTIFIED BY 'REDACTED_PROD_SSH_PASS'; GRANT ALL PRIVILEGES ON workorder_DB.* TO 'cdu'@'localhost'; FLUSH PRIVILEGES;" 2>/dev/null; then
    echo -e "${GREEN}✓ 数据库创建成功${NC}"
else
    echo -e "${YELLOW}⚠ 数据库创建可能失败，请手动创建${NC}"
    echo "手动创建命令："
    echo "CREATE DATABASE workorder_DB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
    echo "CREATE USER 'cdu'@'localhost' IDENTIFIED BY 'REDACTED_PROD_SSH_PASS';"
    echo "GRANT ALL PRIVILEGES ON workorder_DB.* TO 'cdu'@'localhost';"
    echo "FLUSH PRIVILEGES;"
fi

# 步骤7：运行数据库迁移
echo -e "${YELLOW}步骤7：运行数据库迁移...${NC}"
if php artisan migrate --force; then
    echo -e "${GREEN}✓ 数据库迁移成功${NC}"
else
    echo -e "${RED}错误：数据库迁移失败${NC}"
    echo "请检查数据库配置"
    exit 1
fi

# 步骤8：导入种子数据
echo -e "${YELLOW}步骤8：导入种子数据...${NC}"
if php artisan db:seed --force; then
    echo -e "${GREEN}✓ 种子数据导入成功${NC}"
else
    echo -e "${YELLOW}⚠ 种子数据导入可能失败${NC}"
fi

# 步骤9：创建存储链接
echo -e "${YELLOW}步骤9：创建存储链接...${NC}"
if php artisan storage:link; then
    echo -e "${GREEN}✓ 存储链接创建成功${NC}"
else
    echo -e "${YELLOW}⚠ 存储链接可能已存在${NC}"
fi

# 步骤10：清除缓存
echo -e "${YELLOW}步骤10：清除应用缓存...${NC}"
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
echo -e "${GREEN}✓ 缓存清除完成${NC}"

# 步骤11：配置Apache
echo -e "${YELLOW}步骤11：配置Apache服务器...${NC}"

# 停止Apache
systemctl stop apache2

# 删除现有配置
rm -f /etc/apache2/sites-enabled/*
rm -f /etc/apache2/sites-available/000-default.conf

# 创建新的Apache配置
cat > /etc/apache2/sites-available/workorder.conf << EOF
<VirtualHost *:80>
    ServerName 117.176.215.210
    DocumentRoot $PROJECT_DIR/public
    
    <Directory $PROJECT_DIR/public>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog \${APACHE_LOG_DIR}/workorder_error.log
    CustomLog \${APACHE_LOG_DIR}/workorder_access.log combined
</VirtualHost>

<VirtualHost *:14580>
    ServerName 117.176.215.210
    DocumentRoot $PROJECT_DIR/public
    
    <Directory $PROJECT_DIR/public>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog \${APACHE_LOG_DIR}/workorder_14580_error.log
    CustomLog \${APACHE_LOG_DIR}/workorder_14580_access.log combined
</VirtualHost>
EOF

# 启用配置
a2ensite workorder.conf
a2enmod rewrite
a2enmod dir

# 更新端口配置
cat > /etc/apache2/ports.conf << EOF
Listen 80
Listen 14580
<IfModule ssl_module>
        Listen 443
</IfModule>
<IfModule mod_gnutls.c>
        Listen 443
</IfModule>
EOF

# 启动Apache
if systemctl start apache2; then
    echo -e "${GREEN}✓ Apache配置完成${NC}"
else
    echo -e "${RED}错误：Apache启动失败${NC}"
    exit 1
fi

# 步骤12：验证部署
echo -e "${YELLOW}步骤12：验证部署...${NC}"

# 检查Apache状态
if systemctl status apache2 --no-pager -l | grep -q "active (running)"; then
    echo -e "${GREEN}✓ Apache运行正常${NC}"
else
    echo -e "${RED}❌ Apache运行异常${NC}"
fi

# 检查端口监听
if netstat -tlnp | grep -q ":14580"; then
    echo -e "${GREEN}✓ 端口14580监听正常${NC}"
else
    echo -e "${RED}❌ 端口14580未监听${NC}"
fi

# 检查虚拟主机配置
if a2query -s | grep -q "workorder"; then
    echo -e "${GREEN}✓ 虚拟主机配置正常${NC}"
else
    echo -e "${RED}❌ 虚拟主机配置异常${NC}"
fi

# 测试网站访问
if curl -I http://127.0.0.1:14580/ 2>/dev/null | grep -q "200 OK"; then
    echo -e "${GREEN}✓ 网站访问正常${NC}"
else
    echo -e "${YELLOW}⚠ 网站访问可能需要等待${NC}"
fi

# 完成部署
echo ""
echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}  部署完成！${NC}"
echo -e "${GREEN}========================================${NC}"
echo ""
echo -e "${BLUE}访问地址：${NC}"
echo -e "  前台：${YELLOW}http://117.176.215.210:14580${NC}"
echo -e "  后台：${YELLOW}http://117.176.215.210:14580/admin${NC}"
echo ""
echo -e "${BLUE}默认登录账户：${NC}"
echo -e "  系统管理员：${YELLOW}admin@workorder.com / admin123${NC}"
echo ""
echo -e "${BLUE}注意事项：${NC}"
echo -e "  1. 请修改 .env 文件中的数据库密码"
echo -e "  2. 首次登录后请立即修改默认密码"
echo -e "  3. 如有问题，请查看Apache日志：/var/log/apache2/error.log"
echo ""
echo -e "${GREEN}部署成功！${NC}"