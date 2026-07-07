# 校园网工单系统 - API接口文档

## 文档概述

本文档详细描述了校园网工单系统的API接口，包括认证方式、请求格式、响应格式、错误处理等。系统采用RESTful API设计风格，支持JSON格式的数据交换。

## API基本信息

- **Base URL**：`http://your-domain.com/api`
- **API版本**：v1.0.0
- **数据格式**：JSON
- **字符编码**：UTF-8
- **认证方式**：Laravel Sanctum Token / Session Auth

## 认证机制

### 1. Session认证（Web应用）
通过Laravel的Session认证机制，用户登录后自动获得认证状态。

### 2. Token认证（API应用）
使用Laravel Sanctum进行Token认证：

```http
Authorization: Bearer {token}
```

### 3. 权限控制
系统支持基于角色的权限控制：

#### 用户角色说明
| 角色 | 权限描述 | 可访问功能 |
|------|----------|------------|
| admin | 系统管理员 | 所有功能，包括用户管理、系统配置、工单管理 |
| workorder_manager | 工单管理员 | 工单管理、分配工单、查看所有工单、工单统计 |
| engineer | 工程师 | 工单处理、查看分配给自己的工单、更新工单状态 |
| user | 普通用户 | 创建工单、查看自己的工单、工单评价 |

#### 账户类型说明
| 账户类型 | 描述 | 适用场景 |
|----------|------|----------|
| staff | 教职工 | 学校教职工账户 |
| student | 学生 | 在校学生账户 |
| external | 外部人员 | 访客或外部服务人员账户 |

#### 权限要求说明
- `admin`：系统管理员权限
- `workorder_manager`：工单管理员权限
- `engineer`：工程师权限
- `user`：普通用户权限

## 通用响应格式

### 成功响应
```json
{
    "success": true,
    "data": {
        // 响应数据
    },
    "message": "操作成功",
    "timestamp": "2025-11-21T07:46:22.171Z"
}
```

### 错误响应
```json
{
    "success": false,
    "error": {
        "code": "ERROR_CODE",
        "message": "错误描述",
        "details": {
            // 详细错误信息
        }
    },
    "timestamp": "2025-11-21T07:46:22.171Z"
}
```

### 分页响应
```json
{
    "success": true,
    "data": {
        "data": [
            // 数据列表
        ],
        "current_page": 1,
        "per_page": 15,
        "total": 100,
        "last_page": 7,
        "from": 1,
        "to": 15
    },
    "message": "获取成功"
}
```

## 错误代码说明

| 错误代码 | HTTP状态码 | 描述 |
|---------|-----------|------|
| AUTH_REQUIRED | 401 | 需要认证 |
| AUTH_INVALID | 401 | 认证无效 |
| PERMISSION_DENIED | 403 | 权限不足 |
| NOT_FOUND | 404 | 资源不存在 |
| VALIDATION_ERROR | 422 | 数据验证失败 |
| SERVER_ERROR | 500 | 服务器内部错误 |

## API接口详情

### 1. 认证相关接口

#### 1.1 用户登录
```http
POST /api/login
```

**请求参数**：
```json
{
    "email": "user@example.com",
    "password": "password123"
}
```

**响应示例**：
```json
{
    "success": true,
    "data": {
        "user": {
            "id": 1,
            "name": "用户名",
            "email": "user@example.com",
            "account_type": "user"
        },
        "token": "1|abc123def456..."
    },
    "message": "登录成功"
}
```

#### 1.2 用户登出
```http
POST /api/logout
```

**请求头**：
```http
Authorization: Bearer {token}
```

**响应示例**：
```json
{
    "success": true,
    "message": "登出成功"
}
```

#### 1.3 获取当前用户信息
```http
GET /api/user
```

**响应示例**：
```json
{
    "success": true,
    "data": {
        "id": 1,
        "name": "用户名",
        "email": "user@example.com",
        "account_type": "user",
        "department": {
            "id": 1,
            "name": "信息技术部"
        }
    }
}
```

### 2. 工单管理接口

#### 2.1 获取工单列表
```http
GET /api/workorders
```

**查询参数**：
| 参数 | 类型 | 必填 | 描述 |
|------|------|------|------|
| keyword | string | 否 | 关键词搜索 |
| status | string | 否 | 工单状态筛选 |
| priority | string | 否 | 优先级筛选 |
| type_id | integer | 否 | 工单类型ID |
| assignee_id | integer | 否 | 处理人ID |
| date_from | string | 否 | 开始日期 (YYYY-MM-DD) |
| date_to | string | 否 | 结束日期 (YYYY-MM-DD) |
| page | integer | 否 | 页码，默认1 |
| per_page | integer | 否 | 每页数量，默认15 |

**响应示例**：
```json
{
    "success": true,
    "data": {
        "data": [
            {
                "id": 1,
                "ticket_no": "WO202411170001",
                "title": "网络连接故障",
                "status": "pending",
                "priority": "high",
                "creator": {
                    "id": 1,
                    "name": "张三"
                },
                "assignee": null,
                "category": {
                    "id": 1,
                    "name": "网络故障"
                },
                "created_at": "2024-11-17T10:00:00Z"
            }
        ],
        "current_page": 1,
        "per_page": 15,
        "total": 50
    }
}
```

#### 2.2 获取工单详情
```http
GET /api/workorders/{id}
```

**路径参数**：
| 参数 | 类型 | 描述 |
|------|------|------|
| id | integer | 工单ID |

**响应示例**：
```json
{
    "success": true,
    "data": {
        "id": 1,
        "ticket_no": "WO202411170001",
        "title": "网络连接故障",
        "description": "办公室网络无法连接",
        "failure_description": "具体故障现象描述",
        "status": "pending",
        "priority": "high",
        "source": "web",
        "contact_name": "李四",
        "contact_phone": "13800138000",
        "location": "老校区1教",
        "location_detail": "301办公室",
        "creator": {
            "id": 1,
            "name": "张三",
            "email": "zhang@example.com"
        },
        "assignee": null,
        "category": {
            "id": 1,
            "name": "网络故障",
            "ticket_prefix": "N"
        },
        "logs": [
            {
                "id": 1,
                "action": "created",
                "content": "创建工单",
                "user": {
                    "name": "张三"
                },
                "created_at": "2024-11-17T10:00:00Z"
            }
        ],
        "attachments": [],
        "created_at": "2024-11-17T10:00:00Z",
        "updated_at": "2024-11-17T10:00:00Z"
    }
}
```

#### 2.3 创建工单
```http
POST /api/workorders
```

**请求参数**：
```json
{
    "title": "网络连接故障",
    "description": "办公室网络无法连接",
    "failure_description": "具体故障现象描述",
    "category_id": 1,
    "contact_name": "李四",
    "contact_phone": "13800138000",
    "contact_email": "li@example.com",
    "location": "老校区1教",
    "location_detail": "301办公室",
    "campus": "old_campus",
    "building": "1教",
    "priority": "high",
    "source": "web",
    "assignee_id": 2,
    "appointment_time": "2024-11-18T14:00:00Z",
    "attachments": [
        {
            "file": "base64_encoded_file_data",
            "filename": "screenshot.jpg",
            "mime_type": "image/jpeg"
        }
    ]
}
```

**响应示例**：
```json
{
    "success": true,
    "data": {
        "id": 1,
        "ticket_no": "WO202411170001",
        "status": "pending",
        "created_at": "2024-11-17T10:00:00Z"
    },
    "message": "工单创建成功"
}
```

#### 2.4 更新工单
```http
PUT /api/workorders/{id}
```

**请求参数**：
```json
{
    "title": "更新后的标题",
    "description": "更新后的描述",
    "priority": "medium",
    "assignee_id": 3
}
```

#### 2.5 删除工单
```http
DELETE /api/workorders/{id}
```

**响应示例**：
```json
{
    "success": true,
    "message": "工单删除成功"
}
```

#### 2.6 工单分配
```http
POST /api/workorders/{id}/assign
```

**请求参数**：
```json
{
    "assignee_id": 2,
    "remark": "请尽快处理"
}
```

#### 2.7 接单
```http
POST /api/workorders/{id}/claim
```

#### 2.8 开始处理
```http
POST /api/workorders/{id}/start
```

**请求参数**：
```json
{
    "remark": "开始处理工单"
}
```

#### 2.9 解决工单
```http
POST /api/workorders/{id}/resolve
```

**请求参数**：
```json
{
    "solution": "解决方案描述",
    "materials_usage": "使用的备件和耗材",
    "attachments": [
        {
            "file": "base64_encoded_file_data",
            "filename": "solution.jpg",
            "mime_type": "image/jpeg"
        }
    ]
}
```

#### 2.10 完成工单
```http
POST /api/workorders/{id}/complete
```

#### 2.11 关闭工单
```http
POST /api/workorders/{id}/close
```

**请求参数**：
```json
{
    "remark": "关闭原因"
}
```

#### 2.12 添加工单日志
```http
POST /api/workorders/{id}/logs
```

**请求参数**：
```json
{
    "action": "comment",
    "content": "处理进度更新",
    "attachments": []
}
```

#### 2.13 更新备件耗材使用情况
```http
POST /api/workorders/{id}/materials
```

**请求参数**：
```json
{
    "materials_usage": "使用网线1根，水晶头2个"
}
```

#### 2.14 邀请协作
```http
POST /api/workorders/{id}/invite-collaborator
```

**请求参数**：
```json
{
    "collaborator_id": 3,
    "invitation_reason": "需要网络专业知识支持"
}
```

#### 2.15 上传附件
```http
POST /api/workorders/{id}/attachments/upload
```

**请求参数**：
```json
{
    "attachments": [
        {
            "file": "base64_encoded_file_data",
            "filename": "document.pdf",
            "mime_type": "application/pdf",
            "description": "相关文档"
        }
    ]
}
```

#### 2.16 获取子分类
```http
GET /api/workorders/subcategories
```

**查询参数**：
| 参数 | 类型 | 必填 | 描述 |
|------|------|------|------|
| category_id | integer | 是 | 父分类ID |

**响应示例**：
```json
{
    "success": true,
    "data": [
        {
            "id": 2,
            "name": "拨号失败",
            "parent_id": "1",
            "ticket_prefix": "N",
            "default_hours": 24
        }
    ]
}
```

### 3. 工单分类接口

#### 3.1 获取工单分类列表
```http
GET /api/workorder-categories
```

**响应示例**：
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "name": "网络故障",
            "parent_id": null,
            "ticket_prefix": "N",
            "default_hours": 24,
            "color": "#ff6b6b",
            "children": [
                {
                    "id": 2,
                    "name": "拨号失败",
                    "parent_id": "1",
                    "ticket_prefix": "N",
                    "default_hours": 8
                }
            ]
        }
    ]
}
```

#### 3.2 获取工单分类选项
```http
GET /api/workorder-categories/options
```

**响应示例**：
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "name": "网络故障",
            "ticket_prefix": "N",
            "default_hours": 24
        }
    ]
}
```

#### 3.3 获取级联分类数据
```http
GET /api/workorder-categories/cascade
```

**响应示例**：
```json
{
    "success": true,
    "data": {
        "categories": [
            {
                "id": 1,
                "name": "网络故障",
                "children": [
                    {
                        "id": 2,
                        "name": "拨号失败"
                    }
                ]
            }
        ]
    }
}
```

### 4. 部门管理接口

#### 4.1 获取部门列表
```http
GET /api/departments
```

**权限要求**：admin

**响应示例**：
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "name": "信息技术部",
            "code": "IT",
            "manager_name": "张经理",
            "manager_phone": "13800138000",
            "status": "active",
            "sort_order": 1,
            "users_count": 10
        }
    ]
}
```

#### 4.2 获取部门树形结构
```http
GET /api/departments/tree
```

**权限要求**：admin

**响应示例**：
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "name": "信息技术部",
            "code": "IT",
            "children": [
                {
                    "id": 2,
                    "name": "系统运维组",
                    "code": "IT_SYS"
                }
            ]
        }
    ]
}
```

#### 4.3 获取部门统计
```http
GET /api/departments/{id}/statistics
```

**权限要求**：admin

**响应示例**：
```json
{
    "success": true,
    "data": {
        "users_count": 10,
        "workorders_count": 50,
        "pending_workorders": 5,
        "completed_workorders": 40,
        "avg_processing_time": 120
    }
}
```

### 5. 用户管理接口

#### 5.1 获取用户列表
```http
GET /api/users
```

**权限要求**：admin

**查询参数**：
| 参数 | 类型 | 必填 | 描述 |
|------|------|------|------|
| keyword | string | 否 | 关键词搜索 |
| department_id | integer | 否 | 部门ID |
| account_type | string | 否 | 账户类型 |
| status | string | 否 | 用户状态 |

#### 5.2 获取工程师列表
```http
GET /api/users/engineers
```

**权限要求**：admin, workorder_manager

**响应示例**：
```json
{
    "success": true,
    "data": [
        {
            "id": 2,
            "name": "工程师A",
            "email": "engineer@example.com",
            "department": {
                "id": 1,
                "name": "信息技术部"
            },
            "workorders_count": 20,
            "avg_processing_time": 90
        }
    ]
}
```

### 6. 位置管理接口

#### 6.1 获取位置列表
```http
GET /api/locations
```

**查询参数**：
| 参数 | 类型 | 必填 | 描述 |
|------|------|------|------|
| campus | string | 否 | 校区筛选 |
| building_type | string | 否 | 建筑类型筛选 |

**响应示例**：
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "name": "1教",
            "campus": "old_campus",
            "building_type": "teaching_building",
            "building_code": "1教",
            "status": "active"
        }
    ]
}
```

### 7. 通知接口

#### 7.1 获取通知列表
```http
GET /api/notifications
```

**查询参数**：
| 参数 | 类型 | 必填 | 描述 |
|------|------|------|------|
| is_read | boolean | 否 | 是否已读 |
| type | string | 否 | 通知类型 |
| limit | integer | 否 | 限制数量，默认20 |

**响应示例**：
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "type": "workorder_assigned",
            "title": "工单已分配",
            "content": "工单WO202411170001已分配给您",
            "is_read": false,
            "is_important": true,
            "workorder": {
                "id": 1,
                "ticket_no": "WO202411170001",
                "title": "网络连接故障"
            },
            "created_at": "2024-11-17T10:00:00Z"
        }
    ]
}
```

#### 7.2 获取未读通知数量
```http
GET /api/notifications/unread-count
```

**响应示例**：
```json
{
    "success": true,
    "data": {
        "unread_count": 5
    }
}
```

#### 7.3 获取最新通知
```http
GET /api/notifications/latest
```

**查询参数**：
| 参数 | 类型 | 必填 | 描述 |
|------|------|------|------|
| limit | integer | 否 | 限制数量，默认5 |

#### 7.4 标记通知为已读
```http
POST /api/notifications/{id}/read
```

#### 7.5 批量标记已读
```http
POST /api/notifications/batch-read
```

**请求参数**：
```json
{
    "notification_ids": [1, 2, 3]
}
```

#### 7.6 删除通知
```http
DELETE /api/notifications/{id}
```

#### 7.7 批量删除通知
```http
DELETE /api/notifications/batch
```

**请求参数**：
```json
{
    "notification_ids": [1, 2, 3]
}
```

### 8. 附件管理接口

#### 8.1 下载附件
```http
GET /api/attachments/{id}/download
```

**响应**：文件流

#### 8.2 预览附件
```http
GET /api/attachments/{id}/preview
```

**响应**：图片文件流（仅适用于图片文件）

#### 8.3 获取附件信息
```http
GET /api/attachments/{id}/info
```

**响应示例**：
```json
{
    "success": true,
    "data": {
        "id": 1,
        "filename": "screenshot.jpg",
        "original_name": "故障截图.jpg",
        "file_size": 1024000,
        "mime_type": "image/jpeg",
        "type": "image",
        "description": "故障现场截图",
        "workorder": {
            "id": 1,
            "ticket_no": "WO202411170001"
        },
        "uploader": {
            "id": 1,
            "name": "张三"
        },
        "created_at": "2024-11-17T10:00:00Z"
    }
}
```

#### 8.4 删除附件
```http
DELETE /api/attachments/{id}
```

### 9. 工单模板接口

#### 9.1 获取工单模板列表
```http
GET /api/workorder-templates
```

**查询参数**：
| 参数 | 类型 | 必填 | 描述 |
|------|------|------|------|
| is_active | boolean | 否 | 是否启用 |
| category_id | integer | 否 | 分类ID |

#### 9.2 从模板创建工单
```http
POST /api/workorder-templates/{id}/createFromTemplate
```

**请求参数**：
```json
{
    "contact_name": "联系人姓名",
    "contact_phone": "联系电话",
    "location": "故障地点",
    "description": "具体问题描述"
}
```

### 10. 统计报表接口

#### 10.1 获取工单统计
```http
GET /api/reports/workorders
```

**查询参数**：
| 参数 | 类型 | 必填 | 描述 |
|------|------|------|------|
| date_from | string | 否 | 开始日期 |
| date_to | string | 否 | 结束日期 |
| group_by | string | 否 | 分组方式 (day/week/month) |
| department_id | integer | 否 | 部门ID |

**响应示例**：
```json
{
    "success": true,
    "data": {
        "summary": {
            "total_workorders": 100,
            "pending_workorders": 10,
            "processing_workorders": 20,
            "completed_workorders": 70,
            "avg_processing_time": 120,
            "satisfaction_rate": 4.5
        },
        "trends": [
            {
                "date": "2024-11-01",
                "count": 10,
                "completed": 8
            }
        ]
    }
}
```

#### 10.2 导出报表
```http
POST /api/reports/export
```

**请求参数**：
```json
{
    "type": "workorders",
    "format": "excel",
    "date_from": "2024-11-01",
    "date_to": "2024-11-30",
    "filters": {
        "status": ["completed"],
        "department_id": 1
    }
}
```

**响应**：文件下载链接

## 批量操作接口

### 1. 批量分配工单
```http
POST /api/workorders/batch/assign
```

**请求参数**：
```json
{
    "workorder_ids": [1, 2, 3],
    "assignee_id": 2,
    "remark": "批量分配"
}
```

### 2. 批量开始处理
```http
POST /api/workorders/batch/start
```

### 3. 批量解决工单
```http
POST /api/workorders/batch/resolve
```

### 4. 批量关闭工单
```http
POST /api/workorders/batch/close
```

## WebSocket 实时通知

系统支持WebSocket实时通知，用于推送工单状态变更、新通知等实时信息。

### 连接地址
```
ws://your-domain.com/ws/notifications
```

### 认证
连接时需要在查询参数中传递token：
```
ws://your-domain.com/ws/notifications?token={auth_token}
```

### 消息格式
```json
{
    "type": "notification",
    "data": {
        "id": 1,
        "title": "新工单分配",
        "content": "工单WO202411170001已分配给您",
        "workorder_id": 1
    },
    "timestamp": "2024-11-17T10:00:00Z"
}
```

## 限流策略

为了保护系统稳定性，API实施以下限流策略：

| 接口类型 | 限制 | 时间窗口 |
|---------|------|---------|
| 认证接口 | 5次/IP | 1分钟 |
| 工单创建 | 10次/用户 | 1分钟 |
| 文件上传 | 20次/用户 | 1分钟 |
| 一般接口 | 100次/用户 | 1分钟 |

## 版本控制

API支持版本控制，通过URL路径指定版本：

- 当前版本：`/api/v1/`
- 向后兼容：至少支持2个主版本

## 测试环境

系统提供完整的API测试环境：

- 测试地址：`http://test.your-domain.com/api`
- 测试账号：
  - 管理员：admin@test.com / admin123
  - 工程师：engineer@test.com / engineer123
  - 用户：user@test.com / user123

## SDK和示例代码

### JavaScript示例
```javascript
// 使用axios进行API调用
const axios = require('axios');

const api = axios.create({
    baseURL: 'http://your-domain.com/api',
    headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json'
    }
});

// 获取工单列表
async function getWorkorders(params = {}) {
    try {
        const response = await api.get('/workorders', { params });
        return response.data;
    } catch (error) {
        console.error('获取工单列表失败:', error.response.data);
        throw error;
    }
}
```

### PHP示例
```php
// 使用Guzzle进行API调用
use GuzzleHttp\Client;

$client = new Client([
    'base_uri' => 'http://your-domain.com/api/',
    'headers' => [
        'Authorization' => 'Bearer ' . $token,
        'Content-Type' => 'application/json'
    ]
]);

// 创建工单
$response = $client->post('workorders', [
    'json' => [
        'title' => '网络故障',
        'description' => '办公室网络无法连接',
        'contact_name' => '张三',
        'contact_phone' => '13800138000',
        'location' => '老校区1教'
    ]
]);

$data = json_decode($response->getBody(), true);
```

## 错误处理最佳实践

1. **检查HTTP状态码**：首先检查响应的HTTP状态码
2. **解析错误信息**：根据错误代码进行相应处理
3. **重试机制**：对于网络错误实施指数退避重试
4. **用户友好提示**：将技术错误转换为用户友好的提示信息

## 更新日志

### v1.0.0 (2025-11-21)
- 初始版本发布
- 完整的工单管理API
- 用户认证和权限控制
- 实时通知支持
- 批量操作接口

---

**文档版本**：v1.0.0  
**最后更新**：2025-11-21  
**API版本**：v1.0.0  
**维护团队**：校园网工单系统开发团队