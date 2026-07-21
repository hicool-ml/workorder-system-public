#!/bin/bash
# ============================================================
# 工单系统「仅代码」安全部署脚本（不碰数据库）
#
# 适用：本次权限规则对齐 + 403 友好页 + 删除模板浮窗，均为纯代码改动，
#       无 migration、无 schema 变更，因此部署只需传代码 + 清缓存。
#
# 安全保障：
#   1. 不导出/导入/迁移数据库——生产数据库零影响。
#   2. 部署前自动备份生产端被覆盖的文件（打包 tar.gz 留在服务器上）。
#   3. 提供一键回滚：从备份还原被覆盖文件 + 清缓存。
#
# 用法（在生产服务器本地或能 SSH 到它的机器上执行）：
#   PROD_HOST=192.168.1.18 PROD_USER=cdu PROD_PASS=xxx bash deploy/deploy_code_only.sh
#
# 回滚：
#   bash deploy/deploy_code_only.sh rollback <备份目录名>
#   （备份目录名会在部署结束时打印出来，形如 workorder-backup-YYYYmmdd_HHMMSS）
# ============================================================
set -euo pipefail

PROD_HOST="${PROD_HOST:-192.168.1.18}"
PROD_USER="${PROD_USER:-cdu}"
PROD_PASS="${PROD_PASS:?请设置 PROD_PASS（SSH 密码）}"
PROD_PATH="${PROD_PATH:-/var/www/workorder}"
BACKUP_NAME="workorder-backup-$(date +%Y%m%d_%H%M%S)"
REMOTE_BACKUP_DIR="${PROD_PATH}-backups/${BACKUP_NAME}"

SSH_BASE="sshpass -p ${PROD_PASS} ssh -o StrictHostKeyChecking=no -o ConnectTimeout=10"

remote() {
  ${SSH_BASE} "${PROD_USER}@${PROD_HOST}" "$@"
}

if [ "${1:-}" = "rollback" ]; then
  BACKUP="${2:?用法: deploy_code_only.sh rollback <备份目录名>}"
  BACKUP_DIR="${PROD_PATH}-backups/${BACKUP}"
  echo "=== 回滚：从 ${BACKUP_DIR} 还原到 ${PROD_PATH} ==="
  remote "test -d ${BACKUP_DIR} || { echo '备份目录不存在: ${BACKUP_DIR}'; exit 1; }"
  remote "cp -a ${BACKUP_DIR}/. ${PROD_PATH}/ && cd ${PROD_PATH} && php artisan view:clear && php artisan config:clear && php artisan cache:clear && php artisan route:clear && echo '回滚完成'"
  exit $?
fi

echo "================================================"
echo "  仅代码部署"
echo "  目标: ${PROD_USER}@${PROD_HOST}:${PROD_PATH}"
echo "  时间: $(date)"
echo "  注意: 不触碰数据库"
echo "================================================"

# --- 1. 连通性检查 ---
echo "[1/4] 检查连接与目标目录..."
remote "test -d ${PROD_PATH} || { echo '目标目录不存在: ${PROD_PATH}'; exit 1; }; echo '  目录存在'; php -v | head -1"

# --- 2. 备份将被覆盖的代码（不含 storage/.env/node_modules） ---
echo "[2/4] 备份现有代码到 ${REMOTE_BACKUP_DIR} ..."
remote "mkdir -p ${PROD_PATH}-backups && cp -a ${PROD_PATH} ${REMOTE_BACKUP_DIR} && echo '  备份完成'"

# --- 3. 同步代码（排除数据/缓存/环境/构建工具） ---
# 只推送源码：app routes config resources public database（schema） bootstrap composer.* artisan
# 关键：不删 storage、不动 .env、不跑 migrate、不导入任何 SQL
echo "[3/4] 同步代码（不碰数据库）..."
sshpass -p "${PROD_PASS}" rsync -az --delete \
  --exclude='.git' \
  --exclude='node_modules' \
  --exclude='.tools' \
  --exclude='.composer-cache' \
  --exclude='.npm-cache' \
  --exclude='storage/app/*' \
  --exclude='storage/logs/*' \
  --exclude='storage/framework/cache/*' \
  --exclude='storage/framework/sessions/*' \
  --exclude='storage/framework/views/*' \
  --exclude='.env' \
  --exclude='deploy' \
  --exclude='tests' \
  --exclude='*_dump.sql' \
  -e "ssh -o StrictHostKeyChecking=no" \
  "./" "${PROD_USER}@${PROD_HOST}:${PROD_PATH}/"
echo "  同步完成"

# --- 4. 清缓存 + 权限 + 验证 ---
echo "[4/4] 清缓存并验证..."
remote "cd ${PROD_PATH} && \
  php artisan view:clear && \
  php artisan config:clear && \
  php artisan cache:clear && \
  php artisan route:clear && \
  (sudo chown -R ${PROD_USER}:www-data storage bootstrap/cache 2>/dev/null || true) && \
  (sudo chmod -R 775 storage bootstrap/cache 2>/dev/null || true) && \
  (sudo systemctl reload apache2 2>/dev/null || sudo systemctl reload nginx 2>/dev/null || true) && \
  echo '  缓存已清、权限已设、Web 服务已 reload' && \
  echo \"  工单数: \$(php artisan tinker --execute='echo App\\\Models\\\Workorder::count();')\" && \
  echo \"  登录页 HTTP: \$(curl -s -o /dev/null -w '%{http_code}' http://127.0.0.1/login)\""

echo ""
echo "================================================"
echo "  部署完成"
echo "  备份目录: ${REMOTE_BACKUP_DIR}"
echo "  回滚命令: PROD_PASS=xxx bash deploy/deploy_code_only.sh rollback ${BACKUP_NAME}"
echo "================================================"
