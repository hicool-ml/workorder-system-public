# Laravel工单系统完整部署工作流程

## 🎯 部署策略概述

我们的部署方案采用**三层依赖管理**策略：

1. **系统级依赖** - 服务器基础环境
2. **应用级依赖** - Laravel项目依赖
3. **运行时依赖** - 数据和服务依赖

## 📋 完整部署检查清单

### 阶段1：源服务器准备
- [ ] 运行项目打包脚本：`./package_project.sh`
- [ ] 验证生成的压缩包：`ls -la campus-workorder-system-v*.tar.gz`
- [ ] 检查打包信息：`cat campus-workorder-system-v*_PACKAGE_INFO.txt`

### 阶段2：目标服务器环境检查
- [ ] 上传依赖检查脚本：`scp check_dependencies.sh user@server:/path/`
- [ ] 运行依赖检查：`./check_dependencies.sh`
- [ ] 确认所有必需依赖已安装
- [ ] 如果有失败项，运行环境准备脚本：`./setup_server.sh`

### 阶段3：项目部署
- [ ] 上传项目包：`scp campus-workorder-system-v*.tar.gz user@server:/path/`
- [ ] 解压项目：`tar -xzf campus-workorder-system-v*.tar.gz`
- [ ] 进入项目目录：`cd campus-workorder-system-v*`

### 阶段4：依赖安装和配置
- [ ] 安装PHP依赖：`composer install --no-dev --optimize-autoloader`
- [ ] 安装前端依赖：`npm install --production`
- [ ] 编译前端资源：`npm run build`
- [ ] 复制环境配置：`cp .env.example .env`
- [ ] 生成应用密钥：`php artisan key:generate`

### 阶段5：数据库设置
- [ ] 创建数据库：`mysql -u root -p` → `CREATE DATABASE workorder;`
- [ ] 创建数据库用户：`CREATE USER 'workorder_user'@'localhost' IDENTIFIED BY 'password';`
- [ ] 授权用户：`GRANT ALL PRIVILEGES ON workorder.* TO 'workorder_user'@'localhost';`
- [ ] 配置.env文件：`nano .env`（设置数据库连接）
- [ ] 运行数据库迁移：`php artisan migrate --force`
- [ ] 导入种子数据：`php artisan db:seed --force`

### 阶段6：系统配置
- [ ] 设置文件权限：`chmod -R 775 storage bootstrap/cache`
- [ ] 创建符号链接：`php artisan storage:link`
- [ ] 清理缓存：`php artisan config:clear && php artisan route:clear && php artisan view:clear`
- [ ] 优化缓存：`php artisan config:cache && php artisan route:cache && php artisan view:cache`

### 阶段7：Web服务器配置
- [ ] 配置Nginx/Apache虚拟主机
- [ ] 设置文档根目录指向public/
- [ ] 启用HTTPS（可选）
- [ ] 重启Web服务器：`sudo systemctl restart nginx/apache2`

### 阶段8：验证和测试
- [ ] 运行健康检查：`php artisan tinker --execute="echo 'OK';"`
- [ ] 测试数据库连接：`php artisan db:show`
- [ ] 访问应用程序：`http://your-domain.com`
- [ ] 使用默认账户登录测试
- [ ] 修改默认密码

## 🔧 依赖管理详解

### 系统级依赖（setup_server.sh处理）

**PHP环境和扩展**：
```bash
# Ubuntu/Debian
sudo apt install -y php8.3 php8.3-fpm php8.3-mysql \
    php8.3-mbstring php8.3-tokenizer php8.3-xml \
    php8.3-ctype php8.3-fileinfo php8.3-json \
    php8.3-bcmath php8.3-openssl php8.3-gd \
    php8.3-curl php8.3-zip
```

**Node.js和构建工具**：
```bash
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs
```

**Web服务器**：
```bash
# Nginx（推荐）
sudo apt install -y nginx
# 或 Apache
sudo apt install -y apache2 libapache2-mod-php8.3
```

**数据库**：
```bash
# MySQL（推荐）
sudo apt install -y mysql-server
# 或 PostgreSQL
sudo apt install -y postgresql postgresql-contrib
```

### 应用级依赖（composer.json定义）

**Laravel核心依赖**：
- `laravel/framework^12.0` - Laravel框架
- `laravel/tinker^2.10.1` - Laravel调试工具

**自动安装命令**：
```bash
composer install --no-dev --optimize-autoloader
```

### 前端依赖（package.json定义）

**构建工具和框架**：
- `vite^7.0.7` - 前端构建工具
- `@tailwindcss/vite^4.0.0` - CSS框架
- `axios^1.11.0` - HTTP客户端

**自动安装和编译**：
```bash
npm install --production
npm run build
```

## 🚨 常见依赖问题和解决方案

### 1. PHP扩展缺失
```bash
# 检查缺失的扩展
php -m | grep 扩展名

# 安装缺失的扩展
sudo apt install -y php8.3-扩展名

# 重启PHP-FPM
sudo systemctl restart php8.3-fpm
```

### 2. Composer版本过低
```bash
# 更新Composer
composer self-update

# 或重新安装
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

### 3. Node.js版本问题
```bash
# 使用nvm管理Node.js版本
curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.39.0/install.sh | bash
source ~/.bashrc
nvm install 20
nvm use 20
```

### 4. 数据库连接失败
```bash
# 检查数据库服务
sudo systemctl status mysql

# 测试连接
mysql -h localhost -u workorder_user -p workorder

# 检查PHP MySQL扩展
php -m | grep mysql
```

### 5. 权限问题
```bash
# 修复Laravel目录权限
sudo chown -R www-data:www-data /path/to/project
chmod -R 775 storage bootstrap/cache
```

## 🔄 自动化 vs 手动部署对比

| 方面 | 自动化脚本 | 手动部署 |
|------|------------|----------|
| **速度** | ⚡ 快速（5-10分钟） | 🐌 慢速（30-60分钟） |
| **可靠性** | ✅ 高（经过测试） | ⚠️ 中（依赖经验） |
| **错误处理** | ✅ 自动检测和修复 | ❌ 需要手动排查 |
| **一致性** | ✅ 标准化 | ⚠️ 可能不一致 |
| **灵活性** | ⚠️ 较低 | ✅ 高度可定制 |
| **学习成本** | ✅ 低 | ❌ 高 |

## 📊 部署时间估算

| 服务器类型 | 自动化部署 | 手动部署 |
|------------|------------|----------|
| **全新Ubuntu服务器** | 10-15分钟 | 45-90分钟 |
| **已有LAMP环境** | 5-10分钟 | 20-30分钟 |
| **Windows WSL环境** | 15-20分钟 | 60-120分钟 |

## 🎯 推荐部署策略

### 生产环境（推荐）
```bash
# 1. 环境检查
./check_dependencies.sh

# 2. 自动环境准备
./setup_server.sh

# 3. 自动部署
./auto_deploy.sh -e production -v
```

### 开发/测试环境
```bash
# 1. 快速手动部署
composer install --no-dev --optimize-autoloader
npm install && npm run build
cp .env.example .env && php artisan key:generate
php artisan migrate --force
```

## 📞 技术支持

如果在部署过程中遇到问题：

1. **运行依赖检查**：`./check_dependencies.sh`
2. **查看详细日志**：
   - Laravel日志：`storage/logs/laravel.log`
   - PHP错误日志：`/var/log/php8.3-fpm.log`
   - Web服务器日志：`/var/log/nginx/error.log`
3. **使用验证脚本**：`./verify_deployment.sh`

---

**🎉 遵循此工作流程，您可以确保Laravel工单系统在任何服务器上都能成功部署！**