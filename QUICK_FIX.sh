#!/bin/bash

# 快速修复脚本
# 专门解决数据库种子数据问题

set -e

# 颜色定义
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}  快速修复工具${NC}"
echo -e "${BLUE}========================================${NC}"

# 从.env文件读取数据库配置
if [ -f ".env" ]; then
    DB_HOST=$(grep "DB_HOST" .env | cut -d'=' -f2)
    DB_PORT=$(grep "DB_PORT" .env | cut -d'=' -f2)
    DB_DATABASE=$(grep "DB_DATABASE" .env | cut -d'=' -f2)
    DB_USERNAME=$(grep "DB_USERNAME" .env | cut -d'=' -f2)
    DB_PASSWORD=$(grep "DB_PASSWORD" .env | cut -d'=' -f2)
    
    echo -e "${GREEN}数据库配置:${NC}"
    echo -e "${YELLOW}用户名: ${DB_USERNAME}${NC}"
    echo -e "${YELLOW}密码: ${DB_PASSWORD}${NC}"
    echo -e "${YELLOW}数据库: ${DB_DATABASE}${NC}"
    echo ""
else
    echo -e "${RED}错误: 未找到.env文件${NC}"
    exit 1
fi

# 添加缺失的字段
echo -e "${YELLOW}添加缺失字段...${NC}"
mysql -u "${DB_USERNAME}" -p"${DB_PASSWORD}" "${DB_DATABASE}" -e "
ALTER TABLE departments ADD COLUMN IF NOT EXISTS parent_id INT NULL;
ALTER TABLE departments ADD COLUMN IF NOT EXISTS level INT DEFAULT 1;
" 2>/dev/null || echo -e "${YELLOW}字段可能已存在${NC}"

# 清理并重新插入数据
echo -e "${YELLOW}重新插入种子数据...${NC}"
mysql -u "${DB_USERNAME}" -p"${DB_PASSWORD}" "${DB_DATABASE}" -e "
DELETE FROM departments;
DELETE FROM workorder_types;

INSERT INTO departments (name, code, description, manager_name, manager_phone, location, status, sort_order, parent_id, level, created_at, updated_at) VALUES
('信息技术部', 'IT', '负责信息技术系统和网络管理', 'IT经理', '13800138000', '总部办公楼', 'active', 1, NULL, 1, NOW(), NOW()),
('行政部', 'ADMIN', '负责行政管理和后勤保障', '行政经理', '13800138001', '总部办公楼', 'active', 2, NULL, 1, NOW(), NOW()),
('财务部', 'FINANCE', '负责财务管理和会计核算', '财务经理', '13800138002', '总部办公楼', 'active', 3, NULL, 1, NOW(), NOW()),
('人力资源部', 'HR', '负责人力资源管理', 'HR经理', '13800138003', '总部办公楼', 'active', 4, NULL, 1, NOW(), NOW()),
('运营部', 'OPERATIONS', '负责日常运营管理', '运营经理', '13800138004', '总部办公楼', 'active', 5, NULL, 1, NOW(), NOW());
" 2>/dev/null

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ 种子数据插入成功${NC}"
else
    echo -e "${RED}✗ 种子数据插入失败${NC}"
    exit 1
fi

# 验证数据
echo -e "${YELLOW}验证数据...${NC}"
DEPT_COUNT=$(mysql -u "${DB_USERNAME}" -p"${DB_PASSWORD}" "${DB_DATABASE}" -e "SELECT COUNT(*) FROM departments;" 2>/dev/null)
echo -e "${GREEN}部门数量: ${DEPT_COUNT}${NC}"

echo ""
echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}  快速修复完成！${NC}"
echo -e "${GREEN}========================================${NC}"
echo ""
echo -e "${YELLOW}现在可以继续部署:${NC}"
echo -e "${BLUE}1. 验证部署: ./verify_deployment.sh${NC}"
echo -e "${BLUE}2. 启动应用: php artisan serve --host=0.0.0.0 --port=8000${NC}"
echo ""
echo -e "${GREEN}🎉 数据库问题已解决！${NC}"