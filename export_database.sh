#!/bin/bash

# Laravel工单系统数据库导出脚本
# 用于导出数据库结构和数据

set -e

# 颜色定义
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# 默认输出文件
OUTPUT_FILE="${1:-database_export_$(date +%Y%m%d_%H%M%S).sql}"

echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}  Laravel工单系统数据库导出工具${NC}"
echo -e "${BLUE}========================================${NC}"
echo -e "${YELLOW}输出文件: ${OUTPUT_FILE}${NC}"
echo ""

# 检查是否在Laravel项目根目录
if [ ! -f "artisan" ]; then
    echo -e "${RED}错误: 请在Laravel项目根目录下运行此脚本${NC}"
    exit 1
fi

# 获取数据库配置
echo -e "${GREEN}读取数据库配置...${NC}"

# 从.env文件读取数据库配置
if [ -f ".env" ]; then
    DB_CONNECTION=$(grep "DB_CONNECTION" .env | cut -d'=' -f2)
    DB_HOST=$(grep "DB_HOST" .env | cut -d'=' -f2)
    DB_PORT=$(grep "DB_PORT" .env | cut -d'=' -f2)
    DB_DATABASE=$(grep "DB_DATABASE" .env | cut -d'=' -f2)
    DB_USERNAME=$(grep "DB_USERNAME" .env | cut -d'=' -f2)
    DB_PASSWORD=$(grep "DB_PASSWORD" .env | cut -d'=' -f2)
else
    echo -e "${RED}错误: 未找到.env配置文件${NC}"
    exit 1
fi

# 设置默认值
DB_CONNECTION=${DB_CONNECTION:-mysql}
DB_HOST=${DB_HOST:-127.0.0.1}
DB_PORT=${DB_PORT:-3306}

echo -e "${YELLOW}数据库类型: ${DB_CONNECTION}${NC}"
echo -e "${YELLOW}主机地址: ${DB_HOST}:${DB_PORT}${NC}"
echo -e "${YELLOW}数据库名称: ${DB_DATABASE}${NC}"
echo -e "${YELLOW}用户名: ${DB_USERNAME}${NC}"

# 检查数据库连接
echo -e "${GREEN}检查数据库连接...${NC}"

case $DB_CONNECTION in
    "mysql")
        if ! command -v mysql &> /dev/null; then
            echo -e "${RED}错误: 未找到mysql命令行工具${NC}"
            exit 1
        fi
        
        # 测试连接
        if ! mysql -h${DB_HOST} -P${DB_PORT} -u${DB_USERNAME} -p${DB_PASSWORD} -e "USE ${DB_DATABASE};" 2>/dev/null; then
            echo -e "${RED}错误: 无法连接到数据库${NC}"
            echo -e "${YELLOW}请检查数据库配置和服务状态${NC}"
            exit 1
        fi
        ;;
    "sqlite")
        DB_DATABASE="database/${DB_DATABASE:-database.sqlite}"
        if [ ! -f "$DB_DATABASE" ]; then
            echo -e "${RED}错误: SQLite数据库文件不存在: ${DB_DATABASE}${NC}"
            exit 1
        fi
        ;;
    "pgsql")
        if ! command -v psql &> /dev/null; then
            echo -e "${RED}错误: 未找到psql命令行工具${NC}"
            exit 1
        fi
        
        # 测试连接
        if ! PGPASSWORD=${DB_PASSWORD} psql -h ${DB_HOST} -p ${DB_PORT} -U ${DB_USERNAME} -d ${DB_DATABASE} -c "SELECT 1;" &>/dev/null; then
            echo -e "${RED}错误: 无法连接到数据库${NC}"
            exit 1
        fi
        ;;
    *)
        echo -e "${RED}错误: 不支持的数据库类型: ${DB_CONNECTION}${NC}"
        exit 1
        ;;
esac

echo -e "${GREEN}数据库连接正常${NC}"

# 开始导出数据库
echo -e "${GREEN}开始导出数据库...${NC}"

# 创建临时文件
TEMP_FILE=$(mktemp)

# 添加SQL文件头
cat > "${TEMP_FILE}" << 'EOF'
-- Laravel工单系统数据库导出文件
-- 导出时间: $(date)
-- 数据库类型: ${DB_CONNECTION}
-- 数据库名称: ${DB_DATABASE}

-- 禁用外键检查
SET FOREIGN_KEY_CHECKS=0;

-- 开始事务
START TRANSACTION;

EOF

# 根据数据库类型执行导出
case $DB_CONNECTION in
    "mysql")
        # 导出MySQL数据库
        mysqldump -h${DB_HOST} -P${DB_PORT} -u${DB_USERNAME} -p${DB_PASSWORD} \
            --single-transaction \
            --routines \
            --triggers \
            --events \
            --hex-blob \
            --default-character-set=utf8mb4 \
            ${DB_DATABASE} >> "${TEMP_FILE}"
        ;;
    "sqlite")
        # 导出SQLite数据库
        sqlite3 ${DB_DATABASE} ".dump" >> "${TEMP_FILE}"
        ;;
    "pgsql")
        # 导出PostgreSQL数据库
        PGPASSWORD=${DB_PASSWORD} pg_dump -h ${DB_HOST} -p ${DB_PORT} -U ${DB_USERNAME} \
            --no-owner \
            --no-privileges \
            --verbose \
            --format=plain \
            ${DB_DATABASE} >> "${TEMP_FILE}"
        ;;
esac

# 添加SQL文件尾
cat >> "${TEMP_FILE}" << 'EOF'

-- 提交事务
COMMIT;

-- 重新启用外键检查
SET FOREIGN_KEY_CHECKS=1;

-- 导出完成
EOF

# 压缩SQL文件
if command -v gzip &> /dev/null; then
    gzip -c "${TEMP_FILE}" > "${OUTPUT_FILE}.gz"
    echo -e "${GREEN}数据库导出完成: ${OUTPUT_FILE}.gz${NC}"
    echo -e "${YELLOW}文件大小: $(du -h ${OUTPUT_FILE}.gz | cut -f1)${NC}"
    
    # 如果没有指定.gz扩展名，也创建未压缩版本
    if [[ ! "${OUTPUT_FILE}" =~ \.gz$ ]]; then
        cp "${TEMP_FILE}" "${OUTPUT_FILE}"
        echo -e "${GREEN}未压缩版本: ${OUTPUT_FILE}${NC}"
    fi
else
    cp "${TEMP_FILE}" "${OUTPUT_FILE}"
    echo -e "${GREEN}数据库导出完成: ${OUTPUT_FILE}${NC}"
    echo -e "${YELLOW}文件大小: $(du -h ${OUTPUT_FILE} | cut -f1)${NC}"
fi

# 清理临时文件
rm -f "${TEMP_FILE}"

echo ""
echo -e "${BLUE}========================================${NC}"
echo -e "${GREEN}  导出完成！${NC}"
echo -e "${BLUE}========================================${NC}"
echo ""
echo -e "${YELLOW}导入方法:${NC}"
echo -e "${YELLOW}MySQL: mysql -u username -p database_name < ${OUTPUT_FILE}${NC}"
echo -e "${YELLOW}PostgreSQL: psql -U username -d database_name -f ${OUTPUT_FILE}${NC}"
echo -e "${YELLOW}SQLite: sqlite3 database.sqlite < ${OUTPUT_FILE}${NC}"
echo ""

# 如果是压缩文件，提供解压导入方法
if [ -f "${OUTPUT_FILE}.gz" ]; then
    echo -e "${YELLOW}压缩文件导入方法:${NC}"
    echo -e "${YELLOW}MySQL: gunzip -c ${OUTPUT_FILE}.gz | mysql -u username -p database_name${NC}"
    echo -e "${YELLOW}PostgreSQL: gunzip -c ${OUTPUT_FILE}.gz | psql -U username -d database_name${NC}"
fi

echo ""
echo -e "${YELLOW}包含的数据表:${NC}"

# 显示导出的数据表
case $DB_CONNECTION in
    "mysql")
        mysql -h${DB_HOST} -P${DB_PORT} -u${DB_USERNAME} -p${DB_PASSWORD} -e "SHOW TABLES FROM ${DB_DATABASE};" 2>/dev/null | tail -n +2
        ;;
    "sqlite")
        sqlite3 ${DB_DATABASE} ".tables"
        ;;
    "pgsql")
        PGPASSWORD=${DB_PASSWORD} psql -h ${DB_HOST} -p ${DB_PORT} -U ${DB_USERNAME} -d ${DB_DATABASE} -c "\dt" 2>/dev/null | tail -n +3 | head -n -2 | awk '{print $3}'
        ;;
esac

echo ""