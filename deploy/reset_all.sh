#!/usr/bin/env bash
# ============================================================
# 服务器整体复位脚本
# 作用：清空代码目录 + 数据库 + Nginx 站点，回到「软件已装、无代码无数据」状态
# 用途：复位后重新执行 bash deploy_ubuntu_pg.sh 做全新部署测试
# 注意：会删除数据库和代码！生产环境严禁使用！
# 用法：bash reset_all.sh
# ============================================================
set -euo pipefail

APP_DIR="/var/www/workorder"
DB_NAME="workorder_db"
DB_USER="workorder"

info()  { echo -e "\033[1;32m[✓]\033[0m $*"; }
warn()  { echo -e "\033[1;33m[!]\033[0m $*"; }

warn "即将清空：代码目录 ${APP_DIR}、数据库 ${DB_NAME}、Nginx 站点配置"
read -rp "确认继续？输入 yes 回车继续，其它任意键取消: " confirm
[ "$confirm" = "yes" ] || { echo "已取消"; exit 0; }

# 1. 删除 Nginx 站点
info "[1/4] 删除 Nginx 站点配置"
sudo rm -f /etc/nginx/sites-enabled/workorder /etc/nginx/sites-available/workorder
sudo systemctl reload nginx 2>/dev/null || true

# 2. 删除代码目录
info "[2/4] 删除代码目录 ${APP_DIR}"
sudo rm -rf "${APP_DIR}"

# 3. drop 数据库 + 用户（PG16 支持 WITH FORCE 强制断开连接）
info "[3/4] 删除数据库 ${DB_NAME} 与用户 ${DB_USER}"
sudo -u postgres psql -c "DROP DATABASE IF EXISTS ${DB_NAME} WITH (FORCE);" 2>/dev/null || \
    sudo -u postgres psql -c "DROP DATABASE IF EXISTS ${DB_NAME};" 2>/dev/null || true
sudo -u postgres psql -c "DROP USER IF EXISTS ${DB_USER};" 2>/dev/null || true

# 4. 清理 Composer/npm 缓存（让下次部署更干净）
info "[4/4] 清理依赖缓存"
rm -rf ~/.composer/cache ~/.npm 2>/dev/null || true

echo ""
info "复位完成！服务器已回到干净状态。"
info "现在可执行：bash deploy_ubuntu_pg.sh  进行全新部署测试"
