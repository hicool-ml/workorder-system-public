#!/bin/bash

# Laravel工单系统服务器环境准备脚本

set -e

# 颜色定义
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}  Laravel工单系统服务器环境准备${NC}"
echo -e "${BLUE}========================================${NC}"

# 检测操作系统
detect_os() {
    if [ -f /etc/os-release ]; then
        . /etc/os-release
        OS=$NAME
        VER=$VERSION_ID
    else
        OS=$(uname -s)
        VER=$(uname -r)
    fi
    echo -e "${GREEN}检测到操作系统: $OS $VER${NC}"
}

# 安装PHP和相关扩展
install_php() {
    echo -e "${YELLOW}安装PHP 8.3和相关扩展...${NC}"
    
    case $OS in
        "Ubuntu"* | "Debian"*)
            echo -e "${GREEN}使用APT包管理器安装...${NC}"
            sudo apt update
            sudo apt install -y software-properties-common ca-certificates apt-transport-https
            # 使用更稳定的PHP PPA源
            sudo add-apt-repository ppa:ondrej/php -y
            sudo apt update
            # 安装PHP核心包（移除可能不存在的包）
            sudo apt install -y php8.3 php8.3-cli php8.3-fpm php8.3-mysql php8.3-pgsql \
                php8.3-mbstring php8.3-tokenizer php8.3-xml php8.3-ctype \
                php8.3-fileinfo php8.3-bcmath php8.3-gd php8.3-curl \
                php8.3-zip php8.3-dom php8.3-intl
            
            # 尝试安装可能存在的扩展（忽略错误）
            echo -e "${YELLOW}尝试安装可选扩展...${NC}"
            sudo apt install -y php8.3-json php8.3-openssl || true
            
            # 安装常用扩展
            sudo apt install -y php8.3-soap php8.3-imap php8.3-ldap php8.3-xsl || true
            
            # 验证关键扩展是否可用
            echo -e "${YELLOW}验证PHP扩展...${NC}"
            php -m | grep -E "mbstring|pdo_mysql|tokenizer|xml|ctype|fileinfo|json|bcmath|openssl" || echo -e "${YELLOW}某些扩展可能已内置${NC}"
            ;;
        "CentOS"* | "Red Hat"* | "Fedora"*)
            echo -e "${GREEN}使用YUM/DNF包管理器安装...${NC}"
            if command -v dnf &> /dev/null; then
                sudo dnf install -y https://dl.fedoraproject.org/pub/epel/epel-release-latest-8.noarch.rpm
                sudo dnf install -y php83 php83-php-fpm php83-php-mysqlnd php83-php-pgsql \
                    php83-php-mbstring php83-php-tokenizer php83-php-xml php83-php-ctype \
                    php83-php-fileinfo php83-php-json php83-php-bcmath php83-php-openssl \
                    php83-php-gd php83-php-curl php83-php-zip php83-php-dom php83-php-intl
            else
                sudo yum install -y https://dl.fedoraproject.org/pub/epel/epel-release-latest-8.noarch.rpm
                sudo yum install -y php83 php83-php-fpm php83-php-mysqlnd php83-php-pgsql \
                    php83-php-mbstring php83-php-tokenizer php83-php-xml php83-php-ctype \
                    php83-php-fileinfo php83-php-json php83-php-bcmath php83-php-openssl \
                    php83-php-gd php83-php-curl php83-php-zip php83-php-dom php83-php-intl
            fi
            ;;
        *)
            echo -e "${RED}不支持的操作系统: $OS${NC}"
            echo -e "${YELLOW}请手动安装PHP 8.3和必要的扩展${NC}"
            exit 1
            ;;
    esac
}

# 安装Node.js
install_nodejs() {
    if ! command -v node &> /dev/null; then
        echo -e "${YELLOW}安装Node.js...${NC}"
        case $OS in
            "Ubuntu"* | "Debian"*)
                curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
                sudo apt-get install -y nodejs
                ;;
            "CentOS"* | "Red Hat"* | "Fedora"*)
                curl -fsSL https://rpm.nodesource.com/setup_20.x | sudo bash -
                if command -v dnf &> /dev/null; then
                    sudo dnf install -y nodejs
                else
                    sudo yum install -y nodejs
                fi
                ;;
            *)
                echo -e "${RED}不支持的操作系统，请手动安装Node.js${NC}"
                ;;
        esac
    else
        echo -e "${GREEN}Node.js已安装: $(node --version)${NC}"
    fi
}

# 安装Composer
install_composer() {
    if ! command -v composer &> /dev/null; then
        echo -e "${YELLOW}安装Composer...${NC}"
        curl -sS https://getcomposer.org/installer | php
        sudo mv composer.phar /usr/local/bin/composer
        sudo chmod +x /usr/local/bin/composer
        echo -e "${GREEN}Composer安装完成${NC}"
    else
        echo -e "${GREEN}Composer已安装: $(composer --version)${NC}"
    fi
}

# 安装Web服务器
install_webserver() {
    echo -e "${YELLOW}选择Web服务器:${NC}"
    echo "1) Nginx (推荐)"
    echo "2) Apache"
    echo "3) 跳过"
    read -p "请选择 (1-3): " -n 1 -r
    echo
    
    case $REPLY in
        1)
            install_nginx
            ;;
        2)
            install_apache
            ;;
        3)
            echo -e "${YELLOW}跳过Web服务器安装${NC}"
            ;;
        *)
            echo -e "${RED}无效选择${NC}"
            install_webserver
            ;;
    esac
}

# 安装Nginx
install_nginx() {
    echo -e "${YELLOW}安装Nginx...${NC}"
    case $OS in
        "Ubuntu"* | "Debian"*)
            sudo apt install -y nginx
            ;;
        "CentOS"* | "Red Hat"* | "Fedora"*)
            if command -v dnf &> /dev/null; then
                sudo dnf install -y nginx
            else
                sudo yum install -y nginx
            fi
            ;;
        *)
            echo -e "${RED}不支持的操作系统${NC}"
            ;;
    esac
    
    # 启动并设置开机自启
    sudo systemctl start nginx
    sudo systemctl enable nginx
    echo -e "${GREEN}Nginx安装并启动完成${NC}"
}

# 安装Apache
install_apache() {
    echo -e "${YELLOW}安装Apache...${NC}"
    case $OS in
        "Ubuntu"* | "Debian"*)
            sudo apt install -y apache2 libapache2-mod-php8.3
            sudo a2enmod php8.3
            ;;
        "CentOS"* | "Red Hat"* | "Fedora"*)
            if command -v dnf &> /dev/null; then
                sudo dnf install -y httpd
            else
                sudo yum install -y httpd
            fi
            ;;
        *)
            echo -e "${RED}不支持的操作系统${NC}"
            ;;
    esac
    
    # 启动并设置开机自启
    if command -v apache2ctl &> /dev/null; then
        sudo systemctl start apache2
        sudo systemctl enable apache2
        echo -e "${GREEN}Apache2安装并启动完成${NC}"
    else
        sudo systemctl start httpd
        sudo systemctl enable httpd
        echo -e "${GREEN}Apache安装并启动完成${NC}"
    fi
}

# 安装数据库
install_database() {
    echo -e "${YELLOW}选择数据库:${NC}"
    echo "1) MySQL (推荐)"
    echo "2) PostgreSQL"
    echo "3) SQLite (轻量级)"
    echo "4) 跳过"
    read -p "请选择 (1-4): " -n 1 -r
    echo
    
    case $REPLY in
        1)
            install_mysql
            ;;
        2)
            install_postgresql
            ;;
        3)
            echo -e "${GREEN}SQLite已内置支持${NC}"
            ;;
        4)
            echo -e "${YELLOW}跳过数据库安装${NC}"
            ;;
        *)
            echo -e "${RED}无效选择${NC}"
            install_database
            ;;
    esac
}

# 安装MySQL
install_mysql() {
    echo -e "${YELLOW}安装MySQL...${NC}"
    case $OS in
        "Ubuntu"* | "Debian"*)
            sudo apt update
            sudo apt install -y mysql-server
            ;;
        "CentOS"* | "Red Hat"* | "Fedora"*)
            if command -v dnf &> /dev/null; then
                sudo dnf install -y mysql-server
            else
                sudo yum install -y mysql-server
            fi
            ;;
        *)
            echo -e "${RED}不支持的操作系统${NC}"
            ;;
    esac
    
    # 启动并设置开机自启
    sudo systemctl start mysql
    sudo systemctl enable mysql
    echo -e "${GREEN}MySQL安装并启动完成${NC}"
    
    echo -e "${YELLOW}建议运行安全配置: sudo mysql_secure_installation${NC}"
}

# 安装PostgreSQL
install_postgresql() {
    echo -e "${YELLOW}安装PostgreSQL...${NC}"
    case $OS in
        "Ubuntu"* | "Debian"*)
            sudo apt update
            sudo apt install -y postgresql postgresql-contrib
            ;;
        "CentOS"* | "Red Hat"* | "Fedora"*)
            if command -v dnf &> /dev/null; then
                sudo dnf install -y postgresql-server postgresql-contrib
            else
                sudo yum install -y postgresql-server postgresql-contrib
            fi
            ;;
        *)
            echo -e "${RED}不支持的操作系统${NC}"
            ;;
    esac
    
    # 启动并设置开机自启
    sudo systemctl start postgresql
    sudo systemctl enable postgresql
    echo -e "${GREEN}PostgreSQL安装并启动完成${NC}"
}

# 安装Redis（可选）
install_redis() {
    echo -e "${YELLOW}是否安装Redis缓存服务器？(y/N): ${NC}"
    read -r response
    if [[ "$response" =~ ^[Yy]$ ]]; then
        echo -e "${YELLOW}安装Redis...${NC}"
        case $OS in
            "Ubuntu"* | "Debian"*)
                sudo apt install -y redis-server
                ;;
            "CentOS"* | "Red Hat"* | "Fedora"*)
                if command -v dnf &> /dev/null; then
                    sudo dnf install -y redis
                else
                    sudo yum install -y redis
                fi
                ;;
            *)
                echo -e "${RED}不支持的操作系统${NC}"
                ;;
        esac
        
        sudo systemctl start redis-server
        sudo systemctl enable redis-server
        echo -e "${GREEN}Redis安装并启动完成${NC}"
    fi
}

# 配置防火墙
configure_firewall() {
    echo -e "${YELLOW}是否配置防火墙？(y/N): ${NC}"
    read -r response
    if [[ "$response" =~ ^[Yy]$ ]]; then
        if command -v ufw &> /dev/null; then
            echo -e "${YELLOW}配置UFW防火墙...${NC}"
            sudo ufw --force reset
            sudo ufw default deny incoming
            sudo ufw default allow outgoing
            sudo ufw allow ssh
            sudo ufw allow 'Nginx Full'
            sudo ufw --force enable
            echo -e "${GREEN}UFW防火墙配置完成${NC}"
        elif command -v firewall-cmd &> /dev/null; then
            echo -e "${YELLOW}配置firewalld防火墙...${NC}"
            sudo firewall-cmd --permanent --add-service=ssh
            sudo firewall-cmd --permanent --add-service=http
            sudo firewall-cmd --permanent --add-service=https
            sudo firewall-cmd --reload
            echo -e "${GREEN}firewalld防火墙配置完成${NC}"
        else
            echo -e "${YELLOW}未找到支持的防火墙工具${NC}"
        fi
    fi
}

# 显示安装摘要
show_summary() {
    echo ""
    echo -e "${BLUE}========================================${NC}"
    echo -e "${GREEN}  环境准备完成！${NC}"
    echo -e "${BLUE}========================================${NC}"
    echo ""
    echo -e "${YELLOW}已安装的组件:${NC}"
    
    # 显示已安装的软件版本
    if command -v php &> /dev/null; then
        echo -e "${GREEN}PHP: $(php -r 'echo PHP_VERSION;')${NC}"
    fi
    
    if command -v node &> /dev/null; then
        echo -e "${GREEN}Node.js: $(node --version)${NC}"
    fi
    
    if command -v composer &> /dev/null; then
        echo -e "${GREEN}Composer: $(composer --version)${NC}"
    fi
    
    if command -v nginx &> /dev/null; then
        echo -e "${GREEN}Nginx: $(nginx -v 2>&1 | cut -d' ' -f3)${NC}"
    fi
    
    if command -v apache2ctl &> /dev/null; then
        echo -e "${GREEN}Apache: $(apache2ctl -v 2>&1 | cut -d' ' -f3 | cut -d' ' -f2)${NC}"
    fi
    
    if command -v mysql &> /dev/null; then
        echo -e "${GREEN}MySQL: $(mysql --version | cut -d' ' -f1)${NC}"
    fi
    
    if command -v psql &> /dev/null; then
        echo -e "${GREEN}PostgreSQL: $(psql --version | cut -d' ' -f3)${NC}"
    fi
    
    if command -v redis-server &> /dev/null; then
        echo -e "${GREEN}Redis: $(redis-server --version | cut -d' ' -f3 | cut -d'=' -f2)${NC}"
    fi
    
    echo ""
    echo -e "${YELLOW}下一步操作:${NC}"
    echo -e "${YELLOW}1. 上传Laravel项目包到服务器${NC}"
    echo -e "${YELLOW}2. 解压并运行部署脚本: ./auto_deploy.sh${NC}"
    echo -e "${YELLOW}3. 配置数据库连接: nano .env${NC}"
    echo -e "${YELLOW}4. 导入数据库: mysql -u username -p database_name < database.sql${NC}"
    echo ""
}

# 主函数
main() {
    # 检测操作系统
    detect_os
    
    # 安装PHP
    install_php
    
    # 安装Node.js
    install_nodejs
    
    # 安装Composer
    install_composer
    
    # 安装Web服务器
    install_webserver
    
    # 安装数据库
    install_database
    
    # 安装Redis（可选）
    install_redis
    
    # 配置防火墙（可选）
    configure_firewall
    
    # 显示摘要
    show_summary
}

# 运行主函数
main "$@"