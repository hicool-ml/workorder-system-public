# 🔧 部署问题修复指南

## ❌ 常见问题：配置文件不存在

### 问题描述
```
[ERROR] 配置文件不存在: deploy_config.json
```

### 解决方案

#### 方法1：使用配置文件模板
```bash
# 1. 解压部署包
tar -xzf campus-workorder-system-v1.0.0_*.tar.gz
cd campus-workorder-system-v1.0.0_*/

# 2. 配置数据库信息
nano deploy_config.json
```

**配置文件示例**：
```json
{
    "database": {
        "connection": "mysql",
        "host": "127.0.0.1",
        "port": "3306",
        "database": "workorder_system",
        "username": "workorder_user",
        "password": "your_password_here"
    },
    "app": {
        "name": "校园网工单系统",
        "env": "production",
        "debug": false,
        "url": "http://your-domain.com"
    }
}
```

#### 方法2：使用简单部署脚本
```bash
# 使用内置的deploy.sh脚本（不需要配置文件）
./deploy.sh
```

#### 方法3：手动部署
```bash
# 1. 安装依赖
composer install --no-dev --optimize-autoloader

# 2. 配置环境
cp .env.example .env
nano .env

# 3. 生成密钥
php artisan key:generate

# 4. 运行迁移
php artisan migrate --force
php artisan db:seed --force

# 5. 设置权限
chmod -R 755 storage/ bootstrap/cache/
```

## 🔧 数据库配置

### 创建数据库和用户
```sql
-- 登录MySQL
mysql -u root -p

-- 创建数据库
CREATE DATABASE workorder_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 创建用户
CREATE USER 'workorder_user'@'localhost' IDENTIFIED BY 'your_strong_password';

-- 授权
GRANT ALL PRIVILEGES ON workorder_system.* TO 'workorder_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### 更新配置文件
```json
{
    "database": {
        "connection": "mysql",
        "host": "127.0.0.1",
        "port": "3306",
        "database": "workorder_system",
        "username": "workorder_user",
        "password": "your_strong_password"
    }
}
```

## 🚀 完整部署流程

### 使用自动部署脚本（推荐）
```bash
# 1. 下载部署包
wget https://your-source-server.com/packages/campus-workorder-system-v1.0.0_*.tar.gz

# 2. 解压
tar -xzf campus-workorder-system-v1.0.0_*.tar.gz
cd campus-workorder-system-v1.0.0_*/

# 3. 配置数据库
nano deploy_config.json

# 4. 运行自动部署
sudo ./auto_deploy.sh -e production -v
```

### 使用简单部署脚本
```bash
# 1. 下载和解压
wget https://your-source-server.com/packages/campus-workorder-system-v1.0.0_*.tar.gz
tar -xzf campus-workorder-system-v1.0.0_*.tar.gz
cd campus-workorder-system-v1.0.0_*/

# 2. 配置环境
cp .env.example .env
nano .env

# 3. 运行简单部署
./deploy.sh
```

## 🆘 其他常见问题

### 1. 权限问题
```bash
# 设置脚本执行权限
chmod +x *.sh

# 设置应用权限
chmod -R 755 storage/ bootstrap/cache/
```

### 2. 依赖问题
```bash
# 检查PHP版本
php --version

# 检查必需扩展
php -m | grep -E "(mysql|pdo|mbstring|curl|zip)"

# 安装缺失扩展
sudo apt install php8.1-mysql php8.1-mbstring php8.1-curl php8.1-zip
```

### 3. 数据库连接失败
```bash
# 测试数据库连接
mysql -u workorder_user -p workorder_system

# 检查MySQL服务
sudo systemctl status mysql

# 重启MySQL服务
sudo systemctl restart mysql
```

## 📞 获取帮助

如果仍然遇到问题，请参考：
- 📖 [完整部署指南](DEPLOY_TO_OTHER_SERVERS.md)
- 📖 [快速部署指南](QUICK_DEPLOY_GUIDE.md)
- 📖 [部署文档索引](DEPLOYMENT_INDEX.md)

---

**💡 提示**：最新版本的部署包已经包含了`deploy_config.json`配置文件，您只需要修改其中的数据库连接信息即可。