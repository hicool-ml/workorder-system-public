# 🔧 Composer配置修复指南

## ❌ 问题描述
运行Composer命令时出现错误：
```
Composer plugins have been disabled for safety in this non-interactive session. 
Set COMPOSER_ALLOW_SUPERUSER=1 if you want to allow plugins to run as root/super user.
```

## 🔍 问题分析

**原因**：Composer检测到以root用户运行，为了安全禁用了插件功能。

**解决方案**：设置环境变量允许root用户运行，或使用非root用户。

## 🔧 解决方案

### 方案1：设置环境变量（推荐）
```bash
# 1. 设置环境变量
export COMPOSER_ALLOW_SUPERUSER=1

# 2. 运行Composer命令
composer install --no-dev --optimize-autoloader

# 3. 或者一次性设置
COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --optimize-autoloader
```

### 方案2：使用非root用户（推荐）
```bash
# 1. 切换到项目所有者
sudo su - waverjiang

# 2. 进入项目目录
cd ~/campus-workorder-system-v1.0.0_*

# 3. 运行Composer命令
composer install --no-dev --optimize-autoloader

# 4. 设置权限（如果需要）
sudo chown -R waverjiang:waverjiang /home/waverjiang/campus-workorder-system-v1.0.0_*
```

### 方案3：全局设置Composer配置
```bash
# 1. 全局允许root用户
sudo bash -c 'echo "export COMPOSER_ALLOW_SUPERUSER=1" >> /etc/environment'

# 2. 或者设置在用户配置中
echo 'export COMPOSER_ALLOW_SUPERUSER=1' >> ~/.bashrc
source ~/.bashrc

# 3. 运行Composer命令
composer install --no-dev --optimize-autoloader
```

### 方案4：修改系统Composer配置
```bash
# 1. 创建Composer全局配置目录
sudo mkdir -p /root/.config/composer

# 2. 创建配置文件
sudo bash -c 'cat > /root/.config/composer/config.json << EOF
{
    "config": {
        "allow-plugins": true
    }
}
EOF'

# 3. 运行Composer命令
composer install --no-dev --optimize-autoloader
```

## 🚀 完整的部署修复流程

### 步骤1：修复Composer权限
```bash
# 进入项目目录
cd ~/campus-workorder-system-v1.0.0_*

# 方法1：设置环境变量
export COMPOSER_ALLOW_SUPERUSER=1

# 方法2：切换到非root用户（推荐）
sudo su - waverjiang
cd ~/campus-workorder-system-v1.0.0_*
```

### 步骤2：运行Composer安装
```bash
# 安装依赖
composer install --no-dev --optimize-autoloader

# 如果仍然有问题，尝试：
composer install --no-dev --optimize-autoloader --no-plugins
```

### 步骤3：修复数据库配置
```bash
# 修复.env配置
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

# 生成应用密钥
php artisan key:generate
```

### 步骤4：运行迁移
```bash
# 清除缓存
php artisan cache:clear
php artisan config:clear

# 运行迁移
php artisan migrate --force
php artisan db:seed --force
```

## 🔍 验证修复

### 检查Composer状态
```bash
# 检查Composer版本
composer --version

# 检查Composer配置
composer config --list

# 检查插件状态
composer show --installed
```

### 检查Laravel应用
```bash
# 检查应用状态
php artisan about

# 检查自动加载
php artisan tinker
>>> app()->version();
```

## 🆘 常见问题

### 1. "Permission denied" 错误
```bash
# 设置正确的文件所有者
sudo chown -R waverjiang:waverjiang /home/waverjiang/campus-workorder-system-v1.0.0_*

# 设置正确的权限
chmod -R 755 /home/waverjiang/campus-workorder-system-v1.0.0_*
```

### 2. "Plugin disabled" 错误
```bash
# 临时允许所有插件
export COMPOSER_ALLOW_SUPERUSER=1

# 或者禁用特定插件
composer install --no-dev --optimize-autoloader --no-plugins
```

### 3. "Memory limit" 错误
```bash
# 增加PHP内存限制
php -d memory_limit=512M /usr/local/bin/composer install --no-dev --optimize-autoloader

# 或者设置在php.ini中
echo "memory_limit = 512M" | sudo tee -a /etc/php/8.1/cli/php.ini
```

## 📋 一键修复脚本

```bash
#!/bin/bash
echo "=== Composer和部署修复脚本 ==="

# 1. 进入项目目录
cd ~/campus-workorder-system-v1.0.0_*

# 2. 设置环境变量
export COMPOSER_ALLOW_SUPERUSER=1

# 3. 设置权限
sudo chown -R waverjiang:waverjiang .
chmod -R 755 .

# 4. 切换到非root用户（推荐）
if [ "$EUID" -eq 0 ]; then
    echo "切换到waverjiang用户..."
    sudo su - waverjiang -c "
        cd ~/campus-workorder-system-v1.0.0_* &&
        export COMPOSER_ALLOW_SUPERUSER=1 &&
        composer install --no-dev --optimize-autoloader &&
        php artisan key:generate &&
        php artisan migrate --force &&
        php artisan db:seed --force
    "
else
    echo "当前用户不是root，直接运行..."
    composer install --no-dev --optimize-autoloader
    php artisan key:generate
    php artisan migrate --force
    php artisan db:seed --force
fi

echo "=== 修复完成 ==="
```

## 📞 获取帮助

如果问题仍然存在，请提供以下信息：

1. **当前用户信息**：
   ```bash
   whoami
   id
   ```

2. **Composer信息**：
   ```bash
   composer --version
   composer config --list
   ```

3. **错误详情**：
   ```bash
   composer install --no-dev --optimize-autoloader -vvv
   ```

---

**💡 提示**：推荐使用非root用户运行Composer命令，这是更安全和标准的做法。