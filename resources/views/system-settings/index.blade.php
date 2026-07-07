@extends('layouts.app')

@section('title', '系统设置')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">系统设置</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="initializeDefaults()">
                <i class="fas fa-redo"></i> 初始化默认设置
            </button>
        </div>
    </div>
</div>

<div class="row">
    <!-- 注册设置 -->
    <div class="col-12 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-user-plus"></i> 注册设置
                </h5>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="registration_enabled"
                           name="registration_enabled"
                           @if($groupedSettings['registration']->firstWhere('key', 'registration_enabled')?->typed_value)
                           checked
                           @endif
                           onchange="toggleRegistration(this.checked)">
                    <label class="form-check-label" for="registration_enabled">
                        开放注册
                    </label>
                </div>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('system-settings.update') }}">
                    @csrf
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="default_user_role" class="form-label">默认用户角色</label>
                                <select class="form-select" id="default_user_role" name="settings[default_user_role]">
                                    <option value="user" @if($groupedSettings['user']->firstWhere('key', 'default_user_role')?->typed_value === 'user') selected @endif>普通用户</option>
                                    <option value="engineer" @if($groupedSettings['user']->firstWhere('key', 'default_user_role')?->typed_value === 'engineer') selected @endif>工程师</option>
                                    <option value="workorder_manager" @if($groupedSettings['user']->firstWhere('key', 'default_user_role')?->typed_value === 'workorder_manager') selected @endif>工单管理员</option>
                                </select>
                                <div class="form-text">新注册用户的默认角色</div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <div class="form-check form-switch mt-4">
                                    <input class="form-check-input" type="checkbox" id="require_email_verification" 
                                           name="settings[require_email_verification]" value="1"
                                           @if($groupedSettings['registration']->firstWhere('key', 'require_email_verification')?->typed_value)
                                           checked
                                           @endif>
                                    <label class="form-check-label" for="require_email_verification">
                                        需要邮箱验证
                                    </label>
                                </div>
                                <div class="form-text">注册时是否需要验证邮箱地址</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> 保存设置
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- 系统设置 -->
    <div class="col-12 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-cog"></i> 系统设置
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('system-settings.update') }}">
                    @csrf
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="system_name" class="form-label">系统名称</label>
                                <input type="text" class="form-control" id="system_name" autocomplete="off"
                                       name="settings[system_name]" autocomplete="off"
                                       value="{{ $groupedSettings['system']->firstWhere('key', 'system_name')?->typed_value ?? '校园网工单系统' }}">
                                <div class="form-text">系统的显示名称</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="system_version" class="form-label">版本号</label>
                                <input type="text" class="form-control" id="system_version" autocomplete="off"
                                       name="settings[system_version]" autocomplete="off"
                                       value="{{ $groupedSettings['version']->firstWhere('key', 'system_version')?->typed_value ?? '2.0.0' }}">
                                <div class="form-text">当前版本号</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="system_release_date" class="form-label">发布日期</label>
                                <input type="date" class="form-control" id="system_release_date" autocomplete="off"
                                       name="settings[system_release_date]" autocomplete="off"
                                       value="{{ $groupedSettings['version']->firstWhere('key', 'system_release_date')?->typed_value ?? date('Y-m-d') }}">
                                <div class="form-text">版本发布日期</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> 保存设置
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- 版本管理 -->
    <div class="col-12 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-code-branch"></i> 版本管理
                </h5>
                <div class="btn-group">
                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#versionUpdateModal">
                        <i class="fas fa-plus"></i> 更新版本
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-info" onclick="loadVersionHistory()">
                        <i class="fas fa-history"></i> 版本历史
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <i class="fas fa-tag fa-2x text-primary"></i>
                            </div>
                            <div>
                                <h6 class="mb-0">当前版本</h6>
                                <span class="badge bg-primary fs-6">{{ $groupedSettings['version']->firstWhere('key', 'system_version')?->typed_value ?? '2.0.0' }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <i class="fas fa-calendar fa-2x text-success"></i>
                            </div>
                            <div>
                                <h6 class="mb-0">发布日期</h6>
                                <span class="text-muted">{{ $groupedSettings['version']->firstWhere('key', 'system_release_date')?->typed_value ?? date('Y-m-d') }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <i class="fas fa-building fa-2x text-info"></i>
                            </div>
                            <div>
                                <h6 class="mb-0">系统名称</h6>
                                <span class="text-muted">{{ $groupedSettings['system']->firstWhere('key', 'system_name')?->typed_value ?? '校园网工单系统' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- 版本历史列表 -->
                <div id="versionHistory" class="mt-4" style="display: none;">
                    <h6 class="border-bottom pb-2">版本历史</h6>
                    <div id="versionHistoryList">
                        <!-- 版本历史将通过AJAX加载 -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 所有设置列表 -->
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-list"></i> 所有设置
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>设置键</th>
                                <th>值</th>
                                <th>类型</th>
                                <th>描述</th>
                                <th>公开</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($settings as $setting)
                            <tr>
                                <td><code>{{ $setting->key }}</code></td>
                                <td>
                                    @if($setting->type === 'boolean')
                                        <span class="badge bg-{{ $setting->typed_value ? 'success' : 'secondary' }}">
                                            {{ $setting->typed_value ? '是' : '否' }}
                                        </span>
                                    @else
                                        {{ Str::limit($setting->value, 50) }}
                                    @endif
                                </td>
                                <td><span class="badge bg-info">{{ $setting->type }}</span></td>
                                <td>{{ $setting->description ?? '-' }}</td>
                                <td>
                                    @if($setting->is_public)
                                        <i class="fas fa-eye text-success"></i>
                                    @else
                                        <i class="fas fa-eye-slash text-muted"></i>
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-primary" 
                                                onclick="editSetting('{{ $setting->key }}', '{{ $setting->value }}', '{{ $setting->type }}')">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <form method="POST" action="{{ route('system-settings.destroy', $setting) }}" 
                                              style="display: inline;" onsubmit="return confirm('确定要删除这个设置吗？')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 编辑设置模态框 -->
<div class="modal fade" id="editSettingModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">编辑设置</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="关闭编辑设置对话框"></button>
            </div>
            <form method="POST" action="{{ route('system-settings.update') }}">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_key" class="form-label">设置键</label>
                        <input type="text" class="form-control" id="edit_key" autocomplete="off" name="edit_key" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="edit_value" class="form-label">设置值</label>
                        <input type="text" class="form-control" id="edit_value" autocomplete="off" name="settings[edit_key]">
                        <div class="form-text">根据设置类型输入相应的值</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                    <button type="submit" class="btn btn-primary">保存</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 版本更新模态框 -->
<div class="modal fade" id="versionUpdateModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">更新系统版本</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="关闭版本更新对话框"></button>
            </div>
            <form method="POST" action="{{ route('system-settings.update-version') }}" id="versionUpdateForm">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="new_version" class="form-label">新版本号</label>
                        <input type="text" class="form-control" id="new_version" name="version" required
                               placeholder="例如：2.1.0" value="{{ $groupedSettings['version']->firstWhere('key', 'system_version')?->typed_value ?? '2.0.0' }}">
                        <div class="form-text">请输入新的版本号，遵循语义化版本规范</div>
                    </div>
                    <div class="mb-3">
                        <label for="new_release_date" class="form-label">发布日期</label>
                        <input type="date" class="form-control" id="new_release_date" name="release_date" required
                               value="{{ date('Y-m-d') }}">
                    </div>
                    <div class="mb-3">
                        <label for="release_notes" class="form-label">发布说明</label>
                        <textarea class="form-control" id="release_notes" name="release_notes" rows="4"
                                  placeholder="请输入此版本的更新内容和改进..."></textarea>
                        <div class="form-text">可选，记录此版本的主要更新内容</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> 更新版本
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function toggleRegistration(enabled) {
    axios.post('{{ route("system-settings.toggle-registration") }}', {
        enabled: enabled
    }, {
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        }
    })
    .then(response => {
        if (response.data.success) {
            location.reload();
        } else {
            alert('操作失败：' + (response.data.message || '未知错误'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('操作失败：' + (error.response?.data?.message || error.message || '网络错误'));
    });
}

function editSetting(key, value, type) {
    document.getElementById('edit_key').value = key;
    document.getElementById('edit_value').name = 'settings[' + key + ']';
    
    // 根据类型设置输入框类型
    const valueInput = document.getElementById('edit_value');
    if (type === 'boolean') {
        // 为布尔类型创建下拉选择
        valueInput.type = 'text';
        valueInput.value = value;
    } else {
        valueInput.type = 'text';
        valueInput.value = value;
    }
    
    new bootstrap.Modal(document.getElementById('editSettingModal')).show();
}

function initializeDefaults() {
    if (confirm('确定要初始化默认设置吗？这可能会覆盖现有设置。')) {
        axios.post('{{ route("system-settings.initialize-defaults") }}', {}, {
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        })
        .then(response => {
            if (response.data.success) {
                location.reload();
            } else {
                alert('初始化失败：' + (response.data.message || '未知错误'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('初始化失败：' + (error.response?.data?.message || error.message || '网络错误'));
        });
    }
}

function loadVersionHistory() {
    const historyDiv = document.getElementById('versionHistory');
    const historyList = document.getElementById('versionHistoryList');
    
    if (historyDiv.style.display === 'none') {
        // 显示加载状态
        historyList.innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin"></i> 加载中...</div>';
        historyDiv.style.display = 'block';
        
        // 加载版本历史
        axios.get('{{ route("system-settings.version-history") }}')
            .then(response => {
                if (response.data && response.data.length > 0) {
                    let html = '';
                    response.data.forEach(item => {
                        html += `
                            <div class="card mb-2">
                                <div class="card-body py-2">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0">
                                            <i class="fas fa-tag text-primary"></i>
                                            版本 ${item.version}
                                        </h6>
                                        <small class="text-muted">${item.created_at}</small>
                                    </div>
                                    <p class="mb-0 mt-2 text-muted">${item.notes}</p>
                                </div>
                            </div>
                        `;
                    });
                    historyList.innerHTML = html;
                } else {
                    historyList.innerHTML = '<div class="text-muted">暂无版本历史记录</div>';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                historyList.innerHTML = '<div class="text-danger">加载版本历史失败</div>';
            });
    } else {
        historyDiv.style.display = 'none';
    }
}

// 处理版本更新表单提交
document.getElementById('versionUpdateForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    axios.post('{{ route("system-settings.update-version") }}', formData, {
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => {
        if (response.data.success) {
            // 关闭模态框
            bootstrap.Modal.getInstance(document.getElementById('versionUpdateModal')).hide();
            
            // 显示成功消息
            alert('版本更新成功！');
            
            // 刷新页面以显示新版本信息
            location.reload();
        } else {
            alert('版本更新失败：' + (response.data.message || '未知错误'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('版本更新失败：' + (error.response?.data?.message || error.message || '网络错误'));
    });
});
</script>
@endsection