@extends('layouts.app')

@section('title', '用户管理')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">用户管理</h3>
                    <div class="card-tools">
                        @if(auth()->user()->hasRole('admin'))
                        <a href="{{ route('users.create') }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus"></i> 新增用户
                        </a>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('users.index') }}" class="mb-3">
                        <div class="row">
                            <div class="col-md-2">
                                <input type="text" name="keyword" class="form-control" placeholder="姓名/用户名" value="{{ request('keyword') }}">
                            </div>
                            <div class="col-md-2">
                                <input type="text" name="email" class="form-control" placeholder="邮箱" value="{{ request('email') }}">
                            </div>
                            <div class="col-md-2">
                                <select name="role" class="form-control">
                                    <option value="">全部角色</option>
                                    <option value="admin" {{ request('role') == 'admin' ? 'selected' : '' }}>管理员</option>
                                    <option value="workorder_manager" {{ request('role') == 'workorder_manager' ? 'selected' : '' }}>工单管理员</option>
                                    <option value="engineer" {{ request('role') == 'engineer' ? 'selected' : '' }}>工程师</option>
                                    <option value="user" {{ request('role') == 'user' ? 'selected' : '' }}>普通用户</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select name="department_id" class="form-control">
                                    <option value="">全部部门</option>
                                    @foreach($departments as $department)
                                    <option value="{{ $department->id }}" {{ request('department_id') == $department->id ? 'selected' : '' }}>{{ $department->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-info">
                                    <i class="fas fa-search"></i> 搜索
                                </button>
                                <a href="{{ route('users.index') }}" class="btn btn-secondary">
                                    <i class="fas fa-redo"></i> 重置
                                </a>
                            </div>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>姓名</th>
                                    <th>用户名</th>
                                    <th>邮箱</th>
                                    <th>角色</th>
                                    <th>部门</th>
                                    <th>联系电话</th>
                                    <th>状态</th>
                                    <th>创建时间</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($users as $user)
                                <tr>
                                    <td>{{ $user->id }}</td>
                                    <td>{{ $user->name }}</td>
                                    <td>{{ $user->username }}</td>
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
                                    <td>{{ $user->department ? $user->department->name : '-' }}</td>
                                    <td>{{ $user->phone ?? '-' }}</td>
                                    <td>
                                        @if($user->is_active)
                                            <span class="badge bg-success text-white">启用</span>
                                        @else
                                            <span class="badge bg-danger text-white">禁用</span>
                                        @endif
                                    </td>
                                    <td>{{ $user->created_at->format('Y-m-d H:i') }}</td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="{{ route('users.show', $user->id) }}" class="btn btn-info btn-sm">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            @if(auth()->user()->hasRole('admin'))
                                            <a href="{{ route('users.edit', $user->id) }}" class="btn btn-warning btn-sm">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            @if($user->id !== auth()->id())
                                            <form method="POST" action="{{ route('users.destroy', $user->id) }}" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('确定要删除该用户吗？')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                            @endif
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="10" class="text-center">暂无数据</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-between">
                        <div>
                            显示 {{ $users->firstItem() }} - {{ $users->lastItem() }} 条，共 {{ $users->total() }} 条记录
                        </div>
                        <div>
                            {{ $users->appends(request()->query())->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection