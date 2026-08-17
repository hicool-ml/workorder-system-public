#!/usr/bin/env bash
# ============================================================
# 工单系统复位脚本（重新初始化，用于测试整个初始化流程）
# 作用：拉取最新代码 → 清空数据库 → 重新 migrate + seed
# 用法：bash reset.sh
# ============================================================
set -e

cd /var/www/workorder

echo "==== [1/4] 拉取最新代码 ===="
git pull

echo "==== [2/4] 安装依赖（vendor 缺失时） ===="
if [ ! -d vendor ]; then
    composer install --no-dev --optimize-autoloader
fi

echo "==== [3/4] 复位数据库并重新初始化（migrate:fresh --seed） ===="
php artisan migrate:fresh --seed --force

echo "==== [4/4] 清理缓存 ===="
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

echo ""
echo "=============================================="
echo "  复位完成！"
echo "  管理员账号见上方 seed 输出（用户名：admin）"
echo "  现在可访问 http://192.168.1.4 测试初始化流程"
echo "=============================================="
