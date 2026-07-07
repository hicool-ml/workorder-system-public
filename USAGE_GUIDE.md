# Laravel工单系统打包部署使用指南

## 快速开始

### 前提条件

确保您在Laravel项目根目录（`/var/www/workorder`）下运行所有命令。

### 打包项目

```bash
cd /var/www/workorder
./package_project.sh
```

#### 方法1：全新服务器部署（推荐）

```bash
# 1. 在目标服务器上运行环境准备脚本
./setup_server.sh

# 2. 传输压缩包到目标服务器
scp packages/workorder-system_v*.tar.gz user@target-server:/path/to/deploy/

# 3. 在目标服务器上解压和部署
tar -xzf workorder-system_v*.tar.gz
cd workorder-system_v*
./auto_deploy.sh -e production -v
```

#### 方法2：已有环境部署

```bash
# 1. 传输压缩包到目标服务器
scp packages/workorder-system_v*.tar.gz user@target-server:/path/to/deploy/

# 2. 在目标服务器上解压和部署
tar -xzf workorder-system_v*.tar.gz
cd workorder-system_v*
./auto_deploy.sh -e production -v
```

#### 方法3：手动环境准备

如果目标服务器已有基础环境，但缺少某些组件：

```bash
# 安装PHP 8.3（Ubuntu/Debian）
sudo apt update
sudo apt install -y php8.3 php8.3-fpm php8.3-mysql php8.3-pgsql \
    php8.3-mbstring php8.3-tokenizer php8.3-xml php8.3-ctype \
    php8.3-fileinfo php8.3-json php8.3-bcmath php8.3-openssl \
    php8.3-gd php8.3-curl php8.3-zip

# 安装Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# 安装Node.js
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt-get install -y nodejs

# 安装Web服务器

**Nginx（推荐）：**
```bash
sudo apt install -y nginx
sudo systemctl start nginx
sudo systemctl enable nginx
```

**Apache：**
```bash
sudo apt install -y apache2 libapache2-mod-php8.3
sudo a2enmod php8.3
sudo systemctl start apache2
sudo systemctl enable apache2
```

**注意：** setup_server.sh脚本会自动检测并安装您选择的Web服务器

#### 服务器环境准备脚本

如果您遇到 `php: 未找到命令` 错误，请先运行：

```bash
# 下载并运行服务器环境准备脚本
wget https://your-domain.com/setup_server.sh
chmod +x setup_server.sh
./setup_server.sh
```

该脚本将自动安装：
- PHP 8.3 及必要扩展
- Composer
- Node.js
- Web服务器（Nginx/Apache）
- 数据库服务器（MySQL/PostgreSQL）
- Redis缓存（可选）
- 防火墙配置

### 部署到其他服务器


## 脚本功能说明

### 1. package_project.sh - 主打包脚本

**功能：**
- ✅ 自动排除不必要的文件（.git、node_modules、日志等）
- ✅ 导出数据库（支持MySQL、PostgreSQL、SQLite）
- ✅ 安装生产环境依赖
- ✅ 编译前端资源（使用本地vite，避免PATH问题）
- ✅ 优化项目配置
- ✅ 创建部署脚本和文档
- ✅ 生成压缩包

**输出：**
- `packages/workorder-system_vYYYYMMDD_HHMMSS.tar.gz`

### 2. export_database.sh - 数据库导出脚本

**功能：**
- ✅ 自动检测数据库配置
- ✅ 支持多种数据库类型
- ✅ 压缩输出
- ✅ 提供导入说明

**使用：**
```bash
./export_database.sh [输出文件名]
```

### 3. auto_deploy.sh - 自动化部署脚本

**功能：**
- ✅ 系统要求检查
- ✅ 多环境支持（production/staging/development）
- ✅ 自动备份功能
- ✅ 依赖安装和优化
- ✅ 数据库迁移和种子数据
- ✅ 健康检查

**选项：**
```bash
./auto_deploy.sh [选项]

选项：
  -e, --environment ENV     部署环境 (production|staging|development)
  -c, --config FILE         配置文件路径
  -b, --backup              启用数据库备份
  --no-backup               禁用数据库备份
  -v, --verbose             详细输出
  --skip-dependencies       跳过依赖安装
  --skip-db-migration       跳过数据库迁移
  --skip-db-seed            跳过数据库种子数据
  -f, --force               强制部署（跳过确认）
  -h, --help                显示帮助信息
```

## 环境配置

### 生产环境配置

```bash
./auto_deploy.sh -e production -v
```

### 测试环境配置

```bash
./auto_deploy.sh -e staging --skip-db-seed
```

### 开发环境配置

```bash
./auto_deploy.sh -e development --skip-dependencies
```

## 数据库配置

### MySQL配置示例

编辑 `.env` 文件：
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=workorder
DB_USERNAME=workorder_user
DB_PASSWORD=your_password
```

### 数据库导入

```bash
# 导入数据库
mysql -u username -p database_name < database.sql

# 或导入压缩版本
gunzip -c database.sql.gz | mysql -u username -p database_name
```

## 默认账户

部署完成后，系统会创建以下默认账户：

| 角色 | 邮箱 | 密码 | 权限 |
|------|------|------|------|
| 管理员 | admin@workorder.com | admin123 | 全部权限 |
| 工程师 | engineer@workorder.com | engineer123 | 工单处理 |
| 用户 | user@workorder.com | user123 | 基础操作 |

**重要：** 部署后请立即修改默认密码！

## 包含的数据

### 部门结构
- 信息技术部（系统运维组、网络管理组、软件开发组）
- 行政部（后勤保障组、文档管理组、接待服务组）
- 人力资源部（招聘培训组、薪酬福利组、员工关系组）
- 财务部（会计核算组、资金管理组、税务管理组）
- 市场部（市场推广组、客户服务组、品牌管理组）

### 工单分类
- 网络故障（拨号失败、网络速度慢、连接不稳定等）
- 多媒体教室（大屏显示、投影仪故障、音响系统等）
- 专项工作（线路测试、设备安装、系统迁移等）
- 设备故障（打印机、复印机、扫描仪等）
- 软件支持（操作系统、办公软件、专业软件等）

### 位置数据
- 老校区（1-7教、1-10栋宿舍、行政楼等）
- 新校区（8-14教、11-18栋宿舍等）
- 东盟校区（A-J教、19-20栋宿舍等）

## 故障排除

### 常见问题

#### 1. vite命令未找到
**问题：** `sh: 1: vite: not found`
**解决：** 脚本已修复，现在使用本地vite路径
```bash
# 手动编译前端资源
./node_modules/.bin/vite build
```

#### 2. 权限问题
**问题：** 文件权限错误
**解决：**
```bash
sudo chown -R www-data:www-data /path/to/project
chmod -R 775 storage bootstrap/cache
```

#### 3. 数据库连接失败
**问题：** 无法连接数据库
**解决：**
```bash
# 检查数据库连接
php artisan tinker
>>> DB::connection()->getPdo();

# 检查.env配置
cat .env | grep DB_
```

#### 4. 依赖安装失败
**问题：** Composer或NPM依赖安装失败
**解决：**
```bash
# 清理并重新安装
composer clear-cache
composer install --no-dev --optimize-autoloader

npm cache clean --force
npm install --production
```

### 日志检查

```bash
# Laravel应用日志
tail -f storage/logs/laravel.log

# Web服务器日志
tail -f /var/log/nginx/error.log
tail -f /var/log/apache2/error.log

# PHP错误日志
tail -f /var/log/php_errors.log
```

## 性能优化

### 应用优化

```bash
# 清理缓存
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# 优化缓存
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 优化自动加载
composer dump-autoload --optimize
```

### 数据库优化

```bash
# 优化数据库表
php artisan db:optimize

# 查看数据库状态
php artisan db:show
```

## 安全建议

### 基本安全措施

1. **修改默认密码**
   ```bash
   php artisan tinker
   >>> $user = User::find(1);
   >>> $user->password = Hash::make('new_strong_password');
   >>> $user->save();
   ```

2. **设置文件权限**
   ```bash
   chmod 600 .env
   chmod 755 public
   ```

3. **启用HTTPS**
   ```bash
   # 配置SSL证书
   sudo certbot --nginx -d your-domain.com
   ```

### 高级安全配置

1. **配置防火墙**
   ```bash
   sudo ufw enable
   sudo ufw allow ssh
   sudo ufw allow 'Nginx Full'
   ```

2. **定期备份**
   ```bash
   # 设置定时备份
   crontab -e
   # 添加：0 2 * * * /path/to/backup_script.sh
   ```

## 升级指南

### 版本升级步骤

1. **备份现有系统**
   ```bash
   ./backup_workorder.sh
   ```

2. **下载新版本**
   ```bash
   wget https://your-domain.com/workorder-system_v2.0.0.tar.gz
   ```

3. **升级部署**
   ```bash
   tar -xzf workorder-system_v2.0.0.tar.gz
   cd workorder-system_v2.0.0
   ./auto_deploy.sh -e production --no-backup
   ```

## 技术支持

如果在使用过程中遇到问题：

1. **查看文档**
   - 完整部署指南：`COMPLETE_DEPLOYMENT_GUIDE.md`
   - 打包说明：`PACKAGING_README.md`
   - 部署总结：`DEPLOYMENT_SUMMARY.md`

2. **检查日志**
   - 应用日志：`storage/logs/laravel.log`
   - 系统日志：`/var/log/syslog`

3. **联系支持**
   - 邮箱：support@your-domain.com
   - 文档：https://your-domain.com/docs

---

**最后更新**: 2025-11-21  
**版本**: 1.0.0  
**修复内容**: 修复了vite命令未找到的问题，现在使用本地vite路径