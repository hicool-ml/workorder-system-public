<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>通知中心调试测试</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-bell"></i> 通知中心功能调试
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <h5>调试信息</h5>
                            <p>此页面用于测试通知中心的各种功能是否正常工作。</p>
                            <ul>
                                <li>打开浏览器开发者工具查看控制台输出</li>
                                <li>测试各个按钮功能</li>
                                <li>检查网络请求是否正确发送</li>
                            </ul>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <button type="button" class="btn btn-sm btn-success" onclick="testMarkAllAsRead()">
                                    <i class="fas fa-check-double"></i> 测试全部标记为已读
                                </button>
                            </div>
                            <div class="col-md-6">
                                <button type="button" class="btn btn-sm btn-primary" onclick="testGetUnreadCount()">
                                    <i class="fas fa-bell"></i> 测试获取未读数量
                                </button>
                            </div>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th width="30">
                                            <input type="checkbox" id="selectAll" class="form-check-input">
                                        </th>
                                        <th width="80">状态</th>
                                        <th>内容</th>
                                        <th width="120">类型</th>
                                        <th width="150">时间</th>
                                        <th width="100">操作</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="table-primary">
                                        <td>
                                            <input type="checkbox" class="form-check-input notification-checkbox" value="1">
                                        </td>
                                        <td>
                                            <span class="badge bg-primary">未读</span>
                                        </td>
                                        <td>
                                            <div class="font-weight-bold">测试通知1</div>
                                            <div class="text-muted small">这是一个测试通知</div>
                                        </td>
                                        <td>
                                            <span class="badge bg-info">系统公告</span>
                                        </td>
                                        <td>
                                            <small class="text-muted">刚刚</small>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-sm btn-primary" onclick="testMarkAsRead(1)">
                                                    标记已读
                                                </button>
                                                <button type="button" class="btn btn-sm btn-danger" onclick="testDeleteNotification(1)">
                                                    删除
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr class="table-primary">
                                        <td>
                                            <input type="checkbox" class="form-check-input notification-checkbox" value="2">
                                        </td>
                                        <td>
                                            <span class="badge bg-primary">未读</span>
                                        </td>
                                        <td>
                                            <div class="font-weight-bold">测试通知2</div>
                                            <div class="text-muted small">这是另一个测试通知</div>
                                        </td>
                                        <td>
                                            <span class="badge bg-info">工单分配</span>
                                        </td>
                                        <td>
                                            <small class="text-muted">5分钟前</small>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-sm btn-primary" onclick="testMarkAsRead(2)">
                                                    标记已读
                                                </button>
                                                <button type="button" class="btn btn-sm btn-danger" onclick="testDeleteNotification(2)">
                                                    删除
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <button type="button" class="btn btn-sm btn-danger" onclick="testBatchDelete()">
                                    <i class="fas fa-trash"></i> 测试批量删除
                                </button>
                                <button type="button" class="btn btn-sm btn-success" onclick="testBatchMarkAsRead()">
                                    <i class="fas fa-check-double"></i> 测试批量标记已读
                                </button>
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <h5>测试结果</h5>
                            <div id="testResults" class="border p-3 bg-light">
                                <p class="text-muted">测试结果将显示在这里...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Axios -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/axios/1.6.0/axios.min.js"></script>
    
    <script>
        // 设置axios默认配置
        axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        axios.defaults.withCredentials = true;
        
        // 添加测试结果到页面
        function addTestResult(testName, success, message) {
            const resultDiv = document.getElementById('testResults');
            const timestamp = new Date().toLocaleTimeString();
            const statusClass = success ? 'text-success' : 'text-danger';
            const statusIcon = success ? '✓' : '✗';
            
            const resultHtml = `
                <div class="mb-2">
                    <strong>[${timestamp}] ${statusIcon} ${testName}</strong>
                    <div class="${statusClass}">${message}</div>
                </div>
            `;
            
            resultDiv.innerHTML = resultHtml + resultDiv.innerHTML;
        }
        
        // 确保DOM完全加载后再执行JavaScript
        $(document).ready(function() {
            console.log('调试页面已加载，初始化功能...');
            
            // 全选/取消全选
            $('#selectAll').change(function() {
                console.log('全选状态改变:', $(this).prop('checked'));
                $('.notification-checkbox').prop('checked', $(this).prop('checked'));
                addTestResult('全选功能', true, `全选状态: ${$(this).prop('checked') ? '选中' : '未选中'}`);
            });

            // 当单个复选框状态改变时，更新全选复选框状态
            $(document).on('change', '.notification-checkbox', function() {
                var allChecked = $('.notification-checkbox').length === $('.notification-checkbox:checked').length;
                console.log('单个复选框状态改变，全选状态应为:', allChecked);
                $('#selectAll').prop('checked', allChecked);
                addTestResult('单个复选框', true, `复选框ID: ${$(this).val()}, 状态: ${$(this).prop('checked') ? '选中' : '未选中'}`);
            });
        });
        
        // 测试标记为已读
        function testMarkAsRead(notificationId) {
            console.log('测试标记通知为已读:', notificationId);
            
            addTestResult('标记已读测试', true, `尝试标记通知 ${notificationId} 为已读`);
            
            axios.post(`/notifications/${notificationId}/read`)
                .then(response => {
                    console.log('标记已读响应:', response.data);
                    addTestResult('标记已读响应', response.data.success, response.data.message || '操作成功');
                })
                .catch(error => {
                    console.error('标记已读错误:', error);
                    let errorMsg = '操作失败';
                    if (error.response && error.response.data && error.response.data.message) {
                        errorMsg = error.response.data.message;
                    }
                    addTestResult('标记已读错误', false, errorMsg);
                });
        }
        
        // 测试全部标记为已读
        function testMarkAllAsRead() {
            console.log('测试全部标记为已读');
            
            addTestResult('全部标记已读测试', true, '尝试标记所有通知为已读');
            
            axios.post('/notifications/read-all')
                .then(response => {
                    console.log('全部标记已读响应:', response.data);
                    addTestResult('全部标记已读响应', response.data.success, response.data.message || '操作成功');
                })
                .catch(error => {
                    console.error('全部标记已读错误:', error);
                    let errorMsg = '操作失败';
                    if (error.response && error.response.data && error.response.data.message) {
                        errorMsg = error.response.data.message;
                    }
                    addTestResult('全部标记已读错误', false, errorMsg);
                });
        }
        
        // 测试删除通知
        function testDeleteNotification(notificationId) {
            console.log('测试删除通知:', notificationId);
            
            addTestResult('删除通知测试', true, `尝试删除通知 ${notificationId}`);
            
            axios.delete(`/notifications/${notificationId}`)
                .then(response => {
                    console.log('删除通知响应:', response.data);
                    addTestResult('删除通知响应', response.data.success, response.data.message || '操作成功');
                })
                .catch(error => {
                    console.error('删除通知错误:', error);
                    let errorMsg = '删除失败';
                    if (error.response && error.response.data && error.response.data.message) {
                        errorMsg = error.response.data.message;
                    }
                    addTestResult('删除通知错误', false, errorMsg);
                });
        }
        
        // 测试批量删除
        function testBatchDelete() {
            console.log('测试批量删除');
            
            const selectedIds = [];
            $('.notification-checkbox:checked').each(function() {
                selectedIds.push($(this).val());
            });
            
            console.log('选中的通知ID:', selectedIds);
            
            if (selectedIds.length === 0) {
                addTestResult('批量删除测试', false, '请选择要删除的通知');
                return;
            }
            
            addTestResult('批量删除测试', true, `尝试删除选中的 ${selectedIds.length} 条通知: ${selectedIds.join(', ')}`);
            
            axios.delete('/notifications/batch', {
                data: {
                    notification_ids: selectedIds
                }
            })
            .then(response => {
                console.log('批量删除响应:', response.data);
                addTestResult('批量删除响应', response.data.success, response.data.message || '操作成功');
            })
            .catch(error => {
                console.error('批量删除错误:', error);
                let errorMsg = '批量删除失败';
                if (error.response && error.response.data && error.response.data.message) {
                    errorMsg = error.response.data.message;
                }
                addTestResult('批量删除错误', false, errorMsg);
            });
        }
        
        // 测试批量标记已读
        function testBatchMarkAsRead() {
            console.log('测试批量标记已读');
            
            const selectedIds = [];
            $('.notification-checkbox:checked').each(function() {
                selectedIds.push($(this).val());
            });
            
            console.log('选中的通知ID:', selectedIds);
            
            if (selectedIds.length === 0) {
                addTestResult('批量标记已读测试', false, '请选择要标记为已读的通知');
                return;
            }
            
            addTestResult('批量标记已读测试', true, `尝试标记选中的 ${selectedIds.length} 条通知为已读: ${selectedIds.join(', ')}`);
            
            axios.post('/notifications/batch-read', {
                notification_ids: selectedIds
            })
            .then(response => {
                console.log('批量标记已读响应:', response.data);
                addTestResult('批量标记已读响应', response.data.success, response.data.message || '操作成功');
            })
            .catch(error => {
                console.error('批量标记已读错误:', error);
                let errorMsg = '批量标记已读失败';
                if (error.response && error.response.data && error.response.data.message) {
                    errorMsg = error.response.data.message;
                }
                addTestResult('批量标记已读错误', false, errorMsg);
            });
        }
        
        // 测试获取未读数量
        function testGetUnreadCount() {
            console.log('测试获取未读数量');
            
            addTestResult('获取未读数量测试', true, '尝试获取未读通知数量');
            
            axios.get('/notifications/unread-count')
                .then(response => {
                    console.log('获取未读数量响应:', response.data);
                    addTestResult('获取未读数量响应', true, `未读通知数量: ${response.data.count}`);
                })
                .catch(error => {
                    console.error('获取未读数量错误:', error);
                    let errorMsg = '获取失败';
                    if (error.response && error.response.data && error.response.data.message) {
                        errorMsg = error.response.data.message;
                    }
                    addTestResult('获取未读数量错误', false, errorMsg);
                });
        }
    </script>
</body>
</html>