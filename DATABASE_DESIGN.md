# 工单系统数据库设计文档

## 数据库表概览
本项目是一个工单管理系统，包含以下数据表：

## 1. 用户相关表

### users 表（用户表）
| 字段名 | 类型 | 说明 | 索引/约束 |
|--------|------|------|-----------|
| id | bigint | 主键 | PRIMARY |
| name | string | 用户名 |  |
| email | string | 邮箱（唯一） | UNIQUE |
| email_verified_at | timestamp | 邮箱验证时间 | NULLABLE |
| password | string | 密码 |  |
| remember_token | string | 记住令牌 | NULLABLE |
| phone | string | 联系电话 | NULLABLE |
| employee_id | string | 员工编号 | NULLABLE |
| department_id | bigint | 部门ID（外键） | FOREIGN KEY → departments.id |
| role | enum | 角色（admin/engineer/user） | DEFAULT 'user' |
| status | enum | 状态（active/inactive） | DEFAULT 'active' |
| location | string | 办公地点 | NULLABLE |
| remarks | text | 备注 | NULLABLE |
| created_at | timestamp | 创建时间 |  |
| updated_at | timestamp | 更新时间 |  |
| **索引** |  |  | INDEX(role, status) |

### departments 表（部门表）
| 字段名 | 类型 | 说明 | 索引/约束 |
|--------|------|------|-----------|
| id | bigint | 主键 | PRIMARY |
| name | string | 部门名称 |  |
| code | string | 部门编码（唯一） | UNIQUE |
| manager_name | string | 部门负责人 | NULLABLE |
| manager_phone | string | 负责人电话 | NULLABLE |
| location | string | 办公地点（关联locations表） | NULLABLE |
| description | text | 部门描述 | NULLABLE |
| status | enum | 状态（active/inactive） | DEFAULT 'active' |
| sort_order | int | 排序 | DEFAULT 0 |
| created_at | timestamp | 创建时间 |  |
| updated_at | timestamp | 更新时间 |  |
| **索引** |  |  | INDEX(status) |

## 2. 工单核心表

### workorders 表（工单表）
| 字段名 | 类型 | 说明 | 索引/约束 |
|--------|------|------|-----------|
| id | bigint | 主键 | PRIMARY |
| ticket_prefix | string | 工单编号前缀 | DEFAULT 'WO' |
| ticket_no | string | 工单编号 |  |
| title | string | 工单标题 | NULLABLE |
| description | text | 问题描述 |  |
| failure_description | text | 具体故障现象 | NULLABLE |
| type_id | bigint | 工单类型ID（外键） | FOREIGN KEY → workorder_types.id |
| category_id | bigint | 工单分类ID（外键） | FOREIGN KEY → workorder_categories_simplified.id |
| creator_id | bigint | 创建人ID（外键） | FOREIGN KEY → users.id |
| assignee_id | bigint | 处理人ID（外键） | FOREIGN KEY → users.id |
| department_id | bigint | 部门ID（外键） | FOREIGN KEY → departments.id |
| department_name | string | 部门名称 | NULLABLE |
| contact_name | string | 联系人姓名 |  |
| contact_phone | string | 联系电话 |  |
| contact_email | string | 联系邮箱 | NULLABLE |
| location | string | 故障地点 |  |
| location_detail | text | 详细地址 | NULLABLE |
| campus | string | 校区 | NULLABLE |
| building | string | 楼栋 | NULLABLE |
| appointment_time | datetime | 预约时间 | NULLABLE |
| source | enum | 工单来源（phone/web/email/scene/other） | DEFAULT 'web' |
| priority | enum | 优先级（high/medium/low） | DEFAULT 'medium' |
| status | enum | 工单状态（pending/assigned/processing/resolved/completed/verifying/closed/rejected） | DEFAULT 'pending' |
| time_limit_hours | int | 时限（小时） | NULLABLE |
| assigned_at | datetime | 分配时间 | NULLABLE |
| started_at | datetime | 开始处理时间 | NULLABLE |
| resolved_at | datetime | 解决时间 | NULLABLE |
| completed_at | timestamp | 完成时间 | NULLABLE |
| closed_at | datetime | 关闭时间 | NULLABLE |
| expected_complete_at | datetime | 预计完成时间 | NULLABLE |
| processing_duration | int | 实际处理时长（分钟） | NULLABLE |
| solution | text | 解决方案 | NULLABLE |
| remarks | text | 备注 | NULLABLE |
| materials_usage | text | 备件耗材使用情况 | NULLABLE |
| other_reason | text | 其他部门原因 | NULLABLE |
| need_visit | boolean | 是否需要回访 | DEFAULT false |
| is_emergency | boolean | 是否紧急 | DEFAULT false |
| phone_assisted | boolean | 电话协助完成 | DEFAULT false |
| created_at | timestamp | 创建时间 | NULLABLE |
| updated_at | timestamp | 更新时间 |  |
| deleted_at | timestamp | 软删除时间 | NULLABLE |
| **索引** |  |  | INDEX(ticket_no), INDEX(status, priority), INDEX(creator_id, created_at), INDEX(assignee_id, status), INDEX(type_id, status), INDEX(campus), INDEX(building), UNIQUE(ticket_prefix, ticket_no) |

### workorder_types 表（工单类型表）
| 字段名 | 类型 | 说明 | 索引/约束 |
|--------|------|------|-----------|
| id | bigint | 主键 | PRIMARY |
| name | string | 工单类型名称 |  |
| code | string | 类型编码（唯一） | UNIQUE |
| description | text | 类型描述 | NULLABLE |
| icon | string | 图标类名 | NULLABLE |
| color | string | 颜色代码 | NULLABLE |
| source_options | json | 工单来源选项 | NULLABLE |
| default_ticket_prefix | string | 默认工单编号前缀 | DEFAULT 'WO' |
| allow_user_select | boolean | 是否允许用户选择 | DEFAULT true |
| parent_id | bigint | 父级分类ID（外键） | FOREIGN KEY → workorder_types.id |
| level | int | 分类层级 | DEFAULT 1 |
| source | string | 来源渠道 | NULLABLE |
| subcategory | string | 子类别 | NULLABLE |
| default_priority | int | 默认优先级 | DEFAULT 2 |
| default_hours | int | 默认处理时限（小时） | DEFAULT 24 |
| allowed_roles | json | 允许创建此类型工单的角色 | NULLABLE |
| status | enum | 状态（active/inactive） | DEFAULT 'active' |
| sort_order | int | 排序 | DEFAULT 0 |
| created_at | timestamp | 创建时间 |  |
| updated_at | timestamp | 更新时间 |  |
| **索引** |  |  | INDEX(parent_id, status), INDEX(source, status), INDEX(status, sort_order) |

### workorder_categories 表（工单分类表）
| 字段名 | 类型 | 说明 | 索引/约束 |
|--------|------|------|-----------|
| id | bigint | 主键 | PRIMARY |
| name | string | 分类名称 |  |
| code | string | 分类编码（唯一） | UNIQUE |
| description | text | 分类描述 | NULLABLE |
| parent_id | bigint | 父分类ID（外键） | FOREIGN KEY → workorder_categories.id |
| level | int | 层级 | DEFAULT 1 |
| sort_order | int | 排序 | DEFAULT 0 |
| status | enum | 状态（active/inactive） | DEFAULT 'active' |
| created_at | timestamp | 创建时间 |  |
| updated_at | timestamp | 更新时间 |  |
| **索引** |  |  | INDEX(parent_id, level, status) |

### workorder_categories_simplified 表（简化工单分类表）
| 字段名 | 类型 | 说明 | 索引/约束 |
|--------|------|------|-----------|
| id | bigint | 主键 | PRIMARY |
| name | string | 分类名称 |  |
| parent_id | string | 父分类ID | NULLABLE |
| ticket_prefix | string | 工单编号前缀 | DEFAULT 'WO' |
| default_hours | int | 默认处理时限（小时） | DEFAULT 24 |
| color | string | 显示颜色 | DEFAULT '#6c757d' |
| description | text | 分类描述 | NULLABLE |
| sort_order | int | 排序顺序 | DEFAULT 0 |
| status | boolean | 状态 | DEFAULT true |
| created_at | timestamp | 创建时间 |  |
| updated_at | timestamp | 更新时间 |  |
| **索引** |  |  | INDEX(parent_id), INDEX(status), INDEX(sort_order) |

## 3. 工单相关功能表

### workorder_logs 表（工单日志表）
| 字段名 | 类型 | 说明 | 索引/约束 |
|--------|------|------|-----------|
| id | bigint | 主键 | PRIMARY |
| workorder_id | bigint | 工单ID（外键） | FOREIGN KEY → workorders.id |
| user_id | bigint | 操作人ID（外键） | FOREIGN KEY → users.id |
| action | string | 操作类型 |  |
| content | text | 操作内容/备注 | NULLABLE |
| old_value | text | 原值 | NULLABLE |
| new_value | text | 新值 | NULLABLE |
| processing_time | datetime | 处理耗时（分钟） | NULLABLE |
| is_system | boolean | 是否系统自动操作 | DEFAULT false |
| created_at | timestamp | 创建时间 |  |
| updated_at | timestamp | 更新时间 |  |
| **索引** |  |  | INDEX(workorder_id, created_at), INDEX(user_id, created_at), INDEX(action, created_at) |

**操作类型枚举值：**
- created: 创建工单
- assigned: 分配工单
- accepted: 接单
- started: 开始处理
- paused: 暂停处理
- resumed: 恢复处理
- transferred: 转派
- resolved: 已解决
- rejected: 拒绝处理
- closed: 关闭工单
- reopened: 重新打开
- comment: 添加备注

### workorder_attachments 表（工单附件表）
| 字段名 | 类型 | 说明 | 索引/约束 |
|--------|------|------|-----------|
| id | bigint | 主键 | PRIMARY |
| workorder_id | bigint | 工单ID（外键） | FOREIGN KEY → workorders.id |
| user_id | bigint | 上传人ID（外键） | FOREIGN KEY → users.id |
| filename | string | 文件名 |  |
| original_name | string | 原始文件名 |  |
| file_path | string | 文件路径 |  |
| file_type | string | 文件类型 |  |
| file_size | bigint | 文件大小（字节） |  |
| mime_type | string | MIME类型 | NULLABLE |
| description | text | 文件描述 | NULLABLE |
| type | enum | 附件类型（image/document/video/audio/other） | DEFAULT 'other' |
| is_public | boolean | 是否公开 | DEFAULT true |
| created_at | timestamp | 创建时间 |  |
| updated_at | timestamp | 更新时间 |  |
| **索引** |  |  | INDEX(workorder_id, type), INDEX(user_id, created_at) |

### workorder_visits 表（工单回访表）
| 字段名 | 类型 | 说明 | 索引/约束 |
|--------|------|------|-----------|
| id | bigint | 主键 | PRIMARY |
| workorder_id | bigint | 工单ID（外键） | FOREIGN KEY → workorders.id |
| visitor_id | bigint | 回访人ID（外键） | FOREIGN KEY → users.id |
| visit_method | enum | 回访方式（phone/sms/email/online/scene） | DEFAULT 'phone' |
| visit_time | datetime | 回访时间 |  |
| visit_content | text | 回访内容 | NULLABLE |
| feedback | text | 用户反馈 | NULLABLE |
| satisfaction_score | int | 满意度评分（1-5分） | NULLABLE |
| response_speed_score | int | 响应速度评分（1-5分） | NULLABLE |
| service_quality_score | int | 服务质量评分（1-5分） | NULLABLE |
| professional_score | int | 专业水平评分（1-5分） | NULLABLE |
| overall_score | int | 总体满意度评分（1-5分） | NULLABLE |
| suggestions | text | 改进建议 | NULLABLE |
| status | enum | 回访状态（pending/completed/failed/skipped） | DEFAULT 'pending' |
| fail_reason | text | 回访失败原因 | NULLABLE |
| need_follow_up | boolean | 是否需要后续跟进 | DEFAULT false |
| follow_up_note | text | 跟进说明 | NULLABLE |
| created_at | timestamp | 创建时间 |  |
| updated_at | timestamp | 更新时间 |  |
| **索引** |  |  | INDEX(workorder_id), INDEX(visitor_id, visit_time), INDEX(status, visit_time) |

### workorder_collaborations 表（工单协作表）
| 字段名 | 类型 | 说明 | 索引/约束 |
|--------|------|------|-----------|
| id | bigint | 主键 | PRIMARY |
| workorder_id | bigint | 工单ID（外键） | FOREIGN KEY → workorders.id |
| inviter_id | bigint | 邀请人ID（外键） | FOREIGN KEY → users.id |
| collaborator_id | bigint | 协作人ID（外键） | FOREIGN KEY → users.id |
| invitation_reason | text | 邀请原因 | NULLABLE |
| status | enum | 状态（pending/accepted/rejected） | DEFAULT 'pending' |
| accepted_at | timestamp | 接受时间 | NULLABLE |
| response_note | text | 回复备注 | NULLABLE |
| created_at | timestamp | 创建时间 |  |
| updated_at | timestamp | 更新时间 |  |
| **索引** |  |  | INDEX(workorder_id, collaborator_id), INDEX(workorder_id, status) |

### workorder_templates 表（工单模板表）
| 字段名 | 类型 | 说明 | 索引/约束 |
|--------|------|------|-----------|
| id | bigint | 主键 | PRIMARY |
| name | string | 模板名称 |  |
| description | text | 工单描述模板 |  |
| category_id | bigint | 工单分类ID（外键） | FOREIGN KEY → workorder_categories_simplified.id |
| contact_name | string | 联系人姓名 | NULLABLE |
| contact_phone | string | 联系人电话 | NULLABLE |
| contact_email | string | 联系人邮箱 | NULLABLE |
| campus | string | 校区 | NULLABLE |
| building | string | 楼栋 | NULLABLE |
| location_detail | text | 位置详情 | NULLABLE |
| time_limit_hours | int | 时限（小时） | NULLABLE |
| priority | string | 优先级 | DEFAULT 'medium' |
| source | string | 来源 | DEFAULT 'web' |
| department_name | string | 部门名称 | NULLABLE |
| need_visit | boolean | 是否需要回访 | DEFAULT false |
| is_emergency | boolean | 是否紧急 | DEFAULT false |
| phone_assisted | boolean | 电话协助 | DEFAULT false |
| other_reason | text | 其他原因 | NULLABLE |
| is_active | boolean | 是否启用 | DEFAULT true |
| creator_id | bigint | 创建人ID（外键） | FOREIGN KEY → users.id |
| created_at | timestamp | 创建时间 |  |
| updated_at | timestamp | 更新时间 |  |
| **索引** |  |  | INDEX(is_active, name), INDEX(creator_id), INDEX(category_id) |

## 4. 系统功能表

### notifications 表（通知表）
| 字段名 | 类型 | 说明 | 索引/约束 |
|--------|------|------|-----------|
| id | bigint | 主键 | PRIMARY |
| user_id | bigint | 用户ID（外键） | FOREIGN KEY → users.id |
| workorder_id | bigint | 工单ID（外键） | FOREIGN KEY → workorders.id |
| type | string | 通知类型 |  |
| title | string | 通知标题 |  |
| content | text | 通知内容 |  |
| data | json | 额外数据 | NULLABLE |
| is_read | boolean | 是否已读 | DEFAULT false |
| read_at | timestamp | 阅读时间 | NULLABLE |
| is_important | boolean | 是否重要通知 | DEFAULT false |
| created_at | timestamp | 创建时间 |  |
| updated_at | timestamp | 更新时间 |  |
| **索引** |  |  | INDEX(user_id, is_read), INDEX(user_id, type), INDEX(user_id, created_at), INDEX(workorder_id, type) |

### locations 表（位置表）
| 字段名 | 类型 | 说明 | 索引/约束 |
|--------|------|------|-----------|
| id | bigint | 主键 | PRIMARY |
| name | string | 地址名称（简化格式，如"1教"、"11栋"） |  |
| campus | string | 校区（old_campus/new_campus/asean_campus） |  |
| building_type | string | 建筑类型（teaching_building/dormitory/office_building/library/other） |  |
| building_code | string | 楼栋代码（与name保持一致） | NULLABLE |
| description | text | 描述 | NULLABLE |
| sort_order | int | 排序 | DEFAULT 0 |
| status | string | 状态 | DEFAULT 'active' |
| created_at | timestamp | 创建时间 |  |
| updated_at | timestamp | 更新时间 |  |
| **索引** |  |  | INDEX(campus, building_type), INDEX(status) |

**位置数据优化说明：**
- 教学楼名称格式：原"第一教学楼" → "1教"
- 宿舍名称格式：原"第一学生宿舍" → "1栋"
- 汇总地址格式：原"老校区1-7教" → "1-7教"
- 避免重复地址，保留汇总地址和具体地址的合理组合

## 5. Laravel 系统表

### cache 表（缓存表）
| 字段名 | 类型 | 说明 | 索引/约束 |
|--------|------|------|-----------|
| key | string | 缓存键 | PRIMARY |
| value | mediumtext | 缓存值 |  |
| expiration | int | 过期时间 |  |

### cache_locks 表（缓存锁表）
| 字段名 | 类型 | 说明 | 索引/约束 |
|--------|------|------|-----------|
| key | string | 锁键 | PRIMARY |
| owner | string | 锁拥有者 |  |
| expiration | int | 过期时间 |  |

### jobs 表（任务队列表）
| 字段名 | 类型 | 说明 | 索引/约束 |
|--------|------|------|-----------|
| id | bigint | 主键 | PRIMARY |
| queue | string | 队列名 | INDEX |
| payload | longtext | 任务数据 |  |
| attempts | unsignedTinyInt | 尝试次数 |  |
| reserved_at | unsignedInt | 预留时间 | NULLABLE |
| available_at | unsignedInt | 可用时间 |  |
| created_at | unsignedInt | 创建时间 |  |

### job_batches 表（任务批次表）
| 字段名 | 类型 | 说明 | 索引/约束 |
|--------|------|------|-----------|
| id | string | 批次ID | PRIMARY |
| name | string | 批次名称 |  |
| total_jobs | int | 总任务数 |  |
| pending_jobs | int | 待处理任务数 |  |
| failed_jobs | int | 失败任务数 |  |
| failed_job_ids | longtext | 失败任务ID列表 |  |
| options | mediumtext | 选项 | NULLABLE |
| cancelled_at | int | 取消时间 | NULLABLE |
| created_at | int | 创建时间 |  |
| finished_at | int | 完成时间 | NULLABLE |

### failed_jobs 表（失败任务表）
| 字段名 | 类型 | 说明 | 索引/约束 |
|--------|------|------|-----------|
| id | bigint | 主键 | PRIMARY |
| uuid | string | 唯一标识 | UNIQUE |
| connection | text | 连接 |  |
| queue | text | 队列 |  |
| payload | longtext | 任务数据 |  |
| exception | longtext | 异常信息 |  |
| failed_at | timestamp | 失败时间 | DEFAULT CURRENT_TIMESTAMP |

### password_reset_tokens 表（密码重置令牌表）
| 字段名 | 类型 | 说明 | 索引/约束 |
|--------|------|------|-----------|
| email | string | 邮箱 | PRIMARY |
| token | string | 令牌 |  |
| created_at | timestamp | 创建时间 | NULLABLE |

### sessions 表（会话表）
| 字段名 | 类型 | 说明 | 索引/约束 |
|--------|------|------|-----------|
| id | string | 会话ID | PRIMARY |
| user_id | bigint | 用户ID（外键） | INDEX, NULLABLE |
| ip_address | string | IP地址 | NULLABLE |
| user_agent | text | 用户代理 | NULLABLE |
| payload | longtext | 会话数据 |  |
| last_activity | int | 最后活动时间 | INDEX |

## 数据库关系图

```
users (用户表)
├── departments (部门表) - 多对一
├── workorders (工单表) - 一对多 (creator_id, assignee_id)
├── workorder_logs (工单日志表) - 一对多
├── workorder_attachments (工单附件表) - 一对多
├── workorder_visits (工单回访表) - 一对多
├── workorder_collaborations (工单协作表) - 一对多 (inviter_id, collaborator_id)
├── workorder_templates (工单模板表) - 一对多
└── notifications (通知表) - 一对多

departments (部门表)
├── users (用户表) - 一对多
└── workorders (工单表) - 一对多

workorder_types (工单类型表)
├── workorder_types (工单类型表) - 自关联 (parent_id)
└── workorders (工单表) - 一对多

workorder_categories (工单分类表)
├── workorder_categories (工单分类表) - 自关联 (parent_id)
└── workorders (工单表) - 一对多

workorder_categories_simplified (简化工单分类表)
├── workorders (工单表) - 一对多
└── workorder_templates (工单模板表) - 一对多

workorders (工单表)
├── workorder_logs (工单日志表) - 一对多
├── workorder_attachments (工单附件表) - 一对多
├── workorder_visits (工单回访表) - 一对多
├── workorder_collaborations (工单协作表) - 一对多
└── notifications (通知表) - 一对多
```

## 数据库设计特点

### 1. 软删除支持
- `workorders` 表使用 `deleted_at` 字段实现软删除
- 保留历史数据，便于数据分析和审计

### 2. 多级分类支持
- `workorder_types` 表支持多级分类
- `workorder_categories` 表支持多级分类

### 3. 完整的日志记录
- `workorder_logs` 表记录工单的所有操作历史
- 支持操作类型、内容、时间等详细信息

### 4. 灵活的附件管理
- `workorder_attachments` 表支持多种文件类型
- 区分公开和私有附件
- 记录文件大小、类型等元信息

### 5. 满意度评价系统
- `workorder_visits` 表支持多维度评价
- 包括总体满意度、响应速度、服务质量、专业水平等

### 6. 协作功能
- `workorder_collaborations` 表支持多人协作处理工单
- 记录邀请、接受、拒绝等协作状态

### 7. 模板功能
- `workorder_templates` 表支持工单模板
- 提高工单创建效率和标准化程度

### 8. 通知系统
- `notifications` 表支持多种通知类型
- 支持已读状态、重要程度标记

### 9. 位置管理
- `locations` 表支持校区、建筑类型、楼栋等多级位置信息
- 便于工单的地理位置管理

## 性能优化建议

### 1. 索引优化
- 为经常查询的字段添加索引
- 复合索引优化多条件查询
- 定期分析查询性能，优化索引策略

### 2. 分区策略
- 对于大数据量的表（如 `workorder_logs`），可考虑按时间分区
- 提高查询性能和数据维护效率

### 3. 数据归档
- 定期归档历史工单数据
- 保持主表数据量在合理范围内

### 4. 缓存策略
- 对频繁访问的数据使用缓存
- 如工单类型、部门信息等相对静态的数据

## 数据安全

### 1. 敏感信息保护
- 用户密码使用哈希加密存储
- 敏感操作记录详细日志

### 2. 权限控制
- 基于角色的访问控制
- 数据行级权限控制

### 3. 数据备份
- 定期备份数据库
- 建立灾难恢复机制

## 总结

该工单系统数据库设计完整，包含了用户管理、部门管理、工单管理、工单类型和分类、日志记录、附件管理、回访评价、协作功能、通知系统、位置管理和系统缓存等功能模块，是一个功能完善的工单管理系统。设计考虑了数据完整性、性能优化、扩展性和安全性等多个方面，为系统的稳定运行和后续扩展提供了良好的基础。