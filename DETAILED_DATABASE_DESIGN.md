# 校园网工单系统 - 详细数据库设计文档

## 文档概述

本文档详细描述了校园网工单系统的数据库设计，包括表结构、关系设计、索引策略、性能优化和数据安全等方面。该数据库设计支持完整的工单管理流程，具有良好的扩展性和性能表现。

## 数据库基本信息

- **数据库类型**：MySQL 8.0+
- **字符集**：utf8mb4
- **排序规则**：utf8mb4_unicode_ci
- **存储引擎**：InnoDB
- **默认时区**：Asia/Shanghai

## 数据库架构设计

### 设计原则

1. **数据完整性**：使用外键约束确保数据一致性
2. **性能优化**：合理的索引设计和查询优化
3. **扩展性**：预留扩展字段，支持未来功能扩展
4. **安全性**：敏感数据加密存储，访问权限控制
5. **可维护性**：清晰的命名规范和文档说明

### 命名规范

- **表名**：小写字母，下划线分隔，复数形式（如：users, workorders）
- **字段名**：小写字母，下划线分隔，语义明确
- **索引名**：idx_表名_字段名，复合索引用下划线连接
- **外键名**：fk_表名_字段名
- **约束名**：uk_表名_字段名（唯一约束），chk_表名_字段名（检查约束）

## 详细表结构设计

### 1. 用户管理模块

#### 1.1 users 表（用户表）

**表描述**：存储系统用户信息，扩展了Laravel默认用户表

```sql
CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL COMMENT '用户姓名',
    email VARCHAR(100) UNIQUE NOT NULL COMMENT '邮箱地址',
    email_verified_at TIMESTAMP NULL COMMENT '邮箱验证时间',
    password VARCHAR(255) NOT NULL COMMENT '密码（Bcrypt加密）',
    remember_token VARCHAR(100) NULL COMMENT '记住登录令牌',
    phone VARCHAR(20) NULL COMMENT '联系电话',
    employee_id VARCHAR(50) NULL COMMENT '员工编号',
    department_id BIGINT UNSIGNED NULL COMMENT '部门ID',
    role ENUM('admin', 'workorder_manager', 'engineer', 'user') DEFAULT 'user' COMMENT '角色：系统管理员、工单管理员、工程师、普通用户',
    status ENUM('active', 'inactive') DEFAULT 'active' COMMENT '状态',
    account_type ENUM('staff', 'student', 'external') DEFAULT 'staff' COMMENT '账户类型：教职工、学生、外部人员',
    username VARCHAR(100) NULL COMMENT '用户名',
    location VARCHAR(255) NULL COMMENT '办公地点',
    remarks TEXT NULL COMMENT '备注信息',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    
    INDEX idx_department_id (department_id),
    INDEX idx_role (role),
    INDEX idx_status (status),
    INDEX idx_account_type (account_type),
    INDEX idx_username (username),
    INDEX idx_role_status (role, status),
    
    FOREIGN KEY fk_users_department_id (department_id) REFERENCES departments(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户表';
```

**字段说明**：
- `role`：支持四种角色 - admin（系统管理员）、workorder_manager（工单管理员）、engineer（工程师）、user（普通用户）
- `account_type`：区分用户身份类型 - staff（教职工）、student（学生）、external（外部人员）
- `status`：用户账户状态管理 - active（激活）、inactive（停用）
- `username`：用户名字段，支持用户名登录
- `department_id`：外键关联部门表，支持部门管理

**索引策略**：
- 主键索引：id
- 唯一索引：email
- 普通索引：department_id, role, status, account_type, username
- 复合索引：role + status

#### 1.2 departments 表（部门表）

**表描述**：存储组织架构信息，支持多级部门结构

```sql
CREATE TABLE departments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL COMMENT '部门名称',
    code VARCHAR(50) UNIQUE NOT NULL COMMENT '部门编码',
    manager_name VARCHAR(100) NULL COMMENT '部门负责人姓名',
    manager_phone VARCHAR(20) NULL COMMENT '负责人电话',
    location VARCHAR(255) NULL COMMENT '办公地点',
    description TEXT NULL COMMENT '部门描述',
    status ENUM('active', 'inactive') DEFAULT 'active' COMMENT '部门状态',
    sort_order INT DEFAULT 0 COMMENT '排序顺序',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    
    INDEX idx_status (status),
    INDEX idx_sort_order (sort_order),
    INDEX idx_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='部门表';
```

**设计特点**：
- `code`：部门编码，确保唯一性，便于系统集成
- `sort_order`：支持部门排序，便于前端显示
- `status`：支持部门启用/禁用状态管理

### 2. 工单核心模块

#### 2.1 workorders 表（工单表）

**表描述**：工单主表，存储工单的核心信息

```sql
CREATE TABLE workorders (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ticket_prefix VARCHAR(10) DEFAULT 'WO' COMMENT '工单编号前缀',
    ticket_no VARCHAR(20) NOT NULL COMMENT '工单编号',
    title VARCHAR(255) NULL COMMENT '工单标题',
    description TEXT NOT NULL COMMENT '问题描述',
    failure_description TEXT NULL COMMENT '具体故障现象',
    type_id BIGINT UNSIGNED NULL COMMENT '工单类型ID',
    category_id BIGINT UNSIGNED NULL COMMENT '工单分类ID',
    creator_id BIGINT UNSIGNED NOT NULL COMMENT '创建人ID',
    assignee_id BIGINT UNSIGNED NULL COMMENT '处理人ID',
    department_id BIGINT UNSIGNED NULL COMMENT '部门ID',
    department_name VARCHAR(100) NULL COMMENT '部门名称',
    contact_name VARCHAR(100) NOT NULL COMMENT '联系人姓名',
    contact_phone VARCHAR(20) NOT NULL COMMENT '联系电话',
    contact_email VARCHAR(100) NULL COMMENT '联系邮箱',
    location VARCHAR(255) NOT NULL COMMENT '故障地点',
    location_detail TEXT NULL COMMENT '详细地址',
    campus VARCHAR(50) NULL COMMENT '校区',
    building VARCHAR(100) NULL COMMENT '楼栋',
    appointment_time DATETIME NULL COMMENT '预约时间',
    source ENUM('phone', 'web', 'email', 'scene', 'other') DEFAULT 'web' COMMENT '工单来源',
    priority ENUM('high', 'medium', 'low') DEFAULT 'medium' COMMENT '优先级',
    status ENUM('pending', 'assigned', 'processing', 'resolved', 'completed', 'verifying', 'closed', 'rejected') DEFAULT 'pending' COMMENT '工单状态',
    time_limit_hours INT NULL COMMENT '处理时限（小时）',
    assigned_at DATETIME NULL COMMENT '分配时间',
    started_at DATETIME NULL COMMENT '开始处理时间',
    resolved_at DATETIME NULL COMMENT '解决时间',
    completed_at TIMESTAMP NULL COMMENT '完成时间',
    closed_at DATETIME NULL COMMENT '关闭时间',
    expected_complete_at DATETIME NULL COMMENT '预计完成时间',
    processing_duration INT NULL COMMENT '实际处理时长（分钟）',
    solution TEXT NULL COMMENT '解决方案',
    remarks TEXT NULL COMMENT '备注',
    materials_usage TEXT NULL COMMENT '备件耗材使用情况',
    other_reason TEXT NULL COMMENT '其他部门原因',
    need_visit BOOLEAN DEFAULT FALSE COMMENT '是否需要回访',
    is_emergency BOOLEAN DEFAULT FALSE COMMENT '是否紧急',
    phone_assisted BOOLEAN DEFAULT FALSE COMMENT '电话协助完成',
    custom_source VARCHAR(100) NULL COMMENT '自定义来源',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    deleted_at TIMESTAMP NULL COMMENT '软删除时间',
    
    UNIQUE KEY uk_ticket_prefix_no (ticket_prefix, ticket_no),
    INDEX idx_ticket_no (ticket_no),
    INDEX idx_status_priority (status, priority),
    INDEX idx_creator_created (creator_id, created_at),
    INDEX idx_assignee_status (assignee_id, status),
    INDEX idx_type_status (type_id, status),
    INDEX idx_category_status (category_id, status),
    INDEX idx_campus (campus),
    INDEX idx_building (building),
    INDEX idx_deleted_at (deleted_at),
    
    FOREIGN KEY fk_workorders_type_id (type_id) REFERENCES workorder_types(id) ON DELETE SET NULL,
    FOREIGN KEY fk_workorders_category_id (category_id) REFERENCES workorder_categories_simplified(id) ON DELETE SET NULL,
    FOREIGN KEY fk_workorders_creator_id (creator_id) REFERENCES users(id) ON DELETE RESTRICT,
    FOREIGN KEY fk_workorders_assignee_id (assignee_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY fk_workorders_department_id (department_id) REFERENCES departments(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='工单表';
```

**设计特点**：
- **复合唯一约束**：ticket_prefix + ticket_no 确保工单编号唯一性
- **软删除支持**：使用 deleted_at 字段实现软删除
- **状态管理**：完整的工单状态流转支持
- **时间跟踪**：详细的时间节点记录，便于性能分析
- **多级分类**：支持工单类型和分类的双重分类体系

**索引策略**：
- 复合唯一索引：uk_ticket_prefix_no
- 单字段索引：ticket_no, campus, building
- 复合索引：status + priority, creator_id + created_at, assignee_id + status
- 外键索引：type_id, category_id, creator_id, assignee_id, department_id

#### 2.2 workorder_types 表（工单类型表）

**表描述**：存储工单类型信息，支持多级分类

```sql
CREATE TABLE workorder_types (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL COMMENT '工单类型名称',
    code VARCHAR(50) UNIQUE NOT NULL COMMENT '类型编码',
    description TEXT NULL COMMENT '类型描述',
    icon VARCHAR(50) NULL COMMENT '图标类名',
    color VARCHAR(20) NULL COMMENT '颜色代码',
    source_options JSON NULL COMMENT '工单来源选项',
    default_ticket_prefix VARCHAR(10) DEFAULT 'WO' COMMENT '默认工单编号前缀',
    allow_user_select BOOLEAN DEFAULT TRUE COMMENT '是否允许用户选择',
    parent_id BIGINT UNSIGNED NULL COMMENT '父级分类ID',
    level INT DEFAULT 1 COMMENT '分类层级',
    source VARCHAR(100) NULL COMMENT '来源渠道',
    subcategory VARCHAR(100) NULL COMMENT '子类别',
    default_priority INT DEFAULT 2 COMMENT '默认优先级（1-高，2-中，3-低）',
    default_hours INT DEFAULT 24 COMMENT '默认处理时限（小时）',
    allowed_roles JSON NULL COMMENT '允许创建此类型工单的角色',
    status ENUM('active', 'inactive') DEFAULT 'active' COMMENT '状态',
    sort_order INT DEFAULT 0 COMMENT '排序',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    
    INDEX idx_parent_status (parent_id, status),
    INDEX idx_source_status (source, status),
    INDEX idx_status_sort (status, sort_order),
    INDEX idx_code (code),
    
    FOREIGN KEY fk_workorder_types_parent_id (parent_id) REFERENCES workorder_types(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='工单类型表';
```

**设计特点**：
- **自关联设计**：parent_id 指向自身，支持多级分类
- **JSON字段**：source_options, allowed_roles 使用JSON存储复杂数据
- **层级管理**：level 字段记录分类层级深度
- **权限控制**：allowed_roles 限制不同角色创建的工单类型

#### 2.3 workorder_categories_simplified 表（简化工单分类表）

**表描述**：简化的工单分类表，采用二级分类结构

```sql
CREATE TABLE workorder_categories_simplified (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL COMMENT '分类名称',
    parent_id VARCHAR(50) NULL COMMENT '父分类ID',
    ticket_prefix VARCHAR(10) DEFAULT 'WO' COMMENT '工单编号前缀',
    default_hours INT DEFAULT 24 COMMENT '默认处理时限（小时）',
    color VARCHAR(20) DEFAULT '#6c757d' COMMENT '显示颜色',
    description TEXT NULL COMMENT '分类描述',
    sort_order INT DEFAULT 0 COMMENT '排序顺序',
    status BOOLEAN DEFAULT TRUE COMMENT '状态',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    
    INDEX idx_parent_id (parent_id),
    INDEX idx_status (status),
    INDEX idx_sort_order (sort_order),
    INDEX idx_ticket_prefix (ticket_prefix)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='简化工单分类表';
```

**设计特点**：
- **二级分类**：parent_id 为 NULL 表示一级分类，否则为二级分类
- **工单编号前缀**：不同分类使用不同的工单编号前缀
- **颜色标识**：color 字段用于前端显示不同颜色

### 3. 工单功能模块

#### 3.1 workorder_logs 表（工单日志表）

**表描述**：记录工单的所有操作历史

```sql
CREATE TABLE workorder_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    workorder_id BIGINT UNSIGNED NOT NULL COMMENT '工单ID',
    user_id BIGINT UNSIGNED NOT NULL COMMENT '操作人ID',
    action VARCHAR(50) NOT NULL COMMENT '操作类型',
    content TEXT NULL COMMENT '操作内容/备注',
    old_value TEXT NULL COMMENT '原值',
    new_value TEXT NULL COMMENT '新值',
    processing_time DATETIME NULL COMMENT '处理耗时',
    is_system BOOLEAN DEFAULT FALSE COMMENT '是否系统自动操作',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    
    INDEX idx_workorder_created (workorder_id, created_at),
    INDEX idx_user_created (user_id, created_at),
    INDEX idx_action_created (action, created_at),
    
    FOREIGN KEY fk_workorder_logs_workorder_id (workorder_id) REFERENCES workorders(id) ON DELETE CASCADE,
    FOREIGN KEY fk_workorder_logs_user_id (user_id) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='工单日志表';
```

**操作类型枚举值**：
- `created`：创建工单
- `assigned`：分配工单
- `accepted`：接单
- `started`：开始处理
- `paused`：暂停处理
- `resumed`：恢复处理
- `transferred`：转派
- `resolved`：已解决
- `rejected`：拒绝处理
- `closed`：关闭工单
- `reopened`：重新打开
- `comment`：添加备注

#### 3.2 workorder_attachments 表（工单附件表）

**表描述**：存储工单相关附件信息

```sql
CREATE TABLE workorder_attachments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    workorder_id BIGINT UNSIGNED NOT NULL COMMENT '工单ID',
    user_id BIGINT UNSIGNED NOT NULL COMMENT '上传人ID',
    filename VARCHAR(255) NOT NULL COMMENT '文件名',
    original_name VARCHAR(255) NOT NULL COMMENT '原始文件名',
    file_path VARCHAR(500) NOT NULL COMMENT '文件路径',
    file_type VARCHAR(50) NOT NULL COMMENT '文件类型',
    file_size BIGINT NOT NULL COMMENT '文件大小（字节）',
    mime_type VARCHAR(100) NULL COMMENT 'MIME类型',
    description TEXT NULL COMMENT '文件描述',
    type ENUM('image', 'document', 'video', 'audio', 'other') DEFAULT 'other' COMMENT '附件类型',
    is_public BOOLEAN DEFAULT TRUE COMMENT '是否公开',
    thumbnail_path VARCHAR(500) NULL COMMENT '缩略图路径',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    
    INDEX idx_workorder_type (workorder_id, type),
    INDEX idx_user_created (user_id, created_at),
    INDEX idx_file_type (file_type),
    
    FOREIGN KEY fk_workorder_attachments_workorder_id (workorder_id) REFERENCES workorders(id) ON DELETE CASCADE,
    FOREIGN KEY fk_workorder_attachments_user_id (user_id) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='工单附件表';
```

**设计特点**：
- **文件分类**：type 字段支持多种文件类型分类
- **权限控制**：is_public 字段控制附件访问权限
- **缩略图支持**：thumbnail_path 字段存储图片缩略图路径

#### 3.3 workorder_visits 表（工单回访表）

**表描述**：存储工单回访和满意度评价信息

```sql
CREATE TABLE workorder_visits (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    workorder_id BIGINT UNSIGNED NOT NULL COMMENT '工单ID',
    visitor_id BIGINT UNSIGNED NOT NULL COMMENT '回访人ID',
    visit_method ENUM('phone', 'sms', 'email', 'online', 'scene') DEFAULT 'phone' COMMENT '回访方式',
    visit_time DATETIME NOT NULL COMMENT '回访时间',
    visit_content TEXT NULL COMMENT '回访内容',
    feedback TEXT NULL COMMENT '用户反馈',
    satisfaction_score INT NULL COMMENT '满意度评分（1-5分）',
    response_speed_score INT NULL COMMENT '响应速度评分（1-5分）',
    service_quality_score INT NULL COMMENT '服务质量评分（1-5分）',
    professional_score INT NULL COMMENT '专业水平评分（1-5分）',
    overall_score INT NULL COMMENT '总体满意度评分（1-5分）',
    suggestions TEXT NULL COMMENT '改进建议',
    status ENUM('pending', 'completed', 'failed', 'skipped') DEFAULT 'pending' COMMENT '回访状态',
    fail_reason TEXT NULL COMMENT '回访失败原因',
    need_follow_up BOOLEAN DEFAULT FALSE COMMENT '是否需要后续跟进',
    follow_up_note TEXT NULL COMMENT '跟进说明',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    
    INDEX idx_workorder_id (workorder_id),
    INDEX idx_visitor_time (visitor_id, visit_time),
    INDEX idx_status_time (status, visit_time),
    INDEX idx_overall_score (overall_score),
    
    FOREIGN KEY fk_workorder_visits_workorder_id (workorder_id) REFERENCES workorders(id) ON DELETE CASCADE,
    FOREIGN KEY fk_workorder_visits_visitor_id (visitor_id) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='工单回访表';
```

**设计特点**：
- **多维度评价**：支持满意度、响应速度、服务质量、专业水平等多维度评分
- **回访状态管理**：完整的回访流程状态跟踪
- **跟进机制**：支持后续跟进和备注

#### 3.4 workorder_collaborations 表（工单协作表）

**表描述**：存储工单协作信息，支持多人协作处理

```sql
CREATE TABLE workorder_collaborations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    workorder_id BIGINT UNSIGNED NOT NULL COMMENT '工单ID',
    inviter_id BIGINT UNSIGNED NOT NULL COMMENT '邀请人ID',
    collaborator_id BIGINT UNSIGNED NOT NULL COMMENT '协作人ID',
    invitation_reason TEXT NULL COMMENT '邀请原因',
    status ENUM('pending', 'accepted', 'rejected') DEFAULT 'pending' COMMENT '状态',
    accepted_at TIMESTAMP NULL COMMENT '接受时间',
    response_note TEXT NULL COMMENT '回复备注',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    
    INDEX idx_workorder_collaborator (workorder_id, collaborator_id),
    INDEX idx_workorder_status (workorder_id, status),
    INDEX idx_collaborator_status (collaborator_id, status),
    
    FOREIGN KEY fk_workorder_collaborations_workorder_id (workorder_id) REFERENCES workorders(id) ON DELETE CASCADE,
    FOREIGN KEY fk_workorder_collaborations_inviter_id (inviter_id) REFERENCES users(id) ON DELETE RESTRICT,
    FOREIGN KEY fk_workorder_collaborations_collaborator_id (collaborator_id) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='工单协作表';
```

### 4. 系统功能模块

#### 4.1 notifications 表（通知表）

**表描述**：存储系统通知信息

```sql
CREATE TABLE notifications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL COMMENT '用户ID',
    workorder_id BIGINT UNSIGNED NULL COMMENT '工单ID',
    type VARCHAR(50) NOT NULL COMMENT '通知类型',
    title VARCHAR(255) NOT NULL COMMENT '通知标题',
    content TEXT NOT NULL COMMENT '通知内容',
    data JSON NULL COMMENT '额外数据',
    is_read BOOLEAN DEFAULT FALSE COMMENT '是否已读',
    read_at TIMESTAMP NULL COMMENT '阅读时间',
    is_important BOOLEAN DEFAULT FALSE COMMENT '是否重要通知',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    
    INDEX idx_user_read (user_id, is_read),
    INDEX idx_user_type (user_id, type),
    INDEX idx_user_created (user_id, created_at),
    INDEX idx_workorder_type (workorder_id, type),
    INDEX idx_is_important (is_important),
    
    FOREIGN KEY fk_notifications_user_id (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY fk_notifications_workorder_id (workorder_id) REFERENCES workorders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='通知表';
```

#### 4.2 locations 表（位置表）

**表描述**：存储位置信息，支持校区、建筑类型、楼栋等多级位置管理

```sql
CREATE TABLE locations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL COMMENT '地址名称',
    campus ENUM('old_campus', 'new_campus', 'asean_campus') NOT NULL COMMENT '校区',
    building_type ENUM('teaching_building', 'dormitory', 'office_building', 'library', 'other') NOT NULL COMMENT '建筑类型',
    building_code VARCHAR(50) NULL COMMENT '楼栋代码',
    description TEXT NULL COMMENT '描述',
    sort_order INT DEFAULT 0 COMMENT '排序',
    status VARCHAR(20) DEFAULT 'active' COMMENT '状态',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    
    INDEX idx_campus_type (campus, building_type),
    INDEX idx_status (status),
    INDEX idx_sort_order (sort_order),
    INDEX idx_building_code (building_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='位置表';
```

#### 4.3 workorder_templates 表（工单模板表）

**表描述**：存储工单模板信息，提高工单创建效率

```sql
CREATE TABLE workorder_templates (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL COMMENT '模板名称',
    description TEXT NULL COMMENT '工单描述模板',
    category_id BIGINT UNSIGNED NULL COMMENT '工单分类ID',
    contact_name VARCHAR(100) NULL COMMENT '联系人姓名',
    contact_phone VARCHAR(20) NULL COMMENT '联系人电话',
    contact_email VARCHAR(100) NULL COMMENT '联系人邮箱',
    campus VARCHAR(50) NULL COMMENT '校区',
    building VARCHAR(100) NULL COMMENT '楼栋',
    location_detail TEXT NULL COMMENT '位置详情',
    time_limit_hours INT NULL COMMENT '时限（小时）',
    priority VARCHAR(20) DEFAULT 'medium' COMMENT '优先级',
    source VARCHAR(20) DEFAULT 'web' COMMENT '来源',
    department_name VARCHAR(100) NULL COMMENT '部门名称',
    need_visit BOOLEAN DEFAULT FALSE COMMENT '是否需要回访',
    is_emergency BOOLEAN DEFAULT FALSE COMMENT '是否紧急',
    phone_assisted BOOLEAN DEFAULT FALSE COMMENT '电话协助',
    other_reason TEXT NULL COMMENT '其他原因',
    is_active BOOLEAN DEFAULT TRUE COMMENT '是否启用',
    creator_id BIGINT UNSIGNED NOT NULL COMMENT '创建人ID',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    
    INDEX idx_is_active_name (is_active, name),
    INDEX idx_creator_id (creator_id),
    INDEX idx_category_id (category_id),
    
    FOREIGN KEY fk_workorder_templates_category_id (category_id) REFERENCES workorder_categories_simplified(id) ON DELETE SET NULL,
    FOREIGN KEY fk_workorder_templates_creator_id (creator_id) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='工单模板表';
```

### 5. Laravel 系统表

系统还包含 Laravel 框架的标准系统表，如：
- `cache`：缓存表
- `cache_locks`：缓存锁表
- `jobs`：任务队列表
- `job_batches`：任务批次表
- `failed_jobs`：失败任务表
- `password_reset_tokens`：密码重置令牌表
- `sessions`：会话表

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

workorder_categories_simplified (简化工单分类表)
├── workorders (工单表) - 一对多
└── workorder_templates (工单模板表) - 一对多

workorders (工单表)
├── workorder_logs (工单日志表) - 一对多
├── workorder_attachments (工单附件表) - 一对多
├── workorder_visits (工单回访表) - 一对多
├── workorder_collaborations (工单协作表) - 一对多
└── notifications (通知表) - 一对多

locations (位置表)
└── workorders (工单表) - 一对多 (通过 campus, building 字段关联)
```

## 索引策略详解

### 1. 主键索引
所有表都使用自增 BIGINT 作为主键，确保高性能的插入和查询。

### 2. 唯一索引
- `users.email`：确保邮箱唯一性
- `departments.code`：确保部门编码唯一性
- `workorder_types.code`：确保工单类型编码唯一性
- `workorders.ticket_prefix + ticket_no`：确保工单编号唯一性

### 3. 复合索引
- `workorders.status + priority`：优化状态和优先级组合查询
- `workorders.creator_id + created_at`：优化用户工单列表查询
- `workorders.assignee_id + status`：优化工程师工单列表查询
- `workorder_logs.workorder_id + created_at`：优化工单日志时间线查询
- `notifications.user_id + is_read`：优化用户未读通知查询

### 4. 外键索引
所有外键字段都建立索引，提高关联查询性能。

## 性能优化策略

### 1. 查询优化
- 使用 Eloquent 关联预加载避免 N+1 查询
- 合理使用数据库索引
- 分页查询大数据集
- 使用查询缓存

### 2. 数据分区策略
对于大数据量表，可考虑按时间分区：
```sql
-- workorder_logs 表按月分区示例
ALTER TABLE workorder_logs 
PARTITION BY RANGE (YEAR(created_at) * 100 + MONTH(created_at)) (
    PARTITION p202501 VALUES LESS THAN (202502),
    PARTITION p202502 VALUES LESS THAN (202503),
    -- ... 更多分区
    PARTITION p_future VALUES LESS THAN MAXVALUE
);
```

### 3. 数据归档策略
- 定期归档历史工单数据
- 软删除数据定期清理
- 日志数据定期压缩归档

## 数据安全策略

### 1. 敏感数据保护
- 用户密码使用 Bcrypt 加密存储
- 敏感操作记录详细日志
- 数据传输使用 HTTPS

### 2. 权限控制
- 基于角色的访问控制
- 数据行级权限控制
- API 接口权限验证

### 3. 数据备份
- 定期全量备份
- 增量备份策略
- 异地备份存储

## 数据迁移和版本控制

### 1. 迁移文件命名规范
- 格式：YYYY_MM_DD_HHMMSS_description.php
- 示例：2024_11_17_000001_create_users_table.php

### 2. 迁移文件组织
- 创建表：create_表名_table.php
- 修改表：update_表名_for_功能.php
- 添加索引：add_字段_to_表名_table.php
- 删除表：drop_表名_table.php

### 3. 数据种子
- 使用 Seeder 类填充初始数据
- 支持开发和生产环境的不同数据
- 数据版本控制和回滚

## 监控和维护

### 1. 性能监控
- 慢查询日志分析
- 索引使用情况监控
- 数据库连接池监控

### 2. 数据一致性检查
- 外键约束验证
- 数据完整性检查
- 定期数据校验

### 3. 维护计划
- 定期索引优化
- 统计信息更新
- 表空间管理

## 总结

该数据库设计具有以下特点：

1. **完整性**：覆盖了工单系统的所有核心功能
2. **扩展性**：预留了扩展字段，支持未来功能扩展
3. **性能**：合理的索引设计和查询优化
4. **安全性**：完善的数据安全策略
5. **可维护性**：清晰的命名规范和文档

该设计已经过充分测试，能够支持生产环境的高并发访问和大数据量存储，为校园网工单系统提供了坚实的数据基础。

---

**文档版本**：v1.0.0  
**最后更新**：2025-11-21  
**数据库版本**：MySQL 8.0+  
**字符集**：utf8mb4