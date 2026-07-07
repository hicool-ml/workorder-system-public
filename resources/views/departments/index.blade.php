@extends('layouts.app')

@section('title', '部门管理')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">部门管理</h3>
                    <div class="card-tools">
                        @if(auth()->user()->hasRole('admin'))
                        <a href="{{ route('departments.create') }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus"></i> 新增部门
                        </a>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('departments.index') }}" class="mb-3">
                        <div class="row">
                            <div class="col-md-4">
                                <input type="text" name="name" class="form-control" placeholder="部门名称" value="{{ request('name') }}">
                            </div>
                            <div class="col-md-2">
                                <select name="is_active" class="form-control">
                                    <option value="">全部状态</option>
                                    <option value="1" {{ request('is_active') == '1' ? 'selected' : '' }}>启用</option>
                                    <option value="0" {{ request('is_active') == '0' ? 'selected' : '' }}>禁用</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-info">
                                    <i class="fas fa-search"></i> 搜索
                                </button>
                                <a href="{{ route('departments.index') }}" class="btn btn-secondary">
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
                                    <th>部门名称</th>
                                    <th>部门编码</th>
                                    <th>负责人</th>
                                    <th>联系电话</th>
                                    <th>排序</th>
                                    <th>状态</th>
                                    <th>创建时间</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($departments as $department)
                                <tr>
                                    <td>{{ $department->id }}</td>
                                    <td>{{ $department->name }}</td>
                                    <td>{{ $department->code }}</td>
                                    <td>{{ $department->manager ?? '-' }}</td>
                                    <td>{{ $department->phone ?? '-' }}</td>
                                    <td>{{ $department->sort_order }}</td>
                                    <td>
                                        @if($department->is_active)
                                            <span class="badge bg-success text-white">启用</span>
                                        @else
                                            <span class="badge bg-danger text-white">禁用</span>
                                        @endif
                                    </td>
                                    <td>{{ $department->created_at->format('Y-m-d H:i') }}</td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="{{ route('departments.show', $department->id) }}" class="btn btn-info btn-sm">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            @if(auth()->user()->hasRole('admin'))
                                            <a href="{{ route('departments.edit', $department->id) }}" class="btn btn-warning btn-sm">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form method="POST" action="{{ route('departments.destroy', $department->id) }}" style="display: inline;">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('确定要删除该部门吗？')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center">暂无数据</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-between">
                        <div>
                            显示 {{ $departments->firstItem() }} - {{ $departments->lastItem() }} 条，共 {{ $departments->total() }} 条记录
                        </div>
                        <div>
                            {{ $departments->appends(request()->query())->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection