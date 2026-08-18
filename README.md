# 工单管理系统

基于 Laravel 12 + Tailwind CSS 4 构建的通用工单管理平台。覆盖从故障申报、分配接单、工程师处理、协作处理到满意度回访的完整闭环，支持多项目地址管理、多通道消息通知（企业微信 / 钉钉 / 飞书 / 短信）、统一身份认证接入。适用于企业 IT 服务台、物业报修、设备运维等多种场景。

---

## 目录

- [功能概览](#功能概览)
- [技术栈](#技术栈)
- [部署流程](#部署流程)
- [初始账号与首次登录](#初始账号与首次登录)
- [设置说明](#设置说明)
- [使用方法](#使用方法)
- [常见问题](#常见问题)
- [相关文档](#相关文档)

---

## 功能概览

### 工单全生命周期

```
创建 → 分配/接单 → 处理中 → 解决 → 完结 → 关闭
```

支持批量操作、状态回滚、协作邀请、电话协助快速结单。

### 核心模块

| 模块 | 说明 |
|------|------|
| 工单管理 | 创建、分配、接单、处理、解决、完结、关闭；支持批量操作、状态回滚、彻底删除 |
| 自助报修 | 普通用户通过简化表单 `http://域名/report` 快速报修 |
| 协作处理 | 工程师可邀请他人协作，支持邀请接受 / 拒绝 / 取消 |
| 故障处理记录单 | 现场填写记录单 + 手写签名，生成 HTML 附件存档，支持 A4 打印 |
| 回访管理 | 工单解决后发起满意度回访 |
| 报修人短信 | 受理通知 + 满意度调查（独立开关，报修人回复自动回写评价） |
| 统计报表 | 分类/来源分布、矩形树图、百分比堆积柱形图、趋势对比，支持自定义周期 |
| 多项目地址 | 支持多个项目/物业地址（可跨省市），每项目独立维护区域 / 楼栋 / 房间 |
| 地址管理 | 内置行政区划库（省/市/区/街道），支持 CSV 批量导入 |
| 工单分类 | 大类 → 子类层级树管理，支持启停、排序 |
| 工单模板 | 预设模板快速创建常见工单 |
| 部门管理 | 组织架构维护 |
| 通知中心 | 站内通知、系统公告、批量操作 |
| 数据备份 | Web 界面一键备份 / 恢复，支持每日自动备份 |
| 主题切换 | 浅色 / 深色 / 跟随系统 |
| PWA | Service Worker 离线缓存、添加到主屏幕 |

### 多通道通知

| 通道 | 模式 | 特性 |
|------|------|------|
| 企业微信 | 群机器人 / 自建应用 | 工单创建 @所有人，分配 / 超时 @指定工程师 |
| 钉钉 | 自定义机器人 / 工作通知 | @ 逻辑与企业微信一致 |
| 飞书 | 自定义机器人 / 自建应用 | @ 逻辑与上述一致 |
| 短信 | 阿里云 / 腾讯云 / 自定义网关 | 按事件 × 通道规则矩阵控制 |
| 站内通知 | — | 系统内置，无需外部服务 |

### 用户角色与权限

| 角色 | 职责 | 可见菜单 |
|------|------|----------|
| **管理员** `admin` | 全局管理 | 全部功能 + 系统设置 + 用户管理 |
| **工单管理员** `workorder_manager` | 工单调度与基础数据 | 工单全流程 + 地址/分类/部门/模板管理 + 报表（不含系统设置） |
| **工程师** `engineer` | 现场处理 | 工单相关功能 |
| **普通用户** `user` | 障碍报修 | 仅 `http://域名/report` 报修页面 |

---

## 技术栈

- **后端**：Laravel 12 + PHP 8.2+ + PostgreSQL 14+
- **前端**：Tailwind CSS 4 + Vite + Blade + ECharts
- **认证**：本地认证 + CAS 3.0 + OIDC/OAuth2 + 微信公众号 OAuth

---

## 部署流程

### 环境要求

| 依赖 | 版本 |
|------|------|
| 操作系统 | Ubuntu 20.04 / 22.04 / 24.04（其它 Linux 亦可，需自行调整） |
| PHP | >= 8.2（需 `pdo_pgsql`、`gd`、`mbstring`、`xml`、`curl`、`zip`、`bcmath` 扩展） |
| PostgreSQL | >= 14（**需预先手动安装**，脚本只做检查不自动装库） |
| Composer | >= 2.x |
| Node.js | >= 20.19（vite 7 要求，脚本会自动升级到 22 LTS） |

### 方式一：一键部署脚本（推荐）

仓库内提供 `deploy/deploy_ubuntu_pg.sh`，会自动完成：系统更新 → 依赖版本检查（过旧自动升级）→ 安装 PHP/Node/Composer/Nginx → 统一时区 → 建库建用户 → 克隆代码 → 安装依赖 + 构建前端 → 空库初始化（migrate + seed）→ 配置 Nginx 站点。脚本幂等，可重复执行。

```bash
# 1. 获取脚本（或克隆仓库后进入 deploy 目录）
cd deploy

# 2. 修改脚本头部变量（按需）
#    APP_DIR      代码目录，默认 /var/www/workorder
#    DB_NAME      数据库名，默认 workorder_db
#    DB_USER      数据库用户，默认 workorder
#    DB_PASSWORD  数据库密码（通过环境变量 DB_PASSWORD 指定；未设置时脚本自动生成随机密码）
#    APP_URL      访问地址，默认 http://192.168.1.4
#    GIT_REPO     代码仓库地址

# 3. 执行部署（需要 sudo 权限）
bash deploy_ubuntu_pg.sh
```

> 部署前请确认 PostgreSQL >= 14 已手动安装并可通过 `psql` 命令访问，否则脚本会提示并退出。

### 方式二：手动部署

```bash
# 1. 克隆代码
git clone https://github.com/hicool-ml/workorder-system-public.git
cd workorder-system-public

# 2. 安装依赖
composer install --no-dev --optimize-autoloader
npm install && npm run build

# 3. 环境配置
cp .env.example .env
php artisan key:generate
# 编辑 .env，配置 DB_*（pgsql）与 APP_URL 等

# 4. 数据库初始化
php artisan migrate --force
php artisan db:seed --force

# 5. 缓存与启动
php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan serve --host=0.0.0.0 --port=8000
```

> 生产环境建议用 Nginx + PHP-FPM 反向代理，并参考一键脚本中的站点配置。

### 方式三：Docker 部署

```bash
export DB_PASSWORD=your_strong_password
docker compose up -d --build

docker compose exec app php artisan migrate --force
docker compose exec app php artisan db:seed --force
docker compose exec app php artisan key:generate
```

镜像内置 Nginx + PHP-FPM + Queue Worker（Supervisor），无需额外进程守护。定时备份需在宿主机添加 Cron：

```bash
* * * * * cd /var/www/html && php artisan schedule:run >> /dev/null 2>&1
```

### 部署后复位

| 脚本 | 作用 | 说明 |
|------|------|------|
| `deploy/reset.sh` | 重新初始化数据 | 拉取最新代码 → `migrate:fresh --seed`（**清空并重建数据库**） |
| `deploy/reset_all.sh` | 服务器整体复位 | 删除代码目录 + 数据库 + Nginx 站点，回到「软件已装、无代码无数据」状态，便于重新执行一键部署 |

> `reset_all.sh` 会删除数据库和代码，**生产环境严禁使用**。

---

## 初始账号与首次登录

全新部署（`db:seed`）只会创建一个 **管理员** 账号，其它角色由管理员登录后手动添加：

- **用户名**：`admin`
- **邮箱**：`admin@workorder.com`
- **密码**：seed 时随机生成，仅在控制台输出一次；也可在 `.env` 用 `SEED_ADMIN_PASSWORD` 预先指定

首次登录后系统会**强制修改密码**，请妥善保存新密码。

---

## 设置说明

所有设置保存在 `system_settings` 表中（键值对模型），修改后立即生效，无需重启服务。设置菜单仅 **管理员** 可见。

### 系统设置（设置 → 系统设置）

| 设置项 | 说明 |
|--------|------|
| 系统名称 | 全站标题、PWA 名称、通知抬头 |
| 系统访问地址 | 企业微信 / 钉钉 / 飞书等通知里的工单链接使用此地址 |
| 会话有效期 | 登录空闲超时（分钟） |
| 注册设置 | 开放注册开关、默认角色、邮箱验证 |
| 地址前缀 | 工单地址展示时的前缀根 |
| 版本管理 | 发布新版本、查看版本历史 |

### 地址管理

1. 进入「地址管理 → 基础地址」，创建项目（从行政区划库选择省/市/区/街道 + 手填门牌号）。
2. 支持创建多个项目（不同物业可跨省市）。
3. 在「地址管理 → 地址树」中为每个项目添加「区域 / 楼栋 / 房间」。
4. 支持 CSV 批量导入地址（模板可在页面下载）。

### 统一身份认证

| 协议 | 配置位置 | 说明 |
|------|----------|------|
| CAS / LinkID | 设置 → CAS 认证 | CAS 3.0 协议，支持用户属性映射 |
| OIDC / OAuth2 | 设置 → OIDC 认证 | 支持 Discovery 自动发现、PKCE、id_token 校验 |
| 微信公众号 | 设置 → 微信登录 | OAuth2，openid 绑定，免密登录 |

### 通知通道配置

在「设置 → 消息设置」中配置各通道，并按「事件 × 通道」规则矩阵开启。详见 [通知配置指南](docs/NOTIFICATION_GUIDE.md)。

| 通道 | 必填参数 |
|------|----------|
| 企业微信（群机器人） | Webhook URL |
| 企业微信（自建应用） | CorpID + Secret + AgentID + 用户 UserID |
| 钉钉（自定义机器人） | Webhook URL + Secret |
| 钉钉（工作通知） | AppKey + AppSecret + AgentID + 用户 userid |
| 飞书（自定义机器人） | Webhook URL + Secret |
| 飞书（自建应用） | App ID + App Secret + 用户 user_id |
| 短信（阿里云） | AccessKey + Secret + 签名 + 模板代码 |
| 短信（腾讯云） | SecretId + SecretKey + SDK AppID + 签名 + 模板代码 |
| 短信（自定义） | API URL + Method + API Key |

> 短信回复回调需要额外配置 `sms_reply_secret`（或 IP 白名单），否则生产环境回调会返回 401。

### 数据备份

备份文件位于 `storage/app/private/backups/`，包含 `database.sql` + `attachments.zip`。

```bash
# 手动备份
php artisan backup:system
```

- 自动备份：每日 02:00，保留最近 30 份（由 schedule 调度，需配置 Cron）。
- Web 界面：设置 → 备份&恢复，支持立即备份、上传、下载、恢复（恢复前会自动备份当前状态以便回滚）。

---

## 使用方法

### 角色与菜单

- **普通用户**：登录后只有「自助报修」入口，填写故障描述 + 分类 + 地址 + 附件即可提交，工单进入待处理池。
- **工程师**：可接单、处理、解决、完结工单，可邀请他人协作、填写故障处理记录单。
- **工单管理员**：在工程师能力基础上，负责工单分配、状态回滚、批量操作、基础数据维护（地址/分类/部门/模板）与报表查看。
- **管理员**：拥有全部权限，另含系统设置、用户管理、数据备份、彻底删除工单等。

### 故障报修（普通用户）

1. 访问 `http://域名/report`。
2. 选择大类 / 二级分类，填写故障描述、区域 / 楼栋 / 门牌号，可上传附件（图片、文档、音视频、压缩包等，单个 ≤ 10MB、最多 5 个）。
3. 提交后进入待处理池，等待工程师接单或管理员分配。

### 工单处理（工程师 / 管理员）

1. **分配/接单**：管理员可把工单分配给指定工程师；工程师可就近接单。
2. **处理中**：开始处理后进入「处理中」，可更新处理记录、上传附件、邀请协作。
3. **解决**：填写解决方案，工单进入「已解决」。
4. **完结**：确认完成后「完结」，可触发报修人满意度调查。
5. **关闭**：管理员可关闭工单。

### 协作处理

工单负责人可邀请其它工程师协作，被邀请人会收到站内通知（及配置的 IM 通道 @ 提醒），可接受或拒绝。接受后协作者可参与工单处理。

### 故障处理记录单（签单）

需签单的工单在「处理中 / 已解决」状态可发起签单，现场填写满意度、意见并手写签名，系统生成可打印的 HTML 处理单并存为附件。

### 数据备份与恢复

见上文 [数据备份](#数据备份)，管理员可在 Web 界面一键操作。

---

## 常见问题

| 问题 | 解决方案 |
|------|----------|
| 登录后强制改密 | 安全策略，首次登录或密码被重置后必须修改 |
| CAS / OIDC 用户无法改密 | 由身份认证服务方管理，此为预期行为 |
| 附件预览异常 | 附件走鉴权路由，刷新或清除浏览器缓存后重试 |
| cURL error 60 | 下载 [cacert.pem](https://curl.se/ca/cacert.pem)，在 `php.ini` 配置 `curl.cainfo` |
| 企业微信 60020 | 服务器出口 IP 未加入企业可信 IP 列表 |
| 企业微信 40001 | CorpID 或 Secret 错误，检查凭证 |
| 报表加载慢 | 确认已执行 `php artisan migrate`（含查询索引） |
| Queue 不工作 | Docker 由 Supervisor 管理；手动部署需运行 `php artisan queue:work` |
| 域名访问部分页面“Failed to fetch” | 内网 HTTP + 外部域名 HTTPS 混合内容导致，确认 `TRUSTED_PROXIES` 已配置（或使用部署脚本默认配置） |

---

## 部署配置参考

| 配置项 | 说明 |
|--------|------|
| `TRUSTED_PROXIES` | 经反向代理 / Cloudflare 隧道部署时填写代理网段（逗号分隔 CIDR/IP），否则日志 IP 与 IP 白名单功能不可用 |
| `NOTIFY_QUEUE` | `true` 时工单通知改异步队列发送，需运行 `queue:work`；`false`（默认）为同步发送 |
| `SESSION_SECURE_COOKIE` | 部署脚本会按 `APP_URL` 的 http/https 自动设置，避免 http 下登录掉线 |

---

## 相关文档

| 文档 | 内容 |
|------|------|
| [系统设置文档](docs/settings/README.md) | 各设置子页的逐页说明 |
| [备份 & 恢复](docs/settings/04-backup-restore.md) | Web 备份恢复操作 |
| [通知配置指南](docs/NOTIFICATION_GUIDE.md) | 短信 / 企业微信 / 钉钉 / 飞书的接入步骤 |
| [OIDC 配置](docs/settings/08-oidc.md) | OIDC / OAuth2 统一身份认证 |
| [微信登录](docs/settings/09-wechat-oauth.md) | 微信公众号 OAuth2 |

---

## License

MIT License

## 联系方式

- **邮箱**：[hicool.ml@gmail.com](mailto:hicool.ml@gmail.com)
