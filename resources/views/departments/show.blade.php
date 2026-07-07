@extends('layouts.app')

@section('title', '部门详情')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">部门信息</h3>
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr>
                            <td><strong>ID:</strong></td>
                            <td>{{ $department->id }}</td>
                        </tr>
                        <tr>
                            <td><strong>部门名称:</strong></td>
                            <td>{{ $department->name }}</td>
                        </tr>
                        <tr>
                            <td><strong>部门编码:</strong></td>
                            <td>{{ $department->code }}</td>
                        </tr>
                        <tr>
                            <td><strong>负责人:</strong></td>
                            <td>{{ $department->manager ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td><strong>联系电话:</strong></td>
                            <td>{{ $department->phone ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td><strong>邮箱:</strong></td>
                            <td>{{ $department->email ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td><strong>排序:</strong></td>
                            <td>{{ $department->sort_order }}</td>
                        </tr>
                        <tr>
                            <td><strong>状态:</strong></td>
                            <td>
                                @if($department->is_active)
                                    <span class="badge badge-success">启用</span>
                                @else
                                    <span class="badge badge-danger">禁用</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td><strong>创建时间:</strong></td>
                            <td>{{ $department->created_at->format('Y-m-d H:i:s') }}</td>
                        </tr>
                    </table>
                    @if($department->description)
                    <div class="mt-3">
                        <strong>部门描述:</strong>
                        <p>{{ $department->description }}</p>
                    </div>
                    @endif
                </div>
                <div class="card-footer">
                    @if(auth()->user()->hasRole('admin'))
                    <a href="{{ route('departments.edit', $department->id) }}" class="btn btn-warning btn-sm">
                        <i class="fas fa-edit"></i> 编辑
                    </a>
                    <form method="POST" action="{{ route('departments.destroy', $department->id) }}" style="display: inline;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('确定要删除该部门吗？')">
                            <i class="fas fa-trash"></i> 删除
                        </button>
                    </form>
                    @endif
                    <a href="{{ route('departments.index') }}" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left"></i> 返回
                    </a>
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">部门统计</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-info"><i class="fas fa-users"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">部门人员</span>
                                    <span class="info-box-number">{{ $department->users()->count() }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-success"><i class="fas fa-ticket-alt"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">相关工单</span>
                                    <span class="info-box-number">{{ $department->workorders()->count() }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-primary"><i class="fas fa-check"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">已解决工单</span>
                                    <span class="info-box-number">{{ $department->workorders()->where('status', 'resolved')->count() }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">部门人员</h3>
                </div>
                <div class="card-body">
                    @if($department->users->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>姓名</th>
                                    <th>邮箱</th>
                                    <th>角色</th>
                                    <th>联系电话</th>
                                    <th>状态</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($department->users as $user)
                                <tr>
                                    <td>{{ $user->name }}</td>
                                    <td>{{ $user->email }}</td>
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
                                    <td>{{ $user->phone ?? '-' }}</td>
                                    <td>
                                        @if($user->is_active)
                                            <span class="badge badge-success">启用</span>
                                        @else
                                            <span class="badge badge-danger">禁用</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('users.show', $user->id) }}" class="btn btn-info btn-sm">
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
                        <i class="fas fa-info-circle"></i> 该部门暂无人员
                    </div>
                    @endif
                </div>
            </div>
            
        </div>
    </div>
</div>
@endsection