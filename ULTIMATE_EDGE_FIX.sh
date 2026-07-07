#!/bin/bash

echo "=== Microsoft Edge 终极兼容性修复 ==="

# 1. 修复 .env 文件
echo "1. 修复环境配置..."
if [ -f .env ]; then
    # 备份原文件
    cp .env .env.backup
    
    # 修复 APP_URL 为 HTTPS
    if grep -q "APP_URL=http://" .env; then
        sed -i 's|APP_URL=http://|APP_URL=https://|g' .env
        echo "✓ 修复了 APP_URL 为 HTTPS"
    fi
    
    # 添加 ASSET_URL
    if ! grep -q "ASSET_URL=" .env; then
        echo "ASSET_URL=https://work.66107166.xyz" >> .env
        echo "✓ 添加了 ASSET_URL"
    fi
else
    echo "❌ .env 文件不存在"
    exit 1
fi

# 2. 修复布局文件中的资源链接
echo "2. 修复资源链接..."

# 创建本地资源副本
mkdir -p public/assets/css
mkdir -p public/assets/js
mkdir -p public/assets/fonts

# 下载关键资源到本地
echo "下载关键资源到本地..."
if command -v curl >/dev/null 2>&1; then
    # 下载 Bootstrap CSS
    curl -s "https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.0/css/bootstrap.min.css" -o public/assets/css/bootstrap.min.css
    
    # 下载 Bootstrap JS
    curl -s "https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.0/js/bootstrap.bundle.min.js" -o public/assets/js/bootstrap.bundle.min.js
    
    # 下载 Font Awesome CSS
    curl -s "https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" -o public/assets/css/fontawesome.min.css
    
    # 下载 Axios
    curl -s "https://cdnjs.cloudflare.com/ajax/libs/axios/1.6.0/axios.min.js" -o public/assets/js/axios.min.js
    
    # 下载 jQuery
    curl -s "https://code.jquery.com/jquery-3.6.0.min.js" -o public/assets/js/jquery.min.js
    
    echo "✓ 关键资源已下载到本地"
else
    echo "❌ curl 不可用，跳过资源下载"
fi

# 3. 创建新的布局文件
echo "3. 创建兼容性布局文件..."
cat > resources/views/layouts/edge-compatible.blade.php << 'EOF'
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', '校园网工单系统') - 校园网工单系统</title>
    
    <!-- 本地资源 -->
    <link href="{{ asset('assets/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/fontawesome.min.css') }}" rel="stylesheet">
    
    <!-- 内联CSS以避免Mixed Content -->
    <style>
        .sidebar {
            min-height: 100vh;
            background-color: #343a40;
        }
        .sidebar .nav-link {
            color: #fff;
            padding: 0.75rem 1rem;
        }
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background-color: #495057;
            color: #fff;
        }
        .sidebar .nav-link i {
            margin-right: 0.5rem;
        }
        .main-content {
            padding: 2rem;
        }
        .status-badge {
            font-size: 0.875rem;
        }
        .priority-high {
            color: #dc3545;
        }
        .priority-medium {
            color: #ffc107;
        }
        .priority-low {
            color: #28a745;
        }
        .card {
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            border: 1px solid rgba(0, 0, 0, 0.125);
        }
        .table th {
            border-top: none;
            font-weight: 600;
        }
        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
        .navbar-brand {
            font-weight: 600;
        }
        .footer {
            background-color: #f8f9fa;
            padding: 1rem 0;
            margin-top: auto;
        }
        
        /* Edge 兼容性修复 */
        .edge-warning {
            position: fixed;
            top: 10px;
            right: 10px;
            z-index: 9999;
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            padding: 10px;
            border-radius: 5px;
            max-width: 300px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        @media (max-width: 768px) {
            .main-content {
                padding: 1rem;
            }
            .sidebar {
                position: fixed;
                top: 0;
                left: -250px;
                width: 250px;
                height: 100vh;
                z-index: 1050;
                transition: left 0.3s ease;
            }
            .sidebar.show {
                left: 0;
            }
            .sidebar-overlay {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background-color: rgba(0, 0, 0, 0.5);
                z-index: 1040;
                display: none;
            }
            .sidebar-overlay.show {
                display: block;
            }
            .mobile-sidebar-toggle {
                display: block;
                position: fixed;
                top: 70px;
                left: 10px;
                z-index: 1030;
                background-color: #343a40;
                color: white;
                border: none;
                border-radius: 0.25rem;
                padding: 0.5rem;
                font-size: 1rem;
            }
        }
        
        @media (min-width: 769px) {
            .mobile-sidebar-toggle {
                display: none;
            }
            .sidebar-overlay {
                display: none !important;
            }
        }
    </style>
    
    @yield('styles')
</head>
<body class="d-flex flex-column min-vh-100">
    <!-- Edge 兼容性警告 -->
    <div id="edgeWarning" class="edge-warning" style="display: none;">
        <h6>🛡️ Edge 浏览器兼容性提示</h6>
        <p>检测到您正在使用 Edge 浏览器。为确保最佳体验：</p>
        <ul>
            <li>请将跟踪防护设置为"平衡"模式</li>
            <li>或将此网站添加到例外列表</li>
            <li>清除浏览器缓存 (Ctrl+Shift+Delete)</li>
        </ul>
        <button type="button" class="btn btn-sm btn-secondary" onclick="document.getElementById('edgeWarning').style.display='none'">
            知道了
        </button>
    </div>

    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="{{ route('workorders.index') }}">
                <i class="fas fa-tools"></i> 校园网工单系统
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('workorders.*') ? 'active' : '' }}" 
                           href="{{ route('workorders.index') }}">
                            <i class="fas fa-list"></i> 工单管理
                        </a>
                    </li>
                    
                    @if(auth()->user()->canManageDepartments())
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('locations.*') ? 'active' : '' }}"
                           href="{{ route('locations.index') }}">
                            <i class="fas fa-map-marker-alt"></i> 地址管理
                        </a>
                    </li>
                    @endif
                    
                    @if(auth()->user()->canManageWorkorderTypes())
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('workorder-categories.*') ? 'active' : '' }}"
                           href="{{ route('workorder-categories.index') }}">
                            <i class="fas fa-sitemap"></i> 工单分类
                        </a>
                    </li>
                    @endif
                    
                    @if(auth()->user()->isAdmin())
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}" 
                           href="{{ route('users.index') }}">
                            <i class="fas fa-users"></i> 用户管理
                        </a>
                    </li>
                    @endif
                </ul>
                
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('logout.get') }}"
                           data-method="post"
                           onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                            <i class="fas fa-sign-out-alt"></i> {{ auth()->user()->name }}
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="d-flex flex-grow-1">
        <!-- Sidebar -->
        @if(auth()->check())
        <nav class="sidebar col-md-3 col-lg-2 d-md-block" id="sidebar">
            <div class="position-sticky pt-3">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('workorders.index') ? 'active' : '' }}" 
                           href="{{ route('workorders.index') }}">
                            <i class="fas fa-list"></i> 工单列表
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('workorders.create') ? 'active' : '' }}"
                           href="{{ route('workorders.create') }}">
                            <i class="fas fa-plus"></i> 创建工单
                        </a>
                    </li>
                </ul>
            </div>
        </nav>
        @endif

        <!-- Main Content Area -->
        <main class="main-content col-md-9 ms-sm-auto col-lg-10 px-md-4">
            @yield('content')
        </main>
    </div>

    <!-- Footer -->
    <footer class="footer mt-auto py-3 bg-light">
        <div class="container">
            <div class="text-center">
                <span class="text-muted">© {{ date('Y') }} 校园网工单系统 - 版本 1.0.0</span>
            </div>
        </div>
    </footer>

    <!-- Logout Form -->
    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
        @csrf
    </form>

    <!-- 内联JavaScript以避免外部资源问题 -->
    <script>
        // 检测Edge浏览器
        function isEdgeBrowser() {
            return /Edge/.test(navigator.userAgent);
        }
        
        // 显示Edge警告
        function showEdgeWarning() {
            if (isEdgeBrowser()) {
                document.getElementById('edgeWarning').style.display = 'block';
            }
        }
        
        // 基础功能
        function initBasicFunctionality() {
            // 移动端侧边栏
            const toggleBtn = document.getElementById('mobileSidebarToggle');
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            
            if (toggleBtn && sidebar) {
                toggleBtn.addEventListener('click', function() {
                    sidebar.classList.toggle('show');
                    if (overlay) {
                        overlay.classList.toggle('show');
                    }
                });
            }
            
            if (overlay) {
                overlay.addEventListener('click', function() {
                    sidebar.classList.remove('show');
                    overlay.classList.remove('show');
                });
            }
        }
        
        // 页面加载完成后初始化
        document.addEventListener('DOMContentLoaded', function() {
            showEdgeWarning();
            initBasicFunctionality();
        });
    </script>
    
    @yield('scripts')
</body>
</html>
EOF

echo "✓ 创建了 Edge 兼容性布局文件"

# 4. 创建控制器方法切换布局
echo "4. 创建布局切换逻辑..."

# 检测Edge浏览器的中间件
cat > app/Http/Middleware/DetectEdgeBrowser.php << 'EOF'
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class DetectEdgeBrowser
{
    public function handle(Request $request, Closure $next)
    {
        $userAgent = $request->header('User-Agent', '');
        
        // 检测Edge浏览器
        if (strpos($userAgent, 'Edge/') !== false || strpos($userAgent, 'Edg/') !== false) {
            // 为Edge浏览器使用兼容性布局
            view()->share('useEdgeLayout', true);
        } else {
            view()->share('useEdgeLayout', false);
        }
        
        return $next($request);
    }
}
EOF

echo "✓ 创建了Edge检测中间件"

# 5. 更新主控制器
echo "5. 更新控制器以支持动态布局..."

# 备份原控制器
cp app/Http/Controllers/Controller.php app/Http/Controllers/Controller.php.backup

# 添加布局选择方法
cat >> app/Http/Controllers/Controller.php << 'EOF'

    /**
     * 获取布局文件
     */
    protected function getLayout()
    {
        if (view()->shared('useEdgeLayout', false)) {
            return 'layouts.edge-compatible';
        }
        
        return 'layouts.app';
    }
EOF

echo "✓ 更新了控制器基础类"

# 6. 创建简化版工单列表页面
echo "6. 创建简化版工单页面..."

# 创建不依赖复杂JavaScript的工单列表
cat > resources/views/workorders/simple-index.blade.php << 'EOF'
@extends(\$useEdgeLayout ? 'layouts.edge-compatible' : 'layouts.app')

@section('title', '工单列表')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">工单列表</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        @if(!\$useEdgeLayout)
        <a href="{{ route('workorders.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> 创建工单
        </a>
        @else
        <a href="{{ route('workorders.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> 创建工单
        </a>
        @endif
    </div>
</div>

<!-- 简化的搜索表单 -->
<div class="card mb-4">
    <div class="card-header">
        <h6 class="mb-0">搜索筛选</h6>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('workorders.index') }}">
            <div class="row g-3">
                <div class="col-md-3">
                    <label for="keyword" class="form-label">关键词</label>
                    <input type="text" class="form-control" id="keyword" name="keyword"
                           value="{{ request('keyword') }}" placeholder="工单号、描述、联系人">
                </div>
                <div class="col-md-2">
                    <label for="status" class="form-label">状态</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">请选择</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}">待处理</option>
                        <option value="assigned" {{ request('status') == 'assigned' ? 'selected' : '' }}">已分配</option>
                        <option value="processing" {{ request('status') == 'processing' ? 'selected' : '' }}">处理中</option>
                        <option value="resolved" {{ request('status') == 'resolved' ? 'selected' : '' }}">已解决</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="priority" class="form-label">优先级</label>
                    <select class="form-select" id="priority" name="priority">
                        <option value="">全部优先级</option>
                        <option value="high" {{ request('priority') == 'high' ? 'selected' : '' }}">高</option>
                        <option value="medium" {{ request('priority') == 'medium' ? 'selected' : '' }}">中</option>
                        <option value="low" {{ request('priority') == 'low' ? 'selected' : '' }}">低</option>
                    </select>
                </div>
            </div>
            <div class="row g-3 mt-3">
                <div class="col-md-8">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="fas fa-search"></i> 搜索
                    </button>
                    <a href="{{ route('workorders.index') }}" class="btn btn-secondary">
                        <i class="fas fa-redo"></i> 重置
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- 工单列表 -->
<div class="card">
    <div class="card-body">
        @forelse(\$workorders as \$workorder)
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>工单号</th>
                        <th>地址</th>
                        <th>类型+问题描述</th>
                        <th>报修人</th>
                        <th>联系方式</th>
                        <th>优先级</th>
                        <th>状态</th>
                        <th>处理人</th>
                        <th>创建历时</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach(\$workorders as \$workorder)
                    <tr>
                        <td>
                            <a href="{{ route('workorders.show', \$workorder->id) }}"
                               class="text-decoration-none">
                                {{ \$workorder->ticket_no }}
                            </a>
                            @if(\$workorder->is_emergency)
                            <i class="fas fa-exclamation-triangle text-danger" title="紧急工单"></i>
                            @endif
                        </td>
                        <td>
                            <small>
                                @if(\$workorder->campus)
                                    {{ \App\Models\Location::CAMPUSES[\$workorder->campus] ?? \$workorder->campus }}
                                @endif
                                @if(\$workorder->building)
                                    {{ \$workorder->building }}
                                @endif
                            </small>
                        </td>
                        <td>
                            @if(\$workorder->category)
                                <span class="badge bg-secondary me-1">{{ \$workorder->category->name }}</span>
                            @endif
                            <a href="{{ route('workorders.show', \$workorder->id) }}"
                               class="text-decoration-none">
                                {{ Str::limit(\$workorder->description, 30) }}
                            </a>
                        </td>
                        <td>{{ \$workorder->contact_name }}</td>
                        <td>{{ \$workorder->contact_phone }}</td>
                        <td>
                            <span class="badge priority-{{ \$workorder->priority }}">
                                {{ \$workorder->priority_text }}
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-{{ \$workorder->status == 'resolved' ? 'success' : (\$workorder->status == 'pending' ? 'warning' : 'info') }}">
                                {{ \$workorder->status_text }}
                            </span>
                        </td>
                        <td>{{ \$workorder->assignee_name }}</td>
                        <td>
                            <small>
                                {{ \$workorder->created_duration }}
                            </small>
                        </td>
                        <td>
                            @if(!\$useEdgeLayout)
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('workorders.show', \$workorder->id) }}" 
                                   class="btn btn-outline-primary" title="查看">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </div>
                            @else
                            <a href="{{ route('workorders.show', \$workorder->id) }}" 
                                   class="btn btn-outline-primary" title="查看">
                                    <i class="fas fa-eye"></i>
                                </a>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @empty
        <div class="text-center py-4">
            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
            <h5 class="text-muted">暂无工单</h5>
            <p class="text-muted">
                <a href="{{ route('workorders.create') }}" class="btn btn-primary">
                    创建第一个工单
                </a>
            </p>
        </div>
        @endforelse
    </div>
</div>

<!-- 分页 -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-center mt-3">
    <div class="text-muted mb-2 mb-md-0">
        显示 {{ \$workorders->firstItem() }} - {{ \$workorders->lastItem() }}
        共 {{ \$workorders->total() }} 条记录
    </div>
    <div class="d-flex justify-content-center">
        {{ \$workorders->appends(request()->query())->links() }}
    </div>
</div>
@endsection
EOF

echo "✓ 创建了简化版工单列表页面"

echo ""
echo "=== 修复完成 ==="
echo "请按以下步骤操作："
echo "1. 运行: php artisan cache:clear"
echo "2. 运行: php artisan config:clear"
echo "3. 在 app/Http/Kernel.php 中注册 DetectEdgeBrowser 中间件"
echo "4. 在 WorkorderController 中使用 \$this->getLayout() 方法"
echo "5. 清除浏览器缓存并测试"
echo ""
echo "Edge 浏览器用户请："
echo "- 将跟踪防护设置为'平衡'模式"
echo "- 清除浏览器缓存 (Ctrl+Shift+Delete)"
echo "- 访问 https://work.66107166.xyz/workorders"