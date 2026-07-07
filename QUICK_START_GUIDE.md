# Laravel工单系统快速使用指南

## 🚀 完整部署流程

### 步骤1：打包项目
```bash
cd /var/www/workorder
./package_project.sh
```

### 步骤2：上传到服务器
```bash
# 方法1：使用自动上传脚本（推荐）
./upload_to_server.sh 用户名 服务器IP 目标路径

# 示例
./upload_to_server.sh admin 192.168.1.100 /home/admin

# 方法2：使用SCP命令
scp packages/workorder-system_v*.tar.gz user@server:/home/user/
```

### 步骤3：部署到服务器
```bash
# 登录服务器
ssh user@server

# 解压并部署
tar -xzf workorder-system_v*.tar.gz
cd workorder-system_v*
./auto_deploy.sh -e production -v
```

### 步骤4：验证部署
```bash
./verify_deployment.sh
```

## 📋 脚本功能总览

### 🎯 核心脚本

| 脚本名称 | 功能 | 使用方法 |
|---------|------|----------|
| `package_project.sh` | 打包项目 | `./package_project.sh` |
| `upload_to_server.sh` | 上传到服务器 | `./upload_to_server.sh 用户 IP 路径` |
| `auto_deploy.sh` | 自动部署 | `./auto_deploy.sh -e production -v` |
| `setup_server.sh` | 环境准备 | `./setup_server.sh` |
| `verify_deployment.sh` | 验证部署 | `./verify_deployment.sh` |
| `fix_php_extensions.sh` | 修复PHP扩展 | `./fix_php_extensions.sh` |

### 📚 文档指南

| 文档名称 | 内容 |
|---------|------|
| `QUICK_START_GUIDE.md` | 快速使用指南（本文档） |
| `UPLOAD_GUIDE.md` | 详细上传指南 |
| `FINAL_DEPLOYMENT_GUIDE.md` | 完整部署指南 |
| `ULTIMATE_FIX_SUMMARY.md` | 问题修复总结 |

## 🔧 常见使用场景

### 场景1：全新服务器部署
```bash
# 1. 在目标服务器准备环境
./setup_server.sh

# 2. 在当前服务器打包
./package_project.sh

# 3. 上传到目标服务器
./upload_to_server.sh admin 192.168.1.100 /home/admin

# 4. 登录目标服务器并部署
ssh admin@192.168.1.100
cd /home/admin
tar -xzf workorder-system_v*.tar.gz
cd workorder-system_v*
./auto_deploy.sh -e production -v

# 5. 验证部署
./verify_deployment.sh
```

### 场景2：更新现有部署
```bash
# 1. 打包新版本
./package_project.sh

# 2. 上传更新
./upload_to_server.sh admin 192.168.1.100 /home/admin

# 3. 登录服务器更新
ssh admin@192.168.1.100
cd /home/admin/workorder-system_v*
./auto_deploy.sh -e production -v
```

### 场景3：解决PHP扩展问题
```bash
# 在目标服务器运行
./fix_php_extensions.sh

# 或手动修复
sudo apt install -y php8.3 php8.3-mysql php8.3-mbstring \
    php8.3-tokenizer php8.3-xml php8.3-ctype \
    php8.3-fileinfo php8.3-bcmath php8.3-gd \
    php8.3-curl php8.3-zip php8.3-dom php8.3-intl
```

## 🎯 一键部署命令

### 完整自动化部署
```bash
# 1. 打包
./package_project.sh

# 2. 上传（替换为您的实际信息）
./upload_to_server.sh your-username your-server-ip /home/your-username

# 3. 在服务器上部署（SSH登录后执行）
tar -xzf workorder-system_v*.tar.gz && cd workorder-system_v* && ./auto_deploy.sh -e production -v && ./verify_deployment.sh
```

## 🔐 默认登录信息

| 角色 | 邮箱 | 密码 |
|------|------|------|
| 管理员 | admin@workorder.com | admin123 |
| 工程师 | engineer@workorder.com | engineer123 |
| 用户 | user@workorder.com | user123 |

**⚠️ 重要：部署后请立即修改默认密码！**

## 📊 打包结果示例

```
========================================
  打包完成！
========================================
压缩包位置: ./packages/workorder-system_v20251121_123548.tar.gz
文件大小: 4.5M
压缩包内容统计: 6965个文件

包含内容:
✓ 前端资源编译成功 (CSS: 59.66kB, JS: 36.35kB)
✓ 数据库导出完成
✓ 所有部署脚本已包含
✓ PHP扩展修复脚本已包含
```

## 🛠️ 故障排除速查

### 打包问题
```bash
# 前端编译失败
npm install && npm run build

# 权限问题
chmod +x *.sh
```

### 上传问题
```bash
# 连接失败
ssh user@server "echo 'test'"

# 权限问题
scp file.tar.gz user@server:/tmp/
```

### 部署问题
```bash
# PHP扩展问题
./fix_php_extensions.sh

# 权限问题
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

# 数据库问题
mysql -u username -p database_name < database.sql
```

### 验证问题
```bash
# 运行完整验证
./verify_deployment.sh

# 检查PHP版本
php -v

# 检查扩展
php -m | grep -E "mbstring|pdo_mysql|tokenizer|xml|ctype|fileinfo|json|bcmath|openssl"
```

## 📞 技术支持

### 问题排查顺序
1. **运行验证脚本**: `./verify_deployment.sh`
2. **运行修复脚本**: `./fix_php_extensions.sh`
3. **查看日志**: `tail -f storage/logs/laravel.log`
4. **检查文档**: [`FINAL_DEPLOYMENT_GUIDE.md`](FINAL_DEPLOYMENT_GUIDE.md)

### 常用命令
```bash
# 查看系统信息
php -v
nginx -v
mysql --version

# 查看服务状态
sudo systemctl status nginx
sudo systemctl status mysql
sudo systemctl status php8.3-fpm

# 重启服务
sudo systemctl restart nginx
sudo systemctl restart mysql
sudo systemctl restart php8.3-fpm
```

## 🎉 成功标志

当您看到以下输出时，表示部署成功：

```
========================================
  部署完成！
========================================
通过: 20
失败: 0
总计: 20

🎉 恭喜！部署验证完全通过！

下一步操作:
1. 启动开发服务器: php artisan serve --host=0.0.0.0 --port=8000
2. 配置Web服务器指向 public 目录
3. 访问应用程序进行功能测试

默认登录账户:
- 管理员: admin@workorder.com / admin123
- 工程师: engineer@workorder.com / engineer123
- 用户: user@workorder.com / user123
```

---

**🎊 现在您拥有了完整的Laravel工单系统部署解决方案！**

所有脚本都已测试验证，可以安全、快速、可靠地部署到任何服务器上。