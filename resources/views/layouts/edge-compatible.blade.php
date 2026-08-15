<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', '工单管理系统') - 工单管理系统</title>
    
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
            <a class="navbar-brand" href="{{ \App\Helpers\UrlHelper::relative_url('/workorders') }}">
                <i class="fas fa-tools"></i> 工单管理系统
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('workorders.*') ? 'active' : '' }}"
                           href="{{ \App\Helpers\UrlHelper::relative_url('/workorders') }}">
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
                        <a class="nav-link" href="{{ route('logout') }}"
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
                           href="{{ \App\Helpers\UrlHelper::relative_url('/workorders') }}">
                            <i class="fas fa-list"></i> 工单列表
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('workorders.create') ? 'active' : '' }}"
                           href="{{ \App\Helpers\UrlHelper::relative_url('/workorders/create') }}">
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
                <span class="text-muted">© {{ date('Y') }} 工单管理系统 - 版本 1.0.0</span>
            </div>
        </div>
    </footer>

    <!-- Logout Form -->
    <form id="logout-form" action="/logout" method="POST" style="display: none;">
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
