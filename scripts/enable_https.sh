#!/bin/bash

# HTTPS启用脚本
# 用于在生产环境中启用HTTPS强制重定向

echo "=== HTTPS配置脚本 ==="
echo "此脚本将配置生产环境的HTTPS设置"

# 检查是否是生产环境
if [ "$APP_ENV" = "local" ]; then
    echo "警告：当前环境为本地开发环境，不建议启用HTTPS强制重定向"
    read -p "是否继续？(y/n): " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        exit 1
    fi
fi

# 更新.env文件
echo "更新.env文件中的HTTPS配置..."
sed -i 's/FORCE_HTTPS=false/FORCE_HTTPS=true/' .env
echo "✓ 已设置FORCE_HTTPS=true"

# 创建生产环境配置
echo "创建生产环境HTTPS配置..."
cat > config/https_production.php << 'EOF'
<?php

return [
    'force' => true,
    'hosts' => [
        // 在这里添加您的域名
        // 'yourdomain.com',
        // 'www.yourdomain.com',
    ],
    'detect_cloudflare' => true,
    'cloudflare_headers' => [
        'HTTP_CF_VISITOR',
        'HTTP_CF_RAY',
        'HTTP_CF_CONNECTING_IP',
    ],
    'local_hosts' => [
        'localhost',
        '127.0.0.1',
        '0.0.0.0',
    ],
    'force_on_api' => true,
    'redirect_status' => 301,
];
EOF

echo "✓ 已创建config/https_production.php"

# 清除缓存
echo "清除Laravel缓存..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

echo "✓ 已清除所有缓存"

echo ""
echo "=== HTTPS配置完成 ==="
echo "请手动编辑config/https_production.php文件，添加您的域名"
echo "然后重启Web服务器使配置生效"
echo ""
echo "Apache重启命令: sudo systemctl restart apache2"
echo "Nginx重启命令: sudo systemctl restart nginx"
echo "PHP-FPM重启命令: sudo systemctl restart php8.2-fpm"