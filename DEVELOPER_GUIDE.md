
# 校园网工单系统 - 开发者指南

## 指南概述

本指南为校园网工单系统的开发人员提供详细的技术文档，包括系统架构、开发环境搭建、代码规范、扩展开发、测试指南等内容。帮助开发者快速理解系统结构，参与功能开发和维护。

## 目录

1. [系统架构](#系统架构)
2. [开发环境搭建](#开发环境搭建)
3. [代码规范](#代码规范)
4. [核心模块开发](#核心模块开发)
5. [前端开发指南](#前端开发指南)
6. [API开发指南](#api开发指南)
7. [数据库开发](#数据库开发)
8. [测试指南](#测试指南)
9. [性能优化](#性能优化)
10. [安全开发](#安全开发)
11. [部署与发布](#部署与发布)
12. [故障排除](#故障排除)

## 系统架构

### 技术栈

**后端技术**：
- **框架**：Laravel 12
- **PHP版本**：PHP 8.2+
- **数据库**：MySQL 8.0+
- **缓存**：Redis
- **队列**：Laravel Queues
- **认证**：Laravel Sanctum + Session

**前端技术**：
- **模板引擎**：Blade
- **CSS框架**：Bootstrap 5.3
- **JavaScript**：jQuery 3.6 + ES6+
- **图标**：Font Awesome 6.4
- **构建工具**：Vite

### 项目结构

```
workorder/
├── app/
│   ├── Console/           # 控制台命令
│   ├── Http/
│   │   ├── Controllers/   # 控制器
│   │   ├── Middleware/    # 中间件
│   │   └── Requests/      # 请求验证
│   ├── Models/            # 模型
│   ├── Providers/         # 服务提供者
│   └── Helpers/          # 辅助函数
├── database/
│   ├── migrations/        # 数据库迁移
│   ├── seeders/          # 数据填充
│   └── factories/        # 模型工厂
├── resources/
│   ├── views/            # 视图模板
│   ├── js/               # JavaScript文件
│   ├── css/              # CSS文件
│   └── lang/             # 语言包
├── routes/               # 路由定义
├── storage/              # 存储目录
├── tests/                # 测试文件
├── config/               # 配置文件
└── public/               # 公共资源
```

### MVC架构

**Model（模型）**：
- 位于 `app/Models/` 目录
- 负责数据访问和业务逻辑
- 使用Eloquent ORM进行数据库操作

**View（视图）**：
- 位于 `resources/views/` 目录
- 使用Blade模板引擎
- 支持组件化和继承

**Controller（控制器）**：
- 位于 `app/Http/Controllers/` 目录
- 处理HTTP请求和响应
- 调用模型和视图

### 设计模式

**Repository模式**：
```php
// 示例：工单仓库模式
interface WorkorderRepositoryInterface {
    public function findById($id);
    public function create(array $data);
    public function update($id, array $data);
}

class WorkorderRepository implements WorkorderRepositoryInterface {
    protected $model;
    
    public function __construct(Workorder $workorder) {
        $this->model = $workorder;
    }
    
    public function findById($id) {
        return $this->model->with(['creator', 'assignee', 'category'])->find($id);
    }
}
```

**Service模式**：
```php
// 示例：工单服务
class WorkorderService {
    protected $repository;
    protected $notificationService;
    
    public function __construct(
        WorkorderRepository $repository,
        NotificationService $notificationService
    ) {
        $this->repository = $repository;
        $this->notificationService = $notificationService;
    }
    
    public function createWorkorder(array $data) {
        $workorder = $this->repository->create($data);
        $this->notificationService->notifyNewWorkorder($workorder);
        return $workorder;
    }
}
```

## 开发环境搭建

### 环境要求

- **PHP**：8.2+ (推荐8.3)
- **Composer**：2.0+
- **Node.js**：18.0+
- **NPM**：9.0+
- **MySQL**：8.0+
- **Redis**：6.0+ (可选)

### 本地开发环境

#### 1. 克隆项目

```bash
git clone https://github.com/your-org/workorder-system.git
cd workorder-system
```

#### 2. 安装依赖

```bash
# 安装PHP依赖
composer install

# 安装前端依赖
npm install
```

#### 3. 环境配置

```bash
# 复制环境配置文件
cp .env.example .env

# 生成应用密钥
php artisan key:generate

# 配置数据库连接
# 编辑 .env 文件
```

#### 4. 数据库设置

```bash
# 创建数据库
mysql -u root -p
CREATE DATABASE workorder_db;

# 运行迁移
php artisan migrate

# 填充初始数据
php artisan db:seed
```

#### 5. 前端资源编译

```bash
# 开发模式（热重载）
npm run dev

# 生产模式
npm run build
```

#### 6. 启动开发服务器

```bash
php artisan serve
```

访问：http://localhost:8000

### Docker开发环境

#### 1. 使用Laravel Sail

```bash
# 安装Sail
php artisan sail:install

# 启动环境
./vendor/bin/sail up

# 运行命令
./vendor/bin/sail php artisan migrate
./vendor/bin/sail npm install
./vendor/bin/sail npm run dev
```

#### 2. 自定义Docker配置

```dockerfile
# Dockerfile
FROM php:8.3-fpm

# 安装扩展
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# 安装Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 复制应用代码
COPY . /var/www/html

# 设置权限
RUN chown -R www-data:www-data /var/www/html
```

```yaml
# docker-compose.yml
version: '3'
services:
  app:
    build: .
    ports:
      - "8000:8000"
    volumes:
      - .:/var/www/html
    environment:
      - DB_HOST=mysql
      - REDIS_HOST=redis
  
  mysql:
    image: mysql:8.0
    environment:
      MYSQL_ROOT_PASSWORD: root
      MYSQL_DATABASE: workorder_db
    volumes:
      - mysql_data:/var/lib/mysql
  
  redis:
    image: redis:6.0
    ports:
      - "6379:6379"
```

### IDE配置

#### VS Code配置

**推荐扩展**：
- PHP Intelephense
- Laravel Blade Snippets
- Laravel Extra Intellisense
- GitLens
- Prettier
- ESLint

**工作区配置**：
```json
{
    "php.validate.executablePath": "/usr/bin/php",
    "php.suggest.basic": false,
    "emmet.includeLanguages": {
        "blade": "html"
    },
    "files.associations": {
        "*.blade.php": "blade"
    }
}
```

#### PhpStorm配置

**Laravel插件**：
- Laravel Plugin
- .env files support
- Blade

**代码风格**：
- 安装PHP CS Fixer
- 配置PSR-12标准

## 代码规范

### PHP编码规范

#### 1. PSR标准

遵循PSR-1、PSR-2、PSR-4、PSR-12标准：

```php
<?php

namespace App\Http\Controllers;

use App\Models\Workorder;
use Illuminate\Http\Request;

class WorkorderController extends Controller
{
    /**
     * 工单列表
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $workorders = Workorder::with(['creator', 'assignee'])
            ->paginate(15);
            
        return view('workorders.index', compact('workorders'));
    }
}
```

#### 2. 命名规范

**类名**：PascalCase
```php
class WorkorderService
class AttachmentController
```

**方法名**：camelCase
```php
public function createWorkorder()
public function getWorkorderById()
```

**变量名**：camelCase
```php
$workorderList
$currentUser
```

**常量名**：UPPER_SNAKE_CASE
```php
const MAX_FILE_SIZE = 10240;
const DEFAULT_PRIORITY = 'medium';
```

#### 3. 注释规范

**类注释**：
```php
/**
 * 工单服务类
 *
 * @package App\Services
 * @author  开发者姓名
 * @version 1.0.0
 * @since   2024-11-21
 */
class WorkorderService
{
    //
}
```

**方法注释**：
```php
/**
 * 创建工单
 *
 * @param array $data 工单数据
 * @return Workorder
 * @throws ValidationException 验证失败异常
 */
public function createWorkorder(array $data): Workorder
{
    //
}
```

### JavaScript编码规范

#### 1. ES6+语法

```javascript
// 使用箭头函数
const handleWorkorderSubmit = (event) => {
    event.preventDefault();
    // 处理逻辑
};

// 使用解构赋值
const { id, title, status } = workorder;

// 使用模板字符串
const message = `工单 ${workorder.ticket_no} 已创建`;
```

#### 2. 命名规范

```javascript
// 变量和函数：camelCase
const workorderList = [];
const getWorkorderById = (id) => {};

// 常量：UPPER_SNAKE_CASE
const API_BASE_URL = '/api/workorders';
const MAX_RETRY_COUNT = 3;

// 类：PascalCase
class WorkorderManager {
    constructor() {
        this.workorders = [];
    }
}
```

#### 3. 注释规范

```javascript
/**
 * 工单管理器
 * @class WorkorderManager
 */
class WorkorderManager {
    /**
     * 创建工单
     * @param {Object} workorderData - 工单数据
     * @returns {Promise<Object>} 创建的工单
     */
    async createWorkorder(workorderData) {
        // 实现逻辑
    }
}
```

### CSS编码规范

#### 1. BEM命名规范

```css
/* 块（Block） */
.workorder-card {
    border: 1px solid #ddd;
    border-radius: 4px;
}

/* 元素（Element） */
.workorder-card__title {
    font-size: 18px;
    font-weight: bold;
}

.workorder-card__content {
    padding: 16px;
}

/* 修饰符（Modifier） */
.workorder-card--high-priority {
    border-color: #ff4444;
}

.workorder-card--completed {
    opacity: 0.7;
}
```

#### 2. SCSS变量和混合

```scss
// 变量
$primary-color: #007bff;
$secondary-color: #6c757d;
$border-radius: 4px;
$box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);

// 混合
@mixin button-style($bg-color, $text-color: white) {
    background-color: $bg-color;
    color: $text-color;
    border: none;
    border-radius: $border-radius;
    padding: 8px 16px;
    cursor: pointer;
    
    &:hover {
        background-color: darken($bg-color, 10%);
    }
}

// 使用
.btn-primary {
    @include button-style($primary-color);
}
```

## 核心模块开发

### 工单模块

#### 1. 模型开发

```php
// app/Models/Workorder.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Workorder extends Model
{
    use SoftDeletes;
    
    protected $fillable = [
        'title', 'description', 'category_id', 'creator_id',
        'assignee_id', 'status', 'priority', 'location'
    ];
    
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'is_emergency' => 'boolean',
    ];
    
    /**
     * 获取创建者
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }
    
    /**
     * 获取处理人
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }
    
    /**
     * 获取分类
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(WorkorderCategorySimplified::class, 'category_id');
    }
    
    /**
     * 获取日志
     */
    public function logs(): HasMany
    {
        return $this->hasMany(WorkorderLog::class);
    }
    
    /**
     * 获取附件
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(WorkorderAttachment::class);
    }
    
    /**
     * 生成工单编号
     */
    public static function generateTicketNo($prefix = 'WO'): string
    {
        $date = date('Ymd');
        $sequence = static::whereDate('created_at', today())
            ->where('ticket_prefix', $prefix)
            ->count() + 1;
            
        return $prefix . $date . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }
    
    /**
     * 检查是否可以编辑
     */
    public function canBeEditedBy(User $user): bool
    {
        return $this->creator_id === $user->id || 
               $user->hasRole(['admin', 'workorder_manager']);
    }
}
```

#### 2. 控制器开发

```php
// app/Http/Controllers/WorkorderController.php
namespace App\Http\Controllers;

use App\Models\Workorder;
use App\Http\Requests\Workorder\StoreWorkorderRequest;
use App\Http\Requests\Workorder\UpdateWorkorderRequest;
use App\Services\WorkorderService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class WorkorderController extends Controller
{
    protected $workorderService;
    
    public function __construct(WorkorderService $workorderService)
    {
        $this->workorderService = $workorderService;
        $this->middleware('auth');
    }
    
    /**
     * 工单列表
     */
    public function index(Request $request): Response
    {
        $query = Workorder::with(['creator', 'assignee', 'category'])
            ->when($request->keyword, function ($q, $keyword) {
                return $q->where('title', 'like', "%{$keyword}%")
                       ->orWhere('description', 'like', "%{$keyword}%");
            })
            ->when($request->status, function ($q, $status) {
                return $q->where('status', $status);
            })
            ->when($request->priority, function ($q, $priority) {
                return $q->where('priority', $priority);
            });
            
        $workorders = $query->orderBy('created_at', 'desc')
            ->paginate(15);
            
        return response()->view('workorders.index', compact('workorders'));
    }
    
    /**
     * 创建工单
     */
    public function store(StoreWorkorderRequest $request): Response
    {
        try {
            $workorder = $this->workorderService->createWorkorder(
                $request->validated()
            );
            
            return response()->json([
                'success' => true,
                'message' => '工单创建成功',
                'data' => $workorder
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '工单创建失败：' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * 工单详情
     */
    public function show(Workorder $workorder): Response
    {
        $workorder->load([
            'creator',
            'assignee',
            'category',
            'logs.user',
            'attachments.user'
        ]);
        
        return response()->view('workorders.show', compact('workorder'));
    }
}
```

#### 3. 请求验证

```php
// app/Http/Requests/Workorder/StoreWorkorderRequest.php
namespace App\Http\Requests\Workorder;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWorkorderRequest extends FormRequest
{
    /**
     * 确定用户是否有权限进行此请求
     */
    public function authorize(): bool
    {
        return true;
    }
    
    /**
     * 获取验证规则
     */
    public function rules(): array
    {
        return [
            'title' => 'nullable|string|max:255',
            'description' => 'required|string|min:10',
            'category_id' => 'required|exists:workorder_categories_simplified,id',
            'contact_name' => 'required|string|max:100',
            'contact_phone' => 'required|string|max:20',
            'contact_email' => 'nullable|email|max:100',
            'location' => 'required|string|max:255',
            'location_detail' => 'nullable|string|max:500',
            'campus' => 'required|in:old_campus,new_campus,asean_campus',
            'building' => 'required|string|max:100',
            'priority' => 'required|in:high,medium,low',
            'source' => 'required|in:phone,web,email,scene,other',
            'assignee_id' => 'nullable|exists:users,id',
            'appointment_time' => 'nullable|date|after:now',
            'attachments' => 'array',
            'attachments.*.file' => 'required|file|max:10240',
            'attachments.*.description' => 'nullable|string|max:255',
        ];
    }
    
    /**
     * 获取自定义错误消息
     */
    public function messages(): array
    {
        return [
            'description.required' => '问题描述不能为空',
            'description.min' => '问题描述至少需要10个字符',
            'category_id.required' => '请选择工单分类',
            'contact_name.required' => '请填写联系人姓名',
            'contact_phone.required' => '请填写联系电话',
            'location.required' => '请填写故障地点',
            'priority.required' => '请选择优先级',
        ];
    }
}
```

#### 4. 服务层开发

```php
// app/Services/WorkorderService.php
namespace App\Services;

use App\Models\Workorder;
use App\Models\WorkorderLog;
use App\Models\User;
use App\Notifications\WorkorderAssigned;
use App\Notifications\NewWorkorderCreated;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class WorkorderService
{
    /**
     * 创建工单
     */
    public function createWorkorder(array $data): Workorder
    {
        return DB::transaction(function () use ($data) {
            // 生成工单编号
            $category = WorkorderCategorySimplified::find($data['category_id']);
            $data['ticket_no'] = Workorder::generateTicketNo($category->ticket_prefix);
            $data['ticket_prefix'] = $category->ticket_prefix;
            $data['creator_id'] = auth()->id();
            $data['status'] = $data['assignee_id'] ? 'assigned' : 'pending';
            
            // 创建工单
            $workorder = Workorder::create($data);
            
            // 记录日志
            $this->logWorkorderAction($workorder, 'created', '工单创建');
            
            // 处理附件
            if (isset($data['attachments'])) {
                $this->handleAttachments($workorder, $data['attachments']);
            }
            
            // 发送通知
            if ($workorder->assignee_id) {
                $workorder->assignee->notify(new WorkorderAssigned($workorder));
            } else {
                $this->notifyWorkorderManagers($workorder);
            }
            
            return $workorder;
        });
    }
    
    /**
     * 分配工单
     */
    public function assignWorkorder(Workorder $workorder, User $assignee, string $remark = ''): bool
    {
        return DB::transaction(function () use ($workorder, $assignee, $remark) {
            $workorder->update([
                'assignee_id' => $assignee->id,
                'status' => 'assigned',
                'assigned_at' => now(),
            ]);
            
            $this->logWorkorderAction($workorder, 'assigned', $remark);
            
            $assignee->notify(new WorkorderAssigned($workorder));
            
            return true;
        });
    }
    
    /**
     * 记录工单操作日志
     */
    private function logWorkorderAction(Workorder $workorder, string $action, string $content = ''): void
    {
        WorkorderLog::create([
            'workorder_id' => $workorder->id,
            'user_id' => auth()->id(),
            'action' => $action,
            'content' => $content,
        ]);
    }
    
    /**
     * 处理附件上传
     */
    private function handleAttachments(Workorder $workorder, array $attachments): void
    {
        foreach ($attachments as $attachment) {
            $file = $attachment['file'];
            $description = $attachment['description'] ?? '';
            
            $path = $file->store('attachments', 'public');
            
            $workorder->attachments()->create([
                'user_id' => auth()->id(),
                'filename' => $file->hashName(),
                'original_name' => $file->getClientOriginalName(),
                'file_path' => $path,
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'description' => $description,
            ]);
        }
    }
}
```

## 前端开发指南

### Blade模板开发

#### 1. 模板继承

```php
<!-- resources/views/layouts/app.blade.php -->
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', '工单系统')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    @include('partials.navigation')
    
    <main class="container-fluid">
        @yield('content')
    </main>
    
    @include('partials.footer')
</body>
</html>
```

```php
<!-- resources/views/workorders/index.blade.php -->
@extends('layouts.app')

@section('title', '工单列表')

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5>工单列表</h5>
                <a href="{{ route('workorders.create') }}" class="btn btn-primary">
                    创建工单
                </a>
            </div>
            <div class="card-body">
                @include('workorders.filters')
                @include('workorders.table')
            </div>
        </div>
    </div>
</div>
@endsection
```

#### 2. 组件化开发

```php
<!-- resources/views/components/workorder-card.blade.php -->
@props([
    'workorder',
    'showActions' => true
])

<div class="card workorder-card {{ $workorder->priority === 'high' ? 'border-danger' : '' }}">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div>
            <span class="badge bg-primary">{{ $workorder->ticket_no }}</span>
            <span class="badge bg-{{ $workorder->priority === 'high' ? 'danger' : ($workorder->priority === 'medium' ? 'warning' : 'secondary') }}">
                {{ $workorder->priority_text }}
            </span>
        </div>
        <small class="text-muted">{{ $workorder->created_at->format('Y-m-d H:i') }}</small>
    </div>
    
    <div class="card-body">
        <h6 class="card-title">{{ $workorder->title ?? $workorder->description }}</h6>
        <p class="card-text text-truncate">{{ $workorder->description }}</p>
        
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <small class="text-muted">
                    创建者：{{ $workorder->creator->name }}
                    @if($workorder->assignee)
                        | 处理人：{{ $workorder->assignee->name }}
                    @endif
                </small>
            </div>
            
            @if($showActions)
                <div>
                    <a href="{{ route('workorders.show', $workorder) }}" class="btn btn-sm btn-outline-primary">
                        查看
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>
```

使用组件：
```php
<!-- 在视图中使用组件 -->
<x-workorder-card :workorder="$workorder" :show-actions="true" />
```

#### 3. JavaScript集成

```php
<!-- resources/views/workorders/create.blade.php -->
@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 工单分类联动
    const categorySelect = document.getElementById('category_id');
    const subcategorySelect = document.getElementById('subcategory_id');
    
    categorySelect.addEventListener('change', function() {
        const categoryId = this.value;
        
        if (categoryId) {
            fetch(`/api/workorders/subcategories?category_id=${categoryId}`)
                .then(response => response.json())
                .then(data => {
                    subcategorySelect.innerHTML = '<option value="">请选择子分类</option>';
                    data.data.forEach(item => {
                        const option = document.createElement('option');
                        option.value = item.id;
                        option.textContent = item.name;
                        subcategorySelect.appendChild(option);
                    });
                });
        } else {
            subcategorySelect.innerHTML = '<option value="">请选择子分类</option>';
        }
    });
    
    // 表单提交
    const form = document.getElementById('workorder-form');
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        fetch('{{ route("workorders.store") }}', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('工单创建成功！');
                window.location.href = data.data.redirect_url || '{{ route("workorders.index") }}';
            } else {
                alert('创建失败：' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('提交失败，请重试');
        });
    });
});
</script>
@endsection
```

### JavaScript模块开发

#### 1. ES6模块

```javascript
// resources/js/modules/workorder.js
export class WorkorderManager {
    constructor() {
        this.apiBaseUrl = '/api/workorders';
        this.init();
    }
    
    init() {
        this.bindEvents();
        this.loadWorkorders();
    }
    
    bindEvents() {
        document.addEventListener('click', (e) => {
            if (e.target.matches('.btn-assign')) {
                this.handleAssign(e);
            }
            if (e.target.matches('.btn-resolve')) {
                this.handleResolve(e);
            }
        });
    }
    
    async loadWorkorders(filters = {}) {
        try {
            const params = new URLSearchParams(filters);
            const response = await fetch(`${this.apiBaseUrl}?${params}`);
            const data = await response.json();
            
            this.renderWorkorderList(data.data);
        } catch (error) {
            console.error('加载工单列表失败:', error);
        }
    }
    
    async assignWorkorder(workorderId, assigneeId) {
        try {
            const response = await fetch(`${this.apiBaseUrl}/${workorderId}/assign`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    assignee_id: assigneeId
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showNotification('工单分配成功', 'success');
                this.loadWorkorders();
            } else {
                this.showNotification(data.message, 'error');
            }
        } catch (error) {
            console.error('分配工单失败:', error);
            this.showNotification('分配失败，请重试', 'error');
        }
    }
    
    renderWorkorderList(workorders) {
        const container = document.getElementById('workorder-list');
        container.innerHTML = workorders.map(workorder => `
            <div class="workorder-item" data-id="${workorder.id}">
                <h5>${workorder.ticket_no} - ${workorder.title}</h5>
                <p>${workorder.description}</p>
                <div class="actions">
                    <button class="btn btn-sm btn-primary btn-assign" data-id="${workorder.id}">
                        分配
                    </button>
                    <button class="btn btn-sm btn-info" onclick="viewWorkorder(${workorder.id})">
                        查看
                    </button>
                </div>
            </div>
        `).join('');
    }
    
    showNotification(message, type = 'info') {
        // 实现通知显示逻辑
        const notification = document.createElement('div');
        notification.className = `alert alert-${type} alert-dismissible fade show`;
        notification.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        document.getElementById('notifications-container').appendChild(notification);
        
        setTimeout(() => {
            notification.remove();
        }, 5000);
    }
}

// 初始化
document.addEventListener('DOMContentLoaded', () => {
    new WorkorderManager();
});
```

#### 2. 工具函数

```javascript
// resources/js/utils/helpers.js
export const helpers = {
    /**
     * 格式化日期
     */
    formatDate(dateString, format = 'YYYY-MM-DD HH:mm:ss') {
        const date = new Date(dateString);
        return date.toLocaleString('zh-CN');
    },
    
    /**
     * 格式化文件大小
     */
    formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    },
    
    /**
     * 防抖函数
     */
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    },
    
    /**
     * 节流函数
     */
    throttle(func, limit) {
        let inThrottle;
        return function() {
            const args = arguments;
            const context = this;
            if (!inThrottle) {
                func.apply(context, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    },
    
    /**
     * 生成UUID
     */
    generateUUID() {
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
            const r = Math.random() * 16 | 0;
            const v = c == 'x' ? r : (r & 0x3 | 0x8);
            return v.toString(16);
        });
    }
};
```

## API开发指南

### RESTful API设计

#### 1. 资源路由

```php
// routes/api.php
Route::apiResource('workorders', WorkorderApiController::class);

// 自定义路由
Route::prefix('workorders/{workorder}')->group(function () {
    Route::post('assign', 'WorkorderApiController@assign');
    Route::post('resolve', 'WorkorderApiController@resolve');
    Route::post('close', 'WorkorderApiController@close');
});
```

#### 2. 控制器开发

```php
// app/Http/Controllers/API/WorkorderApiController.php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Workorder\StoreWorkorderRequest;
use App\Models\Workorder;
use App\Http\Resources\WorkorderResource;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class WorkorderApiController extends Controller
{
    /**
     * 工单列表
     */
    public function index(Request $request): Response
    {
        $query = Workorder::with(['creator', 'assignee', 'category'])
            ->when($request->keyword, function ($q, $keyword) {
                return $q->where('title', 'like', "%{$keyword}%")
                       ->orWhere('description', 'like', "%{$keyword}%");
            })
            ->when($request->status, function ($q, $status) {
                return $q->where('status', $status);
            });
            
        $workorders = $query->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);
            
        return response()->json([
            'success' => true,
            'data' => WorkorderResource::collection($workorders),
            'meta' => [
                'current_page' => $workorders->currentPage(),
                'per_page' => $workorders->perPage(),
                'total' => $workorders->total(),
                'last_page' => $workorders->lastPage(),
            ]
        ]);
    }
    
    /**
     * 创建工单
     */
    public function store(StoreWorkorderRequest $request): Response
    {
        try {
            $workorder = $this->workorderService->createWorkorder(
                $request->validated()
            );
            
            return response()->json([
                'success' => true,
                'message' => '工单创建成功',
                'data' => new WorkorderResource($workorder)
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '工单创建失败',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * 工单详情
     */
    public function show(Workorder $workorder): Response
    {
        $workorder->load([
            'creator',
            'assignee',
            'category',
            'logs.user',
            'attachments.user'
        ]);
        
        return response()->json([
            'success' => true,
            'data' => new WorkorderResource($workorder)
        ]);
    }
}
```

#### 3. API资源

```php
// app/Http/Resources/WorkorderResource.php
namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkorderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ticket_no' => $this->ticket_no,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
            'status_text' => $this->status_text,
            'priority' => $this->priority,
            'priority_text' => $this->priority_text,
            'source' => $this->source,
            'source_text' => $this->source_text,
            'location' => $this->location,
            'location_detail' => $this->location_detail,
            'contact_name' => $this->contact_name,
            'contact_phone' => $this->contact_phone,
            'contact_email' => $this->contact_email,
            'creator' => new UserResource($this->whenLoaded('creator')),
            'assignee' => new UserResource($this->whenLoaded('assignee')),
            'category' => new WorkorderCategoryResource($this->whenLoaded('category')),
            'logs' => WorkorderLogResource::collection($this->whenLoaded('logs')),
            'attachments' => WorkorderAttachmentResource::collection($this->whenLoaded('attachments')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'assigned_at' => $this->assigned_at,
            'started_at' => $this->started_at,
            'resolved_at' => $this->resolved_at,
            'completed_at' => $this->completed_at,
        ];
    }
}
```

#### 4. API版本控制

```php
// routes/api.php
Route::prefix('v1')->group(function () {
    Route::apiResource('workorders', WorkorderApiController::class);
});

Route::prefix('v2')->group(function () {
    Route::apiResource('workorders', WorkorderV2Controller::class);
});
```

#### 5. API认证

```php
// app/Http/Controllers/API/AuthController.php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * 用户登录
     */
    public function login(Request $request): Response
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);
        
        $user = User::where('email', $request->email)->first();
        
        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['邮箱或密码错误'],
            ]);
        }
        
        $token = $user->createToken('api-token')->plainTextToken;
        
        return response()->json([
            'success' => true,
            'data' => [
                'user' => $user,
                'token' => $token
            ]
        ]);
    }
    
    /**
     * 用户登出
     */
    public function logout(Request $request): Response
    {
        $request->user()->currentAccessToken()->delete();
        
        return response()->json([
            'success' => true,
            'message' => '登出成功'
        ]);
    }
}
```

## 数据库开发

### 迁移文件

#### 1. 创建表迁移

```php
// database/migrations/2024_11_17_000004_create_workorders_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('workorders', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_prefix', 10)->default('WO');
            $table->string('ticket_no', 20);
            $table->string('title')->nullable();
            $table->text('description');
            $table->text('failure_description')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedBigInteger('creator_id');
            $table->unsignedBigInteger('assignee_id')->nullable();
            $table->unsignedBigInteger('department_id')->nullable();
            $table->string('department_name')->nullable();
            $table->string('contact_name');
            $table->string('contact_phone');
            $table->string('contact_email')->nullable();
            $table->string('location');
            $table->text('location_detail')->nullable();
            $table->string('campus')->nullable();
            $table->string('building')->nullable();
            $table->enum('source', ['phone', 'web', 'email', 'scene', 'other'])->default('web');
            $table->enum('priority', ['high', 'medium', 'low'])->default('medium');
            $table->enum('status', ['pending', 'assigned', 'processing', 'resolved', 'completed', 'verifying', 'closed', 'rejected'])->default('pending');
            $table->integer('time_limit_hours')->nullable();
            $table->datetime('assigned_at')->nullable();
            $table->datetime('started_at')->nullable();
            $table->datetime('resolved_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->datetime('closed_at')->nullable();
            $table->datetime('expected_complete_at')->nullable();
            $table->integer('processing_duration')->nullable();
            $table->text('solution')->nullable();
            $table->text('remarks')->nullable();
            $table->text('materials_usage')->nullable();
            $table->boolean('need_visit')->default(false);
            $table->boolean('is_emergency')->default(false);
            $table->boolean('phone_assisted')->default(false);
            $table->timestamps();
            $table->softDeletes();
            
            // 索引
            $table->unique(['ticket_prefix', 'ticket_no']);
            $table->index('ticket_no');
            $table->index(['status', 'priority']);
            $table->index(['creator_id', 'created_at']);
            $table->index(['assignee_id', 'status']);
            $table->index('category_id');
            $table->index('campus');
            $table->index('building');
            $table->index('deleted_at');
            
            // 外键
            $table->foreign('category_id')->references('id')->on('workorder_categories_simplified')->onDelete('set null');
            $table->foreign('creator_id')->references('id')->on('users')->onDelete('restrict');
            $table->foreign('assignee_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('department_id')->references('id')->on('departments')->onDelete('set null');
        });
    }
    
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workorders');
    }
};
```

#### 2. 修改表迁移

```php
// database/migrations/2024_11_18_000003_update_workorders_table_add_fields.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workorders', function (Blueprint $table) {
            $table->text('other_reason')->nullable()->after('materials_usage');
            $table->string('custom_source')->nullable()->after('source');
            $table->index('custom_source');
        });
    }
    
    public function down(): void
    {
        Schema::table('workorders', function (Blueprint $table) {
            $table->dropIndex(['custom_source']);
            $table->dropColumn(['other_reason', 'custom_source']);
        });
    }
};
```

### 数据填充

#### 1. 基础数据填充

```php
// database/seeders/WorkorderCategorySeeder.php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WorkorderCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => '网络故障',
                'parent_id' => null,
                'ticket_prefix' => 'N',
                'default_hours' => 24,
                'color' => '#ff6b6b',
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => '拨号失败',
                'parent_id' => '1',
                'ticket_prefix' => 'N',
                'default_hours' => 8,
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // 更多分类数据...
        ];
        
        DB::table('workorder_categories_simplified')->insert($categories);
    }
}
```

#### 2. 测试数据填充

```php
// database/seeders/WorkorderSeeder.php
namespace Database\Seeders;

use App\Models\User;
use App\Models\Workorder;
use App\Models\WorkorderCategorySimplified;
use Illuminate\Database\Seeder;

class WorkorderSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();
        $categories = WorkorderCategorySimplified::all();
        
        foreach (range(1, 50) as $index) {
            Workorder::create([
                'ticket_no' => Workorder::generateTicketNo(),
                'description' => "测试工单描述 #{$index}",
                'category_id' => $categories->random()->id,
                'creator_id' => $users->random()->id,
                'assignee_id' => $users->random()->id,
                'contact_name' => '测试联系人',
                'contact_phone' => '13800138000',
                'location' => '老校区1教',
                'priority' => ['high', 'medium', 'low'][array_rand(['high', 'medium', 'low'])],
                'status' => ['pending', 'assigned', 'processing', 'resolved', 'completed'][array_rand(['pending', 'assigned', 'processing', 'resolved', 'completed'])],
            ]);
        }
    }
}
```

### 模型工厂

```php
// database/factories/WorkorderFactory.php
namespace Database\Factories;

use App\Models\User;
use App\Models\WorkorderCategorySimplified;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Workorder>
 */
class WorkorderFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'ticket_no' => function () {
                return \App\Models\Workorder::generateTicketNo();
            },
            'description' => fake()->sentence(10),
            'category_id' => WorkorderCategorySimplified::factory(),
            'creator_id' => User::factory(),
            'assignee_id' => User::factory(),
            'contact_name' => fake()->name,
            'contact_phone' => fake()->phoneNumber,
            'location' => fake()->address,
            'priority' => fake()->randomElement(['high', 'medium', 'low']),
            'status' => fake()->randomElement(['pending', 'assigned', 'processing']),
        ];
    }
}
```

## 测试指南

### 单元测试

#### 1. 模型测试

```php
// tests/Unit/WorkorderTest.php
namespace Tests\Unit;

use App\Models\User;
use App\Models\Workorder;
use Tests\TestCase;

class WorkorderTest extends TestCase
{
    /**
     * 测试工单编号生成
     */
    public function test_ticket_no_generation(): void
    {
        $ticketNo = Workorder::generateTicketNo('N');
        
        $this->assertStringStartsWith('N', $ticketNo);
        $this->assertMatchesRegularExpression('/^N\d{8}\d{4}$/', $ticketNo);
    }
    
    /**
     * 测试工单状态流转
     */
    public function test_status_transition(): void
    {
        $workorder = Workorder::factory()->create(['status' => 'pending']);
        
        // 分配工单
        $workorder->update(['status' => 'assigned', 'assignee_id' => User::factory()->create()->id]);
        $this->assertEquals('assigned', $workorder->status);
        
        // 开始处理
        $workorder->update(['status' => 'processing', 'started_at' => now()]);
        $this->assertEquals('processing', $workorder->status);
        
        // 解决工单
        $workorder->update(['status' => 'resolved', 'resolved_at' => now()]);
        $this->assertEquals('resolved', $workorder->status);
    }
    
    /**
     * 测试工单关系
     */
    public function test_workorder_relationships(): void
    {
        $creator = User::factory()->create();
        $assignee = User::factory()->create();
        
        $workorder = Workorder::factory()->create([
            'creator_id' => $creator->id,
            'assignee_id' => $assignee->id,
        ]);
        
        $this->assertInstanceOf(User::class, $workorder->creator);
        $this->assertEquals($creator->id, $workorder->creator->id);
        
        $this->assertInstanceOf(User::class, $workorder->assignee);
        $this->assertEquals($assignee->id, $workorder->assignee->id);
    }
}
```

#### 2. 服务测试

```php
// tests/Unit/WorkorderServiceTest.php
namespace Tests\Unit;

use App\Models\User;
use App\Models\Workorder;
use App\Services\WorkorderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkorderServiceTest extends TestCase
{
    use RefreshDatabase;
    
    private WorkorderService $service;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(WorkorderService::class);
    }
    
    /**
     * 测试创建工单
     */
    public function test_create_workorder(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        
        $data = [
            'description' => '测试工单描述',
            'category_id' => 1,
            'contact_name' => '测试联系人',
            'contact_phone' => '13800138000',
            'location' => '测试地点',
            'priority' => 'medium',
        ];
        
        $workorder = $this->service->createWorkorder($data);
        
        $this->assertInstanceOf(Workorder::class, $workorder);
        $this->assertEquals($data['description'], $workorder->description);
        $this->assertEquals($user->id, $workorder->creator_id);
        $this->assertNotNull($workorder->ticket_no);
    }
    
    /**
     * 测试分配工单
     */
    public function test_assign_workorder(): void
    {
        $workorder = Workorder::factory()->create(['status' => 'pending']);
        $assignee = User::factory()->create();
        
        $result = $this->service->assignWorkorder($workorder, $assignee);
        
        $this->assertTrue($result);
        
        $workorder->refresh();
        $this->assertEquals('assigned', $workorder->status);
        $this->assertEquals($assignee->id, $workorder->assignee_id);
        $this->assertNotNull($workorder->assigned_at);
    }
}
```

### 功能测试

#### 1. 控制器测试

```php
// tests/Feature/WorkorderControllerTest.php
namespace Tests\Feature;

use App\Models\User;
use App\Models\Workorder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkorderControllerTest extends TestCase
{
    use RefreshDatabase;
    
    /**
     * 测试工单列表页面
     */
    public function test_workorder_index_page(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        
        Workorder::factory()->count(5)->create(['creator_id' => $user->id]);
        
        $response = $this->get(route('workorders.index'));
        
        $response->assertStatus(200);
        $response->assertViewIs('workorders.index');
        $response->assertViewHas('workorders');
    }
    
    /**
     * 测试创建工单
     */
    public function test_create_workorder(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        
        $data = [
            'description' => '测试工单描述',
            'category_id' => 1,
            'contact_name' => '测试联系人',
            'contact_phone' => '13800138000',
            'location' => '测试地点',
            'priority' => 'medium',
        ];
        
        $response = $this->post(route('workorders.store'), $data);
        
        $response->assertRedirect();
        $this->assertDatabaseHas('workorders', [
            'description' => $data['description'],
            'creator_id' => $user->id,
        ]);
    }
    
    /**
     * 测试工单详情页面
     */
    public function test_workorder_show_page(): void
    {
        $user = User::factory()->create();
        $workorder = Workorder::factory()->create(['creator_id' => $user->id]);
        
        $this->actingAs($user);
        
        $response = $this->get(route('workorders.show', $workorder));
        
        $response->assertStatus(200);
        $response->assertViewIs('workorders.show');
        $response->assertViewHas('workorder');
        $response->assertSee($workorder->description);
    }
}
```

#### 2. API测试

```php
// tests/Feature/WorkorderApiTest.php
namespace Tests\Feature;

use App\Models\User;
use App\Models\Workorder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WorkorderApiTest extends TestCase
{
    use RefreshDatabase;
    
    /**
     * 测试获取工单列表API
     */
    public function test_get_workorders_api(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        
        Workorder::factory()->count(5)->create();
        
        $response = $this->getJson('/api/workorders');
        
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                '*' => [
                    'id',
                    'ticket_no',
                    'description',
                    'status',
                    'priority',
                    'created_at',
                ]
            ]
        ]);
    }
    
    /**
     * 测试创建工单API
     */
    public function test_create_workorder_api(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        
        $data = [
            'description' => 'API测试工单',
            'category_id' => 1,
            'contact_name' => 'API测试',
            'contact_phone' => '13800138000',
            'location' => 'API测试地点',
            'priority' => 'high',
        ];
        
        $response = $this->postJson('/api/workorders', $data);
        
        $response->assertStatus(201);
        $response->assertJson([
            'success' => true,
            'message' => '工单创建成功'
        ]);
        
        $this->assertDatabaseHas('workorders', [
            'description' => $data['description'],
            'creator_id' => $user->id,
        ]);
    }
}
```

### 测试数据管理

#### 1. 测试数据库

```php
// tests/CreatesApplication.php
namespace Tests;

use Illuminate\Contracts\Console\Kernel;

trait CreatesApplication
{
    /**
     * Creates the application.
     */
    public function createApplication(): \Illuminate\Foundation\Application
    {
        $app = require __DIR__.'/../bootstrap/app.php';
        
        $app->make(Kernel::class)->bootstrap();
        
        return $app;
    }
}
```

#### 2. 测试环境配置

```env
# phpunit.xml
<env name="APP_ENV" value="testing"/>
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
<env name="CACHE_DRIVER" value="array"/>
<env name="SESSION_DRIVER" value="array"/>
<env name="QUEUE_CONNECTION" value="sync"/>
```

### 运行测试

```bash
# 运行所有测试
./vendor/bin/phpunit

# 运行特定测试文件
./vendor/bin/phpunit tests/Unit/WorkorderTest.php

# 运行特定测试方法
./vendor/bin/phpunit --filter test_ticket_no_generation

# 生成测试覆盖率报告
./vendor/bin/phpunit --coverage-html coverage
```

## 性能优化

### 数据库优化

#### 1. 查询优化

```php
// 避免 N+1 查询
$workorders = Workorder::with(['creator', 'assignee', 'category'])->get();

// 使用索引优化查询
$workorders = Workorder::where('status', 'pending')
    ->where('priority', 'high')
    ->orderBy('created_at', 'desc')
    ->limit(50)
    ->get();

// 使用查询作用域
// 在模型中定义作用域
public function scopeHighPriority($query)
{
    return $query->where('priority', 'high');
}

public function scopePending($query)
{
    return $query->where('status', 'pending');
}

// 使用作用域
$workorders = Workorder::highPriority()->pending()->get();
```

#### 2. 缓存策略

```php
// 缓存工单分类
$categories = Cache::remember('workorder_categories', 3600, function () {
    return WorkorderCategorySimplified::with('children')->get();
});

// 缓存用户统计
$userStats = Cache::remember("user_stats_{$userId}", 1800, function () use ($userId) {
    return [
        'total_workorders' => Workorder::where('creator_id', $userId)->count(),
        'completed_workorders' => Workorder::where('creator_id', $userId)
            ->where('status', 'completed')->count(),
    ];
});

// 清除缓存
Cache::forget('workorder_categories');
Cache::forget("user_stats_{$userId}");
```

### 前端优化

#### 1. 资源优化

```javascript
// 延迟加载
const loadWorkorders = async (page = 1) => {
    const response = await fetch(`/api/workorders?page=${page}`);
    const data = await response.json();
    return data;
};

// 防抖搜索
const debouncedSearch = debounce(async (keyword) => {
    const results = await searchWorkorders(keyword);
    renderSearchResults(results);
}, 300);

// 图片懒加载
const lazyImages = document.querySelectorAll('img[data-src]');
const imageObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            const img = entry.target;
            img.src = img.dataset.src;
            img.removeAttribute('data-src');
            imageObserver.unobserve(img);
        }
    });
});

lazyImages.forEach(img => imageObserver.observe(img));
```

#### 2. 代码分割

```javascript
// 动态导入模块
const loadWorkorderModule = async () => {
    const module = await import('./modules/workorder.js');
    return module.default;
};

// 条件加载
if (document.querySelector('.workorder-form')) {
    import('./modules/workorder-form.js').then(module => {
        module.init();
    });
}
```

### 应用优化

#### 1. 配置优化

```php
// config/app.php
'providers' => [
    // 只启