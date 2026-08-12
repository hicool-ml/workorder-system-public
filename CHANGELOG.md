# 变更日志 (CHANGELOG)

本项目所有重要变更均会记录在此文件中。

格式基于 [Keep a Changelog](https://keepachangelog.com/zh-CN/1.1.0/)，
版本号遵循 [语义化版本](https://semver.org/lang/zh-CN/)。

---

## [未发布]

### 新增
- **微信登录（公众号 OAuth2）**：普通微信直接认证，支持 `snsapi_base` 静默 / `snsapi_userinfo` 授权两种 Scope；`users` 表新增 `wechat_openid`（按 AppID 唯一）；首次使用需一次性绑定系统账号，之后免密登录；独立配置页（`system-settings/wechat-oauth`），回调地址 `wechat/callback`
- **会话有效期设置**：系统设置新增「登录会话有效期（分钟）」参数（`session_lifetime`，默认 120），`ApplySessionLifetime` 中间件在 StartSession 之前动态应用，保存后立即生效；用于缓解微信内置浏览器清 Cookie 导致的频繁掉线，可调大至如 30 天
- **钉钉通知通道**：自定义机器人（Webhook + 加签）/ 工作通知（企业内部应用）双模式，@ 逻辑与企业微信一致（userid 优先，手机号兜底），独立配置页与通知规则矩阵，支持测试发送
- **飞书通知通道**：自定义机器人（Webhook + 签名校验）/ 自建应用双模式，@ 按 user_id/open_id，独立配置页与通知规则矩阵，支持测试发送
- **OIDC 统一身份认证**：OpenID Connect（OAuth2 Authorization Code + PKCE）接入，支持 OIDC Discovery 自动发现端点、state/nonce 校验、`id_token` 的 exp/aud/iss 校验与 JWKS RSA 签名验证（仅 RS256），与本地/CAS 认证共存，独立配置页；`users` 表新增 `oidc_sub` 唯一用户标识字段
- **用户字段扩展**：用户表新增 `dingtalk_userid`、`feishu_user_id` 字段，用于 IM 通道 @ 提醒（用户管理编辑页可填写）
- **地址树结构**：`locations` 表新增 `parent_id` / `level_id` 自引用树（`location_levels` 定义层级），`workorders` 新增 `location_id` 关联地址树节点；`Location` 模型提供 `full_address` / `full_address_delimited` 祖先链拼地址

### 变更
- **数据库迁移至 PostgreSQL 16**：由 MySQL 8 整体迁移（26 张表、序列、索引、外键），一键转换脚本见 `scripts/convert_mysql_dump_to_pgsql.py`，步骤详见 [README MySQL → PostgreSQL 迁移](README.md)；docker-compose 数据库镜像切换为 `postgres:16-alpine`，`.env.example` 默认 `pgsql`
- **备份 & 恢复支持 PostgreSQL**：`backup:system` 与 Web 恢复流程优先使用 `pg_dump` / `psql`，不可用时回退纯 PHP 导出/导入；新增 `pg_bin_path()` 辅助函数自动探测 PostgreSQL 二进制目录并匹配版本

### 修复
- **IM 通道创建事件 @全体静默失效**：`DingTalkService` / `FeishuService` 的 `sendText` 补齐 `isAtAll` 参数透传，工单创建时 @所有人恢复正常
- **消息设置入口缺失**：消息设置页补充钉钉、飞书配置入口卡片，短信入口增加启用状态显示
- **通知规则页面残留代码**：清理重复的加载占位行与 catch 块死代码
- **工单地址访问器报错**：`Workorder` 模型 `location` 字符串列与 `location()` 关系同名导致 Eloquent 返回列值，`campus_name` / `building_name` 访问器改为按 `location_id` 判定关系，旧数据回退遗留 `campus` / `building` 字段
- **分类列表页 PG 兼容**：`WorkorderCategoryController` 查询顶级分类时对布尔列 `status` 传字符串 `'active'`，PostgreSQL 报「invalid input syntax for type boolean」，改为 `true`（MySQL 下静默返回空集的隐患一并消除）
- **备份下载文件损坏**：部分 PHP 源文件带 UTF-8 BOM（`EF BB BF`），PHP 将其作为字面输出注入每个 HTTP 响应体开头，导致下载的备份 zip 无法解压；已剥离全部 BOM 并清理编译视图缓存
- **工单列表页 500**：`locationInfo` 关系把文本楼名（如「A栋」）当作 `locations.id` 查询，PostgreSQL 报「invalid input syntax for type bigint」；访问器加数字守卫、列表分页改为手动批量解析地址、搜索改用数字 id 匹配
- **禁用账号仍可登录**：本地登录 / CAS / OIDC 未校验用户状态，禁用用户在 SSO 登录时还会被 `status='active'` 强制复活；已统一在三种登录入口拦截，且 SSO 更新用户资料不再重置禁用状态
- **`users/engineers` 路由 404**：静态路由注册在 `Route::resource('users')` 之后被 `users/{user}` 遮蔽；已提前注册
- **非法工单 URL 触发 PG 500**：`workorders/{workorder}` 未约束参数类型，`/workorders/report` 等文本段触发 bigint 崩溃；`Route::resource` 增加 `->whereNumber('workorder')` 改为 404
- **报表导出 N+1 与崩溃**：导出逐行查询回访/父分类/校区/楼栋，且文本楼名触发 PG 类型错误；已 eager load 相关关系并预取地址映射
- **报表分类分布硬编码 id**：网络/多媒体子分类分布直接写死根分类 id 1/2，改为按分类名解析
- **地址树丢失区域归属**：新建/编辑地址未记录 `campus_id`，地址级联选择与校区统计失效；已沿父链向上继承 campus_id
- **批量解决空值崩溃**：`batchResolve` 对不存在的工单先访问 `canBeOperatedBy` 再判空，已调整判空顺序
- **编辑页 JS 注入**：`edit.blade.php` 将楼栋值直接拼入 `<script>`，文本楼名导致 JS 语法错误；已改为仅数字时输出
- **工单编辑日志误判**：「创建时间已修改」标记在 `update()` 之后才比较，恒不命中；已改为更新前取值并按 Carbon 解析比较
- **事务内提前返回未回滚**：`store()` 在「选择其他部门未填原因」「无电话协助权限」分支 `return back()` 前未 `DB::rollBack()`，已补回滚
- **版本历史前导点**：`version_notes_` 前缀替换残留首字符点号，改用 `Str::after` 剥离前缀
- **SSO 手机号归一化边界**：`86` 前缀剥离条件 `strlen > 11` 会误判 13 位号；改为恰好 13 位才剥离
- **附件预览死路由**：`attachments.preview.version` 指向不存在的方法，已删除

### 安全
- **强制修改默认密码**：首次登录或管理员重置密码后，用户必须修改密码才能使用系统（`ForcePasswordChange` 中间件 + `password_changed_at` 字段）
- **接单/协作邀请乐观锁**：通过 `WHERE status='pending'` 条件更新 + 影响行数校验，杜绝并发重复接单的竞态条件
- **OIDC id_token 校验**：验证 nonce（防重放）、`exp`（过期）、`aud`（必须包含本应用 client_id）、`iss`（与配置 issuer 一致）；`jwks_uri` 可用时执行 RSA 签名验证，失败即拒绝
- **通知规则接口加权限**：`api/notification-rules` 及短信/IM 测试、证书管理等接口补充 `role:admin` 中间件

### 性能
- **报表查询索引优化**：`workorders` 表新增 5 个复合索引（`status+created_at`、`campus_id+created_at`、`category_id+created_at`、`is_emergency+created_at`、`expected_complete_at+status`），经 EXPLAIN 验证已被优化器采用
- **报表导出去 N+1**：导出改为单次 eager load 回访/父分类/校区关系 + 地址名称预取映射，替代逐行查询

### 新增
- **系统备份脚本**：`php artisan backup:system` 命令，支持数据库（MySQL 用 `mysqldump`、PostgreSQL 用 `pg_dump`，均提供纯 PHP 回退）+ 附件 zip 打包，自动清理旧备份，已注册每日 02:00 调度

## [1.3.0] - 2026-07-15

### 安全
- **附件授权漏洞修复**：`download`/`preview`/`destroy` 统一走 `canViewWorkorder`，覆盖工单管理员和协作工程师（此前遗漏导致 403 误拦截）
- **`canManageWorkorderAttachments` 工单级权限**：从纯角色检查改为工单级授权（防止工程师越权操作非本人工单附件）
- **CAS 安全加固**：校验 CAS 返回的用户标识非空（拒绝无唯一标识的登录）、登录时重新生成会话令牌（防会话固定攻击）
- **短信内容脱敏**：短信不再包含工单标题（可能含用户故障描述），仅保留编号；移除 SMS 模板 title 参数
- **默认账号安全警告**：README 默认账号表添加安全警告，生产环境必须修改

### 变更
- **角色权限修正**：`canCreateWorkorders` 恢复工单管理员；普通用户（含 CAS）仅能通过 `/report` 报修
- **CAS 用户禁止修改个人信息**：个人资料页对 CAS 用户显示只读提示，后端路由加防护
- **.env.example 默认 MySQL**：与 README 部署说明一致（此前默认 SQLite 导致不一致）

### 新增
- **Docker 部署**：多阶段 Dockerfile + docker-compose（Nginx + PHP-FPM + Queue Worker + MySQL 一体化）
- **`composer setup:prod`**：生产环境部署脚本，迁移前自动备份
- **README 新增章节**：Docker 部署、生产环境优化、备份与恢复、常见问题排查、监控建议、性能基准参考

---
## [1.2.0] - 2026-07-15

### 新增
- CAS 统一身份认证（LinkID）集成：用户通过统一身份认证自助申报故障，形成"工单池"供工程师就近接单
- 短信通知接口架构：通用短信通道，后台可按通知类型开启/关闭
- PWA 渐进式 Web 应用：离线缓存、可安装、轻量化移动端体验
- 工单池模式：未分配的普通工单与 CAS 工单统一进入公开池，工程师可自行选择就近处理
- 通知规则矩阵（事件 × 通道）后台管理
- SMS / CAS 系统设置入口（仅管理员可操作）

### 修复
- 修复 `SystemSettingController` ParseError（语法错误、`Auth` 类未导入）
- 附件预览 BOM 损坏问题
- 通知中心批量操作按钮上移至筛选区，提升操作效率
- CAS 工单通知格式与常规工单统一

---

## [1.1.0] - 2026-07-14

### 新增
- **故障处理记录单**（A4 打印格式）：含故障单号、报障日期、报障人、联系方式、地址、处理人、处理日期、故障现象、解决方案、处理结果、用户满意度（满意/一般/不满意/其它）、回访情况、意见和建议、用户签字
- 独立签名页面：全屏横向签名画布，右侧纵向操作按钮（清除/确认提交）
- 需签单工单完整闭环：用户填写记录单并签字后方可结束工单，记录单作为工单附件（HTML，预留 PDF）保存
- 签名记录单支持回访情况记录

### 修复
- 签名页 UI 重写：卡片式表单、表单项与填写项视觉区分（字体/下划线）、签名按钮触发全屏画布
- 签名图片横向压缩问题
- CSS 输出修复
- 工单删除附件功能
- 工单分类管理：列表页一键启用/停用开关 + 数据清理
- 地址管理：校区管理设为默认页面

---

## [1.0.0] - 2026-07-13

### 统计报表
- 故障类型分布改为**矩形树图**（面积映射数值、空间填充布局、纵向排列、4:3 宽高比）
- 故障类型占比按周期生成**百分比堆积柱形图**：标注数量与百分比，支持自定义起始日期 + 周期数，自动溢出保护
- 多周期汇总百分比堆积柱形图（网络-多媒体-专项-其它优先级排序）
- 工单量趋势图（原"工单趋势"），"其它"调整至最右
- 所有图表统一以顶部"起始日期 + 周期"筛选条件驱动

### 工单管理
- 创建工单表单优化：三个优先级改为一行、移除冗余说明文字（签单/附件/预约时间）、时间控件对齐
- 指定接单工程师改为单行
- 修复分配图标点击无效、`canAcceptWorkorders` 方法缺失（BadMethodCallException）

### 文档与维护
- 重写 README.md（版权声明：项目维护者 hicool，邮箱 hicool.ml@gmail.com）
- 清理调试文件、过期文档和测试代码
- 首次推送至 GitHub（hicool-ml/workorder-system）

---

## 早期版本 (2026-07 之前)

### 统计与导出
- 导出 CSV：处理时长改为 hh:mm 格式、备件耗材使用简化、修复 Windows 不兼容的 stream_filter_append
- 工单分类分布统计改为递归收集所有层级子分类
- 完善统计报表：趋势图/来源分布/优先级分布/完成率 + 消除 N+1 查询

### 工单列表与筛选
- 分类筛选改为层级级联（工单大类 → 故障分类）
- 修复筛选逻辑（重复 show_closed 块、orWhere 破坏 AND 链、orderBy 丢失）
- 搜索栏移动端默认收起 + 日期范围快捷按钮 + 高级筛选修复

### 附件与移动端
- 手机端拍照上传附件（capture 属性 + getUserMedia API 双方案）
- PC 端拍照弹窗实时预览 + 截图
- 非图片附件显示文件格式彩色图标（PDF/Word/Excel/TXT）
- 附件预览缓存破坏参数 + no-cache 响应头
- 移动端工单列表卡片间距与边框优化

---

## 变更类型说明

- **新增 (Added)**：新增的功能
- **变更 (Changed)**：对已有功能的修改
- **弃用 (Deprecated)**：即将移除的功能
- **移除 (Removed)**：已移除的功能
- **修复 (Fixed)**：任何 Bug 修复
- **安全 (Security)**：安全相关修复
- **性能 (Performance)**：性能优化
