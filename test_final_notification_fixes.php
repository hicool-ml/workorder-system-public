<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>最终通知功能测试</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1>最终通知功能测试</h1>
        
        <!-- 通知铃铛模拟 -->
        <div class="card mb-4">
            <div class="card-header">
                <h5>通知铃铛模拟</h5>
            </div>
            <div class="card-body">
                <div class="dropdown">
                    <button class="btn btn-primary dropdown-toggle" type="button" id="notificationDropdown" data-bs-toggle="dropdown">
                        <i class="far fa-bell"></i>
                        <span class="badge bg-danger" id="notificationCount">0</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-end" id="notificationDropdownMenu">
                        <li><h6 class="dropdown-header">通知中心</h6></li>
                        <li><hr class="dropdown-divider"></li>
                        <li id="notificationList">
                            <div class="dropdown-item text-center text-muted">
                                <i class="fas fa-spinner fa-spin"></i> 加载中...
                            </div>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="/notifications">
                            <i class="fas fa-eye"></i> 查看所有通知
                        </a></li>
                    </ul>
                </div>
            </div>
        </div>
        
        <!-- API测试 -->
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>API 测试</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <button class="btn btn-primary" onclick="testUnreadCount()">
                                <i class="fas fa-bell"></i> 测试获取未读数量
                            </button>
                            <div id="unreadCountResult" class="mt-2"></div>
                        </div>
                        
                        <div class="mb-3">
                            <button class="btn btn-info" onclick="testLatestNotifications()">
                                <i class="fas fa-list"></i> 测试获取最新通知
                            </button>
                            <div id="latestNotificationsResult" class="mt-2"></div>
                        </div>
                        
                        <div class="mb-3">
                            <button class="btn btn-success" onclick="testMarkAllAsRead()">
                                <i class="fas fa-check-double"></i> 测试全部标记已读
                            </button>
                            <div id="markAllAsReadResult" class="mt-2"></div>
                        </div>
                        
                        <div class="mb-3">
                            <button class="btn btn-warning" onclick="testCreateAnnouncement()">
                                <i class="fas fa-bullhorn"></i> 测试创建公告
                            </button>
                            <div id="createAnnouncementResult" class="mt-2"></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>测试结果</h5>
                    </div>
                    <div class="card-body">
                        <div id="testResults">
                            <p class="text-muted">点击上方按钮进行测试...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/axios/1.6.0/axios.min.js"></script>
    
    <script>
        // 设置axios默认配置
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (token) {
            axios.defaults.headers.common['X-CSRF-TOKEN'] = token;
        } else {
            axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
        }
        
        function logResult(message, isSuccess = true) {
            const timestamp = new Date().toLocaleTimeString();
            const className = isSuccess ? 'text-success' : 'text-danger';
            const icon = isSuccess ? '✓' : '✗';
            
            $('#testResults').prepend(`
                <div class="alert ${isSuccess ? 'alert-success' : 'alert-danger'} alert-sm">
                    <small>[${timestamp}] ${icon} ${message}</small>
                </div>
            `);
        }
        
        function testUnreadCount() {
            $('#unreadCountResult').html('<i class="fas fa-spinner fa-spin"></i> 测试中...');
            
            axios.get('/notifications/unread-count')
                .then(response => {
                    const count = response.data.count;
                    $('#notificationCount').text(count);
                    $('#unreadCountResult').html(`
                        <div class="alert alert-success">
                            未读通知数量: <strong>${count}</strong>
                        </div>
                    `);
                    logResult('获取未读通知数量成功');
                })
                .catch(error => {
                    console.error('Error:', error);
                    let errorMsg = '获取失败';
                    if (error.response && error.response.data && error.response.data.message) {
                        errorMsg = error.response.data.message;
                    }
                    $('#unreadCountResult').html(`
                        <div class="alert alert-danger">
                            错误: ${errorMsg}
                        </div>
                    `);
                    logResult('获取未读通知数量失败: ' + errorMsg, false);
                });
        }
        
        function testLatestNotifications() {
            $('#latestNotificationsResult').html('<i class="fas fa-spinner fa-spin"></i> 测试中...');
            
            axios.get('/notifications/latest?limit=3')
                .then(response => {
                    const notifications = response.data;
                    let html = '<div class="alert alert-success">获取到 ' + notifications.length + ' 条通知</div>';
                    
                    notifications.forEach(notification => {
                        html += `
                            <div class="card mb-2">
                                <div class="card-body p-2">
                                    <h6 class="card-title">${notification.title}</h6>
                                    <p class="card-text small">${notification.content}</p>
                                    <small class="text-muted">${notification.created_at}</small>
                                </div>
                            </div>
                        `;
                    });
                    
                    $('#latestNotificationsResult').html(html);
                    logResult('获取最新通知成功');
                })
                .catch(error => {
                    console.error('Error:', error);
                    let errorMsg = '获取失败';
                    if (error.response && error.response.data && error.response.data.message) {
                        errorMsg = error.response.data.message;
                    }
                    $('#latestNotificationsResult').html(`
                        <div class="alert alert-danger">
                            错误: ${errorMsg}
                        </div>
                    `);
                    logResult('获取最新通知失败: ' + errorMsg, false);
                });
        }
        
        function testMarkAllAsRead() {
            $('#markAllAsReadResult').html('<i class="fas fa-spinner fa-spin"></i> 测试中...');
            
            axios.post('/notifications/read-all')
                .then(response => {
                    $('#markAllAsReadResult').html(`
                        <div class="alert alert-success">
                            全部标记为已读成功
                        </div>
                    `);
                    logResult('全部标记为已读成功');
                    // 重新获取通知数量
                    testUnreadCount();
                })
                .catch(error => {
                    console.error('Error:', error);
                    let errorMsg = '操作失败';
                    if (error.response && error.response.data && error.response.data.message) {
                        errorMsg = error.response.data.message;
                    }
                    $('#markAllAsReadResult').html(`
                        <div class="alert alert-danger">
                            错误: ${errorMsg}
                        </div>
                    `);
                    logResult('全部标记为已读失败: ' + errorMsg, false);
                });
        }
        
        function testCreateAnnouncement() {
            $('#createAnnouncementResult').html('<i class="fas fa-spinner fa-spin"></i> 测试中...');
            
            axios.post('/notifications/create-announcement', {
                title: '最终测试公告 ' + new Date().toLocaleTimeString(),
                content: '这是通过最终测试页面创建的公告，用于验证通知功能。',
                target_type: 'all',
                is_important: false
            })
                .then(response => {
                    $('#createAnnouncementResult').html(`
                        <div class="alert alert-success">
                            创建公告成功: ${response.data.message}
                        </div>
                    `);
                    logResult('创建公告成功');
                    // 重新获取通知列表
                    testLatestNotifications();
                })
                .catch(error => {
                    console.error('Error:', error);
                    let errorMsg = '创建失败';
                    if (error.response && error.response.data && error.response.data.message) {
                        errorMsg = error.response.data.message;
                    }
                    $('#createAnnouncementResult').html(`
                        <div class="alert alert-danger">
                            错误: ${errorMsg}
                        </div>
                    `);
                    logResult('创建公告失败: ' + errorMsg, false);
                });
        }
        
        // 模拟通知下拉菜单功能
        function loadNotificationCount() {
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
                    $('#notificationCount').text('!');
                    $('#notificationCount').show();
                });
        }
        
        function loadLatestNotifications() {
            $('#notificationList').html('<div class="dropdown-item text-center text-muted"><i class="fas fa-spinner fa-spin"></i> 加载中...</div>');
            
            axios.get('/notifications/latest?limit=5')
                .then(response => {
                    const notifications = response.data;
                    let html = '';
                    
                    if (notifications.length === 0) {
                        html = '<div class="dropdown-item text-center text-muted">暂无通知</div>';
                    } else {
                        notifications.forEach(notification => {
                            const unreadClass = !notification.is_read ? 'bg-light' : '';
                            
                            html += `
                                <li>
                                    <a href="#" class="dropdown-item ${unreadClass}">
                                        <div class="d-flex">
                                            <div class="flex-grow-1">
                                                <div class="small">
                                                    <strong>${notification.title}</strong>
                                                </div>
                                                <div class="small text-muted">${notification.content}</div>
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
                    $('#notificationList').html('<div class="dropdown-item text-center text-danger">加载失败</div>');
                });
        }
        
        // 点击通知下拉菜单时加载最新通知
        $('#notificationDropdown').click(function() {
            loadLatestNotifications();
        });
        
        // 页面加载完成后自动运行一些测试
        $(document).ready(function() {
            setTimeout(function() {
                testUnreadCount();
            }, 1000);
            
            setTimeout(function() {
                testLatestNotifications();
            }, 2000);
        });
    </script>
</body>
</html>