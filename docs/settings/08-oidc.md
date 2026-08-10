# 统一身份认证（OIDC / OAuth2）

> 路径：侧边栏 → **设置** → **统一身份认证** → OIDC 认证
> 路由：`system-settings/oidc`（仅管理员）

接入支持 OpenID Connect / OAuth2 标准协议的 IAM 平台（泛微令信通、派拉、宁盾、阿里云 IDaaS、TOPIAM 等）。启用后，登录页会出现「统一身份认证」入口，用户点击后跳转到 IAM 平台完成认证，认证成功后自动回跳并在本系统创建账号。OIDC 账号与本地、CAS 账号共存。

## 协议特性

- **Authorization Code Flow + PKCE (S256)**：符合 OAuth 2.1 安全要求，公开客户端（无 Secret）同样安全
- **OIDC Discovery**：填写 Issuer URL 后自动发现授权、Token、UserInfo、End Session 端点及 `jwks_uri`
- **Confidential / Public Client**：配置了 Client Secret 走机密客户端，留空则纯 PKCE
- **标准声明自动映射**：`sub` / `preferred_username` / `name` / `email` / `phone_number` 等
- **id_token 安全校验**：验证 nonce（防重放）、`exp`（过期）、`aud`（须包含本应用 Client ID）、`iss`（须与配置 Issuer 一致）；Discovery 提供 `jwks_uri` 时执行 RSA (RS256) 签名验证，失败即拒绝

## 配置项

| 字段 | 设置键 | 说明 |
|------|--------|------|
| 认证开关 | `oidc_enabled` | 启用后登录页显示「统一身份认证」入口 |
| Issuer URL | `oidc_issuer` | IAM 平台标识，如 `https://iam.example.com`；填写后自动 Discovery，无需手动填端点 |
| Client ID | `oidc_client_id` | 在 IAM 平台注册本系统后获得的客户端 ID |
| Client Secret | `oidc_client_secret` | 机密客户端填写；公开客户端留空（走 PKCE） |
| Scope | `oidc_scope` | 默认 `openid profile email`，部分平台需追加 `phone`、`department` |
| Authorization Endpoint | `oidc_authorize_endpoint` | 手动模式；Discovery 可用时留空 |
| Token Endpoint | `oidc_token_endpoint` | 手动模式；Discovery 可用时留空 |
| UserInfo Endpoint | `oidc_userinfo_endpoint` | 手动模式；Discovery 可用时留空 |
| End Session Endpoint | `oidc_end_session_endpoint` | 单点登出地址，留空则仅登出本系统 |

## 接入步骤

1. 在 IAM 平台注册本系统为 OAuth2 Client
2. 将回调地址设置为：`http(s)://你的域名/oidc/callback`（即 `route('oidc.callback')`）
3. 在 IAM 平台客户端管理页获取 Issuer URL / Client ID / Client Secret
4. 平台支持 Discovery 时仅需填写 Issuer + Client ID + Client Secret；否则手动填写各端点地址
5. 勾选启用并保存（保存时会自动清除 Discovery 缓存）
6. 登录页出现「统一身份认证」按钮，用户点击后跳转至 IAM 平台完成认证

## OIDC 用户行为约定

- 自动创建为本系统的**普通用户**角色（只能通过 `/report` 报修），与本地账号共存
- OIDC 用户的个人信息和密码由 IAM 平台管理，**无法在本系统内修改**
- 首次登录自动建号（以 `oidc_sub` 为唯一标识），之后每次登录按返回声明更新本地记录
- 已禁用的本地账号无法通过 OIDC 登录（登录前校验用户状态）

## 关联设置键

`oidc_enabled`、`oidc_issuer`、`oidc_client_id`、`oidc_client_secret`、`oidc_scope`、`oidc_authorize_endpoint`、`oidc_token_endpoint`、`oidc_userinfo_endpoint`、`oidc_end_session_endpoint`。

> 配置变更后系统会自动清除 `oidc_discovery` 缓存并重新发现，无需手动操作。
