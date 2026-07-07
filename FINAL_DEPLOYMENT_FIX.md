# 🚀 最终部署修复方案

## ❌ 当前问题
用户仍然遇到Composer权限错误：
```
Composer plugins have been disabled for safety in this non-interactive session. 
Set COMPOSER_ALLOW_SUPERUSER=1 if you want to allow plugins to run as root/super user.
```

## 🎯 终极解决方案

### 方案1：直接使用sudo运行（最简单）
```bash
# 1. 进入项目目录
cd ~/campus-workorder-system-v1.0.0_*

# 2. 直接使用sudo运行所有命令
sudo COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --optimize-autoloader
sudo php artisan key:generate
sudo php artisan migrate --force
sudo php artisan db:seed --force
```

### 方案2：修改全局Composer配置
```bash
# 1. 创建全局Composer配置
sudo mkdir -p /root/.config/composer
sudo bash -c 'cat > /root/.config/composer/config.json << EOF
{
    "config": {
        "allow-plugins": true,
        "allow-superuser": true
    }
}
EOF'

# 2. 运行Composer命令
sudo composer install --no-dev --optimize-autoloader
sudo php artisan key:generate
sudo php artisan migrate --force
sudo php artisan db:seed --force
```

### 方案3：禁用插件运行
```bash
# 1. 禁用插件运行
sudo composer install --no-dev --optimize-autoloader --no-plugins

# 2. 手动生成自动加载
sudo composer dump-autoload --optimize

# 3. 运行Laravel命令
sudo php artisan key:generate
sudo php artisan migrate --force
sudo php artisan db:seed --force
```

## 🚀 完整的一键部署脚本

```bash
#!/bin/bash
echo "=== 终极部署修复脚本 ==="

# 1. 进入项目目录
cd ~/campus-workorder-system-v1.0.0_*

# 2. 设置权限（如果需要）
sudo chown -R root:root .
sudo chmod -R 755 .

# 3. 方法1：使用环境变量
echo "尝试方法1：设置环境变量..."
sudo COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --optimize-autoloader

if [ $? -eq 0 ]; then
    echo "✅ 方法1成功！"
    SUCCESS=1
else
    echo "❌ 方法1失败，尝试方法2..."
    
    # 4. 方法2：禁用插件
    echo "尝试方法2：禁用插件..."
    sudo composer install --no-dev --optimize-autoloader --no-plugins
    
    if [ $? -eq 0 ]; then
        echo "✅ 方法2成功！"
        sudo composer dump-autoload --optimize
        SUCCESS=1
    else
        echo "❌ 方法2失败，尝试方法3..."
        
        # 5. 方法3：修改全局配置
        echo "尝试方法3：修改全局配置..."
        sudo mkdir -p /root/.config/composer
        sudo bash -c 'cat > /root/.config/composer/config.json << EOF
{
    "config": {
        "allow-plugins": true,
        "allow-superuser": true
    }
}
EOF'
        
        sudo composer install --no-dev --optimize-autoloader
        
        if [ $? -eq 0 ]; then
            echo "✅ 方法3成功！"
            SUCCESS=1
        else
            echo "❌ 所有方法都失败了"
            SUCCESS=0
        fi
    fi
fi

# 6. 如果Composer成功，继续Laravel部署
if [ "$SUCCESS" -eq 1 ]; then
    echo "开始Laravel部署..."
    
    # 生成应用密钥
    sudo php artisan key:generate
    
    # 修复.env配置
    sudo bash -c 'cat > .env << EOF
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
EOF'
    
    # 创建数据库
    echo "创建数据库..."
    mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS workorder_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; CREATE USER IF NOT EXISTS 'workorder_user'@'localhost' IDENTIFIED BY 'your_password'; GRANT ALL PRIVILEGES ON workorder_system.* TO 'workorder_user'@'localhost'; FLUSH PRIVILEGES;" 2>/dev/null || echo "请手动创建数据库"
    
    # 运行迁移
    echo "运行数据库迁移..."
    sudo php artisan migrate --force
    sudo php artisan db:seed --force
    
    # 设置Apache配置
    echo "配置Apache..."
    sudo bash -c 'cat > /etc/apache2/sites-available/workorder.conf << EOF
<VirtualHost *:14580>
    ServerName 117.176.215.210
    DocumentRoot /home/waverjiang/campus-workorder-system-v1.0.0_*/public
    
    <Directory /home/waverjiang/campus-workorder-system-v1.0.0_*/public>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
EOF'
    
    sudo a2ensite workorder.conf
    sudo a2dissite 000-default.conf
    sudo a2enmod rewrite
    sudo systemctl restart apache2
    
    echo "✅ 部署完成！"
    echo "请修改 .env 文件中的数据库密码"
    echo "然后访问：http://117.176.215.210:14580"
else
    echo "❌ 部署失败，请检查错误信息"
fi

echo "=== 脚本执行完成 ==="
```

## 🔧 手动部署步骤

如果脚本仍然失败，请按以下步骤手动执行：

### 1. 安装Composer依赖
```bash
cd ~/campus-workorder-system-v1.0.0_*

# 尝试以下任一方法：
# 方法A
sudo COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --optimize-autoloader

# 方法B
sudo composer install --no-dev --optimize-autoloader --no-plugins
sudo composer dump-autoload --optimize

# 方法C
sudo composer install --no-dev --optimize-autoloader --ignore-platform-reqs
```

### 2. 生成应用密钥
```bash
sudo php artisan key:generate
```

### 3. 配置环境文件
```bash
sudo nano .env
# 修改数据库配置为：
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=workorder_system
DB_USERNAME=workorder_user
DB_PASSWORD=your_actual_password
```

### 4. 运行数据库迁移
```bash
sudo php artisan migrate --force
sudo php artisan db:seed --force
```

### 5. 配置Web服务器
```bash
sudo systemctl restart apache2
# 访问 http://117.176.215.210:14580
```

## 🆘 故障排除

### 如果所有Composer方法都失败
```bash
# 1. 重新安装Composer
sudo curl -sS https://getcomposer.org/installer | sudo php
sudo mv composer.phar /usr/local/bin/composer

# 2. 使用本地Composer
sudo /usr/local/bin/composer install --no-dev --optimize-autoloader

# 3. 或者直接下载依赖
sudo mkdir -p vendor
cd /tmp
sudo composer create-project laravel/laravel temp-project
sudo cp -r temp-project/vendor/* ~/campus-workorder-system-v1.0.0_*/vendor/
sudo rm -rf temp-project
```

### 如果Laravel命令失败
```bash
# 检查PHP版本
sudo php --version

# 检查必需的PHP扩展
sudo php -m | grep -E "(pdo|mysql|mbstring|curl|zip)"

# 安装缺失扩展
sudo apt install -y php8.1-mysql php8.1-pdo-mysql php8.1-mbstring php8.1-curl php8.1-zip
```

## 📋 验证部署

### 检查部署状态
```bash
# 1. 检查文件
ls -la ~/campus-workorder-system-v1.0.0_*/

# 2. 检查Composer
sudo composer show --installed

# 3. 检查Laravel
sudo php artisan about

# 4. 检查Apache
sudo a2query -s
sudo systemctl status apache2
```

### 测试访问
```bash
# 本地测试
curl -I http://127.0.0.1:14580/

# 检查日志
sudo tail -f /var/log/apache2/error.log
```

## 🎯 默认登录信息

| 角色 | 邮箱 | 密码 |
|------|------|------|
| 系统管理员 | admin@workorder.com | admin123 |

---

**💡 提示**：如果所有方法都失败，请考虑重新下载部署包或联系技术支持。