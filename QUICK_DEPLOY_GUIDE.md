# 🚀 工单系统快速部署指南

## 📋 一键部署（推荐）

### 前提条件
- 服务器已安装 Ubuntu 20.04+ 或 CentOS 8+
- 具有 sudo 权限的用户账户
- 稳定的网络连接

### 快速部署步骤

```bash
# 1. 下载最新部署包
wget https://your-source-server.com/packages/campus-workorder-system-v1.0.0_*.tar.gz

# 2. 解压到目标目录
tar -xzf campus-workorder-system-v1.0.0_*.tar.gz
cd campus-workorder-system-v1.0.0_*/

# 3. 运行自动部署脚本
sudo ./auto_deploy.sh -e production -v

# 4. 完成！访问您的网站
```

## 🔧 手动部署（5分钟完成）

### 第一步：环境检查
```bash
# 检查PHP版本（需要8.1+）
php --version

# 检查MySQL版本（需要8.0+）
mysql --version

# 检查Web服务器
apache2 --version  # 或 nginx -v
```

### 第二步：获取代码
```bash
# 下载并解压
wget https://your-source-server.com/packages/campus-workorder-system-v1.0.0_*.tar.gz
tar -xzf campus-workorder-system-v1.0.0_*.tar.gz
cd campus-workorder-system-v1.0.0_*/
```

### 第三步：安装依赖
```bash
# PHP依赖
composer install --no-dev --optimize-autoloader

# 前端依赖（可选）
npm install && npm run build
```

### 第四步：配置环境
```bash
# 复制环境配置
cp .env.example .env

# 编辑配置文件
nano .env
```

**重要配置项**：
```env
APP_NAME=校园网工单系统
APP_ENV=production
APP_DEBUG=false
APP_URL=http://your-domain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=workorder_system
DB_USERNAME=workorder_user
DB_PASSWORD=your_password
```

### 第五步：初始化应用
```bash
# 生成密钥
php artisan key:generate

# 运行数据库迁移
php artisan migrate --force

# 导入初始数据
php artisan db:seed --force

# 设置权限
chmod -R 755 storage/ bootstrap/cache/
```

### 第六步：配置Web服务器

**Apache配置**：
```bash
# 创建虚拟主机
sudo nano /etc/apache2/sites-available/workorder.conf
```

```apache
<VirtualHost *:80>
    ServerName your-domain.com
    DocumentRoot /path/to/project/public
    
    <Directory /path/to/project/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

```bash
# 启用站点
sudo a2ensite workorder.conf
sudo systemctl reload apache2
```

**Nginx配置**：
```bash
# 创建站点配置
sudo nano /etc/nginx/sites-available/workorder
```

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/project/public;
    index index.php;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
    }
}
```

```bash
# 启用站点
sudo ln -s /etc/nginx/sites-available/workorder /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
```

## 🎯 部署完成！

### 默认登录账户

| 角色 | 邮箱 | 密码 |
|------|------|------|
| 系统管理员 | admin@workorder.com | admin123 |
| 工单管理员 | workorder_manager@workorder.com | admin123 |
| 工程师 | engineer@workorder.com | engineer123 |
| 普通用户 | user@workorder.com | user123 |

**⚠️ 安全提示**：首次登录后请立即修改默认密码！

### 访问地址
- 前台：`http://your-domain.com`
- 后台：`http://your-domain.com/admin`

## 🔍 验证部署

```bash
# 检查应用状态
php artisan about

# 检查路由
php artisan route:list

# 测试数据库连接
php artisan tinker
>>> DB::connection()->getPdo();
```

## 🆘 常见问题

### 1. 500错误
```bash
# 检查权限
ls -la storage/
chmod -R 755 storage/ bootstrap/cache/

# 检查.env文件
cat .env
```

### 2. 数据库连接失败
```bash
# 测试连接
mysql -u workorder_user -p workorder_system

# 检查服务状态
sudo systemctl status mysql
```

### 3. 文件上传失败
```bash
# 检查上传目录权限
ls -la storage/app/public/
chmod -R 755 storage/app/public/
```

## 📞 获取帮助

如果遇到问题，请参考：
- 📖 [完整部署指南](DEPLOY_TO_OTHER_SERVERS.md)
- 📖 [部署维护指南](DEPLOYMENT_MAINTENANCE_GUIDE.md)
- 📖 [用户手册](USER_MANUAL.md)

## 🔄 更新系统

```bash
# 备份当前版本
cp -r /path/to/project /path/to/project.backup

# 下载新版本
wget https://your-source-server.com/packages/campus-workorder-system-v*.tar.gz

# 更新代码
tar -xzf campus-workorder-system-v*.tar.gz
rsync -av campus-workorder-system-v*/ /path/to/project/

# 运行更新
cd /path/to/project
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan cache:clear
```

---

**🎉 恭喜！您的工单系统已成功部署！**

现在您可以开始使用校园网工单系统了。如有任何问题，请参考相关文档或联系技术支持。