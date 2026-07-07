# 校园网工单系统

## 项目概述

校园网工单系统是一个基于 Laravel 12 框架开发的 Web 应用程序，旨在实现校园网络维护任务的全流程管理，涵盖工单创建、处理、跟踪与统计分析等功能。系统支持多角色协作（管理员、工程师、普通用户），提升故障响应效率和管理透明度。

## 功能特性

### 核心功能
- ✅ **工单管理**：创建、分配、处理、解决、关闭工单
- ✅ **多源接入**：支持电话、网络、邮件、现场等多种上报方式
- ✅ **智能分类**：根据工单类型自动设置优先级和处理时限
- ✅ **闭环管理**：从报修到回访形成完整闭环
- ✅ **数据分析**：提供可视化报表，助力决策优化

### 角色权限
- **管理员**：拥有最高权限，负责系统配置、用户管理、数据备份等
- **工程师**：可查看、接单、处理、回访工单
- **普通用户**：只能提交工单、查看进度、参与回访

### 工单流程
1. **问题提交** → 用户通过多种渠道提交工单
2. **工单分配** → 管理员分配给合适的工程师
3. **开始处理** → 工程师接单并开始处理
4. **问题解决** → 工程师完成问题修复
5. **用户验证** → 用户确认问题是否解决
6. **满意度回访** → 系统自动或手动进行满意度调查
7. **工单关闭** → 完成整个工单流程

## 技术架构

### 后端技术栈
- **框架**：Laravel 12
- **数据库**：MySQL 8.0+
- **PHP版本**：PHP 8.2+
- **认证**：Laravel Auth
- **中间件**：角色权限控制

### 前端技术栈
- **UI框架**：Bootstrap 5.3
- **图标库**：Font Awesome 6.4
- **JavaScript**：jQuery 3.6
- **模板引擎**：Blade

### 数据库设计

详细的数据库设计文档请参阅：[DATABASE_DESIGN.md](DATABASE_DESIGN.md)

#### 核心数据表
- **users**：用户表（扩展了Laravel默认用户表）
- **departments**：部门表（支持多级部门结构）
- **workorder_types**：工单类型表（支持来源和子类别）
- **workorders**：工单主表（核心业务表）
- **workorder_logs**：工单处理记录表
- **workorder_attachments**：工单附件表
- **workorder_visits**：回访记录表
- **workorder_collaborations**：工单协作表
- **workorder_templates**：工单模板表
- **notifications**：通知表
- **locations**：位置表

## 安装部署

### 环境要求
- PHP >= 8.2
- MySQL >= 8.0
- Composer
- Node.js & NPM（用于前端资源编译）

### 安装步骤

1. **克隆项目**
```bash
git clone <repository-url>
cd workorder
```

2. **安装依赖**
```bash
composer install
npm install
```

3. **环境配置**
```bash
cp .env.example .env
php artisan key:generate
```

4. **数据库配置**
编辑 `.env` 文件，配置数据库连接：
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=workorder_db
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

5. **运行迁移**
```bash
php artisan migrate
```

6. **创建初始数据**
```bash
php artisan db:seed
```

## 默认管理员账户

系统安装完成后，会自动创建以下默认账户：

| 角色 | 邮箱 | 密码 | 说明 |
|------|--------|------|------|
| 管理员 | admin@workorder.com | admin123 | 系统管理员，拥有所有权限 |
| 工程师 | engineer@workorder.com | engineer123 | 测试工程师，可处理工单 |
| 普通用户 | user@workorder.com | user123 | 测试用户，可提交工单 |

**重要**：请在首次登录后立即修改默认密码，确保系统安全！

7. **编译前端资源**
```bash
npm run build
```

8. **启动服务**
```bash
php artisan serve
```

### Web服务器配置

#### Apache配置
```apache
<VirtualHost *:80>
    ServerName your-domain.com
    DocumentRoot /path/to/workorder/public
    
    <Directory /path/to/workorder/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

#### Nginx配置
```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/workorder/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

## 使用指南

### 管理员操作

#### 用户管理
1. 访问 `/users` 进入用户管理页面
2. 点击"创建用户"添加新用户
3. 设置用户角色和所属部门
4. 管理用户状态和权限

#### 部门管理
1. 访问 `/departments` 进入部门管理页面
2. 支持多级部门结构
3. 设置部门负责人和联系方式
4. 管理部门状态

#### 工单类型管理
1. 访问 `/workorder-types` 进入工单类型管理
2. 配置工单来源和子类别
3. 设置默认优先级和处理时限
4. 管理工单类型状态

### 工程师操作

#### 工单处理
1. 登录系统查看分配给自己的工单
2. 点击"开始处理"开始工单处理
3. 添加处理记录和上传附件
4. 完成后填写解决方案
5. 进行用户回访和满意度调查

### 普通用户操作

#### 提交工单
1. 访问 `/workorders/create` 创建工单
2. 填写工单基本信息和问题描述
3. 提供准确的联系方式和位置信息
4. 上传相关附件（可选）
5. 提交工单等待处理

#### 查看进度
1. 访问工单列表查看自己的工单
2. 点击工单号查看详细信息
3. 查看处理记录和当前状态
4. 参与满意度回访

## API文档

### 认证接口
所有API接口需要通过Laravel的认证机制。

### 工单相关接口

#### 获取工单列表
```
GET /api/workorders
参数：
- keyword: 关键词搜索
- status: 状态筛选
- priority: 优先级筛选
- type_id: 类型筛选
- assignee_id: 处理人筛选
- date_from: 开始日期
- date_to: 结束日期
```

#### 创建工单
```
POST /api/workorders
参数：
- title: 工单标题（必填）
- description: 问题描述（必填）
- type_id: 工单类型ID（必填）
- contact_name: 联系人（必填）
- contact_phone: 联系电话（必填）
- location: 故障地点（必填）
- priority: 优先级
- source: 工单来源
- attachments: 附件文件
```

### 部门相关接口

#### 获取部门树形结构
```
GET /api/departments/tree
返回：部门树形JSON数据
```

#### 获取部门统计
```
GET /api/departments/{id}/statistics
返回：部门用户数、工单数等统计信息
```

### 工单类型相关接口

#### 获取工单类型选项
```
GET /api/workorder-types/options
参数：
- source: 来源筛选
返回：工单类型列表
```

## 系统维护

### 数据备份
```bash
# 数据库备份
mysqldump -u username -p workorder_db > backup.sql

# 文件备份
tar -czf workorder_backup.tar.gz /path/to/workorder
```

### 日志管理
```bash
# 查看Laravel日志
tail -f storage/logs/laravel.log

# 清理旧日志
php artisan log:clear
```

### 性能优化
```bash
# 清理缓存
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# 优化自动加载
composer dump-autoload --optimize
```

## 常见问题

### Q: 工单编号如何生成？
A: 系统自动生成，格式为 WO + 日期 + 序号，如 WO202411170001

### Q: 如何设置工单优先级？
A: 可以在工单类型中设置默认优先级，也可以在创建工单时手动调整

### Q: 支持哪些文件类型的附件？
A: 支持图片（jpg、png、gif）、文档（pdf、doc、docx、txt）等，单个文件最大10MB

### Q: 如何重置管理员密码？
A: 使用以下命令：
```bash
php artisan tinker
User::where('email', 'admin@example.com')->update(['password' => Hash::make('newpassword')]);
```

### Q: 系统支持多语言吗？
A: 当前版本仅支持中文，后续版本会考虑多语言支持

## 开发指南

### 代码规范
- 遵循 PSR-12 编码规范
- 使用 Laravel 的编码约定
- 所有数据库操作使用 Eloquent ORM
- 控制器方法保持简洁，业务逻辑放在 Service 层

### 测试
```bash
# 运行单元测试
php artisan test

# 运行特定测试
php artisan test --filter WorkorderTest
```

### 贡献指南
1. Fork 项目
2. 创建功能分支
3. 提交代码
4. 创建 Pull Request

## 版本历史

### v2.0.0 (2025-12-16)
- 优化登录页面显示，避免重复系统名称
- 将第二个系统名称改为"系统登录"
- 提升用户体验和界面一致性

### v1.0.0 (2024-11-17)
- 初始版本发布
- 实现基础工单管理功能
- 支持多角色权限控制
- 完成前后端基础界面

## 许可证

本项目采用 MIT 许可证，详情请参阅 LICENSE 文件。

## 联系方式

- 项目维护者：开发团队
- 邮箱：support@example.com
- 问题反馈：请使用 GitHub Issues

## 更新日志

### 2024-11-17
- 完成系统基础架构搭建
- 实现用户认证和权限管理
- 完成工单核心功能
- 实现部门和工单类型管理
- 完成前端界面开发
