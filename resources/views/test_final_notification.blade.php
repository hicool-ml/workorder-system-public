<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>通知功能最终测试</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1>通知功能最终测试</h1>
        <p class="text-muted">这个页面模拟真实的前端环境，测试修复后的通知功能。</p>
        
        <!-- 模拟导航栏 -->
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
            <div class="container-fluid">
                <span class="navbar-brand">校园网工单系统</span>
                <div class="d-flex">
                    <ul class="navbar-nav">
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
                                <li><a class="dropdown-item" href="/notifications">
                                    <i class="fas fa-eye"></i> 查看所有通知
                                </a></li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
        
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5>测试状态</h5>
                    </div>
                    <div class="card-body">
                        <div id="testStatus">
                            <div class="alert alert-info">
                                <i class="fas fa-spinner fa-spin"></i> 正在初始化测试...
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card mt-3">
                    <div class="card-header">
                        <h5>控制面板</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2 d-md-flex">
                            <button class="btn btn-primary" onclick="testUnreadCount()">
                                <i class="fas fa-bell"></i> 测试未读数量
                            </button>
                            <button class="btn btn-info" onclick="testLatestNotifications()">
                                <i class="fas fa-list"></i> 测试最新通知
                            </button>
                            <button class="btn btn-success" onclick="createTestNotification()">
                                <i class="fas fa-plus"></i> 创建测试通知
                            </button>
                            <button class="btn btn-warning" onclick="refreshAll()">
                                <i class="fas fa-sync"></i> 刷新所有
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5>修复说明</h5>
                    </div>
                    <div class="card-body">
                        <h6>问题诊断：</h6>
                        <ul class="small">
                            <li>原始问题：通知铃铛显示0，点击显示"加载失败"</li>
                            <li>根本原因：axios配置缺少CSRF token和withCredentials设置</li>
                        </ul>
                        
                        <h6>修复方案：</h6>
                        <ul class="small">
                            <li>在全局axios配置中添加CSRF token</li>
                            <li>设置withCredentials为true以支持认证</li>
                            <li>简化各个API请求，移除重复的headers配置</li>
                        </ul>
                        
                        <h6>测试结果：</h6>
                        <div id="testResults" class="small">
                            <div class="text-muted">等待测试完成...</div>
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
        // 修复后的axios配置
        axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        axios.defaults.withCredentials = true;
        
        let testResults = [];
        let isInitialized = false;
        
        function addTestResult(test, success, message) {
            testResults.push({test, success, message});
            updateTestResults();
        }
        
        function updateTestResults() {
            const resultsHtml = testResults.map(result => {
                const icon = result.success ? '✓' : '✗';
                const color = result.success ? 'success' : 'danger';
                return `<div class="alert alert-${color} py-1 px-2 mb-1">
                    <small><strong>${icon} ${result.test}:</strong> ${result.message}</small>
                </div>`;
            }).join('');
            
            document.getElementById('testResults').innerHTML = resultsHtml || '<div class="text-muted">暂无测试结果</div>';
        }
        
        function updateStatus(message, type = 'info') {
            const statusHtml = `<div class="alert alert-${type}">
                <i class="fas fa-info-circle"></i> ${message}
            </div>`;
            document.getElementById('testStatus').innerHTML = statusHtml;
        }
        
        // 加载未读通知数量（修复后的代码）
        function loadNotificationCount() {
            return axios.get('/notifications/unread-count')
                .then(response => {
                    const count = response.data.count;
                    $('#notificationCount').text(count);
                    
                    if (count > 0) {
                        $('#notificationCount').show();
                    } else {
                        $('#notificationCount').hide();
                    }
                    
                    return count;
                })
                .catch(error => {
                    console.error('Error loading notification count:', error);
                    throw error;
                });
        }
        
        // 加载最新通知（修复后的代码）
        function loadLatestNotifications() {
            $('#notificationList').html('<div class="dropdown-item text-center text-muted"><i class="fas fa-spinner fa-spin"></i> 加载中...</div>');
            
            return axios.get('/notifications/latest?limit=5')
                .then(response => {
                    const notifications = response.data;
                    let html = '';
                    
                    if (notifications.length === 0) {
                        html = '<div class="dropdown-item text-center text-muted">暂无通知</div>';
                    } else {
                        notifications.forEach(notification => {
                            const importantClass = notification.is_important ? 'text-warning' : '';
                            const unreadClass = !notification.is_read ? 'bg-light' : '';
                            
                            html += `
                                <li>
                                    <a href="#"
                                       class="dropdown-item ${unreadClass}"
                                       onclick="markNotificationAsRead(${notification.id}, event)">
                                        <div class="d-flex">
                                            <div class="flex-shrink-0">
                                                <i class="fas fa-bell fa-lg text-muted"></i>
                                            </div>
                                            <div class="flex-grow-1 ms-2">
                                                <div class="small ${importantClass}">
                                                    <strong>${notification.title}</strong>
                                                    ${notification.is_important ? '<i class="fas fa-star ms-1"></i>' : ''}
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
                    return notifications;
                })
                .catch(error => {
                    console.error('Error loading notifications:', error);
                    $('#notificationList').html('<div class="dropdown-item text-center text-danger">加载失败</div>');
                    throw error;
                });
        }
        
        // 标记通知为已读（修复后的代码）
        function markNotificationAsRead(notificationId, event) {
            if (event) {
                event.preventDefault();
            }
            
            axios.post(`/notifications/${notificationId}/read`, {})
                .then(response => {
                    if (response.data.success) {
                        loadNotificationCount();
                        loadLatestNotifications();
                    }
                })
                .catch(error => {
                    console.error('Error marking notification as read:', error);
                });
        }
        
        // 测试函数
        function testUnreadCount() {
            updateStatus('正在测试未读通知数量...', 'info');
            loadNotificationCount()
                .then(count => {
                    addTestResult('获取未读通知数量', true, `成功获取到 ${count} 条未读通知`);
                    updateStatus('未读通知数量测试完成', 'success');
                })
                .catch(error => {
                    addTestResult('获取未读通知数量', false, `错误: ${error.response?.data?.message || error.message}`);
                    updateStatus('未读通知数量测试失败', 'danger');
                });
        }
        
        function testLatestNotifications() {
            updateStatus('正在测试最新通知...', 'info');
            loadLatestNotifications()
                .then(notifications => {
                    addTestResult('获取最新通知', true, `成功获取到 ${notifications.length} 条通知`);
                    updateStatus('最新通知测试完成', 'success');
                })
                .catch(error => {
                    addTestResult('获取最新通知', false, `错误: ${error.response?.data?.message || error.message}`);
                    updateStatus('最新通知测试失败', 'danger');
                });
        }
        
        function createTestNotification() {
            updateStatus('正在创建测试通知...', 'info');
            axios.post('/notifications/create-announcement', {
                title: '最终测试通知 ' + new Date().toLocaleTimeString(),
                content: '这是最终测试通知，验证修复后的功能是否正常工作。',
                target_type: 'all',
                is_important: false
            })
                .then(response => {
                    addTestResult('创建测试通知', true, response.data.message);
                    updateStatus('测试通知创建成功', 'success');
                    // 刷新通知列表
                    loadNotificationCount();
                    loadLatestNotifications();
                })
                .catch(error => {
                    addTestResult('创建测试通知', false, `错误: ${error.response?.data?.message || error.message}`);
                    updateStatus('测试通知创建失败', 'danger');
                });
        }
        
        function refreshAll() {
            updateStatus('正在刷新所有通知数据...', 'info');
            Promise.all([
                loadNotificationCount(),
                loadLatestNotifications()
            ])
                .then(([count, notifications]) => {
                    addTestResult('刷新所有数据', true, `未读: ${count} 条，最新: ${notifications.length} 条`);
                    updateStatus('所有数据刷新完成', 'success');
                })
                .catch(error => {
                    addTestResult('刷新所有数据', false, `错误: ${error.message}`);
                    updateStatus('数据刷新失败', 'danger');
                });
        }
        
        // 页面加载时自动初始化
        $(document).ready(function() {
            // 点击通知下拉菜单时加载最新通知
            $('#notificationDropdown').click(function() {
                if (!isInitialized) {
                    loadLatestNotifications();
                }
            });
            
            // 初始化测试
            setTimeout(() => {
                updateStatus('正在初始化通知功能...', 'info');
                
                loadNotificationCount()
                    .then(count => {
                        addTestResult('初始化 - 未读数量', true, `成功获取到 ${count} 条未读通知`);
                        
                        return loadLatestNotifications();
                    })
                    .then(notifications => {
                        addTestResult('初始化 - 最新通知', true, `成功获取到 ${notifications.length} 条通知`);
                        isInitialized = true;
                        updateStatus('通知功能初始化完成，修复成功！', 'success');
                    })
                    .catch(error => {
                        addTestResult('初始化', false, `错误: ${error.response?.data?.message || error.message}`);
                        updateStatus('通知功能初始化失败', 'danger');
                    });
            }, 1000);
        });
    </script>
</body>
</html>