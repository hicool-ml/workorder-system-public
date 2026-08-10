# 系统设置文档

「设置」是侧边栏的折叠主菜单，仅**管理员**可见。它把原来臃肿的单页设置拆成了职责清晰的子页，每个子页只负责一类配置。

所有子页都挂载在 `settings/{section}` 路由下（统一身份认证除外，它们在 `system-settings/cas`、`system-settings/oidc`、`system-settings/wechat-oauth`），入口为侧边栏 → **设置** → 对应子项。

## 子菜单一览

| 子菜单 | 路由 | 文档 | 职责 |
|--------|------|------|------|
| 注册设置 | `settings/registration` | [注册设置](01-registration.md) | 开放注册开关、默认角色、邮箱验证 |
| 系统设置 | `settings/system` | [系统设置](02-system.md) | 系统名称、版本号、发布日期、访问地址、会话有效期 |
| 版本管理 | `settings/version` | [版本管理](03-version.md) | 当前版本展示、发布新版本、版本历史 |
| 备份 & 恢复 | `settings/backup` | [备份 & 恢复](04-backup-restore.md) | 手动/自动备份、上传、下载、恢复 |
| 消息设置 | `settings/messaging` | [消息设置](05-messaging.md) | 通知规则、短信、企业微信、钉钉、飞书入口 |
| 详细设置 | `settings/all` | [详细设置](06-advanced-settings.md) | 全部 `system_settings` 键值表的查看与编辑 |
| 统一身份认证 | `system-settings/cas` | [统一身份认证](07-cas.md) | CAS / LinkID 接入与用户属性映射 |
| 统一身份认证（OIDC） | `system-settings/oidc` | [OIDC 统一身份认证](08-oidc.md) | OIDC / OAuth2 接入、Discovery、PKCE 与 id_token 校验 |
| 微信登录 | `system-settings/wechat-oauth` | [微信公众号登录](09-wechat-oauth.md) | 公众号 OAuth2、openid 绑定、免密登录 |

## 配置存储

除备份产生的文件外，所有设置都保存在 `system_settings` 表中（键值对模型 `App\Models\SystemSetting`）。每个设置项带 `type`（`string` / `boolean` / `integer` 等）、`description`、`is_public` 等字段。「详细设置」页就是这张表的直接视图。

修改任意设置后立即生效，无需重启服务。

## 权限

整个「设置」菜单及其所有子页、备份操作、CAS/短信/企业微信配置，仅 **管理员（`admin`）** 角色可访问。其他角色在侧边栏看不到该菜单。

## 相关文档

- [通知配置指南](../NOTIFICATION_GUIDE.md) — 短信、企业微信、钉钉、飞书的完整配置步骤与排错
- [项目 README](../../README.md) — 安装部署、角色权限、整体功能概览
