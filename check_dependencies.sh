#!/bin/bash

# Laravel工单系统依赖检查脚本
# 用于验证目标服务器是否满足所有依赖要求

set -e

# 颜色定义
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}  Laravel工单系统依赖检查${NC}"
echo -e "${BLUE}========================================${NC}"
echo ""

# 检查结果统计
TOTAL_CHECKS=0
PASSED_CHECKS=0
FAILED_CHECKS=0

# 检查函数
check_command() {
    local command_name=$1
    local command_path=$2
    local min_version=${3:-"any"}
    local check_type=${4:-"command"}
    
    TOTAL_CHECKS=$((TOTAL_CHECKS + 1))
    
    if command -v "$command_path" &> /dev/null; then
        if [ "$check_type" = "version" ] && [ "$min_version" != "any" ]; then
            current_version=$($command_path --version 2>/dev/null | head -1 | grep -oE '[0-9]+\.[0-9]+' | head -1)
            if [ -n "$current_version" ]; then
                if printf '%s\n%s\n' "$min_version" "$current_version" | sort -V -C &>/dev/null; then
                    echo -e "${GREEN}✅ ${command_name}: ${current_version} (>= ${min_version})${NC}"
                    PASSED_CHECKS=$((PASSED_CHECKS + 1))
                else
                    echo -e "${RED}❌ ${command_name}: ${current_version} (< ${min_version})${NC}"
                    FAILED_CHECKS=$((FAILED_CHECKS + 1))
                fi
            else
                echo -e "${YELLOW}⚠️  ${command_name}: 版本检测失败${NC}"
                FAILED_CHECKS=$((FAILED_CHECKS + 1))
            fi
        else
            echo -e "${GREEN}✅ ${command_name}: 已安装${NC}"
            PASSED_CHECKS=$((PASSED_CHECKS + 1))
        fi
    else
        echo -e "${RED}❌ ${command_name}: 未安装${NC}"
        FAILED_CHECKS=$((FAILED_CHECKS + 1))
    fi
}

# 检查PHP扩展
check_php_extension() {
    local extension_name=$1
    local is_required=${2:-"required"}
    
    TOTAL_CHECKS=$((TOTAL_CHECKS + 1))
    
    if php -m | grep -q "$extension_name"; then
        echo -e "${GREEN}✅ PHP扩展 ${extension_name}: 已安装${NC}"
        PASSED_CHECKS=$((PASSED_CHECKS + 1))
    else
        if [ "$is_required" = "required" ]; then
            echo -e "${RED}❌ PHP扩展 ${extension_name}: 未安装 (必需)${NC}"
            FAILED_CHECKS=$((FAILED_CHECKS + 1))
        else
            echo -e "${YELLOW}⚠️  PHP扩展 ${extension_name}: 未安装 (可选)${NC}"
            TOTAL_CHECKS=$((TOTAL_CHECKS - 1))  # 可选扩展不计入总数
        fi
    fi
}

# 检查系统服务
check_service() {
    local service_name=$1
    local display_name=${2:-$service_name}
    
    TOTAL_CHECKS=$((TOTAL_CHECKS + 1))
    
    if systemctl is-active --quiet "$service_name"; then
        echo -e "${GREEN}✅ 服务 ${display_name}: 运行中${NC}"
        PASSED_CHECKS=$((PASSED_CHECKS + 1))
    elif systemctl is-enabled --quiet "$service_name"; then
        echo -e "${YELLOW}⚠️  服务 ${display_name}: 已启用但未运行${NC}"
        FAILED_CHECKS=$((FAILED_CHECKS + 1))
    else
        echo -e "${RED}❌ 服务 ${display_name}: 未安装或未启用${NC}"
        FAILED_CHECKS=$((FAILED_CHECKS + 1))
    fi
}

# 检查端口
check_port() {
    local port=$1
    local service_name=$2
    
    TOTAL_CHECKS=$((TOTAL_CHECKS + 1))
    
    if netstat -tuln 2>/dev/null | grep -q ":$port "; then
        echo -e "${GREEN}✅ 端口 ${port} (${service_name}): 开放${NC}"
        PASSED_CHECKS=$((PASSED_CHECKS + 1))
    else
        echo -e "${YELLOW}⚠️  端口 ${port} (${service_name}): 未开放${NC}"
        FAILED_CHECKS=$((FAILED_CHECKS + 1))
    fi
}

echo -e "${CYAN}=== 系统信息 ===${NC}"
echo -e "${YELLOW}操作系统: $(uname -s) $(uname -r)${NC}"
echo -e "${YELLOW}架构: $(uname -m)${NC}"
if [ -f /etc/os-release ]; then
    . /etc/os-release
    echo -e "${YELLOW}发行版: $NAME $VERSION${NC}"
fi
echo ""

echo -e "${CYAN}=== 核心依赖检查 ===${NC}"
check_command "PHP" "php" "8.2" "version"
check_command "Composer" "composer" "2.0" "version"
check_command "Node.js" "node" "18.0" "version"
check_command "NPM" "npm" "8.0" "version"

echo ""
echo -e "${CYAN}=== PHP扩展检查 ===${NC}"
if command -v php &> /dev/null; then
    # 必需扩展
    check_php_extension "mbstring" "required"
    check_php_extension "pdo_mysql" "required"
    check_php_extension "tokenizer" "required"
    check_php_extension "xml" "required"
    check_php_extension "ctype" "required"
    check_php_extension "fileinfo" "required"
    check_php_extension "json" "required"
    check_php_extension "bcmath" "required"
    check_php_extension "openssl" "required"
    
    # 推荐扩展
    check_php_extension "gd" "optional"
    check_php_extension "curl" "optional"
    check_php_extension "zip" "optional"
    check_php_extension "dom" "optional"
    check_php_extension "intl" "optional"
else
    echo -e "${RED}❌ PHP未安装，跳过扩展检查${NC}"
fi

echo ""
echo -e "${CYAN}=== Web服务器检查 ===${NC}"
check_command "Nginx" "nginx"
check_command "Apache" "apache2ctl"
check_service "nginx" "Nginx"
check_service "apache2" "Apache2"

echo ""
echo -e "${CYAN}=== 数据库检查 ===${NC}"
check_command "MySQL" "mysql"
check_command "PostgreSQL" "psql"
check_command "SQLite3" "sqlite3"
check_service "mysql" "MySQL"
check_service "postgresql" "PostgreSQL"

echo ""
echo -e "${CYAN}=== 缓存服务检查 ===${NC}"
check_command "Redis" "redis-server"
check_service "redis-server" "Redis"

echo ""
echo -e "${CYAN}=== 网络端口检查 ===${NC}"
check_port "80" "HTTP"
check_port "443" "HTTPS"
check_port "22" "SSH"
check_port "3306" "MySQL"
check_port "5432" "PostgreSQL"
check_port "6379" "Redis"

echo ""
echo -e "${CYAN}=== 磁盘空间检查 ===${NC}"
TOTAL_CHECKS=$((TOTAL_CHECKS + 1))
AVAILABLE_SPACE=$(df -BG / | awk 'NR==2 {print $4}' | sed 's/G//')
if [ "$AVAILABLE_SPACE" -ge 10 ]; then
    echo -e "${GREEN}✅ 可用磁盘空间: ${AVAILABLE_SPACE}GB (>= 10GB)${NC}"
    PASSED_CHECKS=$((PASSED_CHECKS + 1))
else
    echo -e "${RED}❌ 可用磁盘空间: ${AVAILABLE_SPACE}GB (< 10GB)${NC}"
    FAILED_CHECKS=$((FAILED_CHECKS + 1))
fi

echo ""
echo -e "${CYAN}=== 内存检查 ===${NC}"
TOTAL_CHECKS=$((TOTAL_CHECKS + 1))
TOTAL_MEM=$(free -m | awk 'NR==2{print $2}')
if [ "$TOTAL_MEM" -ge 2048 ]; then
    echo -e "${GREEN}✅ 总内存: ${TOTAL_MEM}MB (>= 2GB)${NC}"
    PASSED_CHECKS=$((PASSED_CHECKS + 1))
else
    echo -e "${RED}❌ 总内存: ${TOTAL_MEM}MB (< 2GB)${NC}"
    FAILED_CHECKS=$((FAILED_CHECKS + 1))
fi

echo ""
echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}  检查结果摘要${NC}"
echo -e "${BLUE}========================================${NC}"
echo -e "${YELLOW}总检查项: ${TOTAL_CHECKS}${NC}"
echo -e "${GREEN}通过: ${PASSED_CHECKS}${NC}"
echo -e "${RED}失败: ${FAILED_CHECKS}${NC}"

if [ $FAILED_CHECKS -eq 0 ]; then
    echo -e "${GREEN}🎉 所有依赖检查通过！可以开始部署。${NC}"
    exit 0
else
    echo -e "${RED}❌ 发现 ${FAILED_CHECKS} 个问题，请先解决这些依赖问题。${NC}"
    echo ""
    echo -e "${YELLOW}建议解决方案:${NC}"
    echo -e "${YELLOW}1. 运行环境准备脚本: ./setup_server.sh${NC}"
    echo -e "${YELLOW}2. 手动安装缺失的依赖${NC}"
    echo -e "${YELLOW}3. 启动必要的服务: sudo systemctl start <service>${NC}"
    exit 1
fi