# 数据库设置指南

## 🔍 问题分析

从您的输出可以看到：
```
[INFO] 数据库名称: workorder_db
[INFO] 用户名: cdu
检查数据库连接...
错误: 无法连接到数据库
```

**问题原因：**
1. 数据库 `workorder_db` 还不存在
2. 用户 `cdu` 可能没有创建权限
3. MySQL服务可能需要配置

## 🚀 解决方案

### 步骤1：登录MySQL并创建数据库
```bash
# 以root用户登录MySQL
sudo mysql -u root -p

# 或者如果没有设置root密码
sudo mysql
```

### 步骤2：创建数据库和用户
在MySQL命令行中执行：

```sql
-- 创建数据库
CREATE DATABASE workorder_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 创建用户（如果用户不存在）
CREATE USER 'cdu'@'localhost' IDENTIFIED BY 'your_password';

-- 授权用户访问数据库
GRANT ALL PRIVILEGES ON workorder_db.* TO 'cdu'@'localhost';

-- 刷新权限
FLUSH PRIVILEGES;

-- 退出MySQL
EXIT;
```

### 步骤3：配置Laravel环境文件
```bash
# 编辑.env文件
nano .env
```

修改数据库配置：
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=workorder_db
DB_USERNAME=cdu
DB_PASSWORD=your_password  # 使用您在步骤2中设置的密码
```

### 步骤4：测试数据库连接
```bash
# 测试连接
mysql -u cdu -p workorder_db

# 应该能够成功连接并显示 MySQL提示符
```

### 步骤5：重新运行部署脚本
```bash
# 现在可以重新运行部署脚本
./auto_deploy.sh -e production -v
```

## 🔧 替代方案：跳过数据库备份

如果只是想快速部署，可以修改部署配置跳过数据库备份：

```bash
# 编辑部署配置
nano deploy_config.json

# 将 database_backup 设置为 false
{
    "production": {
        "database_backup": false,
        ...
    }
}
```

## 📋 完整的数据库设置命令

```bash
# 1. 登录MySQL
sudo mysql -u root -p

# 2. 在MySQL中执行
CREATE DATABASE workorder_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'cdu'@'localhost' IDENTIFIED BY 'your_strong_password';
GRANT ALL PRIVILEGES ON workorder_db.* TO 'cdu'@'localhost';
FLUSH PRIVILEGES;
EXIT;

# 3. 配置.env文件
nano .env

# 4. 设置密码
DB_PASSWORD=your_strong_password

# 5. 测试连接
mysql -u cdu -p workorder_db

# 6. 重新运行部署
./auto_deploy.sh -e production -v
```

## 🛠️ 如果MySQL root密码未知

### 方法1：重置MySQL root密码
```bash
# 停止MySQL服务
sudo systemctl stop mysql

# 以安全模式启动
sudo mysqld_safe --skip-grant-tables &

# 登录并重置密码
sudo mysql -u root

# 在MySQL中执行
USE mysql;
UPDATE user SET authentication_string=PASSWORD('new_root_password') WHERE User='root';
FLUSH PRIVILEGES;
EXIT;

# 重启MySQL
sudo systemctl restart mysql
```

### 方法2：使用sudo mysql
```bash
# 直接使用sudo登录
sudo mysql

# 然后创建用户和数据库
```

## 🎯 快速部署方案

如果您想跳过复杂的数据库设置：

```bash
# 1. 使用SQLite（最简单）
nano .env

# 修改为
DB_CONNECTION=sqlite
DB_DATABASE=/var/www/workorder/database/database.sqlite

# 2. 创建SQLite数据库文件
touch database/database.sqlite

# 3. 重新运行部署
./auto_deploy.sh -e production -v
```

## 📊 验证数据库设置

```bash
# 检查数据库是否创建
mysql -u root -p -e "SHOW DATABASES;"

# 检查用户权限
mysql -u root -p -e "SHOW GRANTS FOR 'cdu'@'localhost';"

# 测试Laravel连接
php artisan tinker --execute="DB::connection()->getPdo();"
```

## 🔐 安全建议

1. **使用强密码**：数据库密码应该包含大小写字母、数字和特殊字符
2. **限制用户权限**：只给必要的权限
3. **运行安全脚本**：`sudo mysql_secure_installation`
4. **定期备份**：设置自动数据库备份

## 📞 故障排除

### 问题1：Access denied
```bash
# 检查用户权限
sudo mysql -u root -p -e "SELECT User, Host FROM mysql.user;"

# 重新授权
sudo mysql -u root -p -e "GRANT ALL PRIVILEGES ON workorder_db.* TO 'cdu'@'localhost'; FLUSH PRIVILEGES;"
```

### 问题2：Can't connect to server
```bash
# 检查MySQL状态
sudo systemctl status mysql

# 启动MySQL
sudo systemctl start mysql

# 检查端口
sudo netstat -tlnp | grep :3306
```

### 问题3：Database doesn't exist
```bash
# 创建数据库
sudo mysql -u root -p -e "CREATE DATABASE workorder_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

---

**🎉 按照这个指南，您应该能够成功解决数据库连接问题并完成部署！**

关键是要先创建数据库和用户，然后配置正确的.env文件。