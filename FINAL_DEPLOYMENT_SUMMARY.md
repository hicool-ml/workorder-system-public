# 校园网工单系统 - Ubuntu Server 24 部署完整指南

## 系统概述

本系统是一个基于Laravel 12.x的校园网工单管理系统，已配置为可在Ubuntu Server 24上一键部署。

## 部署包信息

- **部署包位置**: `packages/campus-workorder-system-v1.0.0_20251121_171616.tar.gz`
- **文件大小**: 1.06MB
- **校验和**: `sha256sum campus-workorder-system-v1.0.0_20251121_171616.tar.gz`

## 系统要求

- **操作系统**: Ubuntu Server 24
- **Web服务器**: Apache2
- **数据库**: MySQL 8.0+
- **PHP版本**: 8.2+
- **内存**: 最低2GB，推荐4GB+
- **存储**: 最低10GB可用空间

## 部署步骤

### 1. 上传部署包

将 `campus-workorder-system-v1.0.0_20251121_171616.tar.gz` 上传到Ubuntu服务器的任意目录（如 `/tmp`）

### 2. 解压部署包

```bash
cd /tmp
tar -xzf campus-workorder-system-v1.0.0_20251121_171616.tar.gz
cd campus-workorder-system-v1.0.0_20251121_171616
```

### 3. 执行一键部署

```bash
sudo bash deploy_to_ubuntu.sh
```

部署脚本将自动完成以下操作：
- 更新系统包
- 安装Apache2、MySQL、PHP8.3及必要扩展
- 配置数据库用户和权限
- 导入数据库数据
- 配置Apache虚拟主机
- 设置文件权限
- 安装Composer依赖
- 配置Laravel应用

## 部署后配置

### 数据库配置

- **数据库名**: workorder_db
- **数据库用户**: cdu
- **数据库密码**: REDACTED_MYSQL_PASS

### Web目录

- **主目录**: `/var/www/workorder`
- **Apache配置文件**: `/etc/apache2/sites-available/workorder.conf`

### 系统访问

部署完成后，通过以下方式访问系统：
- **系统地址**: `http://服务器IP地址`
- **默认管理员账户**:
  - 用户名: admin
  - 密码: admin123

## 部署脚本详细说明

### 环境配置脚本 (ubuntu_server_setup.sh)

此脚本负责：
- 系统更新和基础软件安装
- Apache2安装和配置
- MySQL 8.0安装和安全配置
- PHP 8.3及必要扩展安装
- Composer安装

### 数据库导出脚本 (export_workorder_database.sh)

此脚本负责：
- 测试数据库连接
- 导出完整数据库结构和数据
- 压缩SQL文件
- 生成校验和

### 一键部署脚本 (deploy_to_ubuntu.sh)

此脚本负责：
- 调用环境配置脚本
- 创建数据库和用户
- 导入数据库数据
- 配置Web环境
- 设置文件权限
- 配置Laravel应用

## 故障排除

### 常见问题

1. **权限问题**
   ```bash
   sudo chown -R www-data:www-data /var/www/workorder
   sudo chmod -R 755 /var/www/workorder
   ```

2. **Apache模块未启用**
   ```bash
   sudo a2enmod rewrite
   sudo systemctl restart apache2
   ```

3. **PHP扩展缺失**
   ```bash
   sudo apt install php8.3-mysql php8.3-xml php8.3-mbstring php8.3-curl php8.3-zip php8.3-gd php8.3-intl
   ```

4. **数据库连接失败**
   ```bash
   sudo systemctl status mysql
   mysql -u cdu -p workorder_db
   ```

### 日志查看

- **Apache错误日志**: `/var/log/apache2/error.log`
- **Laravel日志**: `/var/www/workorder/storage/logs/laravel.log`
- **MySQL日志**: `/var/log/mysql/error.log`

## 系统维护

### 备份数据库

```bash
mysqldump -u cdu -p workorder_db > workorder_backup_$(date +%Y%m%d).sql
```

### 更新系统

```bash
sudo apt update && sudo apt upgrade -y
cd /var/www/workorder
composer update
php artisan optimize
```

### 清理缓存

```bash
cd /var/www/workorder
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

## 技术支持

如遇到部署问题，请检查：
1. 系统资源是否充足
2. 网络连接是否正常
3. 所有依赖是否正确安装
4. 日志文件中的错误信息

## 部署验证

部署完成后，可通过以下方式验证系统是否正常运行：

1. **访问系统主页**: `http://服务器IP`
2. **检查系统状态**: `http://服务器IP/test_system.php`
3. **验证数据库连接**: 系统应能正常显示数据表信息

---

**部署完成时间**: 2025年11月21日  
**版本**: v1.0.0  
**适用系统**: Ubuntu Server 24  
**技术栈**: Laravel 12.x + Apache2 + MySQL 8.0 + PHP 8.3