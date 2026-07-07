<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', '校园网工单系统') - 校园网工单系统</title>
    
    <!-- Bootstrap CSS -->
    <link href="{{ asset('assets/css/bootstrap.min.css') }}" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="{{ asset('assets/css/fontawesome.min.css') }}" rel="stylesheet">
    <!-- Custom CSS -->
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
        
        /* 移动端优化样式 */
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
            
            .table-responsive {
                font-size: 0.875rem;
            }
            
            .btn-group .btn {
                padding: 0.25rem 0.4rem;
                font-size: 0.75rem;
            }
            
            .card-body {
                padding: 1rem;
            }
            
            .modal-dialog {
                margin: 0.5rem;
                max-width: calc(100% - 1rem);
            }
            
            .navbar-brand {
                font-size: 1rem;
            }
            
            .breadcrumb {
                font-size: 0.875rem;
                margin-bottom: 1rem;
            }
            
            .d-flex.justify-content-between {
                flex-direction: column;
                gap: 1rem;
            }
            
            .btn-toolbar {
                width: 100%;
                justify-content: flex-end;
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
        
        /* 表格移动端优化 */
        @media (max-width: 576px) {
            .table-responsive {
                border: none;
            }
            
            .table {
                font-size: 0.8rem;
            }
            
            .table th,
            .table td {
                padding: 0.5rem;
                vertical-align: middle;
            }
            
            .btn-group-sm .btn {
                padding: 0.2rem 0.3rem;
                font-size: 0.7rem;
            }
            
            .badge {
                font-size: 0.7rem;
            }
        }
        
        /* 表单移动端优化 */
        @media (max-width: 768px) {
            .form-row {
                margin-bottom: 1rem;
            }
            
            .form-label {
                font-size: 0.875rem;
                margin-bottom: 0.25rem;
            }
            
            .form-control,
            .form-select {
                font-size: 0.875rem;
            }
            
            .modal-body {
                padding: 1rem;
            }
            
            .modal-footer {
                padding: 0.75rem 1rem;
            }
            
            .btn {
                font-size: 0.875rem;
            }
        }
        
        /* 附件预览样式 */
        .attachment-item {
            display: flex;
            align-items: center;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
        }
        
        .attachment-thumbnail {
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #e9ecef;
            border-radius: 0.25rem;
            overflow: hidden;
        }
        
        .img-thumbnail {
            max-width: 100%;
            max-height: 100%;
            object-fit: cover;
        }
        
        .attachment-info {
            padding: 0 0.5rem;
        }
        
        .attachment-name {
            font-weight: 500;
            margin-bottom: 0.25rem;
            word-break: break-all;
        }
        
        .attachment-size {
            font-size: 0.875rem;
            color: #6c757d;
        }
        
        .attachment-actions {
            margin-left: auto;
        }
        
        .remove-attachment {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
        }
    </style>
    @yield('styles')
</head>
<body class="d-flex flex-column min-vh-100">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="{{ \App\Helpers\UrlHelper::relative_url('/workorders') }}">
                <i class="fas fa-tools"></i> 校园网工单系统
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
                    <!-- Notifications Dropdown Menu -->
                    <li class="nav-item dropdown">
                        <a class="nav-link" href="#" role="button" data-bs-toggle="dropdown" id="notificationDropdown">
                            <i class="far fa-bell"></i>
                            <span class="badge bg-danger" id="notificationCount">0</span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-end" id="notificationDropdownMenu">
                            <li><h6 class="dropdown-header">通知中心</h6></li>
                            <li><hr class="dropdown-divider"></li>
                            <li id="notificationList">
                                <div class="dropdown-item text-center text-muted">
                                    <i class="fas fa-spinner fa-spin"></i> 加载中...
                                </div>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="{{ route('notifications.index') }}">
                                <i class="fas fa-eye"></i> 查看所有通知
                            </a></li>
                        </ul>
                    </li>
                    
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user"></i> {{ auth()->user()->name }}
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="{{ route('notifications.index') }}">
                                <i class="fas fa-bell"></i> 通知中心
                            </a></li>
                            <li><a class="dropdown-item" href="{{ route('profile') }}">
                                <i class="fas fa-user-circle"></i> 个人资料
                            </a></li>
                            <li><a class="dropdown-item" href="{{ route('dashboard') }}">
                                <i class="fas fa-tachometer-alt"></i> 仪表板
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="{{ route('logout.get') }}"
                                   data-method="post"
                                   onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                <i class="fas fa-sign-out-alt"></i> 退出登录
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- 移动端侧边栏遮罩层 -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <!-- 移动端侧边栏切换按钮 -->
    @if(auth()->check())
    <button class="mobile-sidebar-toggle" id="mobileSidebarToggle">
        <i class="fas fa-bars"></i>
    </button>
    @endif

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
                    
                    @if(auth()->user()->canManageWorkorderTypes())
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('workorder-templates.*') ? 'active' : '' }}"
                           href="{{ route('workorder-templates.index') }}">
                            <i class="fas fa-file-alt"></i> 工单模板
                        </a>
                    </li>
                    @endif
                    
                    @if(auth()->user()->canViewReports())
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}"
                           href="{{ route('reports.index') }}">
                            <i class="fas fa-chart-bar"></i> 统计报表
                        </a>
                    </li>
                    @endif
                </ul>
                
                @if(auth()->user()->isAdmin())
                <hr class="text-white">
                <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
                    <span>系统管理</span>
                </h6>
                <ul class="nav flex-column mb-2">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('locations.*') ? 'active' : '' }}"
                           href="{{ route('locations.index') }}">
                            <i class="fas fa-map-marker-alt"></i> 地址管理
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('workorder-categories.*') ? 'active' : '' }}"
                           href="{{ route('workorder-categories.index') }}">
                            <i class="fas fa-sitemap"></i> 工单分类
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('departments.*') ? 'active' : '' }}"
                           href="{{ route('departments.index') }}">
                            <i class="fas fa-building"></i> 部门管理
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}"
                           href="{{ route('users.index') }}">
                            <i class="fas fa-users"></i> 用户管理
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('system-settings.*') ? 'active' : '' }}"
                           href="{{ route('system-settings.index') }}">
                            <i class="fas fa-cogs"></i> 系统设置
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('logout.get') }}"
                           data-method="post"
                           onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                            <i class="fas fa-sign-out-alt"></i> 退出登录
                        </a>
                    </li>
                </ul>
                @endif
            </div>
        </nav>
        @endif

        <!-- Main Content Area -->
        <main class="main-content col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <!-- Breadcrumb -->
            @if(isset($breadcrumbs))
            <nav aria-label="breadcrumb" class="mb-3">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ \App\Helpers\UrlHelper::relative_url('/workorders') }}">首页</a></li>
                    @foreach($breadcrumbs as $breadcrumb)
                        @if($loop->last)
                            <li class="breadcrumb-item active">{{ $breadcrumb['title'] }}</li>
                        @else
                            <li class="breadcrumb-item">
                                <a href="{{ $breadcrumb['url'] ?? '#' }}">{{ $breadcrumb['title'] }}</a>
                            </li>
                        @endif
                    @endforeach
                </ol>
            </nav>
            @endif

            <!-- Flash Messages -->
            @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            @endif

            @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            @endif

            @if(session('warning'))
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle"></i> {{ session('warning') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            @endif

            @if(isset($errors) && $errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Page Content -->
            @yield('content')
        </main>
    </div>

    <!-- Footer -->
    <footer class="footer mt-auto py-3 bg-light">
        <div class="container">
            <div class="text-center">
                <span class="text-muted">© {{ date('Y') }} 校园网工单系统 - 版本 1.1.0</span>
            </div>
        </div>
    </footer>

    <!-- Logout Form -->
    <form id="logout-form" action="/logout" method="POST" style="display: none;">
        @csrf
    </form>
    
    <!-- Fallback logout script for mobile compatibility -->
    <script>
        // 处理退出登录的兼容性问题
        document.addEventListener('DOMContentLoaded', function() {
            // 为所有退出登录链接添加点击事件监听
            const logoutLinks = document.querySelectorAll('a[href*="logout"]');
            
            logoutLinks.forEach(function(link) {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    // 尝试提交表单
                    try {
                        const logoutForm = document.getElementById('logout-form');
                        if (logoutForm) {
                            logoutForm.submit();
                        } else {
                            // 使用相对URL，让浏览器自动处理协议
                            window.location.href = "/logout";
                        }
                    } catch (error) {
                        console.error('退出登录错误:', error);
                        // 使用相对URL，让浏览器自动处理协议
                        window.location.href = "/logout";
                    }
                });
            });
        });
    </script>

    <!-- Bootstrap JS -->
    <script src="{{ asset('assets/js/bootstrap.bundle.min.js') }}"></script>
    <!-- jQuery -->
    <script src="{{ asset('assets/js/jquery.min.js') }}"></script>
    <!-- Axios -->
    <script src="{{ asset('assets/js/axios.min.js') }}"></script>
    <!-- Microsoft Edge兼容性修复 -->
    <script src="{{ asset('js/edge-compatibility-fix.js') }}"></script>
    
    @yield('scripts')
    
    <script>
        // 设置axios默认配置
        @if(auth()->check())
        axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        axios.defaults.withCredentials = true;
        @endif
        
        // Auto-hide flash messages
        setTimeout(function() {
            $('.alert-dismissible').fadeOut('slow');
        }, 5000);
        
        // 移除强制协议转换，让浏览器自动处理协议
        // 这样可以支持HTTP和HTTPS两种环境
        
        // 移动端侧边栏切换
        $(document).ready(function() {
            $('#mobileSidebarToggle').click(function() {
                $('#sidebar').toggleClass('show');
                $('#sidebarOverlay').toggleClass('show');
                $('body').toggleClass('sidebar-open');
            });
            
            $('#sidebarOverlay').click(function() {
                $('#sidebar').removeClass('show');
                $('#sidebarOverlay').removeClass('show');
                $('body').removeClass('sidebar-open');
            });
            
            // 点击侧边栏链接后自动关闭侧边栏（仅在移动端）
            if (window.innerWidth <= 768) {
                $('.sidebar .nav-link').click(function() {
                    $('#sidebar').removeClass('show');
                    $('#sidebarOverlay').removeClass('show');
                    $('body').removeClass('sidebar-open');
                });
            }
            
            // 窗口大小改变时重置侧边栏状态
            $(window).resize(function() {
                if (window.innerWidth > 768) {
                    $('#sidebar').removeClass('show');
                    $('#sidebarOverlay').removeClass('show');
                    $('body').removeClass('sidebar-open');
                }
            });
            
            // 通知功能 - 延迟初始化以确保页面完全加载
            setTimeout(function() {
                loadNotificationCount();
                loadLatestNotifications();
            }, 100);
            
            // 定期刷新通知数量
            setInterval(loadNotificationCount, 30000);
            
            // 点击通知下拉菜单时加载最新通知
            $('#notificationDropdown').click(function() {
                loadLatestNotifications();
            });
        });
        
        // 加载未读通知数量
        function loadNotificationCount() {
            @if(auth()->check())
            axios.get('/notifications/unread-count')
                .then(response => {
                    const count = response.data.count;
                    $('#notificationCount').text(count);
                    
                    if (count > 0) {
                        $('#notificationCount').show();
                    } else {
                        $('#notificationCount').hide();
                    }
                })
                .catch(error => {
                    console.error('Error loading notification count:', error);
                    // 修复：显示错误信息但不要隐藏通知数量
                    $('#notificationCount').text('!');
                    $('#notificationCount').show();
                });
            @else
                $('#notificationCount').hide();
            @endif
        }
        
        // 加载最新通知
        function loadLatestNotifications() {
            @if(auth()->check())
            $('#notificationList').html('<div class="dropdown-item text-center text-muted"><i class="fas fa-spinner fa-spin"></i> 加载中...</div>');
            
            axios.get('/notifications/latest?limit=5')
                .then(response => {
                    // 检查响应数据是否存在
                    if (!response || !response.data) {
                        $('#notificationList').html('<div class="dropdown-item text-center text-muted">无响应数据</div>');
                        return;
                    }
                    
                    const notifications = response.data;
                    let html = '';
                    
                    // 检查响应是否为错误对象
                    if (notifications && typeof notifications === 'object' && notifications.error) {
                        html = `<div class="dropdown-item text-center text-danger">${notifications.error}</div>`;
                    } else if (!Array.isArray(notifications)) {
                        // 如果不是数组，可能是字符串或其他格式
                        if (typeof notifications === 'string') {
                            html = `<div class="dropdown-item text-center text-danger">服务器错误: ${notifications}</div>`;
                        } else {
                            html = '<div class="dropdown-item text-center text-muted">数据格式错误</div>';
                        }
                    } else if (notifications.length === 0) {
                        html = '<div class="dropdown-item text-center text-muted">暂无通知</div>';
                    } else {
                        notifications.forEach(notification => {
                            const importantClass = notification.is_important ? 'text-warning' : '';
                            const unreadClass = !notification.is_read ? 'bg-light' : '';
                            
                            // 生成通知标题和内容
                            let title = notification.title || '系统通知';
                            let content = notification.content || '';
                            
                            // 如果标题或内容为空，尝试从data字段生成
                            if (!notification.title && notification.data) {
                                if (notification.data.ticket_no) {
                                    title = `工单 #${notification.data.ticket_no}`;
                                } else if (notification.data.workorder_id) {
                                    title = `工单 #${notification.data.workorder_id}`;
                                }
                                
                                if (notification.data.description) {
                                    content = notification.data.description;
                                } else if (notification.data.assignee_name) {
                                    content = `分配给: ${notification.data.assignee_name}`;
                                }
                            }
                            
                            html += `
                                <li>
                                    <a href="#"
                                       class="dropdown-item ${unreadClass}"
                                       onclick="markNotificationAsRead(${notification.id}, event)">
                                        <div class="d-flex">
                                            <div class="flex-shrink-0">
                                                ${notification.data && notification.data.avatar ?
                                                    `<img src="${notification.data.avatar}" class="img-circle img-sm" alt="Avatar">` :
                                                    '<i class="fas fa-bell fa-lg text-muted"></i>'
                                                }
                                            </div>
                                            <div class="flex-grow-1 ms-2">
                                                <div class="small ${importantClass}">
                                                    <strong>${title}</strong>
                                                    ${notification.is_important ? '<i class="fas fa-star ms-1"></i>' : ''}
                                                    ${notification.data && notification.data.priority ? `<span class="badge bg-secondary ms-1">${notification.data.priority}</span>` : ''}
                                                </div>
                                                <div class="small text-muted">${content}</div>
                                                <div class="small text-muted">${notification.created_at}</div>
                                            </div>
                                            ${!notification.is_read ? '<div class="flex-shrink-0"><span class="badge bg-primary">新</span></div>' : ''}
                                        </div>
                                    </a>
                                </li>
                            `;
                            
                            if (notifications.indexOf(notification) < notifications.length - 1) {
                                html += '<li><hr class="dropdown-divider"></li>';
                            }
                        });
                    }
                    
                    $('#notificationList').html(html);
                })
                .catch(error => {
                    console.error('Error loading notifications:', error);
                    let errorMsg = '加载失败';
                    
                    // 详细错误分析
                    if (error.response) {
                        // 服务器响应了错误状态码
                        if (error.response.data) {
                            if (typeof error.response.data === 'string') {
                                errorMsg = `服务器错误: ${error.response.data}`;
                            } else if (error.response.data.message) {
                                errorMsg = error.response.data.message;
                            } else if (error.response.data.error) {
                                errorMsg = error.response.data.error;
                            }
                        } else {
                            errorMsg = `HTTP错误 ${error.response.status}`;
                        }
                    } else if (error.request) {
                        // 请求已发出但没有收到响应
                        errorMsg = '网络连接失败，请检查网络连接';
                    } else if (error.message) {
                        // 其他错误
                        if (error.message.includes('Unexpected token')) {
                            errorMsg = '服务器返回了无效的数据格式';
                        } else {
                            errorMsg = error.message;
                        }
                    }
                    
                    $('#notificationList').html(`<div class="dropdown-item text-center text-danger">${errorMsg}</div>`);
                });
            @else
            $('#notificationList').html('<div class="dropdown-item text-center text-muted">请先登录</div>');
            @endif
        }
        
        // 标记通知为已读
        function markNotificationAsRead(notificationId, event) {
            @if(auth()->check())
            if (event) {
                event.preventDefault();
            }
            
            axios.post(`/notifications/${notificationId}/read`, {})
                .then(response => {
                    if (response.data.success) {
                        loadNotificationCount();
                        loadLatestNotifications();
                        
                        // 如果通知有跳转链接，则跳转
                        const notification = response.data.notification;
                        if (notification && notification.data && notification.data.action_url) {
                            window.location.href = notification.data.action_url;
                        }
                    }
                })
                .catch(error => {
                    console.error('Error marking notification as read:', error);
                });
            @else
            console.log('User not authenticated');
            @endif
        }
    </script>
</body>
</html>
