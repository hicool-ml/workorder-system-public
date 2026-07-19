# 统一身份认证（CAS）

> 路径：侧边栏 → **设置** → **统一身份认证**
> 路由：`system-settings/cas`（仅管理员）

接入学校的 CAS / LinkID 统一身份认证。启用后，登录页会出现「统一身份认证」入口，用户点击后跳转到学校认证页面，认证成功后自动回跳并在本系统创建账号。CAS 账号与本地账号共存。

## 配置项

### 认证开关

顶部勾选框，控制是否启用 CAS。启用后登录页才显示认证入口。

### 服务地址

| 字段 | 设置键 | 说明 |
|------|--------|------|
| **CAS Base URL** | `cas_base_url` | CAS 服务根地址，如 `https://sourceid.ruishan.cc/linkid` |
| **Service ID** | `cas_service_id` | 可选，在 LinkID 平台注册 Service Provider 后获得 |

### 用户属性映射

LinkID 认证成功后会返回一组用户属性，这里配置每个本系统字段对应 CAS 返回的哪个属性名。默认值适用于大多数 CAS 3.0 实现：

| 本系统字段 | 设置键 | 默认属性名 | 必填 |
|-----------|--------|-----------|------|
| 工号/学号（登录标识） | `cas_attr_username` | `uid` | 是 |
| 姓名 | `cas_attr_name` | `cn` | 是 |
| 手机号 | `cas_attr_phone` | `mobile` | 否 |
| 邮箱 | `cas_attr_email` | `mail` | 否 |
| 部门 | `cas_attr_department` | `department` | 否 |

工号/学号作为用户唯一标识，用于匹配或创建本系统用户。

## 接入步骤

1. 在 LinkID 平台注册本系统为 Service Provider
2. 把回调地址设置为系统的 CAS 回调路由：`http(s)://你的域名/cas/callback`
3. 在本页填写 Base URL 和 Service ID
4. 配置好用户属性映射
5. 勾选启用并保存
6. 登录页出现「统一身份认证」按钮，用户点击即跳转到学校认证页面

## CAS 用户行为约定

- 自动创建为本系统的**普通用户**角色（只能通过 `/report` 报修）
- CAS 用户的个人信息和密码由学校统一身份认证管理，**无法在本系统内修改**（个人资料页会提示）
- 首次登录自动建号，之后每次登录按 CAS 返回属性更新本地记录

## 关联设置键

`cas_enabled`、`cas_base_url`、`cas_service_id`、`cas_attr_username`、`cas_attr_name`、`cas_attr_phone`、`cas_attr_email`、`cas_attr_department`。

## 也可用 .env 配置

除本页外，CAS 基础配置也可在 `.env` 里预设（页面保存会覆盖到 `system_settings` 表，优先级高于 `.env`）：

```env
CAS_ENABLED=true
CAS_BASE_URL=https://linkid.example.com/cas
CAS_SERVICE_ID=workorder
```

属性映射的更细粒度配置在 `config/services.php` 的 `cas` 节。
