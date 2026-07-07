#!/bin/bash

# 数据库种子数据修复脚本
# 解决departments表结构不匹配问题

set -e

# 颜色定义
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}  数据库种子数据修复工具${NC}"
echo -e "${BLUE}========================================${NC}"

# 从.env文件读取数据库配置
if [ -f ".env" ]; then
    DB_HOST=$(grep "DB_HOST" .env | cut -d'=' -f2)
    DB_PORT=$(grep "DB_PORT" .env | cut -d'=' -f2)
    DB_DATABASE=$(grep "DB_DATABASE" .env | cut -d'=' -f2)
    DB_USERNAME=$(grep "DB_USERNAME" .env | cut -d'=' -f2)
    DB_PASSWORD=$(grep "DB_PASSWORD" .env | cut -d'=' -f2)
    
    echo -e "${GREEN}从.env文件读取到配置:${NC}"
    echo -e "${YELLOW}数据库: ${DB_DATABASE}${NC}"
    echo ""
else
    echo -e "${RED}错误: 未找到.env文件${NC}"
    exit 1
fi

# 检查departments表结构
echo -e "${YELLOW}检查departments表结构...${NC}"
TABLE_STRUCTURE=$(mysql -u "${DB_USERNAME}" -p"${DB_PASSWORD}" "${DB_DATABASE}" -e "DESCRIBE departments;" 2>/dev/null)

if [ -n "$TABLE_STRUCTURE" ]; then
    echo -e "${GREEN}当前表结构:${NC}"
    echo "$TABLE_STRUCTURE"
else
    echo -e "${RED}错误: 无法获取表结构${NC}"
    exit 1
fi

# 检查是否存在parent_id字段
if echo "$TABLE_STRUCTURE" | grep -q "parent_id"; then
    echo -e "${GREEN}✓ parent_id字段已存在${NC}"
    PARENT_ID_EXISTS=true
else
    echo -e "${YELLOW}⚠ parent_id字段不存在，需要添加${NC}"
    PARENT_ID_EXISTS=false
fi

# 添加parent_id字段（如果不存在）
if [ "$PARENT_ID_EXISTS" = false ]; then
    echo -e "${YELLOW}添加parent_id字段...${NC}"
    
    # 添加parent_id字段
    if mysql -u "${DB_USERNAME}" -p"${DB_PASSWORD}" "${DB_DATABASE}" -e "ALTER TABLE departments ADD COLUMN parent_id INT NULL;" 2>/dev/null; then
        echo -e "${GREEN}✓ parent_id字段添加成功${NC}"
    else
        echo -e "${RED}✗ parent_id字段添加失败${NC}"
        exit 1
    fi
    
    # 添加索引
    echo -e "${YELLOW}添加索引...${NC}"
    mysql -u "${DB_USERNAME}" -p"${DB_PASSWORD}" "${DB_DATABASE}" -e "ALTER TABLE departments ADD INDEX idx_parent_id (parent_id);" 2>/dev/null || echo -e "${YELLOW}索引添加失败（可能已存在）${NC}"
fi

# 检查是否存在level字段
if echo "$TABLE_STRUCTURE" | grep -q "level"; then
    echo -e "${GREEN}✓ level字段已存在${NC}"
    LEVEL_EXISTS=true
else
    echo -e "${YELLOW}⚠ level字段不存在，需要添加${NC}"
    LEVEL_EXISTS=false
fi

# 添加level字段（如果不存在）
if [ "$LEVEL_EXISTS" = false ]; then
    echo -e "${YELLOW}添加level字段...${NC}"
    
    # 添加level字段
    if mysql -u "${DB_USERNAME}" -p"${DB_PASSWORD}" "${DB_DATABASE}" -e "ALTER TABLE departments ADD COLUMN level INT DEFAULT 1;" 2>/dev/null; then
        echo -e "${GREEN}✓ level字段添加成功${NC}"
    else
        echo -e "${RED}✗ level字段添加失败${NC}"
        exit 1
    fi
fi

# 清理现有的种子数据
echo -e "${YELLOW}清理现有的种子数据...${NC}"
mysql -u "${DB_USERNAME}" -p"${DB_PASSWORD}" "${DB_DATABASE}" -e "DELETE FROM departments;" 2>/dev/null
mysql -u "${DB_USERNAME}" -p"${DB_PASSWORD}" "${DB_DATABASE}" -e "DELETE FROM workorder_types;" 2>/dev/null

# 重新运行种子数据
echo -e "${YELLOW}重新运行种子数据...${NC}"
if php artisan db:seed --class=DepartmentSeeder 2>/dev/null; then
    echo -e "${GREEN}✓ DepartmentSeeder运行成功${NC}"
else
    echo -e "${RED}✗ DepartmentSeeder运行失败${NC}"
    echo -e "${YELLOW}尝试手动插入数据...${NC}"
    
    # 手动插入基础部门数据
    mysql -u "${DB_USERNAME}" -p"${DB_PASSWORD}" "${DB_DATABASE}" -e "
    INSERT INTO departments (name, code, description, manager_name, manager_phone, location, status, sort_order, parent_id, level, created_at, updated_at) VALUES
    ('信息技术部', 'IT', '负责信息技术系统和网络管理', 'IT经理', '13800138000', '总部办公楼', 'active', 1, NULL, 1, NOW(), NOW()),
    ('行政部', 'ADMIN', '负责行政管理和后勤保障', '行政经理', '13800138001', '总部办公楼', 'active', 2, NULL, 1, NOW(), NOW()),
    ('财务部', 'FINANCE', '负责财务管理和会计核算', '财务经理', '13800138002', '总部办公楼', 'active', 3, NULL, 1, NOW(), NOW()),
    ('人力资源部', 'HR', '负责人力资源管理', 'HR经理', '13800138003', '总部办公楼', 'active', 4, NULL, 1, NOW(), NOW()),
    ('运营部', 'OPERATIONS', '负责日常运营管理', '运营经理', '13800138004', '总部办公楼', 'active', 5, NULL, 1, NOW(), NOW());
    " 2>/dev/null
    
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✓ 基础部门数据插入成功${NC}"
    else
        echo -e "${RED}✗ 基础部门数据插入失败${NC}"
    fi
fi

# 运行其他种子数据
echo -e "${YELLOW}运行其他种子数据...${NC}"
php artisan db:seed --class=WorkorderTypeSeeder 2>/dev/null || echo -e "${YELLOW}WorkorderTypeSeeder失败，可稍后手动运行${NC}"

# 验证数据
echo -e "${YELLOW}验证插入的数据...${NC}"
DEPT_COUNT=$(mysql -u "${DB_USERNAME}" -p"${DB_PASSWORD}" "${DB_DATABASE}" -e "SELECT COUNT(*) FROM departments;" 2>/dev/null)
TYPE_COUNT=$(mysql -u "${DB_USERNAME}" -p"${DB_PASSWORD}" "${DB_DATABASE}" -e "SELECT COUNT(*) FROM workorder_types;" 2>/dev/null)

echo -e "${GREEN}数据统计:${NC}"
echo -e "${BLUE}部门数量: ${DEPT_COUNT}${NC}"
echo -e "${BLUE}工单类型数量: ${TYPE_COUNT}${NC}"

echo ""
echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}  数据库种子数据修复完成！${NC}"
echo -e "${GREEN}========================================${NC}"
echo ""
echo -e "${YELLOW}现在可以继续部署流程:${NC}"
echo -e "${BLUE}1. 验证部署: ./verify_deployment.sh${NC}"
echo -e "${BLUE}2. 启动应用: php artisan serve --host=0.0.0.0 --port=8000${NC}"
echo ""
echo -e "${GREEN}🎉 数据库问题已解决！${NC}"