# 系统设置文档

「设置」是侧边栏的折叠主菜单，仅**管理员**可见。它把设置按职能拆分成子页，每个子页只负责一类配置。

## 子菜单一览

| 子菜单 | 路由 | 文档 | 职责 |
|--------|------|------|------|
| 系统设置 | `settings/system` | [系统设置](02-system.md) | 系统名称、访问地址、会话有效期、注册设置、地址前缀、版本管理 |
| 备份 & 恢复 | `settings/backup` | [备份 & 恢复](04-backup-restore.md) | 手动/自动备份、上传、下载、恢复 |
| 消息设置 | `settings/messaging` | [消息设置](05-messaging.md) | 通知规则、短信、企业微信、钉钉、飞书入口 |
| 详细设置 | `settings/all` | [详细设置](06-advanced-settings.md) | 全部 `system_settings` 键值表的查看与编辑 |
| 统一身份认证 | `system-settings/cas` | [CAS 认证](07-cas.md) | CAS / LinkID 接入 |
| OIDC 认证 | `system-settings/oidc` | [OIDC 认证](08-oidc.md) | OIDC / OAuth2 接入 |
| 微信登录 | `system-settings/wechat-oauth` | [微信登录](09-wechat-oauth.md) | 微信公众号 OAuth2 |

## 配置存储

所有设置保存在 `system_settings` 表中（键值对模型）。修改后立即生效，无需重启服务。

## 权限

整个「设置」菜单及所有子页仅 **管理员** 角色可访问。

## 相关文档

- [通知配置指南](../NOTIFICATION_GUIDE.md) — 短信、企业微信、钉钉、飞书的完整配置步骤
- [项目 README](../../README.md) — 安装部署、角色权限、功能概览
