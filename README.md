# CDU 校园网络工单系统

基于 Laravel 12 + Tailwind CSS 4 构建的校园网络运维工单管理平台，覆盖从故障申报、智能分配、工程师处理到满意度回访的全流程闭环，支持统一身份认证接入、短信通知、PWA 离线访问。

## 功能概览

### 工单全生命周期

| 状态 | 说明 |
|------|------|
| `pending` 待处理 | 新建工单（含 CAS 自助报修），等待分配或工程师自行接单 |
| `assigned` 已分配 | 管理员分配给指定工程师 |
| `processing` 处理中 | 工程师接单并开始处理 |
| `resolved` 已解决 | 工程师完成修复，等待用户确认 |
| `completed` 已完结 | 用户确认问题已解决 |
| `closed` 已关闭 | 工单流程结束 |

### 核心模块

- **工单管理** — 创建、分配、接单、处理、解决、完结、关闭，支持批量操作
- **自助报修** — CAS 用户通过简化表单快速报修，提交后进入工单池，工程师可就近自行接单
- **故障处理记录单** — 需签单工单现场填写记录单 + 手写签名，生成 HTML 附件存档，支持 A4 打印
- **协作处理** — 工程师可邀请他人协作，支持邀请接受/拒绝流程
- **回访管理** — 工单解决后自动发起满意度调查
- **统计报表** — 矩形树图（面积占比）、百分比堆积柱形图（按周期趋势）、工单量趋势对比，支持自定义起止日期和周期数
- **通知中心** — 站内通知、系统公告、批量操作、多通道调度
- **企业微信群通知** — 群机器人 / 自建应用双模式，工单创建 @所有人，分配/超时 @指定工程师（UserID 优先，手机号兜底），系统公告自动推送。详细配置见 [通知配置指南](docs/NOTIFICATION_GUIDE.md)
- **分类管理** — 支持停用/启用（保留历史数据）、排序、级联选择
- **校区/地址管理** — 校区 + 楼栋 + 门牌号三级地址体系
- **部门管理** — 多级组织架构树
- **用户管理** — 角色权限、状态切换、批量操作、统计数据
- **工单模板** — 预设模板快速创建常见工单
- **PWA** — Service Worker 离线缓存、推送通知、添加到主屏幕

### 用户角色与权限

系统共 4 种角色，权限逐级递减、互不重叠：

| 角色 | 职责 | 主要操作 |
|------|------|----------|
| **管理员** `admin` | 全局管理，系统级配置 | 用户/角色管理、系统设置（CAS/短信）、工单全流程、数据备份、所有报表 |
| **工单管理员** `workorder_manager` | 接收报修、工单调度与基础数据维护 | 创建工单、分配调度、工单分类/地址/部门/模板维护、报表导出 |
| **工程师** `engineer` | 现场处理工单 | 接单（含工单池自选取单）、填写处理过程、耗材记录、故障记录单签字、协作邀请、工单解决 |
| **普通用户** `user` | 障碍报修 | 通过 `/report` 简化报修表单提交工单、查看进度、参与满意度回访 |

> **普通用户（含 CAS 认证用户）** 只能通过简化报修页面 `http://域名/report` 提交工单，无法访问标准工单创建、分配、处理等管理功能。
> **CAS 用户** 的个人信息和密码由学校统一身份认证系统管理，无法在本系统内修改。

### 集成扩展

- **统一身份认证（CAS / LinkID）** — 与本地认证共存，CAS 用户自动创建并登录，通过简化报修表单提交工单
- **短信通知** — 通用短信网关（阿里云 / 腾讯云 / 自定义），按事件 x 通道规则矩阵控制发送，后台可开关
- **通知调度器** — 站内通知 + 短信统一调度，规则化配置哪些事件触发哪些通道
- **企业微信群通知** — 群机器人 / 自建应用双模式，工单创建 @所有人，分配/超时 @指定工程师（UserID 优先，手机号兜底），系统公告自动推送。详细配置见 [通知配置指南](docs/NOTIFICATION_GUIDE.md)

### 企业微信 / 短信通知详细配置

短信（阿里云 / 腾讯云 / 自定义网关）和企业微信（群机器人 / 自建应用）的完整配置步骤、用户 UserID @ 提醒设置、SSL 证书管理、常见问题排查等，请参阅 **[通知配置指南](docs/NOTIFICATION_GUIDE.md)**。


## 技术栈

### 后端

- **框架**：Laravel 12
- **语言**：PHP 8.2+
- **数据库**：MySQL 8.0+
- **认证**：Laravel Auth + CAS 3.0 协议
- **队列/缓存/Session**：database 驱动
- **文件存储**：local disk（`storage/app/public`）

### 前端

- **CSS 框架**：Tailwind CSS 4（已移除 Bootstrap 依赖）
- **构建工具**：Vite 7
- **模板引擎**：Blade
- **图表**：ECharts（矩形树图、堆积柱形图、趋势图）
- **PWA**：Service Worker + Web App Manifest

## 项目结构

```
app/
├── Http/Controllers/
│   ├── Auth/                      # 认证（本地登录 + CAS）
│   │   ├── AuthenticatedSessionController.php
│   │   ├── CasAuthController.php  # CAS/LinkID 统一身份认证
│   │   └── RegisteredUserController.php
│   ├── Traits/
│   │   └── HandlesReport.php      # CAS 用户简化报修逻辑
│   ├── WorkorderController.php    # 工单主控制器
│   ├── AttachmentController.php   # 附件预览/下载/删除
│   ├── ReportController.php       # 统计报表
│   ├── SystemSettingController.php# 系统设置 + SMS/CAS 配置
│   ├── WorkorderSignatureController.php # 故障处理记录单 + 签名
│   └── ...
├── Models/
│   ├── Workorder.php              # 工单模型（含通知调度、日志、状态流转）
│   ├── WorkorderAttachment.php    # 附件模型（图片压缩、缩略图、预览）
│   ├── WorkorderSignatureDocument.php # 签名文档模型
│   ├── Notification.php           # 通知模型（工单事件通知）
│   ├── SystemSetting.php          # 系统设置（键值对）
│   ├── Campus.php / Location.php  # 校区/地址
│   └── ...
├── Services/
│   ├── Notification/
│   │   └── NotificationDispatcher.php # 多通道通知调度器
│   ├── Sms/
│   │   ├── SmsManager.php         # 短信管理器（读取 system_settings）
│   │   ├── AliyunSmsDriver.php
│   │   ├── TencentSmsDriver.php
│   │   └── CustomSmsDriver.php
│   ├── WorkorderPermissionService.php
│   └── WorkorderSignaturePDFService.php
config/
└── services.php                   # CAS/SMS 第三方服务配置
resources/
├── views/
│   ├── workorders/                # 工单相关视图（创建/详情/报表/签名）
│   ├── reports/                   # 统计报表视图
│   ├── system-settings/           # 系统设置（通知规则/短信/CAS）
│   ├── notifications/             # 通知中心
│   └── layouts/app.blade.php      # 主布局（PWA/主题切换）
├── css/app.css                    # Tailwind CSS 4 入口
└── js/
    ├── app.js
    └── pwa.js                     # PWA 注册 + 推送通知
public/
├── sw.js                          # Service Worker
└── offline.html                   # 离线回退页
```

## 安装部署

### 环境要求

- PHP >= 8.2（已测试 8.4）
- MySQL >= 8.0
- Composer
- Node.js >= 18 + NPM

### 安装步骤

1. **克隆项目**

```bash
git clone <repository-url>
cd workorder-system
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

编辑 `.env`，配置数据库连接：

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=workorder_db
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

4. **数据库初始化**

```bash
php artisan migrate
php artisan db:seed
```

5. **创建存储符号链接**

```bash
php artisan storage:link
```

6. **构建前端资源**

```bash
npm run build      # 生产构建
# 或
npm run dev        # 开发模式（热更新）
```

7. **启动服务**

```bash
php artisan serve --host=127.0.0.1 --port=8000
```

### CAS / LinkID 配置

在 **系统设置 > 统一身份认证** 页面配置，或编辑 `.env`：

```env
CAS_ENABLED=true
CAS_BASE_URL=https://linkid.example.com/cas
CAS_SERVICE_ID=workorder
```

CAS 用户属性映射在 `config/services.php` 的 `cas` 节配置。

### 短信配置

在 **系统设置 > 短信配置** 页面选择服务商并填写凭证。通知发送规则在 **系统设置 > 通知规则** 页面按事件 x 通道矩阵开关。

## 默认账号

> **安全警告**：以下为开发测试用默认账号，**生产环境部署后必须立即修改所有默认密码**。系统已内置强制改密机制，首次登录或管理员重置密码后会跳转到改密页面。

| 角色 | 登录名 | 密码 | 说明 |
|------|--------|------|------|
| 管理员 | admin | admin123 | 全部权限 |
| 工程师 | engineer | engineer123 | 接单处理 |
| 普通用户 | user | user123 | 提交工单 |

## 数据库表

系统共 26 张数据表，核心业务表：

- `workorders` — 工单主表
- `workorder_logs` — 处理记录
- `workorder_attachments` — 附件
- `workorder_visits` — 回访记录
- `workorder_collaborations` — 协作记录
- `workorder_templates` — 工单模板
- `workorder_signature_documents` — 故障处理记录单
- `workorder_categories` / `workorder_categories_simplified` — 故障分类
- `notifications` — 站内通知
- `users` / `departments` — 用户/部门
- `campuses` / `locations` — 校区/地址
- `system_settings` — 系统配置（键值对）
- `workorder_sources` — 工单来源

## Docker 部署

项目提供多阶段 Dockerfile 和 docker-compose，一条命令启动应用 + MySQL：

```bash
# 设置数据库密码（可选，默认 secret/rootsecret）
export DB_PASSWORD=your_strong_password
export DB_ROOT_PASSWORD=your_root_password

# 构建并启动
docker compose up -d --build

# 初始化数据库（首次部署）
docker compose exec app php artisan migrate --force
docker compose exec app php artisan db:seed
docker compose exec app php artisan storage:link
docker compose exec app php artisan key:generate

# 验证
curl http://localhost/login
```

镜像内置 Nginx + PHP-FPM + Queue Worker（Supervisor 管理），无需额外配置进程守护。

### 环境变量

| 变量 | 默认值 | 说明 |
|------|--------|------|
| `DB_PASSWORD` | `secret` | 应用数据库用户密码 |
| `DB_ROOT_PASSWORD` | `rootsecret` | MySQL root 密码 |

## 生产环境优化

### OPcache

Docker 镜像已开启 OPcache（`validate_timestamps=0`），代码变更后需重启容器：

```bash
docker compose restart app
```

### 队列与调度

Queue Worker 已由 Supervisor 在容器内自动管理。定时任务（如每日备份）需在宿主机添加 Cron：

```bash
* * * * * cd /var/www/html && php artisan schedule:run >> /dev/null 2>&1
```

或使用 Docker 的 cron sidecar 容器。

### 数据库索引

报表查询已优化 5 个复合索引（见 `2026_07_15_080000_add_report_query_indexes` 迁移），
覆盖状态+时间、校区+时间、分类+时间等高频筛选组合。

### 性能基准参考

| 场景 | 单服务器参考值 |
|------|----------------|
| 工单列表分页 | < 50ms（含筛选，2000 条数据） |
| 统计报表 | < 200ms（90 天范围，5 个维度） |
| 并发接单 | 乐观锁保证无重复 |
| 附件上传 | 单文件最大 10MB，最多 5 个 |

> 以上为开发环境参考值，生产环境实际性能取决于服务器配置和网络条件。

## 备份与恢复

```bash
# 手动备份（数据库 + 附件，存入 storage/backups）
php artisan backup:system

# 生产部署建议使用含备份的初始化
composer setup:prod

# 自动备份（已在调度中注册，每日 02:00）
# 保留最近 30 份，可通过 --keep=N 调整
```

备份文件位于 `storage/app/private/backups/YYYYMMDD_HHMMSS/`，包含 `database.sql` 和 `attachments.zip`。

## 常见问题排查

**登录后被强制跳转到修改密码页面**：默认密码安全策略，首次登录或管理员重置密码后必须修改。

**CAS 用户无法修改个人信息**：CAS 用户的账号信息由学校统一身份认证管理，此为预期行为。

**短信发送失败**：检查系统设置 > 短信配置中的网关参数和通知规则（事件 x 通道）。

**附件预览异常**：已添加缓存破坏参数，如仍有问题请清除浏览器缓存或检查 `storage:link`。

**报表加载缓慢**：确认已运行最新迁移（包含查询索引优化），检查 `workorders` 表数据量。

**Queue Worker 不工作**：Docker 部署中由 Supervisor 自动管理；手动部署需运行 `php artisan queue:work`。

**企业微信 / 短信等 HTTPS 出站报 `cURL error 60`**：PHP 未配置 CA 证书，导致 cURL 无法校验 HTTPS 证书链。从 https://curl.se/ca/cacert.pem 下载 `cacert.pem`，在 `php.ini` 中设置 `curl.cainfo` 与 `openssl.cafile` 指向它，重启 PHP 后生效。本项目已内置 `.tools/php/extras/cacert.pem`。
也可在企业微信配置页直接上传证书或关闭验证（仅限测试）。

**企业微信自建应用报 `60020` 或 `not allow to access from your ip`**：服务器出口 IP 未加入企业微信应用的可信 IP 列表。在管理后台 → 应用详情 →「企业可信IP」中添加服务器公网 IP。注意配置可信 IP 前需先设置可信域名（要求已备案且主体关联）。

**企业微信自建应用报 `invalid corpid` 或 `40001`**：CorpID 或 Secret 填写错误，或者 Secret 已被重置。检查凭证是否正确，必要时在管理后台重置 Secret 后更新配置。

## 监控建议

- **日志**：`storage/logs/laravel.log`，建议配合日志轮转（`LOG_STACK=daily`）
- **健康检查**：`/up` 路由返回服务器状态
- **备份监控**：检查 `storage/app/private/backups/` 下是否有当日备份
- **队列积压**：`php artisan queue:failed` 查看失败任务

---

## License

Copyright (c) 2025-2026 hicool (hicool.ml@gmail.com). All rights reserved.

本项目为私有项目，仅限内部使用，未经作者书面许可不得复制、传播或用于商业用途。

## 联系方式

- **项目维护者**：hicool
- **邮箱**：[hicool.ml@gmail.com](mailto:hicool.ml@gmail.com)
