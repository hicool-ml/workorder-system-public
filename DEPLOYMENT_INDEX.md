8d6aadcd5efd4b4f828b4ffe34fa7397.B9Y3kKN4mIs2LPVN# 📚 工单系统部署文档索引

## 🎯 快速开始

### 新手部署（推荐）
- 🚀 [快速部署指南](QUICK_DEPLOY_GUIDE.md) - 5分钟完成基础部署
- 📋 [部署前检查清单](#部署前检查清单) - 确保环境准备就绪

### 完整部署
- 📖 [部署到其他服务器完整指南](DEPLOY_TO_OTHER_SERVERS.md) - 详细的部署流程
- 🔧 [部署维护指南](DEPLOYMENT_MAINTENANCE_GUIDE.md) - 部署后的维护和优化

## 📋 部署文档分类

### 🚀 快速部署
| 文档 | 描述 | 适用场景 |
|------|------|----------|
| [QUICK_DEPLOY_GUIDE.md](QUICK_DEPLOY_GUIDE.md) | 5分钟快速部署 | 新手、测试环境、快速体验 |
| [auto_deploy.sh](auto_deploy.sh) | 自动化部署脚本 | 生产环境、批量部署 |

### 📖 详细指南
| 文档 | 描述 | 适用场景 |
|------|------|----------|
| [DEPLOY_TO_OTHER_SERVERS.md](DEPLOY_TO_OTHER_SERVERS.md) | 完整部署指南 | 生产环境、自定义配置 |
| [DEPLOYMENT_MAINTENANCE_GUIDE.md](DEPLOYMENT_MAINTENANCE_GUIDE.md) | 部署维护指南 | 系统维护、性能优化 |

### 🔧 环境准备
| 脚本 | 描述 | 适用场景 |
|------|------|----------|
| [setup_server.sh](setup_server.sh) | 环境准备脚本 | 新服务器初始化 |
| [check_dependencies.sh](check_dependencies.sh) | 依赖检查脚本 | 环境验证 |

### 📦 项目打包
| 脚本 | 描述 | 适用场景 |
|------|------|----------|
| [package_project.sh](package_project.sh) | 项目打包脚本 | 创建部署包、版本发布 |

## 🎯 部署场景选择

### 场景1：全新服务器部署
**推荐流程**：
1. 📋 [部署前检查清单](#部署前检查清单)
2. 🚀 [快速部署指南](QUICK_DEPLOY_GUIDE.md)
3. 📖 [部署维护指南](DEPLOYMENT_MAINTENANCE_GUIDE.md)

### 场景2：测试环境部署
**推荐流程**：
1. 🚀 [快速部署指南](QUICK_DEPLOY_GUIDE.md)
2. 🔧 [环境准备脚本](setup_server.sh)

### 场景3：生产环境部署
**推荐流程**：
1. 📋 [部署前检查清单](#部署前检查清单)
2. 📖 [完整部署指南](DEPLOY_TO_OTHER_SERVERS.md)
3. 🔧 [自动部署脚本](auto_deploy.sh)
4. 📖 [部署维护指南](DEPLOYMENT_MAINTENANCE_GUIDE.md)

### 场景4：批量部署
**推荐流程**：
1. 📦 [项目打包脚本](package_project.sh)
2. 🔧 [环境准备脚本](setup_server.sh)
3. 🔧 [自动部署脚本](auto_deploy.sh)

## 📋 部署前检查清单

### 🔍 系统要求检查
- [ ] **操作系统**：Ubuntu 20.04+ / CentOS 8+ / Debian 10+
- [ ] **CPU**：最少2核心，推荐4核心
- [ ] **内存**：最少4GB，推荐8GB
- [ ] **存储**：最少20GB可用空间
- [ ] **网络**：稳定的互联网连接

### 🛠️ 软件环境检查
- [ ] **PHP**：版本 >= 8.1
- [ ] **数据库**：MySQL >= 8.0 或 MariaDB >= 10.3
- [ ] **Web服务器**：Apache >= 2.4 或 Nginx >= 1.18
- [ ] **Composer**：版本 >= 2.0
- [ ] **Node.js**：版本 >= 16.0（可选，前端编译）

### 🔐 权限和安全检查
- [ ] **sudo权限**：当前用户具有sudo权限
- [ ] **防火墙**：端口22、80、443已开放
- [ ] **SSL证书**：已准备或计划配置Let's Encrypt

### 📁 文件和目录检查
- [ ] **部署包**：已下载最新部署包
- [ ] **目标目录**：已确定应用安装路径
- [ ] **备份计划**：已制定数据备份策略

## 🚀 部署命令速查

### 一键部署
```bash
# 下载并解压部署包
wget https://your-source-server.com/packages/campus-workorder-system-v1.0.0_*.tar.gz
tar -xzf campus-workorder-system-v1.0.0_*.tar.gz
cd campus-workorder-system-v1.0.0_*/

# 运行自动部署
sudo ./auto_deploy.sh -e production -v
```

### 手动部署
```bash
# 1. 环境检查
./check_dependencies.sh

# 2. 环境准备（如需要）
sudo ./setup_server.sh

# 3. 安装依赖
composer install --no-dev --optimize-autoloader

# 4. 配置环境
cp .env.example .env
php artisan key:generate

# 5. 数据库迁移
php artisan migrate --force
php artisan db:seed --force

# 6. 设置权限
chmod -R 755 storage/ bootstrap/cache/
```

### 项目打包
```bash
# 在源服务器上运行
./package_project.sh

# 生成的包位于 packages/ 目录
ls -la packages/
```

## 🔧 常用配置模板

### Apache虚拟主机配置
```apache
<VirtualHost *:80>
    ServerName your-domain.com
    DocumentRoot /var/www/workorder/public
    
    <Directory /var/www/workorder/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

### Nginx站点配置
```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /var/www/workorder/public;
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

### 数据库配置
```sql
CREATE DATABASE workorder_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'workorder_user'@'localhost' IDENTIFIED BY 'your_strong_password';
GRANT ALL PRIVILEGES ON workorder_system.* TO 'workorder_user'@'localhost';
FLUSH PRIVILEGES;
```

## 🆘 故障排除快速参考

### 常见错误及解决方案

| 错误类型 | 可能原因 | 解决方案 |
|----------|----------|----------|
| 500错误 | 文件权限不足 | `chmod -R 755 storage/ bootstrap/cache/` |
| 数据库连接失败 | 配置错误或服务未启动 | 检查`.env`文件和MySQL服务状态 |
| 文件上传失败 | 上传目录权限或PHP配置 | 检查`storage/app/public/`权限和`php.ini`配置 |
| 页面加载慢 | 缓存未配置或数据库查询慢 | 运行`php artisan config:cache`优化 |

### 日志文件位置
- **应用日志**：`storage/logs/laravel.log`
- **Apache日志**：`/var/log/apache2/`
- **Nginx日志**：`/var/log/nginx/`
- **PHP错误日志**：`/var/log/php8.1-fpm.log`
- **MySQL日志**：`/var/log/mysql/`

## 📞 技术支持

### 📖 相关文档
- [用户手册](USER_MANUAL.md) - 系统使用指南
- [开发者指南](DEVELOPER_GUIDE.md) - 开发和定制指南
- [API文档](API_DOCUMENTATION.md) - 接口文档
- [数据库设计](DETAILED_DATABASE_DESIGN.md) - 数据库结构说明

### 🔍 常用诊断命令
```bash
# 检查应用状态
php artisan about

# 检查路由配置
php artisan route:list

# 检查缓存状态
php artisan cache:status

# 测试数据库连接
php artisan tinker
>>> DB::connection()->getPdo();

# 运行系统测试
php artisan test
```

### 📧 获取帮助
如果遇到问题，请按以下顺序获取帮助：
1. 查看相关文档
2. 检查日志文件
3. 运行诊断命令
4. 联系技术支持

---

**💡 提示**：建议首次部署时使用[快速部署指南](QUICK_DEPLOY_GUIDE.md)，熟悉后再参考完整文档进行自定义配置。