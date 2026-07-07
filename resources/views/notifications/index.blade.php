@extends('layouts.app')

@section('title', '通知中心')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-bell"></i> 通知中心
                        @if ($unreadCount > 0)
                            <span class="badge bg-danger ms-2">{{ $unreadCount }}</span>
                        @endif
                    </h3>
                    
                    <div class="card-tools">
                        @if ($unreadCount > 0)
                            <button type="button" class="btn btn-sm btn-outline-success" id="markAllAsReadBtn">
                                <i class="fas fa-check-double"></i> 全部标记为已读
                            </button>
                        @endif
                        
                        @if (Auth::user()->isAdmin())
                            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#announcementModal">
                                <i class="fas fa-bullhorn"></i> 发布公告
                            </button>
                        @endif
                    </div>
                </div>
                
                <div class="card-body">
                    <!-- 筛选器 -->
                    <form method="GET" action="{{ route('notifications.index') }}" class="mb-3">
                        <div class="row">
                            <div class="col-md-3">
                                <select name="is_read" class="form-control form-control-sm">
                                    <option value="">全部状态</option>
                                    <option value="0" {{ request('is_read') == '0' ? 'selected' : '' }}>未读</option>
                                    <option value="1" {{ request('is_read') == '1' ? 'selected' : '' }}>已读</option>
                                </select>
                            </div>
                            
                            <div class="col-md-3">
                                <select name="type" class="form-control form-control-sm">
                                    <option value="">全部类型</option>
                                    <option value="workorder_created" {{ request('type') == 'workorder_created' ? 'selected' : '' }}>工单创建</option>
                                    <option value="workorder_assigned" {{ request('type') == 'workorder_assigned' ? 'selected' : '' }}>工单分配</option>
                                    <option value="workorder_started" {{ request('type') == 'workorder_started' ? 'selected' : '' }}>工单开始</option>
                                    <option value="workorder_resolved" {{ request('type') == 'workorder_resolved' ? 'selected' : '' }}>工单解决</option>
                                    <option value="workorder_closed" {{ request('type') == 'workorder_closed' ? 'selected' : '' }}>工单关闭</option>
                                    <option value="workorder_comment" {{ request('type') == 'workorder_comment' ? 'selected' : '' }}>工单评论</option>
                                    <option value="workorder_visit_completed" {{ request('type') == 'workorder_visit_completed' ? 'selected' : '' }}>工单回访</option>
                                    <option value="system_announcement" {{ request('type') == 'system_announcement' ? 'selected' : '' }}>系统公告</option>
                                </select>
                            </div>
                            
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-sm btn-primary">
                                    <i class="fas fa-search"></i> 筛选
                                </button>
                                <a href="{{ route('notifications.index') }}" class="btn btn-sm btn-secondary">
                                    <i class="fas fa-redo"></i> 重置
                                </a>
                            </div>
                        </div>
                    </form>
                    
                    <!-- 通知列表 -->
                    @if ($notifications->count() > 0)
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
                                    @foreach ($notifications as $notification)
                                        <tr class="{{ $notification->is_read ? '' : 'table-primary' }}">
                                            <td>
                                                <input type="checkbox" class="form-check-input notification-checkbox" value="{{ $notification->id }}">
                                            </td>
                                            <td>
                                                @if ($notification->is_read)
                                                    <span class="badge bg-secondary">已读</span>
                                                @else
                                                    <span class="badge bg-primary">未读</span>
                                                @endif
                                                
                                                @if ($notification->is_important)
                                                    <i class="fas fa-star text-warning" title="重要通知"></i>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="d-flex">
                                                    @if ($notification->data && isset($notification->data['avatar']))
                                                        <img src="{{ $notification->data['avatar'] }}" class="img-circle img-sm mr-2" alt="Avatar">
                                                    @endif
                                                    
                                                    <div>
                                                        <div class="font-weight-bold">{{ $notification->title }}</div>
                                                        <div class="text-muted small">{{ $notification->content }}</div>
                                                        
                                                        @if ($notification->data && isset($notification->data['action_url']))
                                                            <a href="{{ $notification->data['action_url'] }}" class="btn btn-sm btn-outline-primary mt-1">
                                                                查看详情
                                                            </a>
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-info">{{ $notification->getTypeTextAttribute() }}</span>
                                            </td>
                                            <td>
                                                <small class="text-muted">{{ $notification->created_at->diffForHumans() }}</small>
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    @if (!$notification->is_read)
                                                        <button type="button" class="btn btn-sm btn-primary btn-mark-read" data-id="{{ $notification->id }}">
                                                            标记已读
                                                        </button>
                                                    @endif
                                                    
                                                    <button type="button" class="btn btn-sm btn-danger btn-delete-notification" data-id="{{ $notification->id }}">
                                                        删除
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <button type="button" class="btn btn-sm btn-danger" id="batchDeleteBtn">
                                    <i class="fas fa-trash"></i> 批量删除
                                </button>
                                <button type="button" class="btn btn-sm btn-success" id="batchMarkAsReadBtn">
                                    <i class="fas fa-check-double"></i> 批量标记已读
                                </button>
                            </div>
                            
                            <div class="col-md-6">
                                {{ $notifications->links() }}
                            </div>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-bell-slash fa-3x text-muted mb-3"></i>
                            <h5>暂无通知</h5>
                            <p class="text-muted">您目前没有任何通知消息</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 系统公告模态框 -->
@if (Auth::user()->isAdmin())
<div class="modal fade" id="announcementModal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('notifications.create-announcement') }}" id="announcementForm">
                @csrf
                <div class="modal-header">
                    <h4 class="modal-title">发布系统公告</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>公告标题</label>
                        <input type="text" name="title" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label>公告内容</label>
                        <textarea name="content" class="form-control" rows="5" required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>发布范围</label>
                        <select name="target_type" class="form-control" required>
                            <option value="all">所有用户</option>
                            <option value="users">指定用户</option>
                            <option value="roles">指定角色</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <div class="form-check">
                            <input type="checkbox" name="is_important" value="true" class="form-check-input" id="is_important">
                            <label class="form-check-label" for="is_important">重要公告</label>
                        </div>
                    </div>
                    <!-- 隐藏字段确保总是发送 is_important -->
                    <input type="hidden" name="is_important" value="false" id="is_important_hidden">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                    <button type="submit" class="btn btn-primary">发布公告</button>
                </div>
            </form>
            </form>
        </div>
    </div>
</div>
@endif
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    console.log('通知中心页面已加载，初始化功能...');
    
    // 标记单个通知为已读
    $(document).on('click', '.btn-mark-read', function() {
        const notificationId = $(this).data('id');
        console.log('标记通知为已读:', notificationId);
        
        if (!notificationId) {
            console.error('通知ID为空');
            alert('无效的通知ID');
            return;
        }
        
        axios.post(`/notifications/${notificationId}/read`)
            .then(response => {
                console.log('标记已读响应:', response.data);
                if (response.data.success) {
                    location.reload();
                } else {
                    alert(response.data.message || '操作失败');
                }
            })
            .catch(error => {
                console.error('标记已读错误:', error);
                let errorMsg = '操作失败';
                if (error.response && error.response.data && error.response.data.message) {
                    errorMsg = error.response.data.message;
                }
                alert(errorMsg);
            });
    });
    
    // 全部标记为已读
    $('#markAllAsReadBtn').click(function() {
        console.log('全部标记为已读');
        
        if (confirm('确定要将所有通知标记为已读吗？')) {
            axios.post('/notifications/read-all')
                .then(response => {
                    console.log('全部标记已读响应:', response.data);
                    if (response.data.success) {
                        location.reload();
                    } else {
                        alert(response.data.message || '操作失败');
                    }
                })
                .catch(error => {
                    console.error('全部标记已读错误:', error);
                    let errorMsg = '操作失败';
                    if (error.response && error.response.data && error.response.data.message) {
                        errorMsg = error.response.data.message;
                    }
                    alert(errorMsg);
                });
        }
    });
    
    // 删除单个通知
    $(document).on('click', '.btn-delete-notification', function() {
        const notificationId = $(this).data('id');
        console.log('删除通知:', notificationId);
        
        if (!notificationId) {
            console.error('通知ID为空');
            alert('无效的通知ID');
            return;
        }
        
        if (confirm('确定要删除这条通知吗？')) {
            axios.delete(`/notifications/${notificationId}`)
                .then(response => {
                    console.log('删除通知响应:', response.data);
                    if (response.data.success) {
                        location.reload();
                    } else {
                        alert(response.data.message || '删除失败');
                    }
                })
                .catch(error => {
                    console.error('删除通知错误:', error);
                    let errorMsg = '删除失败';
                    if (error.response && error.response.data && error.response.data.message) {
                        errorMsg = error.response.data.message;
                    }
                    alert(errorMsg);
                });
        }
    });
    
    // 批量删除
    $('#batchDeleteBtn').click(function() {
        console.log('批量删除');
        
        const selectedIds = [];
        $('.notification-checkbox:checked').each(function() {
            selectedIds.push(parseInt($(this).val()));
        });
        
        console.log('选中的通知ID:', selectedIds);
        
        if (selectedIds.length === 0) {
            alert('请选择要删除的通知');
            return;
        }
        
        if (confirm(`确定要删除选中的 ${selectedIds.length} 条通知吗？`)) {
            axios.delete('/notifications/batch', {
                data: {
                    notification_ids: selectedIds
                }
            })
            .then(response => {
                console.log('批量删除响应:', response.data);
                if (response.data.success) {
                    location.reload();
                } else {
                    alert(response.data.message || '批量删除失败');
                }
            })
            .catch(error => {
                console.error('批量删除错误:', error);
                let errorMsg = '批量删除失败';
                if (error.response && error.response.data && error.response.data.message) {
                    errorMsg = error.response.data.message;
                }
                alert(errorMsg);
            });
        }
    });
    
    // 批量标记已读
    $('#batchMarkAsReadBtn').click(function() {
        console.log('批量标记已读');
        
        const selectedIds = [];
        $('.notification-checkbox:checked').each(function() {
            selectedIds.push(parseInt($(this).val()));
        });
        
        console.log('选中的通知ID:', selectedIds);
        
        if (selectedIds.length === 0) {
            alert('请选择要标记为已读的通知');
            return;
        }
        
        if (confirm(`确定要将选中的 ${selectedIds.length} 条通知标记为已读吗？`)) {
            axios.post('/notifications/batch-read', {
                notification_ids: selectedIds
            })
            .then(response => {
                console.log('批量标记已读响应:', response.data);
                if (response.data.success) {
                    location.reload();
                } else {
                    alert(response.data.message || '批量标记已读失败');
                }
            })
            .catch(error => {
                console.error('批量标记已读错误:', error);
                let errorMsg = '批量标记已读失败';
                if (error.response && error.response.data && error.response.data.message) {
                    errorMsg = error.response.data.message;
                }
                alert(errorMsg);
            });
        }
    });
    
    // 全选/取消全选
    $('#selectAll').change(function() {
        console.log('全选状态改变:', $(this).prop('checked'));
        $('.notification-checkbox').prop('checked', $(this).prop('checked'));
    });

    // 当单个复选框状态改变时，更新全选复选框状态
    $(document).on('change', '.notification-checkbox', function() {
        var allChecked = $('.notification-checkbox').length === $('.notification-checkbox:checked').length;
        console.log('单个复选框状态改变，全选状态应为:', allChecked);
        $('#selectAll').prop('checked', allChecked);
    });
    
    // 定期刷新未读通知数量
    setInterval(function() {
        axios.get('/notifications/unread-count')
            .then(response => {
                if (response.data.count > 0) {
                    // 更新页面上的未读数量显示
                    const badge = $('.navbar-nav .bg-danger');
                    if (badge.length) {
                        badge.text(response.data.count);
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
    }, 30000); // 每30秒刷新一次

    // 处理重要公告复选框
    $('#is_important').change(function() {
        const isChecked = $(this).is(':checked');
        $('#is_important_hidden').val(isChecked ? 'true' : 'false');
    });

    // 模态框显示时重置表单
    $('#announcementModal').on('show.bs.modal', function () {
        $('#is_important').prop('checked', false);
        $('#is_important_hidden').val('false');
    });

    // 处理发布公告表单提交
    $('#announcementForm').submit(function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        axios.post('{{ route("notifications.create-announcement") }}', formData)
            .then(response => {
                if (response.data.success) {
                    // 关闭模态框
                    $('#announcementModal').modal('hide');
                    
                    // 显示成功消息
                    const alertDiv = `
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle"></i> ${response.data.message}
                            <button type="button" class="btn-close" data-bs-dismiss="alert">
                                <span>&times;</span>
                            </button>
                        </div>
                    `;
                    
                    // 在页面顶部显示成功消息
                    $('.container-fluid').prepend(alertDiv);
                    
                    // 3秒后自动隐藏成功消息
                    setTimeout(function() {
                        $('.alert-success').fadeOut('slow');
                    }, 3000);
                    
                    // 刷新通知列表
                    if (typeof loadNotificationCount === 'function') {
                        loadNotificationCount();
                    }
                    if (typeof loadLatestNotifications === 'function') {
                        loadLatestNotifications();
                    }
                } else {
                    alert(response.data.message || '发布公告失败');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                let errorMsg = '发布公告失败';
                if (error.response && error.response.data && error.response.data.message) {
                    errorMsg = error.response.data.message;
                }
                alert(errorMsg);
            });
    });
});
</script>
@endsection