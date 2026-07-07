# Laravel工单系统项目打包说明

## 概述

本文档说明如何使用提供的脚本将Laravel工单系统打包成可部署的压缩包，以便在其他服务器上快速部署。

## 打包工具说明

### 1. 主打包脚本 (`package_project.sh`)

这是主要的打包脚本，执行完整的打包流程：

```bash
./package_project.sh
```

**功能特点：**
- 自动排除不必要的文件（node_modules、日志、缓存等）
- 导出数据库结构和数据
- 安装生产环境依赖
- 编译前端资源
- 优化项目配置
- 创建自动部署脚本
- 生成完整的部署文档
- 创建压缩包

**输出：**
- `packages/workorder-system_vYYYYMMDD_HHMMSS.tar.gz` - 完整的项目包

### 2. 数据库导出脚本 (`export_database.sh`)

独立的数据库导出工具：

```bash
./export_database.sh [输出文件名]
```

**支持数据库：**
- MySQL/MariaDB
- PostgreSQL
- SQLite

**功能特点：**
- 自动检测数据库配置
- 导出完整的数据结构和数据
- 支持压缩输出
- 提供导入说明

### 3. 自动部署脚本 (`auto_deploy.sh`)

高级自动化部署工具：

```bash
./auto_deploy.sh [选项]
```

**选项说明：**
- `-e, --environment ENV` - 部署环境 (production|staging|development)
- `-c, --config FILE` - 配置文件路径
- `-b, --backup` - 启用数据库备份
- `--no-backup` - 禁用数据库备份
- `-v, --verbose` - 详细输出
- `--skip-dependencies` - 跳过依赖安装
- `--skip-db-migration` - 跳过数据库迁移
- `--skip-db-seed` - 跳过数据库种子数据
- `-f, --force` - 强制部署（跳过确认）

### 4. 部署配置文件 (`deploy_config.json`)

包含所有部署相关的配置信息：

- 系统要求
- 环境配置
- 数据库配置
- 性能优化设置
- 安全配置
- 监控和备份设置

## 快速开始

### 步骤1：打包项目

在Laravel项目根目录运行：

```bash
# 确保脚本有执行权限
chmod +x package_project.sh export_database.sh auto_deploy.sh

# 运行打包脚本
./package_project.sh
```

打包完成后，会在 `packages/` 目录下生成压缩包。

### 步骤2：传输到目标服务器

```bash
# 使用SCP传输
scp packages/workorder-system_v*.tar.gz user@target-server:/path/to/deploy/

# 或使用rsync
rsync -avz packages/workorder-system_v*.tar.gz user@target-server:/path/to/deploy/
```

### 步骤3：在目标服务器部署

```bash
# 解压项目包
tar -xzf workorder-system_v*.tar.gz
cd workorder-system_v*

# 运行自动部署脚本
./auto_deploy.sh -e production -v

# 或者使用简单的部署脚本
./deploy.sh
```

## 手动打包流程

如果需要手动打包，可以按以下步骤：

### 1. 准备项目文件

```bash
# 创建打包目录
mkdir -p /tmp/workorder_package
cd /path/to/laravel/project

# 复制项目文件，排除不必要的文件
rsync -av --progress \
    --exclude='.git' \
    --exclude='node_modules' \
    --exclude='storage/logs/*' \
    --exclude='storage/framework/cache/*' \
    --exclude='storage/framework/sessions/*' \
    --exclude='storage/framework/views/*' \
    --exclude='storage/app/public/*' \
    --exclude='bootstrap/cache/*' \
    --exclude='vendor' \
    --exclude='.env' \
    ./ /tmp/workorder_package/
```

### 2. 导出数据库

```bash
cd /tmp/workorder_package
/path/to/original/project/export_database.sh database.sql
```

### 3. 安装依赖

```bash
# 安装生产依赖
composer install --no-dev --optimize-autoloader --no-interaction

# 编译前端资源
npm install --production
npm run build
```

### 4. 配置项目

```bash
# 复制环境配置
cp .env.example .env.production

# 创建必要目录
mkdir -p storage/logs storage/framework/cache storage/framework/sessions storage/framework/views storage/app/public bootstrap/cache

# 设置权限
chmod -R 775 storage bootstrap/cache
```

### 5. 创建部署脚本

将 `auto_deploy.sh` 和 `deploy.sh` 复制到打包目录。

### 6. 创建压缩包

```bash
cd /tmp
tar -czf workorder-system_v$(date +%Y%m%d_%H%M%S).tar.gz workorder_package/
```

## 部署包内容说明

生成的部署包包含以下内容：

```
workorder-system_vYYYYMMDD_HHMMSS/
├── app/                          # 应用代码
├── bootstrap/                    # 启动文件
├── config/                       # 配置文件
├── database/                     # 数据库文件
│   ├── migrations/              # 数据库迁移
│   ├── seeders/                 # 数据库种子
│   └── database.sql            # 导出的数据库
├── public/                      # 公共资源
├── resources/                   # 视图和资源文件
├── routes/                      # 路由文件
├── storage/                     # 存储目录（空）
├── vendor/                      # Composer依赖
├── .env.example                 # 环境配置模板
├── .env.production              # 生产环境配置
├── artisan                      # Laravel命令行工具
├── composer.json               # Composer配置
├── composer.lock               # Composer锁定文件
├── package.json                # NPM配置
├── deploy.sh                   # 简单部署脚本
├── auto_deploy.sh              # 高级部署脚本
├── deploy_config.json          # 部署配置
├── README_DEPLOYMENT.md        # 部署说明
└── COMPLETE_DEPLOYMENT_GUIDE.md # 完整部署指南
```

## 环境要求

### 打包环境

- PHP 8.2+
- Composer
- Node.js 16+
- MySQL/PostgreSQL/SQLite
- rsync
- tar
- gzip

### 部署环境

- PHP 8.2+
- Web服务器 (Nginx/Apache)
- 数据库服务器
- Composer
- 必要的PHP扩展

## 故障排除

### 打包问题

1. **权限错误**
   ```bash
   chmod +x *.sh
   ```

2. **依赖安装失败**
   ```bash
   composer install --no-dev --optimize-autoloader --no-interaction
   ```

3. **数据库导出失败**
   ```bash
   # 检查数据库连接
   php artisan tinker
   >>> DB::connection()->getPdo();
   ```

### 部署问题

1. **权限问题**
   ```bash
   sudo chown -R www-data:www-data /path/to/project
   chmod -R 775 storage bootstrap/cache
   ```

2. **数据库连接问题**
   ```bash
   # 检查.env配置
   cat .env | grep DB_
   
   # 测试连接
   php artisan db:show
   ```

3. **依赖问题**
   ```bash
   composer install --no-dev --optimize-autoloader
   ```

## 最佳实践

### 打包前准备

1. **清理项目**
   ```bash
   php artisan config:clear
   php artisan route:clear
   php artisan view:clear
   php artisan cache:clear
   ```

2. **更新依赖**
   ```bash
   composer update --no-dev
   npm update
   ```

3. **测试项目**
   ```bash
   php artisan test
   ```

### 部署后验证

1. **健康检查**
   ```bash
   php artisan tinker --execute="echo 'Application is working';"
   ```

2. **功能测试**
   ```bash
   curl -f http://your-domain.com/health
   ```

3. **日志检查**
   ```bash
   tail -f storage/logs/laravel.log
   ```

## 版本控制

建议为每个版本创建独立的打包脚本：

```bash
# 创建版本标签
git tag -a v1.0.0 -m "Release version 1.0.0"
git push origin v1.0.0

# 打包特定版本
git checkout v1.0.0
./package_project.sh
```

## 自动化

可以将打包过程集成到CI/CD流水线中：

```yaml
# .github/workflows/deploy.yml
name: Build and Package

on:
  push:
    tags:
      - 'v*'

jobs:
  build:
    runs-on: ubuntu-latest
    steps:
    - uses: actions/checkout@v2
    
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '8.3'
        
    - name: Install dependencies
      run: |
        composer install --no-dev --optimize-autoloader
        npm install --production
        npm run build
        
    - name: Package project
      run: ./package_project.sh
      
    - name: Upload artifact
      uses: actions/upload-artifact@v2
      with:
        name: workorder-package
        path: packages/
```

## 支持

如果在使用过程中遇到问题：

1. 查看日志文件
2. 检查系统要求
3. 参考完整部署指南
4. 联系技术支持

---

**最后更新**: 2025-11-21
**版本**: 1.0.0