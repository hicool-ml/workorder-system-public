@extends('layouts.app')

@section('title', '校区管理')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">地址管理</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group" role="group">
            <a href="{{ route('locations.index') }}" class="btn {{ request()->routeIs('locations.index') ? 'btn-primary' : 'btn-outline-primary' }}">
                <i class="fas fa-building"></i> 地址管理
            </a>
            <a href="{{ route('locations.campuses') }}" class="btn {{ request()->routeIs('locations.campuses') ? 'btn-primary' : 'btn-outline-primary' }}">
                <i class="fas fa-map-marker-alt"></i> 校区管理
            </a>
        </div>
        <a href="{{ route('locations.create-campus') }}" class="btn btn-primary ms-2">
            <i class="fas fa-plus"></i> 新增校区
        </a>
    </div>
</div>

<!-- 搜索和筛选 -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('locations.campuses') }}">
            <div class="row g-3">
                <div class="col-md-4">
                    <label for="keyword" class="form-label">关键词</label>
                    <input type="text" class="form-control" id="keyword" name="keyword" autocomplete="off"
                           value="{{ request('keyword') }}" placeholder="校区名称、代码或描述" autocomplete="off">
                </div>
                <div class="col-md-3">
                    <label for="status" class="form-label">状态</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">全部状态</option>
                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>启用</option>
                        <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>禁用</option>
                    </select>
                </div>
                <div class="col-md-5 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="fas fa-search"></i> 搜索
                    </button>
                    <a href="{{ route('locations.campuses') }}" class="btn btn-secondary">
                        <i class="fas fa-times"></i> 重置
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- 校区列表 -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>校区名称</th>
                        <th>排序</th>
                        <th>状态</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($campuses as $campus)
                    <tr>
                        <td>{{ $campus->id }}</td>
                        <td>{{ $campus->name }}</td>
                        <td>{{ $campus->sort_order }}</td>
                        <td>
                            @if($campus->status == 'active')
                                <span class="badge bg-success">{{ $campus->status_text }}</span>
                            @else
                                <span class="badge bg-danger">{{ $campus->status_text }}</span>
                            @endif
                        </td>
                        <td>
                            <div class="btn-group" role="group">
                                <a href="{{ route('locations.show-campus', $campus->id) }}"
                                   class="btn btn-sm btn-outline-info" title="查看">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('locations.edit-campus', $campus->id) }}"
                                   class="btn btn-sm btn-outline-primary" title="编辑">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="{{ route('locations.toggle-campus-status', $campus->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-sm btn-outline-warning" title="切换状态">
                                        <i class="fas fa-toggle-{{ $campus->status == 'active' ? 'off' : 'on' }}"></i>
                                    </button>
                                </form>
                                <form action="{{ route('locations.destroy-campus', $campus->id) }}" method="POST"
                                      onsubmit="return confirm('确定要删除这个校区吗？')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="删除">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center">暂无校区数据</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- 分页 -->
        <div class="d-flex justify-content-center mt-4">
            {{ $campuses->appends(request()->query())->links('pagination.bootstrap-5') }}
        </div>
    </div>
</div>
@endsection