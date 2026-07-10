@extends('layouts.app')

@section('title', '地址管理')

@section('content')
<div class="flex items-center justify-between mb-6 pb-4 border-b border-border">
    <h1 class="text-xl font-semibold text-ink">地址管理</h1>
    <div class="flex gap-2">
        <div class="flex gap-2" role="group">
            <a href="{{ route('locations.index') }}" class="btn {{ request()->routeIs('locations.index') ? 'btn-primary' : 'btn-outline-primary' }}">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 21h18 M5 21V7l8-4v18 M19 21V11l-6-4 M9 9v.01 M9 12v.01 M9 15v.01 M9 18v.01"/></svg> 地址管理
            </a>
            <a href="{{ route('locations.campuses') }}" class="btn {{ request()->routeIs('locations.campuses') ? 'btn-primary' : 'btn-outline-primary' }}">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z M12 13a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/></svg> 校区管理
            </a>
        </div>
        @if(request()->routeIs('locations.index'))
            <a href="{{ route('locations.create') }}" class="btn btn-primary ml-2">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/></svg> 新增地址
            </a>
        @else
            <a href="{{ route('locations.create-campus') }}" class="btn btn-primary ml-2">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/></svg> 新增校区
            </a>
        @endif
    </div>
</div>

<!-- 搜索和筛选 -->
<div class="card mb-4">
    <div>
        <form method="GET" action="{{ route('locations.index') }}">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                    <label for="keyword" class="label">关键词</label>
                    <input type="text" class="input" id="keyword" name="keyword" autocomplete="off"
                           value="{{ request('keyword') }}" placeholder="地址名称" autocomplete="off">
                </div>
                <div>
                    <label for="campus_id" class="label">校区</label>
                    <select class="input" id="campus_id" name="campus_id">
                        <option value="">全部校区</option>
                        @foreach($campuses as $id => $name)
                        <option value="{{ $id }}" {{ request('campus_id') == $id ? 'selected' : '' }}>
                            {{ $name }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="building_type" class="label">建筑类型</label>
                    <select class="input" id="building_type" name="building_type">
                        <option value="">全部类型</option>
                        @foreach(\App\Models\Location::BUILDING_TYPES as $key => $value)
                        <option value="{{ $key }}" {{ request('building_type') == $key ? 'selected' : '' }}>
                            {{ $value }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="status" class="label">状态</label>
                    <select class="input" id="status" name="status">
                        <option value="">全部状态</option>
                        @foreach(\App\Models\Location::STATUSES as $key => $value)
                        <option value="{{ $key }}" {{ request('status') == $key ? 'selected' : '' }}>
                            {{ $value }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary mr-2">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 19a8 8 0 1 0 0-16 8 8 0 0 0 0 16z M21 21l-4.35-4.35"/></svg> 搜索
                    </button>
                    <a href="{{ route('locations.index') }}" class="btn btn-secondary">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18 6L6 18M6 6l12 12"/></svg> 重置
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- 地址列表 -->
<div class="card p-5">
    <div>
        <div class="overflow-x-auto">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>地址名称</th>
                        <th>校区</th>
                        <th>建筑类型</th>
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
                        <td><span class="badge bg-blue-100 text-blue-700">{{ $location->campus_text }}</span></td>
                        <td><span class="badge bg-slate-100 text-slate-600">{{ $location->building_type_text }}</span></td>
                        <td>{{ $location->sort_order }}</td>
                        <td>
                            @if($location->status == 'active')
                                <span class="badge bg-green-100 text-green-700">{{ $location->status_text }}</span>
                            @else
                                <span class="badge bg-red-100 text-red-700">{{ $location->status_text }}</span>
                            @endif
                        </td>
                        <td>
                            <div class="flex gap-2" role="group">
                                <a href="{{ route('locations.show', $location->id) }}"
                                   class="btn btn-sm btn-outline-info" title="查看">
                                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/></svg>
                                </a>
                                <a href="{{ route('locations.edit', $location->id) }}"
                                   class="btn btn-sm btn-outline-primary" title="编辑">
                                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7 M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                </a>
                                <form action="{{ route('locations.destroy', $location->id) }}" method="POST"
                                      onsubmit="return confirm('确定要删除这个地址吗？')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="删除">
                                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18 M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2 M10 11v6 M14 11v6"/></svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center">暂无地址数据</td>
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