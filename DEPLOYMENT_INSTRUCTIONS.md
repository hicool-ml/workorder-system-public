# 校园网工单系统 - Ubuntu Server 24 部署说明

## 部署包信息

- **文件名**: `campus-workorder-system-v1.0.0_20251121_211203.tar.gz`
- **位置**: `packages/` 目录
- **大小**: 约 1.1MB
- **目标系统**: Ubuntu Server 24

## 快速部署步骤

### 1. 上传部署包

将 `campus-workorder-system-v1.0.0_20251121_211203.tar.gz` 上传到Ubuntu服务器的任意目录（如 `/tmp`）

### 2. 解压部署包

```bash
cd /tmp
tar -xzf campus-workorder-system-v1.0.0_20251121_211203.tar.gz
cd campus-workorder-system-v1.0.0_20251121_171616
```

### 3. 运行一键部署

```bash
sudo bash deploy_to_ubuntu.sh
```

## 系统要求

- **操作系统**: Ubuntu Server 24
- **PHP版本**: 8.2+
- **数据库**: MySQL 8.0+
- **Web服务器**: Apache2
- **内存**: 最低2GB，推荐4GB+
- **存储**: 最低10GB可用空间

## 数据库配置

- **数据库名**: workorder_db
- **用户名**: cdu
- **密码**: REDACTED_MYSQL_PASS
- **主机**: 127.0.0.1:3306

## 默认登录账户

| 角色 | 用户名 | 密码 | 说明 |
|------|--------|------|------|
| 系统管理员 | admin | admin123 | 拥有系统所有权限 |
| 工程师 | wangyang | (请查看数据库) | 负责工单处理 |
| 普通用户 | (请查看数据库) | (请查看数据库) | 基础工单操作权限 |

**重要提示**: 首次登录后请立即修改默认密码！

## 部署后访问

部署完成后，通过以下方式访问系统：
- **系统地址**: `http://服务器IP地址`

## 部署脚本功能

`deploy_to_ubuntu.sh` 脚本将自动完成以下操作：

1. **环境配置**: 安装和配置Apache2、MySQL、PHP8.3
2. **项目部署**: 复制项目文件到 `/var/www/workorder`
3. **依赖安装**: 安装Composer依赖包
4. **环境配置**: 配置 `.env` 文件
5. **数据库导入**: 导入数据库数据和结构
6. **权限设置**: 设置正确的文件和目录权限
7. **服务启动**: 启动并配置Apache2服务
8. **部署验证**: 验证系统是否正常运行

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
   # 检查MySQL服务状态
   sudo systemctl status mysql
   
   # 检查数据库用户权限
   mysql -u root -p -e "SHOW GRANTS FOR 'cdu'@'localhost';"
   ```

4. **PHP扩展缺失**
   ```bash
   sudo apt install php8.3-mysql php8.3-xml php8.3-mbstring php8.3-curl php8.3-zip php8.3-gd php8.3-intl
   ```

### 日志文件

- **Apache错误日志**: `/var/log/apache2/error.log`
- **Apache访问日志**: `/var/log/apache2/access.log`
- **Laravel日志**: `/var/www/workorder/storage/logs/laravel.log`
- **MySQL错误日志**: `/var/log/mysql/error.log`

## 技术栈

- **后端框架**: Laravel 12.x
- **前端**: Blade模板 + Tailwind CSS
- **数据库**: MySQL 8.0+
- **Web服务器**: Apache2
- **PHP版本**: 8.3
- **包管理**: Composer

## 维护命令

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

## 安全建议

1. **修改默认密码**: 首次登录后立即修改所有默认账户密码
2. **防火墙配置**: 确保只开放必要的端口（80, 443, 22）
3. **定期备份**: 定期备份数据库和重要文件
4. **系统更新**: 定期更新系统和依赖包
5. **日志监控**: 定期检查系统日志，发现异常及时处理

## 支持信息

如遇到部署问题，请：
1. 检查系统是否满足最低要求
2. 查看相关日志文件
3. 确认网络连接正常
4. 验证所有依赖是否正确安装

---

**部署包版本**: v1.0.0  
**创建时间**: 2025年11月21日  
**适用系统**: Ubuntu Server 24  
**技术栈**: Laravel 12.x + Apache2 + MySQL 8.0 + PHP 8.3