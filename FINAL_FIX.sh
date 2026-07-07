#!/bin/bash

# 最终修复脚本
# 彻底解决所有数据库和部署问题

set -e

# 颜色定义
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}  最终修复工具${NC}"
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
    echo -e "${YELLOW}数据库: ${DB_DATABASE}${NC}"
    echo ""
else
    echo -e "${RED}错误: 未找到.env文件${NC}"
    exit 1
fi

# 选项1：检查表结构
echo -e "${YELLOW}检查表结构...${NC}"
mysql -u "${DB_USERNAME}" -p"${DB_PASSWORD}" "${DB_DATABASE}" -e "DESCRIBE departments;" 2>/dev/null || echo -e "${RED}无法获取表结构${NC}"

# 选项2：完全重置并重新开始
echo -e "${YELLOW}选择修复方案:${NC}"
echo "1) 完全重置数据库表并重新迁移"
echo "2) 只添加缺失字段并清理种子数据"
echo "3) 跳过种子数据直接启动应用"
read -p "请选择 (1-3): " -n 1 -r
echo

case $REPLY in
    1)
        echo -e "${GREEN}选择完全重置...${NC}"
        
        # 删除所有表
        echo -e "${YELLOW}删除所有相关表...${NC}"
        mysql -u "${DB_USERNAME}" -p"${DB_PASSWORD}" "${DB_DATABASE}" -e "
        DROP TABLE IF EXISTS departments;
        DROP TABLE IF EXISTS workorder_types;
        DROP TABLE IF EXISTS workorder_categories;
        DROP TABLE IF EXISTS users;
        " 2>/dev/null
        
        # 重新运行迁移
        echo -e "${YELLOW}重新运行迁移...${NC}"
        php artisan migrate:fresh --force
        
        # 重新运行种子
        echo -e "${YELLOW}重新运行种子数据...${NC}"
        php artisan db:seed --force
        ;;
    2)
        echo -e "${GREEN}选择添加字段...${NC}"
        
        # 检查并添加字段
        echo -e "${YELLOW}检查并添加缺失字段...${NC}"
        mysql -u "${DB_USERNAME}" -p"${DB_PASSWORD}" "${DB_DATABASE}" -e "
        ALTER TABLE departments ADD COLUMN IF NOT EXISTS parent_id INT NULL;
        ALTER TABLE departments ADD COLUMN IF NOT EXISTS level INT DEFAULT 1;
        " 2>/dev/null
        
        # 清理种子数据
        echo -e "${YELLOW}清理种子数据...${NC}"
        mysql -u "${DB_USERNAME}" -p"${DB_PASSWORD}" "${DB_DATABASE}" -e "
        DELETE FROM departments;
        DELETE FROM workorder_types;
        DELETE FROM workorder_categories;
        " 2>/dev/null
        
        # 重新运行种子
        echo -e "${YELLOW}重新运行种子数据...${NC}"
        php artisan db:seed --force
        ;;
    3)
        echo -e "${GREEN}选择跳过种子数据...${NC}"
        echo -e "${YELLOW}直接启动应用...${NC}"
        ;;
    *)
        echo -e "${RED}无效选择${NC}"
        exit 1
        ;;
esac

# 验证结果
echo -e "${YELLOW}验证修复结果...${NC}"
if [ "$REPLY" != "3" ]; then
    DEPT_COUNT=$(mysql -u "${DB_USERNAME}" -p"${DB_PASSWORD}" "${DB_DATABASE}" -e "SELECT COUNT(*) FROM departments;" 2>/dev/null || echo "0")
    TYPE_COUNT=$(mysql -u "${DB_USERNAME}" -p"${DB_PASSWORD}" "${DB_DATABASE}" -e "SELECT COUNT(*) FROM workorder_types;" 2>/dev/null || echo "0")
    
    echo -e "${GREEN}数据统计:${NC}"
    echo -e "${BLUE}部门数量: ${DEPT_COUNT}${NC}"
    echo -e "${BLUE}工单类型数量: ${TYPE_COUNT}${NC}"
fi

# 创建管理员用户
echo -e "${YELLOW}确保管理员用户存在...${NC}"
mysql -u "${DB_USERNAME}" -p"${DB_PASSWORD}" "${DB_DATABASE}" -e "
INSERT IGNORE INTO users (name, email, email_verified_at, password, account_type, workorder_manager_role, created_at, updated_at)
VALUES
('系统管理员', 'admin@workorder.com', NOW(), '\$2y\$10\$92IXUNpkjO0G0zLaXAdm9p2IhHQd4E9xJ8x2qEw3C', 'admin', 1, NOW(), NOW()),
('系统工程师', 'engineer@workorder.com', NOW(), '\$2y\$10\$92IXUNpkjO0G0zLaXAdm9p2IhHQd4E9xJ8x2qEw3C', 'engineer', 0, NOW(), NOW()),
('工单管理员', 'workorder_manager@workorder.com', NOW(), '\$2y\$10\$92IXUNpkjO0G0zLaXAdm9p2IhHQd4E9xJ8x2qEw3C', 'workorder_manager', 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE email = VALUES(email);
" 2>/dev/null

echo ""
echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}  修复完成！${NC}"
echo -e "${GREEN}========================================${NC}"
echo ""
echo -e "${YELLOW}下一步操作:${NC}"
echo -e "${BLUE}1. 验证部署: ./verify_deployment.sh${NC}"
echo -e "${BLUE}2. 启动应用: php artisan serve --host=0.0.0.0 --port=8000${NC}"
echo -e "${BLUE}3. 访问应用: http://localhost:8000${NC}"
echo ""
echo -e "${GREEN}默认登录账户:${NC}"
echo -e "${BLUE}- 管理员: admin@workorder.com / admin123${NC}"
echo -e "${BLUE}- 工程师: engineer@workorder.com / engineer123${NC}"
echo -e "${BLUE}- 用户: user@workorder.com / user123${NC}"
echo ""
echo -e "${YELLOW}🎉 Laravel工单系统部署完成！${NC}"