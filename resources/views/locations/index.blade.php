@extends('layouts.app')

@section('title', '地址管理')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">地址管理</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="{{ route('locations.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> 新增地址
        </a>
    </div>
</div>

<!-- 搜索和筛选 -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('locations.index') }}">
            <div class="row g-3">
                <div class="col-md-3">
                    <label for="keyword" class="form-label">关键词</label>
                    <input type="text" class="form-control" id="keyword" name="keyword" 
                           value="{{ request('keyword') }}" placeholder="地址名称、楼栋代码">
                </div>
                <div class="col-md-2">
                    <label for="campus" class="form-label">校区</label>
                    <select class="form-select" id="campus" name="campus">
                        <option value="">全部校区</option>
                        @foreach(\App\Models\Location::CAMPUSES as $key => $value)
                        <option value="{{ $key }}" {{ request('campus') == $key ? 'selected' : '' }}>
                            {{ $value }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="building_type" class="form-label">建筑类型</label>
                    <select class="form-select" id="building_type" name="building_type">
                        <option value="">全部类型</option>
                        @foreach(\App\Models\Location::BUILDING_TYPES as $key => $value)
                        <option value="{{ $key }}" {{ request('building_type') == $key ? 'selected' : '' }}>
                            {{ $value }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="status" class="form-label">状态</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">全部状态</option>
                        @foreach(\App\Models\Location::STATUSES as $key => $value)
                        <option value="{{ $key }}" {{ request('status') == $key ? 'selected' : '' }}>
                            {{ $value }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="fas fa-search"></i> 搜索
                    </button>
                    <a href="{{ route('locations.index') }}" class="btn btn-secondary">
                        <i class="fas fa-times"></i> 重置
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- 地址列表 -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>地址名称</th>
                        <th>校区</th>
                        <th>建筑类型</th>
                        <th>楼栋代码</th>
                        <th>排序</th>
                        <th>状态</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($locations as $location)
                    <tr>
                        <td>{{ $location->id }}</td>
                        <td>{{ $location->name }}</td>
                        <td><span class="badge bg-info">{{ $location->campus_text }}</span></td>
                        <td><span class="badge bg-secondary">{{ $location->building_type_text }}</span></td>
                        <td>{{ $location->building_code ?? '-' }}</td>
                        <td>{{ $location->sort_order }}</td>
                        <td>
                            @if($location->status == 'active')
                                <span class="badge bg-success">{{ $location->status_text }}</span>
                            @else
                                <span class="badge bg-danger">{{ $location->status_text }}</span>
                            @endif
                        </td>
                        <td>
                            <div class="btn-group" role="group">
                                <a href="{{ route('locations.show', $location->id) }}" 
                                   class="btn btn-sm btn-outline-info" title="查看">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('locations.edit', $location->id) }}" 
                                   class="btn btn-sm btn-outline-primary" title="编辑">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="{{ route('locations.destroy', $location->id) }}" method="POST" 
                                      onsubmit="return confirm('确定要删除这个地址吗？')">
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
                        <td colspan="8" class="text-center">暂无地址数据</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- 分页 -->
        <div class="d-flex justify-content-center mt-4">
            {{ $locations->appends(request()->query())->links() }}
        </div>
    </div>
</div>
@endsection