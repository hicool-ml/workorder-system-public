# 🔧 数据库配置修复指南

## ❌ 问题描述
运行 `php artisan migrate --force` 时出现错误：
```
could not find driver (Connection: sqlite, SQL: select exists...)
```

**原因**：Laravel正在尝试使用SQLite而不是MySQL，说明`.env`文件配置不正确。

## 🔍 诊断问题

### 1. 检查当前数据库配置
```bash
# 检查.env文件
cat .env | grep DB_

# 检查Laravel当前使用的数据库连接
php artisan tinker
>>> config('database.default');
>>> config('database.connections.mysql.host');
```

### 2. 检查PHP MySQL扩展
```bash
# 检查PHP扩展
php -m | grep -i mysql
php -m | grep -i pdo

# 检查PDO MySQL驱动
php -r "echo PDO::getAvailableDrivers();"
```

## 🔧 修复方案

### 方案1：修复.env配置（推荐）
```bash
# 1. 进入项目目录
cd ~/campus-workorder-system-v1.0.0_20251121_184312

# 2. 备份现有.env文件
cp .env .env.backup

# 3. 创建正确的.env配置
cat > .env << EOF
APP_NAME="校园网工单系统"
APP_ENV=production
APP_KEY=base64:$(php artisan key:generate --show)
APP_DEBUG=false
APP_URL=http://117.176.215.210:14580

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

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

MEMCACHED_HOST=127.0.0.1

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="\${APP_NAME}"

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false

PUSHER_APP_ID=
PUSHER_APP_KEY=
PUSHER_APP_SECRET=
PUSHER_HOST=
PUSHER_PORT=443
PUSHER_SCHEME=https
PUSHER_APP_CLUSTER=mt1

VITE_APP_NAME="\${APP_NAME}"
EOF

# 4. 替换数据库密码（请修改为实际密码）
sed -i 's/your_password_here/your_actual_password/' .env
```

### 方案2：使用.env.example重新创建
```bash
# 1. 使用.env.example作为模板
cp .env.example .env

# 2. 编辑.env文件
nano .env

# 3. 确保以下配置正确：
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=workorder_system
DB_USERNAME=workorder_user
DB_PASSWORD=your_actual_password

# 4. 重新生成应用密钥
php artisan key:generate
```

### 方案3：检查并安装PHP MySQL扩展
```bash
# 1. 安装PHP MySQL扩展
sudo apt update
sudo apt install -y php8.1-mysql php8.1-pdo-mysql

# 2. 重启PHP-FPM
sudo systemctl restart php8.1-fpm

# 3. 重启Apache
sudo systemctl restart apache2

# 4. 验证扩展
php -m | grep -i mysql
php -r "echo in_array('mysql', PDO::getAvailableDrivers()) ? 'MySQL driver available' : 'MySQL driver NOT available';"
```

## 🗄️ 数据库准备

### 1. 创建数据库和用户
```bash
# 登录MySQL
mysql -u root -p

# 创建数据库
CREATE DATABASE workorder_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# 创建用户
CREATE USER 'workorder_user'@'localhost' IDENTIFIED BY 'your_strong_password';

# 授权
GRANT ALL PRIVILEGES ON workorder_system.* TO 'workorder_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### 2. 测试数据库连接
```bash
# 测试MySQL连接
mysql -u workorder_user -p workorder_system

# 测试Laravel数据库连接
php artisan tinker
>>> DB::connection()->getPdo();
>>> DB::select('SELECT 1');
```

## 🚀 运行迁移

### 1. 清除缓存
```bash
# 清除Laravel缓存
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# 重新生成配置缓存
php artisan config:cache
```

### 2. 运行迁移
```bash
# 运行数据库迁移
php artisan migrate --force

# 导入种子数据
php artisan db:seed --force

# 或者一次性运行
php artisan migrate:fresh --seed --force
```

## 🔍 验证配置

### 1. 检查Laravel配置
```bash
# 检查数据库连接
php artisan tinker
>>> config('database.default');
>>> config('database.connections.mysql');
>>> DB::connection()->getDatabaseName();
```

### 2. 检查数据库表
```bash
# 检查数据库表
mysql -u workorder_user -p workorder_system
SHOW TABLES;
EXIT;
```

## 🆘 常见错误解决

### 1. "could not find driver" 错误
```bash
# 检查PDO驱动
php -r "print_r(PDO::getAvailableDrivers());"

# 如果没有mysql，安装扩展
sudo apt install php8.1-mysql php8.1-pdo-mysql
sudo systemctl restart php8.1-fpm
```

### 2. "Access denied for user" 错误
```bash
# 检查数据库用户
mysql -u root -p
SELECT User, Host FROM mysql.user WHERE User = 'workorder_user';
SHOW GRANTS FOR 'workorder_user'@'localhost';
```

### 3. "Unknown database" 错误
```bash
# 检查数据库是否存在
mysql -u root -p
SHOW DATABASES LIKE 'workorder_system';
```

## 📋 完整修复命令

```bash
#!/bin/bash
echo "=== 数据库配置修复脚本 ==="

# 1. 进入项目目录
cd ~/campus-workorder-system-v1.0.0_20251121_184312

# 2. 备份现有配置
cp .env .env.backup.$(date +%Y%m%d_%H%M%S)

# 3. 创建正确的.env配置
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

echo "请修改 .env 文件中的数据库密码"
echo "然后运行以下命令："
echo "1. php artisan key:generate"
echo "2. php artisan migrate --force"
echo "3. php artisan db:seed --force"

echo "=== 修复脚本完成 ==="
```

## 📞 获取帮助

如果问题仍然存在，请提供以下信息：

1. **PHP扩展信息**：
   ```bash
   php -m | grep -i mysql
   php -r "print_r(PDO::getAvailableDrivers());"
   ```

2. **.env配置**：
   ```bash
   cat .env | grep DB_
   ```

3. **错误详情**：
   ```bash
   php artisan migrate --force -v
   ```

---

**💡 提示**：确保MySQL服务正在运行：`sudo systemctl status mysql`