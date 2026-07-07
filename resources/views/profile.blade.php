@extends('layouts.app')

@section('title', '个人资料')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">个人资料</h1>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">基本信息</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('profile.update') }}">
                    @csrf
                    <input type="hidden" name="_method" value="PUT">
                    
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="name" class="form-label">姓名</label>
                            <input type="text" class="form-control" id="name" name="name" 
                                   value="{{ auth()->user()->name }}" required>
                        </div>
                        <div class="col-md-6">
                            <label for="email" class="form-label">邮箱</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="{{ auth()->user()->email }}" required>
                        </div>
                    </div>
                    
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="phone" class="form-label">电话</label>
                            <input type="tel" class="form-control" id="phone" name="phone" 
                                   value="{{ auth()->user()->phone }}">
                        </div>
                        <div class="col-md-6">
                            <label for="employee_id" class="form-label">员工编号</label>
                            <input type="text" class="form-control" id="employee_id" name="employee_id" 
                                   value="{{ auth()->user()->employee_id }}">
                        </div>
                    </div>
                    
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="location" class="form-label">办公地点</label>
                            <input type="text" class="form-control" id="location" name="location" 
                                   value="{{ auth()->user()->location }}">
                        </div>
                        <div class="col-md-6">
                            <label for="department_id" class="form-label">所属部门</label>
                            <select class="form-select" id="department_id" name="department_id">
                                <option value="">请选择部门</option>
                                @foreach(App\Models\Department::where('status', 'active')->get() as $department)
                                <option value="{{ $department->id }}" 
                                        {{ auth()->user()->department_id == $department->id ? 'selected' : '' }}>
                                    {{ $department->full_path ?? $department->name }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="remarks" class="form-label">备注</label>
                        <textarea class="form-control" id="remarks" name="remarks" rows="3">{{ auth()->user()->remarks }}</textarea>
                    </div>
                    
                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> 保存信息
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- 修改密码 -->
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="card-title mb-0">修改密码</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('profile.password') }}">
                    @csrf
                    @method('PUT')
                    
                    <div class="mb-3">
                        <label for="current_password" class="form-label">当前密码</label>
                        <input type="password" class="form-control" id="current_password" 
                               name="current_password" required>
                    </div>
                    
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="password" class="form-label">新密码</label>
                            <input type="password" class="form-control" id="password" 
                                   name="password" required minlength="6">
                        </div>
                        <div class="col-md-6">
                            <label for="password_confirmation" class="form-label">确认密码</label>
                            <input type="password" class="form-control" id="password_confirmation" 
                                   name="password_confirmation" required>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-key"></i> 修改密码
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <!-- 用户统计 -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="card-title mb-0">我的统计</h6>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-6 mb-3">
                        <div class="border rounded p-2">
                            <strong>{{ auth()->user()->createdWorkorders()->count() }}</strong>
                            <br><small class="text-muted">创建工单</small>
                        </div>
                    </div>
                    <div class="col-6 mb-3">
                        <div class="border rounded p-2">
                            <strong>{{ auth()->user()->assignedWorkorders()->count() }}</strong>
                            <br><small class="text-muted">处理工单</small>
                        </div>
                    </div>
                </div>
                
                @if(auth()->user()->canHandleWorkorders())
                <div class="row text-center mt-3">
                    <div class="col-6 mb-3">
                        <div class="border rounded p-2">
                            <strong>{{ auth()->user()->pending_workorders_count }}</strong>
                            <br><small class="text-muted">待处理</small>
                        </div>
                    </div>
                    <div class="col-6 mb-3">
                        <div class="border rounded p-2">
                            <strong>{{ auth()->user()->today_workorders_count }}</strong>
                            <br><small class="text-muted">今日处理</small>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
        
        <!-- 角色权限 -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="card-title mb-0">角色权限</h6>
            </div>
            <div class="card-body">
                <div class="mb-2">
                    <strong>当前角色：</strong>
                    <span class="badge bg-primary">{{ auth()->user()->role_text }}</span>
                </div>
                
                <div class="mb-2">
                    <strong>账户状态：</strong>
                    <span class="badge bg-{{ auth()->user()->status == 'active' ? 'success' : 'danger' }}">
                        {{ auth()->user()->status_text }}
                    </span>
                </div>
                
                <div class="mb-2">
                    <strong>权限列表：</strong>
                    <ul class="mt-2">
                        @if(auth()->user()->canHandleWorkorders())
                        <li><i class="fas fa-check text-success"></i> 处理工单</li>
                        @endif
                        @if(auth()->user()->canAssignWorkorders())
                        <li><i class="fas fa-check text-success"></i> 分配工单</li>
                        @endif
                        @if(auth()->user()->canManageWorkorderTypes())
                        <li><i class="fas fa-check text-success"></i> 管理工单类型</li>
                        @endif
                        @if(auth()->user()->canManageDepartments())
                        <li><i class="fas fa-check text-success"></i> 管理部门</li>
                        @endif
                        @if(auth()->user()->canViewReports())
                        <li><i class="fas fa-check text-success"></i> 查看报表</li>
                        @endif
                    </ul>
                </div>
            </div>
        </div>
        
        <!-- 最近活动 -->
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">最近活动</h6>
            </div>
            <div class="card-body">
                @if($recentLogs = App\Models\WorkorderLog::where('user_id', auth()->id())->latest()->limit(5)->get())
                @foreach($recentLogs as $log)
                <div class="d-flex justify-content-between align-items-center mb-2 p-2 border rounded">
                    <div>
                        <small class="text-muted">{{ $log->action_text }}</small>
                        @if($log->content)
                        <br><small>{{ Str::limit($log->content, 30) }}</small>
                        @endif
                    </div>
                    <small class="text-muted">{{ $log->created_at->format('m-d H:i') }}</small>
                </div>
                @endforeach
                @else
                <div class="text-center text-muted">
                    <i class="fas fa-history fa-2x mb-2"></i>
                    <p>暂无活动记录</p>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection