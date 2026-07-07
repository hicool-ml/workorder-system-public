@extends('layouts.app')

@section('title', '用户详情')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">用户信息</h3>
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr>
                            <td><strong>ID:</strong></td>
                            <td>{{ $user->id }}</td>
                        </tr>
                        <tr>
                            <td><strong>姓名:</strong></td>
                            <td>{{ $user->name }}</td>
                        </tr>
                        <tr>
                            <td><strong>用户名:</strong></td>
                            <td>{{ $user->username }}</td>
                        </tr>
                        <tr>
                            <td><strong>邮箱:</strong></td>
                            <td>{{ $user->email }}</td>
                        </tr>
                        <tr>
                            <td><strong>角色:</strong></td>
                            <td>
                                @switch($user->role)
                                    @case('admin')
                                        <span class="badge bg-danger text-white">管理员</span>
                                        @break
                                    @case('workorder_manager')
                                        <span class="badge bg-primary text-white">工单管理员</span>
                                        @break
                                    @case('engineer')
                                        <span class="badge bg-warning text-dark">工程师</span>
                                        @break
                                    @case('user')
                                        <span class="badge bg-info text-white">普通用户</span>
                                        @break
                                    @default
                                        <span class="badge bg-secondary text-white">{{ $user->role }}</span>
                                @endswitch
                            </td>
                        </tr>
                        <tr>
                            <td><strong>部门:</strong></td>
                            <td>{{ $user->department ? $user->department->name : '-' }}</td>
                        </tr>
                        <tr>
                            <td><strong>联系电话:</strong></td>
                            <td>{{ $user->phone ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td><strong>工号:</strong></td>
                            <td>{{ $user->employee_id ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td><strong>状态:</strong></td>
                            <td>
                                @if($user->is_active)
                                    <span class="badge badge-success">启用</span>
                                @else
                                    <span class="badge badge-danger">禁用</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td><strong>创建时间:</strong></td>
                            <td>{{ $user->created_at->format('Y-m-d H:i:s') }}</td>
                        </tr>
                        <tr>
                            <td><strong>最后登录:</strong></td>
                            <td>{{ $user->last_login_at ? $user->last_login_at->format('Y-m-d H:i:s') : '-' }}</td>
                        </tr>
                    </table>
                </div>
                <div class="card-footer">
                    @if(auth()->user()->hasRole('admin'))
                    <a href="{{ route('users.edit', $user->id) }}" class="btn btn-warning btn-sm">
                        <i class="fas fa-edit"></i> 编辑
                    </a>
                    @if($user->id !== auth()->id())
                    <form method="POST" action="{{ route('users.destroy', $user->id) }}" style="display: inline;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('确定要删除该用户吗？')">
                            <i class="fas fa-trash"></i> 删除
                        </button>
                    </form>
                    @endif
                    @endif
                    <a href="{{ route('users.index') }}" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left"></i> 返回
                    </a>
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">工单统计</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-info"><i class="fas fa-ticket-alt"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">创建工单</span>
                                    <span class="info-box-number">{{ $user->createdWorkorders()->count() }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-warning"><i class="fas fa-tools"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">处理工单</span>
                                    <span class="info-box-number">{{ $user->assignedWorkorders()->count() }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-success"><i class="fas fa-check"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">已解决</span>
                                    <span class="info-box-number">{{ $user->assignedWorkorders()->where('status', 'resolved')->count() }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-primary"><i class="fas fa-clock"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">处理中</span>
                                    <span class="info-box-number">{{ $user->assignedWorkorders()->whereIn('status', ['assigned', 'processing'])->count() }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">最近工单</h3>
                </div>
                <div class="card-body">
                    @if($recentWorkorders->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>工单编号</th>
                                    <th>标题</th>
                                    <th>状态</th>
                                    <th>优先级</th>
                                    <th>创建时间</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentWorkorders as $workorder)
                                <tr>
                                    <td>{{ $workorder->ticket_no }}</td>
                                    <td>{{ $workorder->title }}</td>
                                    <td>
                                        <span class="badge badge-{{ $workorder->status == 'closed' ? 'secondary' : ($workorder->status == 'resolved' ? 'success' : 'warning') }}">
                                            {{ $workorder->status_text }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-{{ $workorder->priority == 'high' ? 'danger' : ($workorder->priority == 'medium' ? 'warning' : 'info') }}">
                                            {{ $workorder->priority_text }}
                                        </span>
                                    </td>
                                    <td>{{ $workorder->created_at->format('Y-m-d H:i') }}</td>
                                    <td>
                                        <a href="{{ route('workorders.show', $workorder->id) }}" class="btn btn-info btn-sm">
                                            <i class="fas fa-eye"></i> 查看
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> 该用户暂无工单记录
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection