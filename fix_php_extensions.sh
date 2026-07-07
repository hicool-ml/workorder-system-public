#!/bin/bash

# PHP扩展修复脚本
# 解决Ubuntu系统中PHP扩展包不存在的问题

set -e

# 颜色定义
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}  PHP扩展修复工具${NC}"
echo -e "${BLUE}========================================${NC}"

# 检查PHP版本
if ! command -v php &> /dev/null; then
    echo -e "${RED}错误: PHP未安装${NC}"
    exit 1
fi

PHP_VERSION=$(php -r "echo PHP_VERSION;")
echo -e "${GREEN}当前PHP版本: ${PHP_VERSION}${NC}"

# 检测操作系统
if [ -f /etc/os-release ]; then
    . /etc/os-release
    OS=$NAME
else
    OS=$(uname -s)
fi
echo -e "${GREEN}操作系统: ${OS}${NC}"

# 修复Ubuntu/Debian系统
fix_ubuntu_extensions() {
    echo -e "${YELLOW}修复Ubuntu/Debian系统的PHP扩展...${NC}"
    
    # 更新包列表
    sudo apt update
    
    # 安装核心PHP包（不包含可能不存在的扩展）
    echo -e "${GREEN}安装核心PHP包...${NC}"
    sudo apt install -y php8.3 php8.3-cli php8.3-fpm php8.3-mysql php8.3-pgsql \
        php8.3-mbstring php8.3-tokenizer php8.3-xml php8.3-ctype \
        php8.3-fileinfo php8.3-bcmath php8.3-gd php8.3-curl \
        php8.3-zip php8.3-dom php8.3-intl
    
    # 尝试安装可选扩展
    echo -e "${GREEN}尝试安装可选扩展...${NC}"
    sudo apt install -y php8.3-json 2>/dev/null || echo -e "${YELLOW}php8.3-json 可能已内置${NC}"
    sudo apt install -y php8.3-openssl 2>/dev/null || echo -e "${YELLOW}php8.3-openssl 可能已内置${NC}"
    sudo apt install -y php8.3-soap 2>/dev/null || echo -e "${YELLOW}php8.3-soap 安装失败${NC}"
    sudo apt install -y php8.3-imap 2>/dev/null || echo -e "${YELLOW}php8.3-imap 安装失败${NC}"
    sudo apt install -y php8.3-ldap 2>/dev/null || echo -e "${YELLOW}php8.3-ldap 安装失败${NC}"
    sudo apt install -y php8.3-xsl 2>/dev/null || echo -e "${YELLOW}php8.3-xsl 安装失败${NC}"
    
    # 验证关键扩展
    echo -e "${GREEN}验证关键扩展...${NC}"
    REQUIRED_EXTENSIONS="mbstring pdo_mysql tokenizer xml ctype fileinfo json bcmath openssl"
    
    for ext in $REQUIRED_EXTENSIONS; do
        if php -m | grep -q "$ext"; then
            echo -e "${GREEN}✓ $ext${NC}"
        else
            echo -e "${RED}✗ $ext${NC}"
        fi
    done
}

# 修复CentOS/RHEL系统
fix_centos_extensions() {
    echo -e "${YELLOW}修复CentOS/RHEL系统的PHP扩展...${NC}"
    
    if command -v dnf &> /dev/null; then
        sudo dnf install -y php83 php83-php-fpm php83-php-mysqlnd php83-php-pgsql \
            php83-php-mbstring php83-php-tokenizer php83-php-xml php83-php-ctype \
            php83-php-fileinfo php83-php-json php83-php-bcmath php83-php-openssl \
            php83-php-gd php83-php-curl php83-php-zip php83-php-dom php83-php-intl
    else
        sudo yum install -y php83 php83-php-fpm php83-php-mysqlnd php83-php-pgsql \
            php83-php-mbstring php83-php-tokenizer php83-php-xml php83-php-ctype \
            php83-php-fileinfo php83-php-json php83-php-bcmath php83-php-openssl \
            php83-php-gd php83-php-curl php83-php-zip php83-php-dom php83-php-intl
    fi
}

# 主修复逻辑
case $OS in
    "Ubuntu"* | "Debian"*)
        fix_ubuntu_extensions
        ;;
    "CentOS"* | "Red Hat"* | "Fedora"*)
        fix_centos_extensions
        ;;
    *)
        echo -e "${RED}不支持的操作系统: $OS${NC}"
        echo -e "${YELLOW}请手动安装PHP扩展${NC}"
        exit 1
        ;;
esac

echo ""
echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}  PHP扩展修复完成！${NC}"
echo -e "${GREEN}========================================${NC}"

# 显示PHP信息
echo -e "${YELLOW}PHP版本信息:${NC}"
php -v

echo ""
echo -e "${YELLOW}已安装的扩展:${NC}"
php -m | tr ' ' '\n' | grep -E "(mbstring|pdo|tokenizer|xml|ctype|fileinfo|json|bcmath|openssl|gd|curl|zip|dom|intl)" | sort

echo ""
echo -e "${BLUE}下一步操作:${NC}"
echo -e "${BLUE}1. 重启PHP-FPM: sudo systemctl restart php8.3-fpm${NC}"
echo -e "${BLUE}2. 重启Web服务器: sudo systemctl restart nginx 或 sudo systemctl restart apache2${NC}"
echo -e "${BLUE}3. 验证Laravel应用: php artisan --version${NC}"