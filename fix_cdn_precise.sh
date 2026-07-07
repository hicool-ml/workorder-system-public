#!/bin/bash

# 精确修复CDN链接脚本
# 只处理特定的视图文件和测试文件

echo "开始精确修复CDN链接..."

# 定义需要修复的文件列表
files=(
    "resources/views/layouts/app.blade.php"
    "resources/views/auth/login.blade.php"
    "resources/views/auth/register.blade.php"
    "resources/views/reports/index.blade.php"
    "resources/views/test-simple.blade.php"
    "resources/views/test_final_notification.blade.php"
    "test_notifications_web.php"
    "test_notification_frontend.php"
    "test_notification_fixed.php"
    "test_final_notification_fixes.php"
    "test_notification_center_debug.php"
)

# 处理每个文件
for file in "${files[@]}"; do
    if [ -f "$file" ]; then
        echo "处理文件: $file"
        # 创建备份
        cp "$file" "$file.bak"
        
        # 替换Bootstrap CSS
        sed -i 's|https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css|https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.0/css/bootstrap.min.css|g' "$file"
        
        # 替换Bootstrap JS
        sed -i 's|https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js|https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.0/js/bootstrap.bundle.min.js|g' "$file"
        
        # 替换Axios
        sed -i 's|https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js|https://cdnjs.cloudflare.com/ajax/libs/axios/1.6.0/axios.min.js|g' "$file"
        
        # 替换Chart.js
        sed -i 's|https://cdn.jsdelivr.net/npm/chart.js|https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.min.js|g' "$file"
        
        echo "✓ $file 已修复"
    else
        echo "⚠ 文件不存在: $file"
    fi
done

echo "CDN链接精确修复完成！"
echo "备份文件已创建，文件扩展名为 .bak"