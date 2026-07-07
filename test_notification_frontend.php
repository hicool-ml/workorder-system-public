<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>通知功能前端测试</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1>通知功能前端测试</h1>
        
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>通知铃铛测试</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <button class="btn btn-primary" onclick="testNotificationCount()">
                                <i class="fas fa-bell"></i> 测试获取未读数量
                            </button>
                            <div id="notificationCountResult" class="mt-2">
                                <span class="badge bg-danger" id="testNotificationCount">0</span>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <button class="btn btn-info" onclick="testNotificationList()">
                                <i class="fas fa-list"></i> 测试获取通知列表
                            </button>
                            <div id="notificationListResult" class="mt-2"></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>操作功能测试</h5>
                    </div>
                    <div class="card-body">
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
        </div>
        
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5>测试日志</h5>
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
        
        function testNotificationCount() {
            $('#notificationCountResult').html('<i class="fas fa-spinner fa-spin"></i> 测试中...');
            
            axios.get('/notifications/unread-count')
                .then(response => {
                    const count = response.data.count;
                    $('#testNotificationCount').text(count);
                    $('#notificationCountResult').html(`
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
                    $('#notificationCountResult').html(`
                        <div class="alert alert-danger">
                            错误: ${errorMsg}
                        </div>
                    `);
                    logResult('获取未读通知数量失败: ' + errorMsg, false);
                });
        }
        
        function testNotificationList() {
            $('#notificationListResult').html('<i class="fas fa-spinner fa-spin"></i> 测试中...');
            
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
                    
                    $('#notificationListResult').html(html);
                    logResult('获取通知列表成功');
                })
                .catch(error => {
                    console.error('Error:', error);
                    let errorMsg = '获取失败';
                    if (error.response && error.response.data && error.response.data.message) {
                        errorMsg = error.response.data.message;
                    }
                    $('#notificationListResult').html(`
                        <div class="alert alert-danger">
                            错误: ${errorMsg}
                        </div>
                    `);
                    logResult('获取通知列表失败: ' + errorMsg, false);
                });
        }
        
        function testMarkAllAsRead() {
            $('#markAllAsReadResult').html('<i class="fas fa-spinner fa-spin"></i> 测试中...');
            
            axios.post('/notifications/read-all')
                .then(response => {
                    $('#markAllAsReadResult').html(`
                        <div class="alert alert-success">
                            全部标记为已读成功，标记了 ${response.data.count} 条通知
                        </div>
                    `);
                    logResult('全部标记为已读成功');
                    // 重新获取通知数量
                    testNotificationCount();
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
                title: '前端测试公告 ' + new Date().toLocaleTimeString(),
                content: '这是通过前端测试创建的公告，用于验证通知功能。',
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
                    testNotificationList();
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
        
        // 页面加载完成后自动运行一些测试
        $(document).ready(function() {
            setTimeout(function() {
                testNotificationCount();
            }, 1000);
            
            setTimeout(function() {
                testNotificationList();
            }, 2000);
        });
    </script>
</body>
</html>