# 当前情况部署指南

## 📍 您当前的状态

✅ **已完成步骤：**
1. 在项目服务器上运行 `./package_project.sh` 生成 `workorder-system_v20251121_123548.tar.gz`
2. 将压缩包上传到新服务器
3. 在新服务器上运行 `./setup_server.sh` 完成环境部署

## 🚀 接下来的正确步骤

### 步骤1：解压项目包
```bash
# 进入上传目录（假设您上传到了 /home/user）
cd /home/user

# 解压项目包
tar -xzf workorder-system_v20251121_123548.tar.gz

# 进入项目目录
cd workorder-system_v20251121_123548
```

### 步骤2：运行自动部署脚本
```bash
# 运行自动部署脚本
./auto_deploy.sh -e production -v
```

这个脚本会自动完成：
- 安装Composer依赖
- 设置应用密钥
- 创建必要的目录
- 设置文件权限
- 创建符号链接
- 清理和优化缓存

### 步骤3：配置数据库连接
```bash
# 编辑环境配置文件
nano .env
```

配置以下信息：
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=workorder_db
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### 步骤4：导入数据库
```bash
# 导入数据库
mysql -u your_username -p workorder_db < database.sql

# 或者如果是压缩版本
gunzip -c database.sql.gz | mysql -u your_username -p workorder_db
```

### 步骤5：运行数据库迁移
```bash
# 运行数据库迁移
php artisan migrate --force
```

### 步骤6：验证部署
```bash
# 运行部署验证
./verify_deployment.sh
```

## 🔧 如果遇到问题

### 问题1：auto_deploy.sh 报错
```bash
# 手动执行部署步骤
composer install --no-dev --optimize-autoloader
php artisan key:generate --force
chmod -R 775 storage bootstrap/cache
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 问题2：PHP扩展问题
```bash
# 运行PHP扩展修复脚本
./fix_php_extensions.sh
```

### 问题3：权限问题
```bash
# 修复权限
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

## 📋 完整的当前状态命令序列

```bash
# 1. 解压项目包
cd /home/user
tar -xzf workorder-system_v20251121_123548.tar.gz
cd workorder-system_v20251121_123548

# 2. 运行自动部署
./auto_deploy.sh -e production -v

# 3. 配置数据库
nano .env

# 4. 导入数据库
mysql -u username -p database_name < database.sql

# 5. 运行迁移
php artisan migrate --force

# 6. 验证部署
./verify_deployment.sh

# 7. 启动服务（可选）
php artisan serve --host=0.0.0.0 --port=8000
```

## 🎯 成功标志

当您看到以下输出时，表示部署成功：

```
========================================
  部署完成！
========================================
通过: 20
失败: 0
总计: 20

🎉 恭喜！部署验证完全通过！
```

## 🔐 默认登录账户

| 角色 | 邮箱 | 密码 |
|------|------|------|
| 管理员 | admin@workorder.com | admin123 |
| 工程师 | engineer@workorder.com | engineer123 |
| 用户 | user@workorder.com | user123 |

**⚠️ 重要：部署后请立即修改默认密码！**

## 📞 如果需要帮助

如果遇到任何问题，请按以下顺序排查：

1. **运行验证脚本**: `./verify_deployment.sh`
2. **运行修复脚本**: `./fix_php_extensions.sh`
3. **查看日志**: `tail -f storage/logs/laravel.log`
4. **检查服务状态**: `sudo systemctl status nginx mysql php8.3-fpm`

---

**🎉 您已经完成了大部分工作！现在只需要完成最后的部署步骤即可！**

注意：您不需要再次上传文件，因为您已经完成了这一步。直接从解压开始即可。