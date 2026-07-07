#!/bin/bash

# 校园网工单系统数据库导出脚本
# 用于导出完整的数据库结构和数据

set -e

# 颜色定义
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# 数据库配置（根据实际.env配置）
DB_HOST="127.0.0.1"
DB_PORT="3306"
DB_DATABASE="workorder_db"
DB_USERNAME="cdu"
DB_PASSWORD="REDACTED_MYSQL_PASS"

# 输出文件配置
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
OUTPUT_FILE="workorder_database_${TIMESTAMP}.sql"
COMPRESSED_FILE="${OUTPUT_FILE}.gz"

echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}  校园网工单系统数据库导出工具${NC}"
echo -e "${BLUE}========================================${NC}"
echo -e "${YELLOW}输出文件: ${OUTPUT_FILE}${NC}"
echo -e "${YELLOW}压缩文件: ${COMPRESSED_FILE}${NC}"
echo ""

# 检查MySQL命令行工具
if ! command -v mysql &> /dev/null; then
    echo -e "${RED}错误: 未找到mysql命令行工具${NC}"
    echo -e "${YELLOW}请安装MySQL客户端: sudo apt install mysql-client${NC}"
    exit 1
fi

if ! command -v mysqldump &> /dev/null; then
    echo -e "${RED}错误: 未找到mysqldump工具${NC}"
    echo -e "${YELLOW}请安装MySQL客户端: sudo apt install mysql-client${NC}"
    exit 1
fi

# 测试数据库连接
echo -e "${GREEN}测试数据库连接...${NC}"
if ! mysql -h${DB_HOST} -P${DB_PORT} -u${DB_USERNAME} -p${DB_PASSWORD} -e "USE ${DB_DATABASE};" 2>/dev/null; then
    echo -e "${RED}错误: 无法连接到数据库${NC}"
    echo -e "${YELLOW}请检查数据库配置:${NC}"
    echo -e "  主机: ${DB_HOST}:${DB_PORT}"
    echo -e "  数据库: ${DB_DATABASE}"
    echo -e "  用户名: ${DB_USERNAME}"
    exit 1
fi
echo -e "${GREEN}✓ 数据库连接正常${NC}"

# 显示数据库信息
echo -e "${GREEN}数据库信息:${NC}"
echo -e "${YELLOW}  主机: ${DB_HOST}:${DB_PORT}${NC}"
echo -e "${YELLOW}  数据库: ${DB_DATABASE}${NC}"
echo -e "${YELLOW}  用户名: ${DB_USERNAME}${NC}"

# 显示数据表
echo -e "${GREEN}数据表列表:${NC}"
mysql -h${DB_HOST} -P${DB_PORT} -u${DB_USERNAME} -p${DB_PASSWORD} -e "SHOW TABLES FROM ${DB_DATABASE};" 2>/dev/null | tail -n +2 | while read table; do
    echo -e "${YELLOW}  - ${table}${NC}"
done

echo ""

# 开始导出数据库
echo -e "${GREEN}开始导出数据库...${NC}"

# 创建SQL文件头
cat > "${OUTPUT_FILE}" << EOF
-- ========================================
-- 校园网工单系统数据库导出文件
-- 导出时间: $(date)
-- 数据库主机: ${DB_HOST}:${DB_PORT}
-- 数据库名称: ${DB_DATABASE}
-- 导出用户: ${DB_USERNAME}
-- ========================================

-- 禁用外键检查
SET FOREIGN_KEY_CHECKS=0;
SET UNIQUE_CHECKS=0;
SET AUTOCOMMIT=0;

-- 开始事务
START TRANSACTION;

-- 设置字符集
SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

EOF

echo -e "${YELLOW}导出数据库结构和数据...${NC}"

# 导出数据库结构和数据
mysqldump -h${DB_HOST} -P${DB_PORT} -u${DB_USERNAME} -p${DB_PASSWORD} \
    --single-transaction \
    --routines \
    --triggers \
    --events \
    --hex-blob \
    --default-character-set=utf8mb4 \
    --skip-add-locks \
    --disable-keys \
    --extended-insert \
    --quick \
    --lock-tables=false \
    ${DB_DATABASE} >> "${OUTPUT_FILE}"

# 添加SQL文件尾
cat >> "${OUTPUT_FILE}" << 'EOF'

-- 提交事务
COMMIT;

-- 重新启用检查
SET FOREIGN_KEY_CHECKS=1;
SET UNIQUE_CHECKS=1;
SET AUTOCOMMIT=1;

-- ========================================
-- 导出完成
-- 总行数: (请手动统计)
-- 导出时间: $(date)
-- ========================================
EOF

# 压缩SQL文件
echo -e "${YELLOW}压缩SQL文件...${NC}"
gzip -c "${OUTPUT_FILE}" > "${COMPRESSED_FILE}"

# 获取文件大小
SQL_SIZE=$(du -h "${OUTPUT_FILE}" | cut -f1)
GZ_SIZE=$(du -h "${COMPRESSED_FILE}" | cut -f1)

# 计算校验和
SQL_SHA256=$(sha256sum "${OUTPUT_FILE}" | cut -d' ' -f1)
GZ_SHA256=$(sha256sum "${COMPRESSED_FILE}" | cut -d' ' -f1)

echo -e "${GREEN}✓ 数据库导出完成${NC}"
echo -e "${YELLOW}  SQL文件: ${OUTPUT_FILE} (${SQL_SIZE})${NC}"
echo -e "${YELLOW}  压缩文件: ${COMPRESSED_FILE} (${GZ_SIZE})${NC}"

# 创建导出信息文件
INFO_FILE="workorder_database_export_${TIMESTAMP}.txt"
cat > "${INFO_FILE}" << EOF
========================================
校园网工单系统数据库导出信息
========================================

导出时间: $(date)
数据库主机: ${DB_HOST}:${DB_PORT}
数据库名称: ${DB_DATABASE}
导出用户: ${DB_USERNAME}

文件信息:
- SQL文件: ${OUTPUT_FILE} (${SQL_SIZE})
- 压缩文件: ${COMPRESSED_FILE} (${GZ_SIZE})

校验和:
- SQL SHA256: ${SQL_SHA256}
- 压缩文件 SHA256: ${GZ_SHA256}

导入方法:
1. 解压压缩文件:
   gunzip -c ${COMPRESSED_FILE} > ${OUTPUT_FILE}

2. 导入到MySQL:
   mysql -h主机地址 -P端口 -u用户名 -p 数据库名 < ${OUTPUT_FILE}

   或直接导入压缩文件:
   gunzip -c ${COMPRESSED_FILE} | mysql -h主机地址 -P端口 -u用户名 -p 数据库名

数据表列表:
EOF

# 添加数据表列表到信息文件
mysql -h${DB_HOST} -P${DB_PORT} -u${DB_USERNAME} -p${DB_PASSWORD} -e "SHOW TABLES FROM ${DB_DATABASE};" 2>/dev/null | tail -n +2 | while read table; do
    echo "- ${table}" >> "${INFO_FILE}"
done

cat >> "${INFO_FILE}" << EOF

注意事项:
1. 导入前请确保目标数据库存在
2. 导入前请备份现有数据（如有）
3. 导入后请检查数据完整性
4. 如有字符集问题，请确保目标数据库使用utf8mb4字符集

========================================
EOF

echo -e "${GREEN}✓ 导出信息文件已创建: ${INFO_FILE}${NC}"

echo ""
echo -e "${BLUE}========================================${NC}"
echo -e "${GREEN}  导出完成！${NC}"
echo -e "${BLUE}========================================${NC}"
echo ""

echo -e "${YELLOW}生成的文件:${NC}"
echo -e "${GREEN}  1. ${OUTPUT_FILE} (${SQL_SIZE})${NC}"
echo -e "${GREEN}  2. ${COMPRESSED_FILE} (${GZ_SIZE})${NC}"
echo -e "${GREEN}  3. ${INFO_FILE}${NC}"

echo ""
echo -e "${YELLOW}校验和:${NC}"
echo -e "${GREEN}  SQL SHA256: ${SQL_SHA256}${NC}"
echo -e "${GREEN}  压缩文件 SHA256: ${GZ_SHA256}${NC}"

echo ""
echo -e "${YELLOW}导入命令:${NC}"
echo -e "${GREEN}  mysql -h${DB_HOST} -P${DB_PORT} -u${DB_USERNAME} -p ${DB_DATABASE} < ${OUTPUT_FILE}${NC}"
echo -e "${GREEN}  或: gunzip -c ${COMPRESSED_FILE} | mysql -h${DB_HOST} -P${DB_PORT} -u${DB_USERNAME} -p ${DB_DATABASE}${NC}"

echo ""
echo -e "${GREEN}数据库导出成功！可以将文件包含在部署包中。${NC}"