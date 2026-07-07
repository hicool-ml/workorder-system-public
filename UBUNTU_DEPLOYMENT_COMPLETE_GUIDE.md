# 校园网工单系统 - Ubuntu Server 24 完整部署指南

## 概述

本指南提供了将校园网工单系统部署到Ubuntu Server 24的完整解决方案，包括环境配置、数据库迁移和项目部署。

## 系统要求

- **操作系统**: Ubuntu Server 24
- **PHP**: >= 8.2
- **MySQL**: >= 8.0
- **Apache**: >= 2.4
- **内存**: 至少 2GB RAM
- **磁盘空间**: 至少 10GB 可用空间

## 快速部署（推荐）

### 步骤1: 创建部署包

在开发服务器上运行：

```bash
# 确保在项目根目录
cd /path/to/workorder/project

# 运行一键打包脚本
./build_and_package.sh
```

这将创建一个完整的部署包，包含：
- 项目文件
- 数据库导出
- 环境配置脚本
- 一键部署脚本

### 步骤2: 上传到目标服务器

```bash
# 使用scp上传部署包
scp campus-workorder-system-*.tar.gz user@your-server:/tmp/

# 或使用其他方式上传（ftp、rsync等）
```

### 步骤3: 部署到Ubuntu Server 24

```bash
# 登录到Ubuntu Server 24
ssh user@your-server

# 切换到临时目录
cd /tmp

# 解压部署包
tar -xzf campus-workorder-system-*.tar.gz

# 进入部署目录
cd campus-workorder-system-*

# 运行一键部署（需要root权限）
sudo bash deploy_to_ubuntu.sh
```

### 步骤4: 访问系统

部署完成后，可以通过以下地址访问：

- **前台**: http://服务器IP地址
- **后台**: http://服务器IP地址/admin

**默认登录账户**:
- 邮箱: admin@workorder.com
- 密码: admin123

## 详细部署步骤

### 1. 环境准备

如果需要手动配置环境，可以运行：

```bash
sudo bash ubuntu_server_setup.sh
```

这将安装：
- PHP 8.3 及必要扩展
- Apache2 Web服务器
- MySQL 数据库服务器
- Composer 依赖管理器
- Node.js (用于前端资源编译)

### 2. 数据库配置

系统会自动创建数据库和用户：

- **数据库名**: workorder_DB
- **用户名**: cdu
- **密码**: REDACTED_PROD_SSH_PASS

### 3. 项目部署

项目将部署到 `/var/www/workorder` 目录，包含：

- Laravel 应用代码
- 配置文件
- 数据库迁移文件
- 前端资源文件

### 4. Web服务器配置

Apache虚拟主机配置：

```apache
<VirtualHost *:80>
    ServerName localhost
    DocumentRoot /var/www/workorder/public
    
    <Directory /var/www/workorder/public>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/workorder_error.log
    CustomLog ${APACHE_LOG_DIR}/workorder_access.log combined
</VirtualHost>
```

## 脚本说明

### 主要脚本文件

1. **build_and_package.sh** - 一键打包脚本
   - 导出数据库
   - 打包项目文件
   - 创建部署包

2. **ubuntu_server_setup.sh** - 环境配置脚本
   - 安装PHP、Apache、MySQL
   - 配置系统环境
   - 创建数据库和用户

3. **export_workorder_database.sh** - 数据库导出脚本
   - 导出完整的数据库结构和数据
   - 生成压缩的SQL文件

4. **create_deployment_package.sh** - 项目打包脚本
   - 复制项目文件
   - 创建部署脚本
   - 生成部署文档

5. **deploy_to_ubuntu.sh** - 一键部署脚本（包含在部署包中）
   - 配置服务器环境
   - 部署项目文件
   - 导入数据库
   - 配置Web服务器

## 配置信息

### 数据库配置

```ini
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=workorder_DB
DB_USERNAME=cdu
DB_PASSWORD=REDACTED_PROD_SSH_PASS
```

### Web目录配置

- **项目路径**: /var/www/workorder
- **Web根目录**: /var/www/workorder/public
- **存储目录**: /var/www/workorder/storage
- **日志目录**: /var/www/workorder/storage/logs

### 系统用户

- **Web服务器用户**: www-data
- **数据库用户**: cdu
- **系统管理员**: admin@workorder.com

## 故障排除

### 常见问题

1. **权限问题**
   ```bash
   sudo chown -R www-data:www-data /var/www/workorder
   sudo chmod -R 755 /var/www/workorder
   ```

2. **Apache 403 错误**
   ```bash
   sudo a2enmod rewrite
   sudo systemctl restart apache2
   ```

3. **数据库连接失败**
   ```bash
   sudo systemctl status mysql
   mysql -u cdu -pREDACTED_PROD_SSH_PASS workorder_DB
   ```

4. **Composer 依赖安装失败**
   ```bash
   cd /var/www/workorder
   sudo composer install --no-dev --optimize-autoloader
   ```

### 日志文件

- **Apache错误日志**: /var/log/apache2/error.log
- **Apache访问日志**: /var/log/apache2/access.log
- **Laravel应用日志**: /var/www/workorder/storage/logs/laravel.log
- **MySQL错误日志**: /var/log/mysql/error.log

## 安全建议

1. **修改默认密码**
   - 首次登录后立即修改管理员密码
   - 修改数据库用户密码

2. **防火墙配置**
   ```bash
   sudo ufw allow ssh
   sudo ufw allow 'Apache Full'
   sudo ufw enable
   ```

3. **SSL证书**
   - 配置HTTPS（使用Let's Encrypt）
   - 强制HTTPS重定向

4. **定期更新**
   ```bash
   sudo apt update && sudo apt upgrade
   ```

## 备份策略

### 数据库备份

```bash
# 创建备份脚本
mysqldump -u cdu -pREDACTED_PROD_SSH_PASS workorder_DB > backup_$(date +%Y%m%d).sql
```

### 文件备份

```bash
# 备份项目文件
tar -czf workorder_backup_$(date +%Y%m%d).tar.gz /var/www/workorder
```

## 性能优化

### PHP优化

编辑 `/etc/php/8.3/fpm/php.ini`:

```ini
memory_limit = 512M
upload_max_filesize = 50M
post_max_size = 50M
max_execution_time = 300
```

### MySQL优化

编辑 `/etc/mysql/mysql.conf.d/mysqld.cnf`:

```ini
innodb_buffer_pool_size = 1G
innodb_log_file_size = 256M
max_connections = 200
```

### Apache优化

编辑 `/etc/apache2/apache2.conf`:

```apache
<IfModule mpm_prefork_module>
    StartServers 4
    MinSpareServers 20
    MaxSpareServers 40
    MaxRequestWorkers 200
    MaxConnectionsPerChild 4500
</IfModule>
```

## 监控和维护

### 系统监控

```bash
# 检查系统资源
htop
df -h
free -h

# 检查服务状态
sudo systemctl status apache2 mysql
```

### 日志监控

```bash
# 实时查看Apache日志
sudo tail -f /var/log/apache2/error.log

# 实时查看应用日志
sudo tail -f /var/www/workorder/storage/logs/laravel.log
```

## 版本更新

### 更新步骤

1. 备份现有数据
2. 下载新版本部署包
3. 运行更新脚本
4. 清除缓存
5. 验证功能

```bash
# 清除Laravel缓存
cd /var/www/workorder
sudo php artisan cache:clear
sudo php artisan config:clear
sudo php artisan route:clear
sudo php artisan view:clear
```

## 技术支持

如遇到问题，请参考：

- **项目文档**: README.md
- **用户手册**: USER_MANUAL.md
- **开发者指南**: DEVELOPER_GUIDE.md
- **API文档**: API_DOCUMENTATION.md

## 版本信息

- **系统版本**: v1.0.0
- **Laravel版本**: 12.x
- **PHP要求**: >= 8.2
- **MySQL要求**: >= 8.0
- **目标系统**: Ubuntu Server 24

---

**注意**: 本部署方案专门针对Ubuntu Server 24设计，确保在干净的系统上部署以获得最佳效果。