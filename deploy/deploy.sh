#!/bin/bash
# ============================================================
# 工单系统上线部署脚本
# 在本地执行，通过 SSH 部署到生产服务器 REDACTED_PROD_HOST
# 用法: bash deploy/deploy.sh
# ============================================================
set -e

PROD_HOST="REDACTED_PROD_HOST"
PROD_USER="cdu"
PROD_PASS="REDACTED_PROD_SSH_PASS"
PROD_PATH="/var/www/workorder"
BACKUP_DIR="/var/www/workorder-backup-$(date +%Y%m%d_%H%M%S)"
MYSQL_USER="cdu"
MYSQL_PASS="REDACTED_MYSQL_PASS"
MYSQL_DB="workorder_db"

echo "========================================"
echo "  工单系统上线部署"
echo "  时间: $(date)"
echo "  目标: ${PROD_USER}@${PROD_HOST}:${PROD_PATH}"
echo "========================================"

# --- 步骤 1: 备份生产数据和代码 ---
echo ""
echo "[1/7] 备份生产环境..."
sshpass -p "$PROD_PASS" ssh -o StrictHostKeyChecking=no ${PROD_USER}@${PROD_HOST} "
    sudo cp -r ${PROD_PATH} ${BACKUP_DIR}
    echo '  代码备份到 ${BACKUP_DIR}'
    mysqldump -u ${MYSQL_USER} -p'${MYSQL_PASS}' --no-tablespaces --default-character-set=utf8mb4 --routines --triggers ${MYSQL_DB} > ${BACKUP_DIR}/db_backup.sql
    echo '  数据库备份到 ${BACKUP_DIR}/db_backup.sql'
    echo '  备份大小:' \$(du -sh ${BACKUP_DIR} | cut -f1)
"
echo "[1/7] 备份完成"

# --- 步骤 2: 最后一次数据同步 ---
echo ""
echo "[2/7] 同步生产最新数据到本地..."
sshpass -p "$PROD_PASS" ssh -o StrictHostKeyChecking=no ${PROD_USER}@${PROD_HOST} "
    mysqldump -u ${MYSQL_USER} -p'${MYSQL_PASS}' --no-tablespaces --default-character-set=utf8mb4 --routines --triggers ${MYSQL_DB} 2>/dev/null
" > deploy/production_final_dump.sql
echo "  生产数据导出到 deploy/production_final_dump.sql ($(du -sh deploy/production_final_dump.sql | cut -f1))"

echo "  导入到本地 MySQL..."
C:/mysql8/bin/mysql.exe -u ${MYSQL_USER} -p${MYSQL_PASS} ${MYSQL_DB} < deploy/production_final_dump.sql 2>/dev/null
echo "[2/7] 数据同步完成"

# --- 步骤 3: 构建前端资源 ---
echo ""
echo "[3/7] 构建前端资源..."
npx vite build
echo "[3/7] 构建完成"

# --- 步骤 4: 上传代码 ---
echo ""
echo "[4/7] 上传代码到生产服务器..."
sshpass -p "$PROD_PASS" rsync -avz --delete \
    --exclude='.git' \
    --exclude='node_modules' \
    --exclude='.tools' \
    --exclude='database/database.sqlite' \
    --exclude='deploy' \
    --exclude='.env' \
    --exclude='storage/app/*' \
    --exclude='storage/logs/*' \
    --exclude='storage/framework/cache/*' \
    --exclude='storage/framework/sessions/*' \
    --exclude='storage/framework/views/*' \
    --exclude='_*.php' \
    --exclude='_*.py' \
    --exclude='production_*.sql' \
    --exclude='converted_*.sql' \
    -e "ssh -o StrictHostKeyChecking=no" \
    ./ ${PROD_USER}@${PROD_HOST}:${PROD_PATH}/
echo "[4/7] 代码上传完成"

# --- 步骤 5: 执行迁移和清理缓存 ---
echo ""
echo "[5/7] 执行数据库迁移和缓存清理..."
sshpass -p "$PROD_PASS" ssh -o StrictHostKeyChecking=no ${PROD_USER}@${PROD_HOST} "
    cd ${PROD_PATH}
    php artisan migrate --force
    php artisan view:clear
    php artisan config:clear
    php artisan cache:clear
    php artisan route:clear
    php artisan storage:link 2>/dev/null || true
"
echo "[5/7] 迁移完成"

# --- 步骤 6: 设置权限并重启服务 ---
echo ""
echo "[6/7] 设置文件权限..."
sshpass -p "$PROD_PASS" ssh -o StrictHostKeyChecking=no ${PROD_USER}@${PROD_HOST} "
    cd ${PROD_PATH}
    sudo chown -R cdu:www-data storage bootstrap/cache
    sudo chmod -R 775 storage bootstrap/cache
    sudo chown -R cdu:www-data public/storage 2>/dev/null || true
    sudo systemctl reload apache2
"
echo "[6/7] 权限设置完成"

# --- 步骤 7: 验证 ---
echo ""
echo "[7/7] 验证部署..."
sshpass -p "$PROD_PASS" ssh -o StrictHostKeyChecking=no ${PROD_USER}@${PROD_HOST} "
    cd ${PROD_PATH}
    echo '  PHP版本:' \$(php -v | head -1)
    echo '  Laravel版本:' \$(php artisan --version)
    echo '  工单数量:' \$(php artisan tinker --execute='echo App\\Models\\Workorder::count();')
    echo '  用户数量:' \$(php artisan tinker --execute='echo App\\Models\\User::count();')
    HTTP_CODE=\$(curl -s -o /dev/null -w '%{http_code}' http://127.0.0.1/login)
    echo '  登录页HTTP状态:' \${HTTP_CODE}
"

rm -f deploy/production_final_dump.sql

echo ""
echo "========================================"
echo "  部署完成"
echo "  备份位置: ${BACKUP_DIR}"
echo "  回滚代码: sudo cp -r ${BACKUP_DIR}/* ${PROD_PATH}/"
echo "  回滚数据库: mysql -u ${MYSQL_USER} -p ${MYSQL_DB} < ${BACKUP_DIR}/db_backup.sql"
echo "========================================"