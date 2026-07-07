#!/bin/bash

# CDN链接修复脚本
# 将jsdelivr.net替换为可用的CDN源

echo "开始修复CDN链接..."

# 备份原始文件
echo "创建备份..."
find . -name "*.php" -o -name "*.blade.php" | xargs grep -l "jsdelivr.net" | while read file; do
    cp "$file" "$file.bak"
done

# 替换Bootstrap CSS
echo "替换Bootstrap CSS链接..."
find . -name "*.php" -o -name "*.blade.php" | xargs sed -i 's|https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css|https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.0/css/bootstrap.min.css|g'

# 替换Bootstrap JS
echo "替换Bootstrap JS链接..."
find . -name "*.php" -o -name "*.blade.php" | xargs sed -i 's|https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js|https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.0/js/bootstrap.bundle.min.js|g'

# 替换Axios
echo "替换Axios链接..."
find . -name "*.php" -o -name "*.blade.php" | xargs sed -i 's|https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js|https://cdnjs.cloudflare.com/ajax/libs/axios/1.6.0/axios.min.js|g'

# 替换Chart.js
echo "替换Chart.js链接..."
find . -name "*.php" -o -name "*.blade.php" | xargs sed -i 's|https://cdn.jsdelivr.net/npm/chart.js|https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.min.js|g'

echo "CDN链接修复完成！"
echo "备份文件已创建，文件扩展名为 .bak"