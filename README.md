# 工单管理系统

基于 Laravel 12 + Tailwind CSS 4 构建的通用工单管理平台。覆盖从故障申报、智能分配、工程师处理到满意度回访的全流程闭环。支持多项目地址管理、多通道通知（企业微信 / 钉钉 / 飞书 / 短信）、统一身份认证接入。适用于企业 IT 服务台、物业报修、设备运维等多种场景。

## 功能概览

### 工单全生命周期

```
创建 → 分配/接单 → 处理中 → 解决 → 完结 → 关闭
```

支持批量操作、回滚状态、协作邀请、电话协助快速结单。

### 核心模块

| 模块 | 说明 |
|------|------|
| 工单管理 | 创建、分配、接单、处理、解决、完结、关闭，支持批量操作和状态回滚 |
| 自助报修 | 普通用户通过简化表单 `http://域名/report` 快速报修 |
| 故障处理记录单 | 现场填写记录单 + 手写签名，生成 HTML 附件存档，支持 A4 打印 |
| 协作处理 | 工程师可邀请他人协作，支持邀请接受/拒绝流程 |
| 回访管理 | 工单解决后发起满意度调查 |
| 报修人短信 | 受理通知 + 满意度调查（独立开关，报修人回复自动回写评价） |
| 统计报表 | 矩形树图、百分比堆积柱形图、趋势对比，支持自定义周期 |
| 多项目地址 | 支持多个项目/物业地址（可跨省市），每项目独立维护区域/楼栋/房间 |
| 地址管理 | 内置行政区划库（省/市/区/街道），支持 CSV 批量导入 |
| 工单模板 | 预设模板快速创建常见工单 |
| 分类管理 | 支持停用/启用、排序、级联选择 |
| 部门管理 | 组织架构维护 |
| 通知中心 | 站内通知、系统公告、批量操作 |
| 数据备份 | Web 界面一键备份/恢复，自动每日备份 |
| PWA | Service Worker 离线缓存、添加到主屏幕 |
| 主题切换 | 浅色 / 深色 / 跟随系统 |

### 多通道通知

| 通道 | 模式 | 特性 |
|------|------|------|
| 企业微信 | 群机器人 / 自建应用 | 工单创建 @所有人，分配/超时 @指定工程师 |
| 钉钉 | 自定义机器人 / 工作通知 | @ 逻辑与企业微信一致 |
| 飞书 | 自定义机器人 / 自建应用 | @ 逻辑与上述一致 |
| 短信 | 阿里云 / 腾讯云 / 自定义网关 | 按事件×通道规则矩阵控制 |
| 站内通知 | — | 系统内置，无需外部服务 |

通知配置详见 [通知配置指南](docs/NOTIFICATION_GUIDE.md)。

### 用户角色与权限

| 角色 | 职责 | 可见菜单 |
|------|------|----------|
| **管理员** `admin` | 全局管理 | 全部功能 + 系统设置 + 用户管理 |
| **工单管理员** `workorder_manager` | 工单调度与基础数据 | 工单全流程 + 地址/分类/部门/模板管理 + 报表（不含系统设置） |
| **工程师** `engineer` | 现场处理 | 工单相关功能 |
| **普通用户** `user` | 障碍报修 | 仅 `http://域名/report` 报修页面 |

## 技术栈

- **后端**：Laravel 12 + PHP 8.2+ + PostgreSQL 14+
- **前端**：Tailwind CSS 4 + Vite + Blade + ECharts
- **认证**：本地认证 + CAS 3.0 + OIDC/OAuth2 + 微信公众号 OAuth

## 安装部署

### 环境要求

- PHP >= 8.2（需 `pdo_pgsql`、`gd`、`mbstring`、`xml`、`curl`、`zip` 扩展）
- PostgreSQL >= 14
- Composer
- Node.js >= 18 + NPM

### 手动部署

```bash
# 1. 克隆 & 安装依赖
git clone https://github.com/hicool-ml/workorder-system-public.git
cd workorder-system-public
composer install --no-dev --optimize-autoloader
npm install && npm run build

# 2. 环境配置
cp .env.example .env
php artisan key:generate
# 编辑 .env 配置数据库连接（DB_CONNECTION=pgsql）

# 3. 数据库初始化
php artisan migrate --force
php artisan db:seed --force

# 4. 存储 & 启动
php artisan storage:link
php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan serve --host=0.0.0.0 --port=8000
```

> 部署后首次运行 `db:seed` 时，控制台会输出随机生成的管理员密码（仅显示一次），请妥善保存。

### Docker 部署

```bash
export DB_PASSWORD=your_strong_password
docker compose up -d --build

# 初始化
docker compose exec app php artisan migrate --force
docker compose exec app php artisan db:seed --force
docker compose exec app php artisan storage:link
docker compose exec app php artisan key:generate
```

镜像内置 Nginx + PHP-FPM + Queue Worker（Supervisor），无需额外进程守护。

定时备份需在宿主机添加 Cron：

```bash
* * * * * cd /var/www/html && php artisan schedule:run >> /dev/null 2>&1
```

## 配置指南

### 首次登录后

1. 用控制台输出的随机密码登录管理员账号
2. 系统会强制要求修改密码
3. 进入「系统设置」完成基础配置

### 系统设置

| 设置项 | 说明 |
|--------|------|
| 系统名称 | 全站标题、PWA 名称、通知抬头 |
| 系统访问地址 | 企业微信通知中的工单链接使用此地址 |
| 会话有效期 | 登录空闲超时（分钟） |
| 注册设置 | 开放注册开关、默认角色、邮箱验证 |
| 版本管理 | 发布新版本、查看版本历史 |

### 地址管理

1. 进入「地址管理 → 基础地址」创建项目（从行政区划库选择省/市/区/街道 + 手填门牌号）
2. 支持创建多个项目（不同物业可跨省市）
3. 在「地址管理 → 地址树」中为每个项目添加区域/楼栋/房间
4. 支持通过 CSV 批量导入地址

### 统一身份认证

| 协议 | 配置位置 | 说明 |
|------|----------|------|
| CAS / LinkID | 系统设置 → CAS 认证 | CAS 3.0 协议，支持用户属性映射 |
| OIDC / OAuth2 | 系统设置 → OIDC 认证 | 支持 Discovery 自动发现、PKCE、id_token 校验 |
| 微信公众号 | 系统设置 → 微信登录 | OAuth2，openid 绑定，免密登录 |

### 通知通道配置

在「系统设置 → 消息设置」中配置各通道。详见 [通知配置指南](docs/NOTIFICATION_GUIDE.md)。

| 通道 | 必填参数 |
|------|----------|
| 企业微信（Webhook） | Webhook URL |
| 企业微信（应用） | CorpID + Secret + AgentID + 用户 UserID |
| 钉钉（Webhook） | Webhook URL + Secret |
| 钉钉（应用） | AppKey + AppSecret + AgentID + 用户 userid |
| 飞书（Webhook） | Webhook URL + Secret |
| 飞书（应用） | App ID + App Secret + 用户 user_id |
| 短信（阿里云） | AccessKey + Secret + 签名 + 模板代码 |
| 短信（腾讯云） | SecretId + SecretKey + SDK AppID + 签名 + 模板代码 |
| 短信（自定义） | API URL + Method + API Key |

> 短信回复回调需要额外配置 `sms_reply_secret`（或 IP 白名单），否则生产环境回调会返回 401。

## 数据备份

```bash
# 手动备份
php artisan backup:system

# 自动备份（每日 02:00，保留最近 30 份）
# 已在 schedule 中注册，需配置 Cron
```

备份文件位于 `storage/app/private/backups/`，包含 `database.sql` + `attachments.zip`。
也支持通过 Web 界面操作（系统设置 → 备份&恢复）。

## 常见问题

| 问题 | 解决方案 |
|------|----------|
| 登录后强制改密 | 安全策略，首次登录或密码被重置后必须修改 |
| CAS/OIDC 用户无法改密 | 由身份认证服务方管理，此为预期行为 |
| 附件预览异常 | 清除浏览器缓存或检查 `php artisan storage:link` |
| cURL error 60 | 下载 [cacert.pem](https://curl.se/ca/cacert.pem)，在 `php.ini` 配置 `curl.cainfo` |
| 企业微信 60020 | 服务器出口 IP 未加入企业可信 IP 列表 |
| 企业微信 40001 | CorpID 或 Secret 错误，检查凭证 |
| 报表加载慢 | 确认已执行 `php artisan migrate`（含查询索引） |
| Queue 不工作 | Docker 由 Supervisor 管理；手动部署需 `php artisan queue:work` |

## 监控

- **日志**：`storage/logs/laravel.log`（建议 `LOG_STACK=daily`）
- **健康检查**：`/up`
- **备份检查**：`storage/app/private/backups/` 下是否有当日备份
- **队列积压**：`php artisan queue:failed`

## 相关文档

| 文档 | 内容 |
|------|------|
| [系统设置文档](docs/settings/README.md) | 各设置子页的逐页说明 |
| [备份 & 恢复](docs/settings/04-backup-restore.md) | Web 备份恢复操作 |
| [通知配置指南](docs/NOTIFICATION_GUIDE.md) | 短信/企业微信/钉钉/飞书的接入步骤 |
| [OIDC 配置](docs/settings/08-oidc.md) | OIDC/OAuth2 统一身份认证 |
| [微信登录](docs/settings/09-wechat-oauth.md) | 微信公众号 OAuth2 |

## License

MIT License

## 联系方式

- **邮箱**：[hicool.ml@gmail.com](mailto:hicool.ml@gmail.com)
