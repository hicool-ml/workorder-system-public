# 🚀 如何部署到其他服务器

## 📋 快速部署（推荐）

### 一键部署命令
```bash
# 1. 下载最新部署包
wget https://your-source-server.com/packages/campus-workorder-system-v1.0.0_*.tar.gz

# 2. 解压并部署
tar -xzf campus-workorder-system-v1.0.0_*.tar.gz
cd campus-workorder-system-v1.0.0_*/

# 3. 配置数据库（可选）
nano deploy_config.json
# 修改数据库连接信息

# 4. 运行自动部署
sudo ./auto_deploy.sh -e production -v

# 5. 完成！访问您的网站
# 前台：http://your-domain.com
# 后台：http://your-domain.com/admin
```

## 🔧 手动部署

### 环境要求
- PHP >= 8.1
- MySQL >= 8.0
- Apache >= 2.4 或 Nginx >= 1.18
- Composer >= 2.0

### 部署步骤
```bash
# 1. 环境检查
./check_dependencies.sh

# 2. 环境准备（如需要）
sudo ./setup_server.sh

# 3. 安装依赖
composer install --no-dev --optimize-autoloader

# 4. 配置环境
cp .env.example .env
php artisan key:generate

# 5. 数据库迁移
php artisan migrate --force
php artisan db:seed --force

# 6. 设置权限
chmod -R 755 storage/ bootstrap/cache/
```

## 📚 详细文档

| 场景 | 推荐文档 |
|------|----------|
| 🚀 快速部署 | [QUICK_DEPLOY_GUIDE.md](QUICK_DEPLOY_GUIDE.md) |
| 📖 完整部署 | [DEPLOY_TO_OTHER_SERVERS.md](DEPLOY_TO_OTHER_SERVERS.md) |
| 📚 文档索引 | [DEPLOYMENT_INDEX.md](DEPLOYMENT_INDEX.md) |
| 🔧 维护指南 | [DEPLOYMENT_MAINTENANCE_GUIDE.md](DEPLOYMENT_MAINTENANCE_GUIDE.md) |

## 🎯 默认账户

| 角色 | 邮箱 | 密码 |
|------|------|------|
| 系统管理员 | admin@workorder.com | admin123 |
| 工单管理员 | workorder_manager@workorder.com | admin123 |
| 工程师 | engineer@workorder.com | engineer123 |
| 普通用户 | user@workorder.com | user123 |

**⚠️ 安全提示**：首次登录后请立即修改默认密码！

## 🎯 部署完成后的步骤

### ⚡ 快速修复文件权限
如果出现 "Permission denied" 错误：

```bash
# 1. 切换到waverjiang用户
sudo su - waverjiang

# 2. 设置项目权限
sudo chown -R waverjiang:waverjiang ~/campus-workorder-system-v1.0.0_*
sudo find ~/campus-workorder-system-v1.0.0_* -type d -exec chmod 755 {} \;
sudo find ~/campus-workorder-system-v1.0.0_* -type f -exec chmod 644 {} \;

# 3. 设置可执行权限
sudo chmod +x ~/campus-workorder-system-v1.0.0_*/artisan
sudo chmod +x ~/campus-workorder-system-v1.0.0_*/*.sh

# 4. 进入项目目录
cd ~/campus-workorder-system-v1.0.0_*
```

### ⚡ 快速修复Composer权限
如果出现 "Composer plugins have been disabled" 错误：

```bash
# 方法1：设置环境变量
export COMPOSER_ALLOW_SUPERUSER=1
composer install --no-dev --optimize-autoloader

# 方法2：切换到非root用户（推荐）
sudo su - waverjiang
cd ~/campus-workorder-system-v1.0.0_*
composer install --no-dev --optimize-autoloader
```

### 🚀 一键完整修复脚本
```bash
#!/bin/bash
echo "=== 一键部署修复脚本 ==="

# 1. 切换到waverjiang用户
if [ "$EUID" -eq 0 ]; then
    echo "切换到waverjiang用户..."
    exec sudo su - waverjiang "$0" "$@"
fi

# 2. 进入项目目录
cd ~/campus-workorder-system-v1.0.0_*

# 3. 设置权限（如果需要）
echo "检查文件权限..."
if [ ! -w . ]; then
    echo "修复文件权限..."
    sudo chown -R waverjiang:waverjiang ~/campus-workorder-system-v1.0.0_*
    sudo find ~/campus-workorder-system-v1.0.0_* -type d -exec chmod 755 {} \;
    sudo find ~/campus-workorder-system-v1.0.0_* -type f -exec chmod 644 {} \;
    sudo chmod +x ~/campus-workorder-system-v1.0.0_*/artisan
fi

# 4. 设置Composer环境
export COMPOSER_ALLOW_SUPERUSER=1

# 5. 安装依赖
echo "安装Composer依赖..."
composer install --no-dev --optimize-autoloader

# 6. 生成应用密钥
echo "生成应用密钥..."
php artisan key:generate

# 7. 修复.env配置
echo "配置环境文件..."
cat > .env << EOF
APP_NAME="校园网工单系统"
APP_ENV=production
APP_DEBUG=false
APP_URL=http://117.176.215.210:14580

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=workorder_system
DB_USERNAME=workorder_user
DB_PASSWORD=your_password_here

BROADCAST_DRIVER=log
CACHE_DRIVER=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_LIFETIME=120

MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="\${APP_NAME}"
EOF

# 8. 清除缓存
echo "清除应用缓存..."
php artisan cache:clear
php artisan config:clear

# 9. 创建数据库
echo "创建数据库..."
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS workorder_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; CREATE USER IF NOT EXISTS 'workorder_user'@'localhost' IDENTIFIED BY 'your_password'; GRANT ALL PRIVILEGES ON workorder_system.* TO 'workorder_user'@'localhost'; FLUSH PRIVILEGES;" 2>/dev/null || echo "请手动创建数据库"

# 10. 运行迁移
echo "运行数据库迁移..."
php artisan migrate --force
php artisan db:seed --force

echo "=== 部署完成 ==="
echo "请修改 .env 文件中的数据库密码"
echo "然后配置Web服务器：参考 WEBSERVER_CONFIG_GUIDE.md"
```

### ⚡ 快速修复数据库配置
如果运行 `php artisan migrate` 时出现 "could not find driver" 错误：

```bash
# 1. 进入项目目录
cd ~/campus-workorder-system-v1.0.0_*

# 2. 修复.env配置
cat > .env << EOF
APP_NAME="校园网工单系统"
APP_ENV=production
APP_DEBUG=false
APP_URL=http://117.176.215.210:14580

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=workorder_system
DB_USERNAME=workorder_user
DB_PASSWORD=your_password_here

BROADCAST_DRIVER=log
CACHE_DRIVER=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_LIFETIME=120

MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="\${APP_NAME}"
EOF

# 3. 生成应用密钥
php artisan key:generate

# 4. 清除缓存
php artisan cache:clear
php artisan config:clear

# 5. 创建数据库（如果还没有）
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS workorder_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; CREATE USER IF NOT EXISTS 'workorder_user'@'localhost' IDENTIFIED BY 'your_password'; GRANT ALL PRIVILEGES ON workorder_system.* TO 'workorder_user'@'localhost'; FLUSH PRIVILEGES;"

# 6. 运行迁移
php artisan migrate --force
php artisan db:seed --force
```

### 🚀 一键完整修复
```bash
#!/bin/bash
echo "=== 一键部署修复脚本 ==="

# 1. 进入项目目录
cd ~/campus-workorder-system-v1.0.0_*

# 2. 修复Composer权限
export COMPOSER_ALLOW_SUPERUSER=1

# 3. 设置文件权限
sudo chown -R waverjiang:waverjiang .
chmod -R 755 .

# 4. 安装依赖
composer install --no-dev --optimize-autoloader

# 5. 修复.env配置
cat > .env << EOF
APP_NAME="校园网工单系统"
APP_ENV=production
APP_DEBUG=false
APP_URL=http://117.176.215.210:14580

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=workorder_system
DB_USERNAME=workorder_user
DB_PASSWORD=your_password_here

BROADCAST_DRIVER=log
CACHE_DRIVER=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_LIFETIME=120

MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="\${APP_NAME}"
EOF

# 6. 生成应用密钥
php artisan key:generate

# 7. 清除缓存
php artisan cache:clear
php artisan config:clear

# 8. 创建数据库
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS workorder_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; CREATE USER IF NOT EXISTS 'workorder_user'@'localhost' IDENTIFIED BY 'your_password'; GRANT ALL PRIVILEGES ON workorder_system.* TO 'workorder_user'@'localhost'; FLUSH PRIVILEGES;"

# 9. 运行迁移
php artisan migrate --force
php artisan db:seed --force

echo "=== 修复完成 ==="
echo "请修改 .env 文件中的数据库密码"
echo "然后配置Web服务器：参考 WEBSERVER_CONFIG_GUIDE.md"
```

### 1. 完成数据库迁移
```bash
# 运行数据库迁移
php artisan migrate --force

# 导入种子数据
php artisan db:seed --force
```

### 2. 设置文件权限
```bash
chmod -R 755 storage/ bootstrap/cache/
sudo chown -R www-data:www-data storage/ bootstrap/cache/
```

### 3. 创建符号链接
```bash
php artisan storage:link
```

### 4. 清除缓存
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### 5. 验证部署
```bash
# 检查应用状态
php artisan about

# 访问应用
# 前台：http://117.176.215.210:14580
# 后台：http://117.176.215.210:14580/admin
```

## � 常见问题

### 500错误
```bash
# 检查权限
chmod -R 755 storage/ bootstrap/cache/
```

### 数据库连接失败
```bash
# 检查配置
cat .env
# 检查服务
sudo systemctl status mysql
```

### 文件上传失败
```bash
# 检查上传目录权限
ls -la storage/app/public/
chmod -R 755 storage/app/public/
```

### Node.js版本警告
```bash
# 如果看到Node.js版本警告，可以升级（可选）
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt-get install -y nodejs
npm install
npm run build
```

## 🌐 Web服务器配置

### ⚡ 强制修复Apache配置（推荐）
如果仍然显示Apache默认页面，请运行以下强制修复命令：

```bash
# 1. 完全重置Apache配置
sudo rm -f /etc/apache2/sites-enabled/*
sudo bash -c 'cat > /etc/apache2/sites-available/workorder.conf << EOF
<VirtualHost *:14580>
    ServerName 117.176.215.210
    DocumentRoot /home/waverjiang/campus-workorder-system-v1.0.0_20251121_183153/public
    
    <Directory /home/waverjiang/campus-workorder-system-v1.0.0_20251121_183153/public>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog \${APACHE_LOG_DIR}/workorder_error.log
    CustomLog \${APACHE_LOG_DIR}/workorder_access.log combined
</VirtualHost>
EOF'

# 2. 启用配置和模块
sudo a2ensite workorder.conf
sudo a2enmod rewrite
sudo a2enmod dir

# 3. 确保端口配置正确
sudo bash -c 'echo "Listen 14580" >> /etc/apache2/ports.conf'

# 4. 设置权限
sudo chown -R www-data:www-data /home/waverjiang/campus-workorder-system-v1.0.0_20251121_183153
sudo chmod -R 755 /home/waverjiang/campus-workorder-system-v1.0.0_20251121_183153

# 5. 重启Apache（重要：使用restart而不是reload）
sudo systemctl restart apache2

# 6. 验证配置
sudo a2query -s
sudo apache2ctl -S | grep 14580
```

### 🔍 诊断Apache配置
如果问题仍然存在，运行诊断脚本：

```bash
# 创建并运行诊断脚本
sudo bash -c 'cat > /tmp/apache_diagnosis.sh << EOF
#!/bin/bash
echo "=== Apache配置诊断 ==="
echo "1. 启用的站点："
sudo a2query -s
echo -e "\n2. 端口监听："
sudo netstat -tlnp | grep :14580
echo -e "\n3. Apache状态："
sudo systemctl status apache2 --no-pager -l
echo -e "\n4. 配置语法："
sudo apache2ctl configtest
echo -e "\n5. 虚拟主机配置："
sudo apache2ctl -S | grep 14580
echo -e "\n6. 文件权限："
ls -la /home/waverjiang/campus-workorder-system-v1.0.0_20251121_183153/public/
echo -e "\n=== 诊断完成 ==="
EOF'

sudo chmod +x /tmp/apache_diagnosis.sh
sudo /tmp/apache_diagnosis.sh
```

### 🚨 如果仍然显示默认页面
尝试以下终极解决方案：

```bash
# 1. 直接修改默认配置文件
sudo nano /etc/apache2/sites-available/000-default.conf
# 将所有内容替换为：
<VirtualHost *:14580>
    ServerName 117.176.215.210
    DocumentRoot /home/waverjiang/campus-workorder-system-v1.0.0_20251121_183153/public
    <Directory /home/waverjiang/campus-workorder-system-v1.0.0_20251121_183153/public>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>

# 2. 重启Apache
sudo systemctl restart apache2

# 3. 清除浏览器缓存并重新访问
# 按 Ctrl+F5 强制刷新
```

## 📋 详细指南

如果需要更详细的指导，请参考：
- 📖 [Web服务器配置指南](WEBSERVER_CONFIG_GUIDE.md) - 详细的Web服务器配置
- 📖 [部署完成后的步骤](POST_DEPLOYMENT_STEPS.md) - 完整的后续步骤指南
- 🔧 [问题修复指南](DEPLOYMENT_FIX.md) - 常见问题解决方案
- 📚 [部署文档索引](DEPLOYMENT_INDEX.md) - 所有部署文档

## 📞 技术支持

- 📖 [完整部署文档](FINAL_DEPLOYMENT_SUMMARY.md)
- 📖 [用户手册](USER_MANUAL.md)
- 📖 [开发者指南](DEVELOPER_GUIDE.md)

---

**💡 提示**：建议首次部署使用一键部署命令，遇到问题再参考详细文档。