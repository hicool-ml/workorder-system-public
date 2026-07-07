#!/bin/bash

# 快速数据库修复脚本
# 解决Laravel工单系统数据库连接问题

set -e

# 颜色定义
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}  快速数据库修复工具${NC}"
echo -e "${BLUE}========================================${NC}"

# 从.env文件读取数据库配置
if [ -f ".env" ]; then
    DB_HOST=$(grep "DB_HOST" .env | cut -d'=' -f2)
    DB_PORT=$(grep "DB_PORT" .env | cut -d'=' -f2)
    DB_DATABASE=$(grep "DB_DATABASE" .env | cut -d'=' -f2)
    DB_USERNAME=$(grep "DB_USERNAME" .env | cut -d'=' -f2)
    DB_PASSWORD=$(grep "DB_PASSWORD" .env | cut -d'=' -f2)
    
    echo -e "${GREEN}从.env文件读取到配置:${NC}"
    echo -e "${YELLOW}主机: ${DB_HOST}:${DB_PORT}${NC}"
    echo -e "${YELLOW}数据库: ${DB_DATABASE}${NC}"
    echo -e "${YELLOW}用户名: ${DB_USERNAME}${NC}"
    echo ""
else
    echo -e "${RED}错误: 未找到.env文件${NC}"
    exit 1
fi

# 检查MySQL服务状态
echo -e "${YELLOW}检查MySQL服务状态...${NC}"
if systemctl is-active --quiet mysql; then
    echo -e "${GREEN}✓ MySQL服务正在运行${NC}"
else
    echo -e "${RED}✗ MySQL服务未运行${NC}"
    echo -e "${YELLOW}启动MySQL服务...${NC}"
    sudo systemctl start mysql
    sleep 3
fi

# 测试root连接
echo -e "${YELLOW}测试MySQL root连接...${NC}"
if sudo mysql -u root -e "SELECT 1;" &>/dev/null; then
    echo -e "${GREEN}✓ 可以使用MySQL root权限${NC}"
else
    echo -e "${RED}✗ 无法使用MySQL root权限${NC}"
    echo -e "${YELLOW}请检查MySQL root密码或权限${NC}"
    exit 1
fi

# 创建数据库
echo -e "${YELLOW}创建数据库 ${DB_DATABASE}...${NC}"
if sudo mysql -u root -e "CREATE DATABASE IF NOT EXISTS \`${DB_DATABASE}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>/dev/null; then
    echo -e "${GREEN}✓ 数据库创建成功${NC}"
else
    echo -e "${RED}✗ 数据库创建失败${NC}"
    exit 1
fi

# 创建用户（如果不存在）
echo -e "${YELLOW}创建用户 ${DB_USERNAME}...${NC}"
if sudo mysql -u root -e "CREATE USER IF NOT EXISTS '${DB_USERNAME}'@'localhost' IDENTIFIED BY '${DB_PASSWORD}';" 2>/dev/null; then
    echo -e "${GREEN}✓ 用户创建成功${NC}"
else
    echo -e "${RED}✗ 用户创建失败${NC}"
    exit 1
fi

# 授权用户
echo -e "${YELLOW}授权用户访问数据库...${NC}"
if sudo mysql -u root -e "GRANT ALL PRIVILEGES ON \`${DB_DATABASE}\`.* TO '${DB_USERNAME}'@'localhost';" 2>/dev/null; then
    echo -e "${GREEN}✓ 用户授权成功${NC}"
else
    echo -e "${RED}✗ 用户授权失败${NC}"
    exit 1
fi

# 刷新权限
echo -e "${YELLOW}刷新权限...${NC}"
if sudo mysql -u root -e "FLUSH PRIVILEGES;" 2>/dev/null; then
    echo -e "${GREEN}✓ 权限刷新成功${NC}"
else
    echo -e "${RED}✗ 权限刷新失败${NC}"
    exit 1
fi

# 测试用户连接
echo -e "${YELLOW}测试用户数据库连接...${NC}"
if mysql -u "${DB_USERNAME}" -p"${DB_PASSWORD}" -e "USE \`${DB_DATABASE}\`; SELECT 1;" 2>/dev/null; then
    echo -e "${GREEN}✓ 用户数据库连接成功${NC}"
else
    echo -e "${RED}✗ 用户数据库连接失败${NC}"
    echo -e "${YELLOW}调试信息:${NC}"
    echo -e "${YELLOW}尝试手动连接: mysql -u ${DB_USERNAME} -p${DB_PASSWORD} ${DB_DATABASE}${NC}"
    exit 1
fi

# 导入数据库（如果存在database.sql）
if [ -f "database.sql" ]; then
    echo -e "${YELLOW}发现数据库文件，是否导入？(y/N): ${NC}"
    read -r response
    if [[ "$response" =~ ^[Yy]$ ]]; then
        echo -e "${YELLOW}导入数据库...${NC}"
        if mysql -u "${DB_USERNAME}" -p"${DB_PASSWORD}" "${DB_DATABASE}" < database.sql; then
            echo -e "${GREEN}✓ 数据库导入成功${NC}"
        else
            echo -e "${RED}✗ 数据库导入失败${NC}"
            echo -e "${YELLOW}您可以稍后手动导入: mysql -u ${DB_USERNAME} -p ${DB_DATABASE} < database.sql${NC}"
        fi
    fi
fi

echo ""
echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}  数据库修复完成！${NC}"
echo -e "${GREEN}========================================${NC}"
echo ""
echo -e "${YELLOW}现在可以重新运行部署脚本:${NC}"
echo -e "${BLUE}./auto_deploy.sh -e production -v${NC}"
echo ""
echo -e "${YELLOW}或者跳过数据库备份直接部署:${NC}"
echo -e "${BLUE}1. 编辑 deploy_config.json${NC}"
echo -e "${BLUE}2. 将 database_backup 设置为 false${NC}"
echo -e "${BLUE}3. 重新运行 ./auto_deploy.sh -e production -v${NC}"