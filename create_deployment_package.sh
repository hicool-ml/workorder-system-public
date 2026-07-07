#!/bin/bash

# 校园网工单系统 - 完整部署包创建脚本
# 用于创建包含环境配置、数据库和项目的完整部署包

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
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
FULL_PACKAGE_NAME="${PROJECT_NAME}-${VERSION}_${TIMESTAMP}"

echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}  校园网工单系统 - 完整部署包创建${NC}"
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

# 步骤1: 导出数据库
echo -e "${YELLOW}步骤1: 导出数据库...${NC}"
if [ -f "export_workorder_database.sh" ]; then
    chmod +x export_workorder_database.sh
    ./export_workorder_database.sh
    echo -e "${GREEN}✓ 数据库导出完成${NC}"
else
    echo -e "${RED}错误: 数据库导出脚本不存在${NC}"
    exit 1
fi

# 查找最新的数据库导出文件
LATEST_DB_FILE=$(ls -t workorder_database_*.sql.gz 2>/dev/null | head -1)
if [ -z "$LATEST_DB_FILE" ]; then
    echo -e "${RED}错误: 未找到数据库导出文件${NC}"
    exit 1
fi

echo -e "${GREEN}找到数据库文件: ${LATEST_DB_FILE}${NC}"

# 步骤2: 复制项目文件
echo -e "${YELLOW}步骤2: 复制项目文件...${NC}"

# 复制应用核心目录
echo "  - 复制应用核心目录..."
cp -r app/ "${TEMP_DIR}/"
cp -r bootstrap/ "${TEMP_DIR}/"
cp -r config/ "${TEMP_DIR}/"
cp -r database/ "${TEMP_DIR}/"
cp -r public/ "${TEMP_DIR}/"
cp -r resources/ "${TEMP_DIR}/"
cp -r routes/ "${TEMP_DIR}/"
cp -r storage/ "${TEMP_DIR}/"

# 复制配置文件
echo "  - 复制配置文件..."
cp composer.json "${TEMP_DIR}/"
cp composer.lock "${TEMP_DIR}/" 2>/dev/null || true
cp package.json "${TEMP_DIR}/" 2>/dev/null || true
cp package-lock.json "${TEMP_DIR}/" 2>/dev/null || true
cp .env.example "${TEMP_DIR}/" 2>/dev/null || echo "警告: .env.example 文件不存在"

# 复制重要文件
echo "  - 复制重要文件..."
cp artisan "${TEMP_DIR}/" 2>/dev/null || echo "警告: artisan 文件不存在"
cp LICENSE "${TEMP_DIR}/" 2>/dev/null || true
cp README.md "${TEMP_DIR}/" 2>/dev/null || true

# 复制文档
echo "  - 复制项目文档..."
cp *.md "${TEMP_DIR}/" 2>/dev/null || true

# 步骤3: 复制部署脚本
echo -e "${YELLOW}步骤3: 复制部署脚本...${NC}"

# 复制环境配置脚本
if [ -f "ubuntu_server_setup.sh" ]; then
    cp ubuntu_server_setup.sh "${TEMP_DIR}/"
    echo "  ✓ 已复制 ubuntu_server_setup.sh"
else
    echo "  ⚠ 警告: ubuntu_server_setup.sh 不存在，跳过复制"
fi

# 复制数据库导出脚本
if [ -f "export_workorder_database.sh" ]; then
    cp export_workorder_database.sh "${TEMP_DIR}/"
    echo "  ✓ 已复制 export_workorder_database.sh"
else
    echo "  ⚠ 警告: export_workorder_database.sh 不存在，跳过复制"
fi

# 复制数据库文件
if [ -f "$LATEST_DB_FILE" ]; then
    cp "$LATEST_DB_FILE" "${TEMP_DIR}/"
    echo "  ✓ 已复制数据库文件: $LATEST_DB_FILE"
    
    # 也复制未压缩的SQL文件（如果存在）
    SQL_FILE="${LATEST_DB_FILE%.gz}"
    if [ -f "$SQL_FILE" ]; then
        cp "$SQL_FILE" "${TEMP_DIR}/"
        echo "  ✓ 已复制SQL文件: $SQL_FILE"
    fi
else
    echo "  ⚠ 警告: 数据库文件不存在，跳过复制"
fi

# 步骤4: 创建部署脚本
echo -e "${YELLOW}步骤4: 创建一键部署脚本...${NC}"

# 确保deploy_to_ubuntu.sh脚本不被覆盖
if [ -f "${TEMP_DIR}/deploy_to_ubuntu.sh" ]; then
    rm -f "${TEMP_DIR}/deploy_to_ubuntu.sh"
fi

cat > "${TEMP_DIR}/deploy_to_ubuntu.sh" << 'EOF'
#!/bin/bash

# 校园网工单系统 - Ubuntu Server 24 一键部署脚本

set -e

# 颜色定义
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}  校园网工单系统 - Ubuntu Server 24 部署${NC}"
echo -e "${BLUE}========================================${NC}"
echo ""

# 检查是否为root用户
if [ "$EUID" -ne 0 ]; then
    echo -e "${RED}错误：请使用sudo运行此脚本${NC}"
    echo "用法：sudo bash $0"
    exit 1
fi

# 获取脚本所在目录
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
echo -e "${GREEN}部署包目录: ${SCRIPT_DIR}${NC}"

# 步骤1: 配置服务器环境
echo -e "${YELLOW}步骤1: 配置服务器环境...${NC}"
if [ -f "${SCRIPT_DIR}/ubuntu_server_setup.sh" ]; then
    chmod +x "${SCRIPT_DIR}/ubuntu_server_setup.sh"
    bash "${SCRIPT_DIR}/ubuntu_server_setup.sh"
    echo -e "${GREEN}✓ 服务器环境配置完成${NC}"
else
    echo -e "${RED}错误: 环境配置脚本不存在${NC}"
    exit 1
fi

# 步骤2: 复制项目文件到Web目录
echo -e "${YELLOW}步骤2: 部署项目文件...${NC}"
WEB_DIR="/var/www/workorder"

# 清理现有目录（如果存在）
if [ -d "$WEB_DIR" ]; then
    echo -e "${YELLOW}备份现有项目文件...${NC}"
    mv "$WEB_DIR" "${WEB_DIR}.backup.$(date +%Y%m%d_%H%M%S)"
fi

# 复制项目文件
cp -r "${SCRIPT_DIR}/"* "$WEB_DIR/"
chown -R www-data:www-data "$WEB_DIR"
chmod -R 755 "$WEB_DIR"
chmod +x "$WEB_DIR/artisan"

echo -e "${GREEN}✓ 项目文件部署完成${NC}"

# 步骤3: 安装PHP依赖
echo -e "${YELLOW}步骤3: 安装PHP依赖...${NC}"
cd "$WEB_DIR"

# 设置Composer权限
export COMPOSER_ALLOW_SUPERUSER=1

# 安装依赖
if composer install --no-dev --optimize-autoloader; then
    echo -e "${GREEN}✓ PHP依赖安装完成${NC}"
else
    echo -e "${RED}错误: PHP依赖安装失败${NC}"
    exit 1
fi

# 步骤4: 配置环境文件
echo -e "${YELLOW}步骤4: 配置环境文件...${NC}"
if [ ! -f ".env" ]; then
    if [ -f ".env.example" ]; then
        cp .env.example .env
    else
        echo -e "${RED}错误: .env.example文件不存在${NC}"
        exit 1
    fi
fi

# 更新.env文件中的数据库配置
sed -i 's/DB_CONNECTION=.*/DB_CONNECTION=mysql/' .env
sed -i 's/DB_HOST=.*/DB_HOST=127.0.0.1/' .env
sed -i 's/DB_PORT=.*/DB_PORT=3306/' .env
sed -i 's/DB_DATABASE=.*/DB_DATABASE=workorder_db/' .env
sed -i 's/DB_USERNAME=.*/DB_USERNAME=cdu/' .env
sed -i 's/DB_PASSWORD=.*/DB_PASSWORD=REDACTED_MYSQL_PASS/' .env

# 设置其他配置
sed -i 's/APP_ENV=.*/APP_ENV=production/' .env
sed -i 's/APP_DEBUG=.*/APP_DEBUG=false/' .env
sed -i 's/APP_URL=.*/APP_URL=http:\/\/localhost/' .env

# 生成应用密钥
php artisan key:generate --force

echo -e "${GREEN}✓ 环境文件配置完成${NC}"

# 步骤5: 导入数据库
echo -e "${YELLOW}步骤5: 导入数据库...${NC}"

# 查找数据库文件
DB_FILE=$(find "$WEB_DIR" -name "workorder_database_*.sql.gz" | head -1)
if [ -z "$DB_FILE" ]; then
    echo -e "${RED}错误: 未找到数据库文件${NC}"
    exit 1
fi

echo -e "${GREEN}找到数据库文件: $DB_FILE${NC}"

# 导入数据库
if gunzip -c "$DB_FILE" | mysql -u cdu -pREDACTED_MYSQL_PASS workorder_db; then
    echo -e "${GREEN}✓ 数据库导入完成${NC}"
else
    echo -e "${RED}错误: 数据库导入失败${NC}"
    exit 1
fi

# 步骤6: 运行数据库迁移（确保最新）
echo -e "${YELLOW}步骤6: 运行数据库迁移...${NC}"
if php artisan migrate --force; then
    echo -e "${GREEN}✓ 数据库迁移完成${NC}"
else
    echo -e "${YELLOW}⚠ 数据库迁移可能失败，但通常是因为表已存在${NC}"
fi

# 步骤7: 创建存储链接
echo -e "${YELLOW}步骤7: 创建存储链接...${NC}"
if php artisan storage:link; then
    echo -e "${GREEN}✓ 存储链接创建完成${NC}"
else
    echo -e "${YELLOW}⚠ 存储链接可能已存在${NC}"
fi

# 步骤8: 设置目录权限
echo -e "${YELLOW}步骤8: 设置目录权限...${NC}"
chmod -R 755 "$WEB_DIR/storage"
chmod -R 755 "$WEB_DIR/bootstrap/cache"
chown -R www-data:www-data "$WEB_DIR/storage"
chown -R www-data:www-data "$WEB_DIR/bootstrap/cache"

echo -e "${GREEN}✓ 目录权限设置完成${NC}"

# 步骤9: 清除缓存
echo -e "${YELLOW}步骤9: 清除应用缓存...${NC}"
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

echo -e "${GREEN}✓ 缓存清除完成${NC}"

# 步骤10: 重启Apache
echo -e "${YELLOW}步骤10: 重启Apache服务...${NC}"
systemctl restart apache2
systemctl enable apache2

echo -e "${GREEN}✓ Apache服务重启完成${NC}"

# 步骤11: 验证部署
echo -e "${YELLOW}步骤11: 验证部署...${NC}"

# 检查Apache状态
if systemctl status apache2 --no-pager -l | grep -q "active (running)"; then
    echo -e "${GREEN}✓ Apache运行正常${NC}"
else
    echo -e "${RED}❌ Apache运行异常${NC}"
fi

# 测试网站访问
if curl -I http://127.0.0.1/ 2>/dev/null | grep -q "200 OK"; then
    echo -e "${GREEN}✓ 网站访问正常${NC}"
else
    echo -e "${YELLOW}⚠ 网站访问可能需要等待${NC}"
fi

# 完成部署
echo ""
echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}  部署完成！${NC}"
echo -e "${GREEN}========================================${NC}"
echo ""
echo -e "${BLUE}访问地址：${NC}"
echo -e "  系统地址：${YELLOW}http://服务器IP地址${NC}"
echo ""
echo -e "${BLUE}默认登录账户：${NC}"
echo -e "  系统管理员：${YELLOW}用户名: admin / 密码: admin123${NC}"
echo -e "  工程师：${YELLOW}用户名: wangyang / 密码: (数据库中未设置默认密码)${NC}"
echo -e "  普通用户：${YELLOW}用户名: (请查看数据库) / 密码: (请查看数据库)${NC}"
echo ""
echo -e "${BLUE}注意事项：${NC}"
echo -e "  1. 首次登录后请立即修改默认密码"
echo -e "  2. 请检查防火墙设置确保80端口开放"
echo -e "  3. 如有问题，请查看Apache日志：/var/log/apache2/error.log"
echo -e "  4. 项目目录：${WEB_DIR}"
echo ""
echo -e "${GREEN}部署成功！${NC}"
EOF

chmod +x "${TEMP_DIR}/deploy_to_ubuntu.sh"

# 步骤5: 创建部署说明文档
echo -e "${YELLOW}步骤5: 创建部署说明文档...${NC}"
cat > "${TEMP_DIR}/DEPLOYMENT_GUIDE.md" << EOF
# 校园网工单系统 - Ubuntu Server 24 部署指南

## 快速部署

### 一键部署（推荐）

\`\`\`bash
# 解压部署包
tar -xzf ${FULL_PACKAGE_NAME}.tar.gz
cd ${FULL_PACKAGE_NAME}

# 运行一键部署脚本
sudo bash deploy_to_ubuntu.sh
\`\`\`

### 手动部署

#### 1. 环境准备
\`\`\`bash
# 运行环境配置脚本
sudo bash ubuntu_server_setup.sh
\`\`\`

#### 2. 部署项目
\`\`\`bash
# 复制项目文件到Web目录
sudo cp -r * /var/www/workorder/
sudo chown -R www-data:www-data /var/www/workorder
sudo chmod -R 755 /var/www/workorder

# 安装依赖
cd /var/www/workorder
sudo composer install --no-dev --optimize-autoloader

# 配置环境
sudo cp .env.example .env
sudo php artisan key:generate
\`\`\`

#### 3. 导入数据库
\`\`\`bash
# 查找数据库文件
ls workorder_database_*.sql.gz

# 导入数据库
gunzip -c workorder_database_*.sql.gz | mysql -u cdu -pREDACTED_MYSQL_PASS workorder_db
\`\`\`

#### 4. 完成部署
\`\`\`bash
# 运行迁移
sudo php artisan migrate --force

# 创建存储链接
sudo php artisan storage:link

# 设置权限
sudo chmod -R 755 storage bootstrap/cache
sudo chown -R www-data:www-data storage bootstrap/cache

# 清除缓存
sudo php artisan cache:clear
sudo php artisan config:clear
sudo php artisan route:clear
sudo php artisan view:clear

# 重启Apache
sudo systemctl restart apache2
\`\`\`

## 系统要求

- **操作系统**: Ubuntu Server 24
- **PHP**: >= 8.2
- **MySQL**: >= 8.0
- **Apache**: >= 2.4
- **Composer**: >= 2.0
- **Node.js**: >= 16 (可选，用于前端资源编译)

## 默认配置

### 数据库配置
- **主机**: 127.0.0.1:3306
- **数据库名**: workorder_db
- **用户名**: cdu
- **密码**: REDACTED_MYSQL_PASS

### Web目录
- **路径**: /var/www/workorder
- **Apache配置**: /etc/apache2/sites-available/workorder.conf

### 默认账户
| 角色 | 用户名 | 密码 | 说明 |
|------|--------|------|------|
| 系统管理员 | admin | admin123 | 拥有系统所有权限 |
| 工程师 | wangyang | (请查看数据库) | 负责工单处理 |
| 普通用户 | (请查看数据库) | (请查看数据库) | 基础工单操作权限 |

**安全提示**：首次登录后请立即修改默认密码！

## 故障排除

### 常见问题

1. **Apache 403 错误**
   \`\`\`bash
   sudo chmod -R 755 /var/www/workorder
   sudo chown -R www-data:www-data /var/www/workorder
   \`\`\`

2. **数据库连接失败**
   \`\`\`bash
   # 检查MySQL服务状态
   sudo systemctl status mysql
   
   # 检查数据库用户权限
   mysql -u root -p -e "SHOW GRANTS FOR 'cdu'@'localhost';"
   \`\`\`

3. **Composer 依赖安装失败**
   \`\`\`bash
   # 更新Composer
   sudo composer self-update
   
   # 清理缓存重新安装
   sudo composer clear-cache
   sudo composer install --no-dev --optimize-autoloader
   \`\`\`

### 日志文件

- **Apache错误日志**: /var/log/apache2/error.log
- **Apache访问日志**: /var/log/apache2/access.log
- **Laravel日志**: /var/www/workorder/storage/logs/laravel.log
- **MySQL错误日志**: /var/log/mysql/error.log

## 技术支持

如遇到部署问题，请参考：
- 项目文档：README.md
- 用户手册：USER_MANUAL.md
- 开发者指南：DEVELOPER_GUIDE.md

## 版本信息

- **版本号**: ${VERSION}
- **打包时间**: $(date)
- **Laravel版本**: 12.x
- **PHP要求**: >= 8.2
- **MySQL要求**: >= 8.0
EOF

# 步骤6: 清理不必要的文件
echo -e "${YELLOW}步骤6: 清理不必要的文件...${NC}"
find "${TEMP_DIR}" -name ".git*" -exec rm -rf {} + 2>/dev/null || true
find "${TEMP_DIR}" -name "node_modules" -exec rm -rf {} + 2>/dev/null || true
find "${TEMP_DIR}" -name "vendor" -exec rm -rf {} + 2>/dev/null || true
find "${TEMP_DIR}" -name "*.log" -delete 2>/dev/null || true
find "${TEMP_DIR}" -name ".DS_Store" -delete 2>/dev/null || true
find "${TEMP_DIR}" -name "Thumbs.db" -delete 2>/dev/null || true

# 创建必要的目录
echo -e "${YELLOW}创建必要目录...${NC}"
mkdir -p "${TEMP_DIR}/storage/logs"
mkdir -p "${TEMP_DIR}/storage/framework/cache"
mkdir -p "${TEMP_DIR}/storage/framework/sessions"
mkdir -p "${TEMP_DIR}/storage/framework/views"
mkdir -p "${TEMP_DIR}/storage/app/public"

# 步骤7: 创建版本信息文件
echo -e "${YELLOW}步骤7: 创建版本信息文件...${NC}"
cat > "${TEMP_DIR}/PACKAGE_INFO.json" << EOF
{
    "name": "${PROJECT_NAME}",
    "version": "${VERSION}",
    "build_timestamp": "${TIMESTAMP}",
    "build_date": "$(date -d @$(date +%s) +%Y-%m-%d\ %H:%M:%S)",
    "target_os": "Ubuntu Server 24",
    "database_file": "$(basename $LATEST_DB_FILE)",
    "requirements": {
        "php": ">=8.2",
        "mysql": ">=8.0",
        "apache": ">=2.4",
        "composer": ">=2.0"
    },
    "deployment": {
        "web_directory": "/var/www/workorder",
        "database_name": "workorder_db",
        "database_user": "cdu",
        "database_password": "REDACTED_MYSQL_PASS"
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
    "scripts": {
        "environment_setup": "ubuntu_server_setup.sh",
        "database_export": "export_workorder_database.sh",
        "deployment": "deploy_to_ubuntu.sh"
    }
}
EOF

# 步骤8: 创建压缩包
echo -e "${YELLOW}步骤8: 创建压缩包...${NC}"
cd /tmp
tar -czf "${FULL_PACKAGE_NAME}.tar.gz" "${FULL_PACKAGE_NAME}/"

# 获取脚本所在目录（项目根目录）
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

# 移动到packages目录
PACKAGES_DIR="${SCRIPT_DIR}/packages"
mkdir -p "$PACKAGES_DIR"
if mv "/tmp/${FULL_PACKAGE_NAME}.tar.gz" "${PACKAGES_DIR}/"; then
    echo -e "${GREEN}✓ 部署包已移动到packages目录${NC}"
else
    echo -e "${RED}❌ 部署包移动失败${NC}"
    echo -e "${YELLOW}部署包位置: /tmp/${FULL_PACKAGE_NAME}.tar.gz${NC}"
    echo -e "${YELLOW}目标目录: ${PACKAGES_DIR}${NC}"
fi

# 计算文件大小和校验和
cd "${PACKAGES_DIR}"
PACKAGE_SIZE=$(du -h "${FULL_PACKAGE_NAME}.tar.gz" | cut -f1)
SHA256_PACKAGE=$(sha256sum "${FULL_PACKAGE_NAME}.tar.gz" | cut -d' ' -f1)

# 清理临时目录
rm -rf "${TEMP_DIR}"

# 创建打包信息文件
cat > "${FULL_PACKAGE_NAME}_DEPLOYMENT_INFO.txt" << EOF
========================================
校园网工单系统 - 完整部署包信息
========================================

项目名称: ${PROJECT_NAME}
版本号: ${VERSION}
打包时间: $(date)
时间戳: ${TIMESTAMP}
目标系统: Ubuntu Server 24

文件信息:
- 部署包: ${FULL_PACKAGE_NAME}.tar.gz (${PACKAGE_SIZE})
- 数据库文件: $(basename $LATEST_DB_FILE)

校验和:
- 部署包 SHA256: ${SHA256_PACKAGE}

快速部署:
1. 上传部署包到Ubuntu Server 24
2. 解压: tar -xzf ${FULL_PACKAGE_NAME}.tar.gz
3. 部署: cd ${FULL_PACKAGE_NAME} && sudo bash deploy_to_ubuntu.sh

包含的脚本:
- ubuntu_server_setup.sh: 环境配置脚本
- export_workorder_database.sh: 数据库导出脚本
- deploy_to_ubuntu.sh: 一键部署脚本

数据库配置:
- 主机: 127.0.0.1:3306
- 数据库: workorder_db
- 用户名: cdu
- 密码: REDACTED_MYSQL_PASS

Web目录: /var/www/workorder

默认账户:
- 系统管理员: 用户名"admin" / 密码"admin123"
- 工程师: 用户名"wangyang" / 密码"(请查看数据库设置)"
- 普通用户: 用户名"(请查看数据库)" / 密码"(请查看数据库设置)"

注意事项:
- 请确保服务器满足环境要求
- 部署前请备份现有数据（如有）
- 首次登录后请修改默认密码
- 请检查防火墙设置确保80端口开放

技术支持:
- 部署指南: DEPLOYMENT_GUIDE.md
- 用户手册: USER_MANUAL.md
========================================
EOF

echo ""
echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}  部署包创建完成！${NC}"
echo -e "${GREEN}========================================${NC}"
echo ""
echo -e "${YELLOW}生成的文件:${NC}"
echo -e "  - ${FULL_PACKAGE_NAME}.tar.gz (${PACKAGE_SIZE})"
echo -e "  - ${FULL_PACKAGE_NAME}_DEPLOYMENT_INFO.txt"
echo -e "  - $(basename $LATEST_DB_FILE)"
echo ""
echo -e "${YELLOW}校验和:${NC}"
echo -e "  SHA256: ${SHA256_PACKAGE}"
echo ""
echo -e "${BLUE}部署说明:${NC}"
echo -e "  1. 将 ${FULL_PACKAGE_NAME}.tar.gz 上传到Ubuntu Server 24"
echo -e "  2. 解压: tar -xzf ${FULL_PACKAGE_NAME}.tar.gz"
echo -e "  3. 运行: cd ${FULL_PACKAGE_NAME} && sudo bash deploy_to_ubuntu.sh"
echo ""
echo -e "${GREEN}部署包创建成功！可以开始部署到新服务器。${NC}"