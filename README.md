# 工单管理系统

基于 Laravel 12 + Tailwind CSS 4 构建的通用工单管理平台，覆盖从故障申报、智能分配、工程师处理到满意度回访的全流程闭环，支持统一身份认证接入、短信通知（含报修人受理通知与满意度短信回复回写）、PWA 离线访问。适用于企业 IT 服务台、物业报修、设备运维等多种场景。

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
- **报修人短信** — 面向报修人的两条独立短信：受理通知（工单受理时发送一次）与满意度调查（工单完结时发送一次，报修人回复 `1`/`0` 自动回写评价）；各自由独立开关控制、整单只发一次，模板可在短信设置页编辑
- **统计报表** — 矩形树图（面积占比）、百分比堆积柱形图（按周期趋势）、工单量趋势对比，支持自定义起止日期和周期数
- **通知中心** — 站内通知、系统公告、批量操作、多通道调度
- **企业微信群通知** — 群机器人 / 自建应用双模式，工单创建 @所有人，分配/超时 @指定工程师（UserID 优先，手机号兜底），系统公告自动推送
- **钉钉通知** — 自定义机器人 / 工作通知（企业内部应用）双模式，@ 逻辑与企业微信一致（userid 优先，手机号兜底），详细配置见 [通知配置指南](docs/NOTIFICATION_GUIDE.md)
- **飞书通知** — 自定义机器人 / 自建应用双模式，工单创建 @所有人（机器人模式），分配/超时 @指定工程师（user_id），详细配置见 [通知配置指南](docs/NOTIFICATION_GUIDE.md)
- **分类管理** — 支持停用/启用（保留历史数据）、排序、级联选择
- **区域/地址管理** — 区域 + 楼栋 + 详细位置三级地址体系
- **部门管理** — 多级组织架构树
- **用户管理** — 角色权限、状态切换、批量操作、统计数据
- **工单模板** — 预设模板快速创建常见工单
- **PWA** — Service Worker 离线缓存、推送通知、添加到主屏幕
- **系统设置** — 按职能拆分为「设置」折叠菜单下的子页：注册设置、系统设置（名称/版本/访问地址）、版本管理、备份&恢复、消息设置（通知规则/短信/企业微信）、详细设置、统一身份认证（CAS / OIDC / 微信）；支持浅色/暗色主题切换。每个子页有独立说明文档，见 [系统设置文档](docs/settings/README.md)
- **数据备份与恢复** — Web 界面一键备份/上传/下载/删除/恢复，恢复前自动创建安全网备份以便回滚；自动每日 02:00 备份，保留最近 30 份。详细说明见 [备份 & 恢复文档](docs/settings/04-backup-restore.md)

### 用户角色与权限

系统共 4 种角色，权限逐级递减、互不重叠：

| 角色 | 职责 | 主要操作 |
|------|------|----------|
| **管理员** `admin` | 全局管理，系统级配置 | 用户/角色管理、系统设置（CAS/短信）、工单全流程、数据备份、所有报表 |
| **工单管理员** `workorder_manager` | 接收报修、工单调度与基础数据维护 | 创建工单、分配调度、工单分类/地址/部门/模板维护、报表导出 |
| **工程师** `engineer` | 现场处理工单 | 接单（含工单池自选取单）、填写处理过程、耗材记录、故障记录单签字、协作邀请、工单解决 |
| **普通用户** `user` | 障碍报修 | 通过 `/report` 简化报修表单提交工单、查看进度、参与满意度回访 |

> **普通用户（含 CAS 认证用户）** 只能通过简化报修页面 `http://域名/report` 提交工单，无法访问标准工单创建、分配、处理等管理功能。
> **CAS 用户** 的个人信息和密码由统一身份认证系统管理，无法在本系统内修改。

### 集成扩展

- **统一身份认证（CAS / OIDC / 微信）** — 与本地认证共存；CAS 用户通过简化报修表单提交工单；OIDC 支持任意标准 OAuth2/OIDC IAM 平台（泛微令信通、派拉、宁盾、阿里云 IDaaS、TOPIAM 等），Authorization Code + PKCE + id_token 校验；微信登录支持普通微信免密认证（需已认证公众号，首次绑定一次），接入说明见 [OIDC 配置文档](docs/settings/08-oidc.md) 与 [微信登录文档](docs/settings/09-wechat-oauth.md)
- **短信通知** — 通用短信网关（阿里云 / 腾讯云 / 自定义），按事件 x 通道规则矩阵控制内部通知发送；另有独立的「报修人短信」通道（受理通知 + 满意度调查），由各自开关单独控制，报修人回复经 `/sms/reply` 回调自动回写满意度
- **通知调度器** — 站内通知 + 短信统一调度，规则化配置哪些事件触发哪些通道
- **企业微信群通知** — 群机器人 / 自建应用双模式，工单创建 @所有人，分配/超时 @指定工程师（UserID 优先，手机号兜底），系统公告自动推送
- **钉钉通知** — 自定义机器人 / 工作通知（企业内部应用）双模式，@ 逻辑与企业微信一致（userid 优先，手机号兜底），详细配置见 [通知配置指南](docs/NOTIFICATION_GUIDE.md)
- **飞书通知** — 自定义机器人 / 自建应用双模式，工单创建 @所有人（机器人模式），分配/超时 @指定工程师（user_id），详细配置见 [通知配置指南](docs/NOTIFICATION_GUIDE.md)

### 企业微信 / 短信通知详细配置

短信（阿里云 / 腾讯云 / 自定义网关）、企业微信（群机器人 / 自建应用）、钉钉（自定义机器人 / 工作通知）、飞书（自定义机器人 / 自建应用）的完整配置步骤、用户 @ 提醒设置、SSL 证书管理、常见问题排查等，请参阅 **[通知配置指南](docs/NOTIFICATION_GUIDE.md)**。


## 技术栈

### 后端

- **框架**：Laravel 12
- **语言**：PHP 8.2+
- **数据库**：PostgreSQL 16+（2026-08 起由 MySQL 8 迁移而来，迁移说明见 [MySQL → PostgreSQL 迁移](#mysql--postgresql-迁移)）
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
│   ├── Auth/                      # 认证（本地登录 + CAS + OIDC + 微信）
│   │   ├── AuthenticatedSessionController.php
│   │   ├── CasAuthController.php  # CAS/LinkID 统一身份认证
│   │   ├── OidcAuthController.php # OIDC/OAuth2 统一身份认证（PKCE + id_token 校验）
│   │   ├── WechatOauthController.php # 微信公众号 OAuth2 登录（openid 绑定 + 免密）
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
│   ├── Campus.php / Location.php  # 区域/地址
│   └── ...
├── Services/
│   ├── Notification/
│   │   ├── NotificationDispatcher.php # 多通道通知调度器
│   │   ├── WeComWebhookService.php   # 企业微信（群机器人/自建应用）
│   │   ├── DingTalkService.php       # 钉钉（自定义机器人/工作通知）
│   │   └── FeishuService.php         # 飞书（自定义机器人/自建应用）
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
- PostgreSQL >= 14（推荐 16，docker-compose 内置 postgres:16-alpine）
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
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=workorder_db
DB_USERNAME=postgres
DB_PASSWORD=your_password
```

> 需在 PHP 中启用 `pdo_pgsql` 扩展。

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

### OIDC 配置

在 **系统设置 > 统一身份认证 > OIDC 认证** 页面配置，接入任意标准 OIDC/OAuth2 IAM 平台：

- **推荐**：填写 Issuer URL（如 `https://iam.example.com`），系统自动通过 OIDC Discovery 发现全部端点
- 平台不支持 Discovery 时，手动填写 Authorization / Token / UserInfo / End Session 端点
- Client Secret 留空则使用 PKCE（公开客户端）；填写则按机密客户端发送 `client_secret`
- 回调地址设为：`http(s)://你的域名/oidc/callback`
- 系统会对 `id_token` 校验 nonce / 过期时间 / 受众 / 签发者，并在拿到 `jwks_uri` 时执行 RSA 签名验证

详细步骤见 [OIDC 统一身份认证文档](docs/settings/08-oidc.md)。

### 短信配置

**系统设置 > 短信配置** 页面分为「短信网关接入」与「报修人短信」两块，各自独立：

**一、短信网关接入（面向内部人员的通知）**

1. 选择服务商：阿里云 / 腾讯云 / 自定义网关，填写签名、模板 CODE 与认证凭证。
2. 在 **系统设置 > 通知规则** 页面按「事件 × 通道」矩阵控制内部通知（站内 / 短信）的发送，接收对象为系统内的管理员、工单管理员、工程师，与报修人无关。
3. 自定义网关模式下，模板文案保存后直接生效；阿里云 / 腾讯云的模板需先在服务商控制台报备审核，本地文案仅供对照，实际发送使用控制台中已通过的模板。

**二、报修人短信（面向报修人，默认关闭，独立于上方矩阵）**

| 配置项 | 说明 |
|------|------|
| **受理通知开关** `creator_sms_enabled` | 工单受理时（创建即分配，或工程师自行接单）向报修人发送一次，告知已受理并提供工程师电话 |
| **满意度调查开关** `creator_survey_enabled` | 工单完结时向报修人发送一次，报修人回复 `1`（满意）/ `0`（不满意）自动回写评价 |

- **模板编辑**：支持占位符 `{系统名称}` `{工程师电话}` `{预约时间}` `{工单编号}`，受理模板按「有预约时间 / 无预约时间」分两套，满意度模板单独一份，保存后立即生效。
- **防重**：每条短信在整单生命周期内最多发送一次，由 `sms_acceptance_sent_at` / `sms_survey_sent_at` 标记保证不重复，创建与接单同时触发也只发一条。
- **回复闭环**：开启满意度调查后，需在短信服务商后台将**上行回复回调地址**配置为 `http(s)://你的域名/sms/reply`（该路由已排除 CSRF 校验）。系统按当前服务商自动适配回调字段：阿里云 `phone_number/content`、腾讯云 `PhoneNumber/ReplyContent`、自定义默认 `phone/content`。

> 报修人短信默认全部关闭，开启后不影响存量工单；回复回写依赖服务商的上行回调能力，未配置回调时短信仍能正常发送，只是无法自动回写评价。可在工单详情页查看「报修人评价」区块（满意 / 不满意 / 未回复 + 发送与回复时间）。

## 默认账号

> **安全警告**：以下为开发测试用默认账号，**生产环境部署后必须立即修改所有默认密码**。系统已内置强制改密机制，首次登录或管理员重置密码后会跳转到改密页面。

| 角色 | 登录名 | 密码 | 说明 |
|------|--------|------|------|
| 管理员 | admin | admin123 | 全部权限 |
| 工单管理员 | manager | manager123 | 接收报修、工单调度 |
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
- `campuses` / `locations` — 区域/地址（含地址树：`locations.parent_id` / `level_id`，工单通过 `workorders.location_id` 关联）
- `system_settings` — 系统配置（键值对）
- `workorder_sources` — 工单来源

> 数据库为 PostgreSQL。主键采用 `bigint GENERATED BY DEFAULT AS IDENTITY`（序列值同步自原 MySQL 的 `AUTO_INCREMENT`），`tinyint(1)` 布尔列映射为 `boolean`，`json` 列映射为 `json`。

## MySQL → PostgreSQL 迁移

系统数据库已于 2026-08 由 MySQL 8 迁移至 PostgreSQL 16，仓库提供一次性转换脚本 `scripts/convert_mysql_dump_to_pgsql.py` 供重复迁移或升级复用。

### 迁移步骤

1. **从 MySQL 导出 dump**（`mysqldump`，需完整表结构与数据）：

   ```bash
   mysqldump -h 127.0.0.1 -P 3306 -u root -p --single-transaction --no-tablespaces \
     workorder_db > workorder_mysql.sql
   ```

2. **转换为 PostgreSQL 语法**：

   ```bash
   python scripts/convert_mysql_dump_to_pgsql.py workorder_mysql.sql workorder_pgsql.sql
   ```

   也可用环境变量指定：`MYSQL_DUMP_PATH` / `PGSQL_OUT_PATH`。

3. **导入 PostgreSQL**：

   ```bash
   createdb -h 127.0.0.1 -p 5433 -U postgres workorder_db
   psql -h 127.0.0.1 -p 5433 -U postgres -d workorder_db -v ON_ERROR_STOP=1 \
     -f workorder_pgsql.sql
   ```

4. **补跑应用自身的待执行迁移**（导出的库落后于最新迁移时）：

   ```bash
   php artisan migrate --force
   ```

### 转换规则

| MySQL | PostgreSQL | 说明 |
|-------|-----------|------|
| `tinyint(1)` | `boolean` | Laravel boolean 列在 MySQL 中即 tinyint(1) |
| `tinyint unsigned` | `smallint` | |
| `int unsigned` | `bigint` | 保险起见放宽为 8 字节 |
| `enum` / `set` | `varchar(255)` | |
| `json` | `json` | |
| `datetime` / `timestamp` | `timestamp` | 不带时区，与 MySQL 语义一致 |
| 自增主键 | `bigint GENERATED BY DEFAULT AS IDENTITY` | |
| `AUTO_INCREMENT` 值 | `SELECT setval(...)` | 每条序列同步原自增值 |
| 外键 | `ALTER TABLE ... ADD CONSTRAINT ... NOT VALID` | 容忍 MySQL 遗留孤儿数据，新写入仍校验 |

导入在单事务内完成，数据写入期间通过 `session_replication_role=replica` 跳过外键检查，导入完成后统一添加外键与索引。

> **迁移后常见兼容点**：MySQL 对布尔列查询 `status = 'active'` 这类字符串比较会静默转成 `0`（返回空集），而 PostgreSQL 会直接报「invalid input syntax for type boolean」。迁移时已排查并修正了代码中此类查询，后续开发若遇 PG 报错请优先检查布尔列是否被传入字符串。

## 相关文档

| 文档 | 内容 |
|------|------|
| [系统设置文档](docs/settings/README.md) | 9 个设置子页（注册/系统/版本/备份恢复/消息/详细/CAS/OIDC/微信）的逐页说明 |
| [备份 & 恢复](docs/settings/04-backup-restore.md) | Web 备份恢复操作、自动备份、命令行备份、排错 |
| [通知配置指南](docs/NOTIFICATION_GUIDE.md) | 短信、企业微信、钉钉、飞书的完整接入步骤与常见报错排查 |

## Docker 部署

项目提供多阶段 Dockerfile 和 docker-compose，一条命令启动应用 + PostgreSQL（`postgres:16-alpine`）：

```bash
# 设置数据库密码（可选，默认 secret）
export DB_PASSWORD=your_strong_password

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
| `DB_PASSWORD` | `secret` | PostgreSQL 应用用户密码（compose 中 `workorder` 用户） |

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
覆盖状态+时间、区域+时间、分类+时间等高频筛选组合。

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

**CAS 用户无法修改个人信息**：CAS 用户的账号信息由统一身份认证服务方管理，此为预期行为。

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
