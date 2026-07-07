#!/bin/bash

# Ubuntu Server 24 环境配置脚本
# 用于配置校园网工单系统的运行环境

set -e

# 颜色定义
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}  Ubuntu Server 24 环境配置脚本${NC}"
echo -e "${BLUE}  校园网工单系统专用${NC}"
echo -e "${BLUE}========================================${NC}"
echo ""

# 检查是否为root用户
if [ "$EUID" -ne 0 ]; then
    echo -e "${RED}错误：请使用sudo运行此脚本${NC}"
    echo "用法：sudo bash $0"
    exit 1
fi

# 检查操作系统版本
echo -e "${YELLOW}检查操作系统版本...${NC}"
if ! grep -q "Ubuntu 24" /etc/os-release; then
    echo -e "${RED}错误：此脚本仅支持Ubuntu Server 24${NC}"
    exit 1
fi
echo -e "${GREEN}✓ 操作系统版本检查通过${NC}"

# 更新系统包
echo -e "${YELLOW}更新系统包...${NC}"
apt update && apt upgrade -y
echo -e "${GREEN}✓ 系统包更新完成${NC}"

# 安装基础工具
echo -e "${YELLOW}安装基础工具...${NC}"
apt install -y curl wget git unzip zip software-properties-common \
    ca-certificates apt-transport-https lsb-release gnupg
echo -e "${GREEN}✓ 基础工具安装完成${NC}"

# 安装PHP 8.3
echo -e "${YELLOW}安装PHP 8.3...${NC}"
apt install -y php8.3 php8.3-cli php8.3-fpm php8.3-mysql php8.3-pgsql \
    php8.3-mbstring php8.3-tokenizer php8.3-xml php8.3-ctype \
    php8.3-fileinfo php8.3-bcmath php8.3-gd php8.3-curl \
    php8.3-zip php8.3-dom php8.3-intl php8.3-soap php8.3-imap \
    php8.3-ldap php8.3-xsl php8.3-opcache

# 设置PHP配置
echo -e "${YELLOW}配置PHP...${NC}"
sed -i 's/memory_limit = 128M/memory_limit = 512M/' /etc/php/8.3/cli/php.ini
sed -i 's/upload_max_filesize = 2M/upload_max_filesize = 50M/' /etc/php/8.3/cli/php.ini
sed -i 's/post_max_size = 8M/post_max_size = 50M/' /etc/php/8.3/cli/php.ini
sed -i 's/max_execution_time = 30/max_execution_time = 300/' /etc/php/8.3/cli/php.ini

# 配置FPM
sed -i 's/memory_limit = 128M/memory_limit = 512M/' /etc/php/8.3/fpm/php.ini
sed -i 's/upload_max_filesize = 2M/upload_max_filesize = 50M/' /etc/php/8.3/fpm/php.ini
sed -i 's/post_max_size = 8M/post_max_size = 50M/' /etc/php/8.3/fpm/php.ini
sed -i 's/max_execution_time = 30/max_execution_time = 300/' /etc/php/8.3/fpm/php.ini

echo -e "${GREEN}✓ PHP 8.3安装和配置完成${NC}"

# 安装Apache2
echo -e "${YELLOW}安装Apache2...${NC}"
apt install -y apache2 libapache2-mod-php8.3

# 启用必要的Apache模块
a2enmod rewrite
a2enmod dir
a2enmod headers
a2enmod expires

echo -e "${GREEN}✓ Apache2安装和配置完成${NC}"

# 安装MySQL
echo -e "${YELLOW}安装MySQL...${NC}"
apt install -y mysql-server

# 启动MySQL服务
systemctl start mysql
systemctl enable mysql

# 安全配置MySQL（非交互式）
echo -e "${YELLOW}配置MySQL安全设置...${NC}"
mysql -e "DELETE FROM mysql.user WHERE User='';"
mysql -e "DELETE FROM mysql.user WHERE User='root' AND Host NOT IN ('localhost', '127.0.0.1', '::1');"
mysql -e "DROP DATABASE IF EXISTS test;"
mysql -e "DELETE FROM mysql.db WHERE Db='test' OR Db='test\\_%';"
mysql -e "FLUSH PRIVILEGES;"

echo -e "${GREEN}✓ MySQL安装和配置完成${NC}"

# 安装Composer
echo -e "${YELLOW}安装Composer...${NC}"
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
chmod +x /usr/local/bin/composer

echo -e "${GREEN}✓ Composer安装完成${NC}"

# 安装Node.js
echo -e "${YELLOW}安装Node.js...${NC}"
curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
apt-get install -y nodejs

echo -e "${GREEN}✓ Node.js安装完成${NC}"

# 创建Web目录
echo -e "${YELLOW}创建Web目录...${NC}"
mkdir -p /var/www/workorder
chown -R www-data:www-data /var/www/workorder
chmod -R 755 /var/www/workorder

echo -e "${GREEN}✓ Web目录创建完成${NC}"

# 配置Apache虚拟主机
echo -e "${YELLOW}配置Apache虚拟主机...${NC}"
cat > /etc/apache2/sites-available/workorder.conf << 'EOF'
<VirtualHost *:80>
    ServerName localhost
    DocumentRoot /var/www/workorder/public
    
    <Directory /var/www/workorder/public>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/workorder_error.log
    CustomLog ${APACHE_LOG_DIR}/workorder_access.log combined
</VirtualHost>
EOF

# 启用站点
a2ensite workorder.conf
a2dissite 000-default.conf

# 重启Apache
systemctl restart apache2
systemctl enable apache2

echo -e "${GREEN}✓ Apache虚拟主机配置完成${NC}"

# 创建数据库和用户
echo -e "${YELLOW}创建数据库和用户...${NC}"
mysql -e "CREATE DATABASE IF NOT EXISTS workorder_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -e "CREATE USER IF NOT EXISTS 'cdu'@'localhost' IDENTIFIED BY 'REDACTED_MYSQL_PASS';"
mysql -e "GRANT ALL PRIVILEGES ON workorder_db.* TO 'cdu'@'localhost';"
mysql -e "FLUSH PRIVILEGES;"

echo -e "${GREEN}✓ 数据库和用户创建完成${NC}"

# 配置防火墙
echo -e "${YELLOW}配置防火墙...${NC}"
ufw --force reset
ufw default deny incoming
ufw default allow outgoing
ufw allow ssh
ufw allow 'Apache Full'
ufw --force enable

echo -e "${GREEN}✓ 防火墙配置完成${NC}"

# 显示安装摘要
echo ""
echo -e "${BLUE}========================================${NC}"
echo -e "${GREEN}  环境配置完成！${NC}"
echo -e "${BLUE}========================================${NC}"
echo ""

echo -e "${YELLOW}已安装的组件版本:${NC}"
echo -e "${GREEN}PHP: $(php -r 'echo PHP_VERSION;')${NC}"
echo -e "${GREEN}Apache: $(apache2 -v | grep -o 'Apache/[0-9.]*')${NC}"
echo -e "${GREEN}MySQL: $(mysql --version | cut -d' ' -f1 | cut -d'-' -f1)${NC}"
echo -e "${GREEN}Composer: $(composer --version | cut -d' ' -f3)${NC}"
echo -e "${GREEN}Node.js: $(node --version)${NC}"

echo ""
echo -e "${YELLOW}数据库配置:${NC}"
echo -e "${GREEN}数据库名: workorder_db${NC}"
echo -e "${GREEN}用户名: cdu${NC}"
echo -e "${GREEN}密码: REDACTED_MYSQL_PASS${NC}"

echo ""
echo -e "${YELLOW}Web目录: /var/www/workorder${NC}"
echo -e "${YELLOW}Apache配置: /etc/apache2/sites-available/workorder.conf${NC}"

echo ""
echo -e "${GREEN}环境配置完成！现在可以部署项目了。${NC}"
echo -e "${BLUE}下一步：${NC}"
echo -e "1. 上传项目压缩包到服务器"
echo -e "2. 解压到 /var/www/workorder 目录"
echo -e "3. 运行部署脚本"