#!/bin/bash

# 校园网工单系统 - 项目打包脚本
# 用于创建部署包，便于在其他服务器上部署

set -e

# 颜色定义
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# 项目信息
PROJECT_NAME="campus-workorder-system"
VERSION="v1.0.0"
PACKAGE_NAME="${PROJECT_NAME}-${VERSION}"
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
FULL_PACKAGE_NAME="${PACKAGE_NAME}_${TIMESTAMP}"

echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}  校园网工单系统 - 项目打包脚本${NC}"
echo -e "${BLUE}========================================${NC}"
echo -e "${YELLOW}项目名称: ${PROJECT_NAME}${NC}"
echo -e "${YELLOW}版本号: ${VERSION}${NC}"
echo -e "${YELLOW}时间戳: ${TIMESTAMP}${NC}"
echo ""

# 检查是否在项目根目录
if [ ! -f "composer.json" ]; then
    echo -e "${RED}错误: 请在项目根目录运行此脚本${NC}"
    exit 1
fi

# 创建临时目录
TEMP_DIR="/tmp/${FULL_PACKAGE_NAME}"
echo -e "${GREEN}创建临时目录: ${TEMP_DIR}${NC}"
mkdir -p "${TEMP_DIR}"

# 复制核心项目文件
echo -e "${GREEN}复制项目文件...${NC}"

# 复制应用代码
echo "  - 复制应用代码..."
cp -r app/ "${TEMP_DIR}/"
cp -r bootstrap/ "${TEMP_DIR}/"
cp -r config/ "${TEMP_DIR}/"
cp -r database/ "${TEMP_DIR}/"
cp -r public/ "${TEMP_DIR}/"
cp -r resources/ "${TEMP_DIR}/"
cp -r routes/ "${TEMP_DIR}/"
cp -r storage/ "${TEMP_DIR}/"

echo "  - 复制数据库迁移和种子文件..."
# 确保所有迁移文件都包含在内
find database/ -name "*.php" -type f -exec cp {} "${TEMP_DIR}/database/" \;

# 复制配置文件
echo "  - 复制配置文件..."
cp composer.json "${TEMP_DIR}/"
cp composer.lock "${TEMP_DIR}/" 2>/dev/null || true
cp package.json "${TEMP_DIR}/" 2>/dev/null || true
cp package-lock.json "${TEMP_DIR}/" 2>/dev/null || true
cp .env.example "${TEMP_DIR}/" 2>/dev/null || echo "警告: .env.example 文件不存在"
cp deploy_config.json "${TEMP_DIR}/" 2>/dev/null || echo "警告: deploy_config.json 文件不存在"

# 复制重要文件
echo "  - 复制重要文件..."
cp artisan "${TEMP_DIR}/" 2>/dev/null || echo "警告: artisan 文件不存在"
cp LICENSE "${TEMP_DIR}/" 2>/dev/null || true
cp README.md "${TEMP_DIR}/" 2>/dev/null || true

# 复制文档
echo "  - 复制项目文档..."
cp *.md "${TEMP_DIR}/" 2>/dev/null || true

# 创建必要的目录
echo "  - 创建必要目录..."
mkdir -p "${TEMP_DIR}/storage/logs"
mkdir -p "${TEMP_DIR}/storage/framework/cache"
mkdir -p "${TEMP_DIR}/storage/framework/sessions"
mkdir -p "${TEMP_DIR}/storage/framework/views"
mkdir -p "${TEMP_DIR}/storage/app/public"

# 创建部署脚本
echo -e "${GREEN}创建部署脚本...${NC}"
cat > "${TEMP_DIR}/deploy.sh" << 'EOF'
#!/bin/bash

# 校园网工单系统 - 自动部署脚本

set -e

echo "========================================"
echo "  校园网工单系统 - 自动部署脚本"
echo "========================================"

# 检查PHP版本
echo "检查PHP版本..."
php_version=$(php -r "echo PHP_VERSION;")
echo "当前PHP版本: $php_version"

if [[ $(echo "$php_version 8.1" | awk '{print ($1 < $2)}') == 1 ]]; then
    echo "错误: 需要PHP 8.1或更高版本"
    exit 1
fi

# 安装Composer依赖
echo "安装Composer依赖..."
if [ ! -f "composer.phar" ]; then
    curl -sS https://getcomposer.org/installer | php
fi
php composer.phar install --no-dev --optimize-autoloader

# 安装NPM依赖（如果存在）
if [ -f "package.json" ]; then
    echo "安装NPM依赖..."
    npm install
    npm run build
fi

# 复制环境配置
if [ ! -f ".env" ]; then
    if [ -f ".env.example" ]; then
        cp .env.example .env
        echo "已创建.env文件，请根据实际情况修改配置"
    else
        echo "错误: .env.example文件不存在"
        exit 1
    fi
fi

# 生成应用密钥
echo "生成应用密钥..."
php artisan key:generate

# 设置目录权限
echo "设置目录权限..."
chmod -R 755 storage/
chmod -R 755 bootstrap/cache/

# 运行数据库迁移
echo "运行数据库迁移..."
php artisan migrate --force

# 导入种子数据
echo "导入种子数据..."
php artisan db:seed --force

# 清除缓存
echo "清除应用缓存..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

echo ""
echo "========================================"
echo "部署完成！"
echo "========================================"
echo "请确保："
echo "1. 已正确配置.env文件中的数据库连接"
echo "2. Web服务器已正确配置文档根目录为public/"
echo "3. 已设置适当的文件权限"
echo ""
echo "访问地址: http://your-domain.com"
echo "========================================"
EOF

chmod +x "${TEMP_DIR}/deploy.sh"

# 复制环境准备脚本到部署包
echo -e "${GREEN}复制环境准备脚本...${NC}"
if [ -f "setup_server.sh" ]; then
    cp setup_server.sh "${TEMP_DIR}/"
    echo "  ✓ 已复制 setup_server.sh"
else
    echo "  ⚠ 警告: setup_server.sh 不存在，跳过复制"
fi

# 复制依赖检查脚本到部署包
echo -e "${GREEN}复制依赖检查脚本...${NC}"
if [ -f "check_dependencies.sh" ]; then
    cp check_dependencies.sh "${TEMP_DIR}/"
    echo "  ✓ 已复制 check_dependencies.sh"
else
    echo "  ⚠ 警告: check_dependencies.sh 不存在，跳过复制"
fi

# 复制自动部署脚本到部署包
echo -e "${GREEN}复制自动部署脚本...${NC}"
if [ -f "auto_deploy.sh" ]; then
    cp auto_deploy.sh "${TEMP_DIR}/"
    echo "  ✓ 已复制 auto_deploy.sh"
else
    echo "  ⚠ 警告: auto_deploy.sh 不存在，跳过复制"
fi

# 创建README部署说明
echo -e "${GREEN}创建部署说明文档...${NC}"
cat > "${TEMP_DIR}/DEPLOY_README.md" << 'EOF'
# 校园网工单系统 - 部署说明

## 快速部署

### 1. 自动部署（推荐）
```bash
chmod +x deploy.sh
./deploy.sh
```

### 2. 手动部署

#### 环境要求
- PHP >= 8.1
- MySQL >= 8.0
- Composer
- Node.js & NPM (可选，用于前端资源编译)

#### 部署步骤

1. **安装依赖**
   ```bash
   composer install --no-dev --optimize-autoloader
   npm install && npm run build  # 如果存在package.json
   ```

2. **配置环境**
   ```bash
   cp .env.example .env
   # 编辑.env文件，配置数据库连接等
   php artisan key:generate
   ```

3. **设置权限**
   ```bash
   chmod -R 755 storage/
   chmod -R 755 bootstrap/cache/
   ```

4. **数据库迁移**
   ```bash
   php artisan migrate --force
   ```

5. **清除缓存**
   ```bash
   php artisan cache:clear
   php artisan config:clear
   php artisan route:clear
   php artisan view:clear
   ```

6. **Web服务器配置**

   **Apache配置示例：**
   ```apache
   <VirtualHost *:80>
       ServerName your-domain.com
       DocumentRoot /path/to/project/public
       
       <Directory /path/to/project/public>
           AllowOverride All
           Require all granted
       </Directory>
   </VirtualHost>
   ```

   **Nginx配置示例：**
   ```nginx
   server {
       listen 80;
       server_name your-domain.com;
       root /path/to/project/public;
       index index.php;
       
       location / {
           try_files $uri $uri/ /index.php?$query_string;
       }
       
       location ~ \.php$ {
           fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
           fastcgi_index index.php;
           fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
           include fastcgi_params;
       }
   }
   ```

## 默认账户

| 角色 | 邮箱 | 密码 | 说明 |
|------|------|------|------|
| 系统管理员 | admin@workorder.com | admin123 | 拥有系统所有权限 |
| 工单管理员 | workorder_manager@workorder.com | admin123 | 负责工单分配和管理 |
| 工程师 | engineer@workorder.com | engineer123 | 负责工单处理 |
| 普通用户 | user@workorder.com | user123 | 基础工单操作权限 |

**安全提示**：首次登录后请立即修改默认密码！

## 技术支持

如遇到部署问题，请参考：
- 部署维护指南：DEPLOYMENT_MAINTENANCE_GUIDE.md
- 用户手册：USER_MANUAL.md
- 开发者指南：DEVELOPER_GUIDE.md

## 版本信息

- 版本号：v1.0.0
- 打包时间：$(date)
- Laravel版本：11.x
- PHP要求：>= 8.1
- MySQL要求：>= 8.0
EOF

# 创建版本信息文件
echo -e "${GREEN}创建版本信息文件...${NC}"
cat > "${TEMP_DIR}/VERSION.json" << EOF
{
    "name": "${PROJECT_NAME}",
    "version": "${VERSION}",
    "build_timestamp": "${TIMESTAMP}",
    "build_date": "$(date -d @$(date +%s) +%Y-%m-%d\ %H:%M:%S)",
    "git_commit": "$(git rev-parse HEAD 2>/dev/null || echo 'unknown')",
    "git_branch": "$(git rev-parse --abbrev-ref HEAD 2>/dev/null || echo 'unknown')",
    "php_version": "$(php -r 'echo PHP_VERSION;' 2>/dev/null || echo 'unknown')",
    "laravel_version": "$(php -r 'echo app()->version();' 2>/dev/null || echo 'unknown')",
    "requirements": {
        "php": ">=8.1",
        "mysql": ">=8.0",
        "composer": ">=2.0",
        "node": ">=16.0 (optional)"
    },
    "features": [
        "工单管理",
        "用户权限管理",
        "部门管理",
        "工单分类",
        "附件上传",
        "工单日志",
        "通知系统",
        "统计报表",
        "工单回访",
        "满意度评价"
    ],
    "documentation": [
        "PROJECT_OVERVIEW.md",
        "DETAILED_DATABASE_DESIGN.md",
        "API_DOCUMENTATION.md",
        "USER_MANUAL.md",
        "DEVELOPER_GUIDE.md",
        "DEPLOYMENT_MAINTENANCE_GUIDE.md",
        "DOCUMENTATION_INDEX.md"
    ]
}
EOF

# 创建排除文件列表
echo -e "${GREEN}创建排除文件列表...${NC}"
cat > "${TEMP_DIR}/.gitignore" << 'EOF'
# 开发和测试文件
*.log
.env
.env.backup
.env.local
.env.testing
storage/logs/*
storage/framework/cache/*
storage/framework/sessions/*
storage/framework/views/*
storage/app/public/*

# 临时文件
tmp/
temp/
*.tmp

# 系统文件
.DS_Store
Thumbs.db

# 编译文件
node_modules/
vendor/
public/hot
public/storage

# IDE文件
.vscode/
.idea/
*.swp
*.swo
*~

# 部署相关
deploy.sh.backup
DEPLOY_README.md.backup
EOF

# 排除不必要的文件
echo -e "${GREEN}清理不必要的文件...${NC}"
find "${TEMP_DIR}" -name ".git*" -exec rm -rf {} + 2>/dev/null || true
find "${TEMP_DIR}" -name "node_modules" -exec rm -rf {} + 2>/dev/null || true
find "${TEMP_DIR}" -name "vendor" -exec rm -rf {} + 2>/dev/null || true
find "${TEMP_DIR}" -name "*.log" -delete 2>/dev/null || true
find "${TEMP_DIR}" -name ".DS_Store" -delete 2>/dev/null || true
find "${TEMP_DIR}" -name "Thumbs.db" -delete 2>/dev/null || true

# 获取脚本所在目录（项目根目录）
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
echo -e "${GREEN}项目根目录: ${SCRIPT_DIR}${NC}"

# 创建压缩包
echo -e "${GREEN}创建压缩包...${NC}"
cd /tmp
tar -czf "${FULL_PACKAGE_NAME}.tar.gz" "${FULL_PACKAGE_NAME}/"

# 检查是否有zip命令，如果有则创建ZIP格式（Windows兼容）
if command -v zip &> /dev/null; then
    echo -e "${GREEN}创建ZIP格式（Windows兼容）...${NC}"
    zip -rq "${FULL_PACKAGE_NAME}.zip" "${FULL_PACKAGE_NAME}/"
    ZIP_CREATED=true
else
    echo -e "${YELLOW}zip命令未找到，跳过ZIP格式创建${NC}"
    ZIP_CREATED=false
fi

# 移动到packages目录
PACKAGES_DIR="${SCRIPT_DIR}/packages"
mkdir -p "$PACKAGES_DIR"
mv "${FULL_PACKAGE_NAME}.tar.gz" "${PACKAGES_DIR}/"
if [ "$ZIP_CREATED" = true ]; then
    mv "${FULL_PACKAGE_NAME}.zip" "${PACKAGES_DIR}/"
fi

# 计算文件大小和校验和（在移动后）
echo -e "${GREEN}计算文件大小和校验和...${NC}"
cd "${PACKAGES_DIR}"
TAR_SIZE=$(du -h "${FULL_PACKAGE_NAME}.tar.gz" | cut -f1)
SHA256_TAR=$(sha256sum "${FULL_PACKAGE_NAME}.tar.gz" | cut -d' ' -f1)

if [ "$ZIP_CREATED" = true ]; then
    ZIP_SIZE=$(du -h "${FULL_PACKAGE_NAME}.zip" | cut -f1)
    SHA256_ZIP=$(sha256sum "${FULL_PACKAGE_NAME}.zip" | cut -d' ' -f1)
fi

# 清理临时目录
echo -e "${GREEN}清理临时文件...${NC}"
rm -rf "${TEMP_DIR}"

# 创建打包信息文件
echo -e "${GREEN}创建打包信息文件...${NC}"
cat > "${PACKAGES_DIR}/${FULL_PACKAGE_NAME}_PACKAGE_INFO.txt" << EOF
========================================
校园网工单系统 - 打包信息
========================================

项目名称: ${PROJECT_NAME}
版本号: ${VERSION}
打包时间: $(date)
时间戳: ${TIMESTAMP}

文件信息:
- TAR包: ${FULL_PACKAGE_NAME}.tar.gz (${TAR_SIZE})
EOF

# 如果ZIP文件存在，添加ZIP相关信息
if [ "$ZIP_CREATED" = true ]; then
    cat >> "${PACKAGES_DIR}/${FULL_PACKAGE_NAME}_PACKAGE_INFO.txt" << EOF
- ZIP包: ${FULL_PACKAGE_NAME}.zip (${ZIP_SIZE})
EOF
fi

cat >> "${PACKAGES_DIR}/${FULL_PACKAGE_NAME}_PACKAGE_INFO.txt" << EOF

校验和:
- TAR包 SHA256: ${SHA256_TAR}
EOF

# 如果ZIP文件存在，添加ZIP校验和
if [ "$ZIP_CREATED" = true ]; then
    cat >> "${PACKAGES_DIR}/${FULL_PACKAGE_NAME}_PACKAGE_INFO.txt" << EOF
- ZIP包 SHA256: ${SHA256_ZIP}
EOF
fi

cat >> "${PACKAGES_DIR}/${FULL_PACKAGE_NAME}_PACKAGE_INFO.txt" << EOF

部署说明:
1. 解压压缩包到目标目录
2. 运行 deploy.sh 进行自动部署
3. 或参考 DEPLOY_README.md 进行手动部署

技术支持:
- 部署维护指南: DEPLOYMENT_MAINTENANCE_GUIDE.md
- 用户手册: USER_MANUAL.md
- 开发者指南: DEVELOPER_GUIDE.md

注意事项:
- 请确保服务器满足环境要求
- 部署前请备份现有数据
- 首次登录后请修改默认密码
========================================
EOF


echo ""
echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}  打包完成！${NC}"
echo -e "${GREEN}========================================${NC}"
echo -e "${YELLOW}生成的文件:${NC}"
echo -e "  - ${FULL_PACKAGE_NAME}.tar.gz (${TAR_SIZE})"

# 如果ZIP文件存在，显示ZIP文件信息
if [ "$ZIP_CREATED" = true ]; then
    echo -e "  - ${FULL_PACKAGE_NAME}.zip (${ZIP_SIZE})"
fi

echo -e "  - ${FULL_PACKAGE_NAME}_PACKAGE_INFO.txt"
echo ""
echo -e "${YELLOW}校验和:${NC}"
echo -e "  TAR SHA256: ${SHA256_TAR}"

# 如果ZIP文件存在，显示ZIP校验和
if [ "$ZIP_CREATED" = true ]; then
    echo -e "  ZIP SHA256: ${SHA256_ZIP}"
fi

echo ""
echo -e "${BLUE}部署说明:${NC}"
echo -e "  1. 将压缩包上传到目标服务器"
echo -e "  2. 解压到目标目录"
echo -e "  3. 运行 deploy.sh 进行自动部署"
echo -e "  4. 或参考 DEPLOY_README.md 进行手动部署"
echo ""
echo -e "${GREEN}打包成功！可以开始部署到其他服务器。${NC}"