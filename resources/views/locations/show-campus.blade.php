@extends('layouts.app')

@section('title', '校区详情')

@section('content')
<div class="flex items-center justify-between mb-6 pb-4 border-b border-border">
    <h1 class="text-xl font-semibold text-ink">校区详情</h1>
    <div class="flex gap-2">
        <div class="flex gap-2" role="group">
            <a href="{{ route('locations.campuses') }}" class="btn btn-secondary">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 12H5 M12 19l-7-7 7-7"/></svg> 返回列表
            </a>
            <a href="{{ route('locations.edit-campus', $campus->id) }}" class="btn btn-primary">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7 M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg> 编辑校区
            </a>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <div class="card p-5">
            <div class="text-sm font-semibold text-ink mb-3">
                <h5 class="text-sm font-semibold text-ink">基本信息</h5>
            </div>
            <div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-3">
                    <div>
                        <strong>校区ID：</strong>
                    </div>
                    <div>
                        {{ $campus->id }}
                    </div>
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-3">
                    <div>
                        <strong>校区名称：</strong>
                    </div>
                    <div>
                        {{ $campus->name }}
                    </div>
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-3">
                    <div>
                        <strong>校区描述：</strong>
                    </div>
                    <div>
                        {{ $campus->description ?: '-' }}
                    </div>
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-3">
                    <div>
                        <strong>排序顺序：</strong>
                    </div>
                    <div>
                        {{ $campus->sort_order }}
                    </div>
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-3">
                    <div>
                        <strong>状态：</strong>
                    </div>
                    <div>
                        @if($campus->status == 'active')
                            <span class="badge bg-green-100 text-green-700">{{ $campus->status_text }}</span>
                        @else
                            <span class="badge bg-red-100 text-red-700">{{ $campus->status_text }}</span>
                        @endif
                    </div>
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-3">
                    <div>
                        <strong>创建时间：</strong>
                    </div>
                    <div>
                        {{ $campus->created_at->format('Y-m-d H:i:s') }}
                    </div>
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-3">
                    <div>
                        <strong>更新时间：</strong>
                    </div>
                    <div>
                        {{ $campus->updated_at->format('Y-m-d H:i:s') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div>
        <div class="card p-5">
            <div class="text-sm font-semibold text-ink mb-3">
                <h5 class="text-sm font-semibold text-ink">操作</h5>
            </div>
            <div>
                <div class="d-grid gap-2">
                    <a href="{{ route('locations.edit-campus', $campus->id) }}" class="btn btn-primary">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7 M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg> 编辑校区
                    </a>
                    
                    <form action="{{ route('locations.toggle-campus-status', $campus->id) }}" method="POST">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn btn-warning w-100">
                            <i class="fas fa-toggle-{{ $campus->status == 'active' ? 'off' : 'on' }}"></i> 
                            {{ $campus->status == 'active' ? '禁用校区' : '启用校区' }}
                        </button>
                    </form>
                    
                    <a href="{{ route('locations.create') }}?campus_id={{ $campus->id }}" class="btn btn-primary">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/></svg> 新增地址
                    </a>
                    
                    <form action="{{ route('locations.destroy-campus', $campus->id) }}" method="POST"
                          onsubmit="return confirm('确定要删除这个校区吗？删除后将无法恢复！')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger w-100" {{ $campus->canBeDeleted() ? '' : 'disabled' }}>
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18 M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2 M10 11v6 M14 11v6"/></svg> 删除校区
                            @if(!$campus->canBeDeleted())
                                <br><small class="text-ink-muted">（有关联地址，无法删除）</small>
                            @endif
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@if($campus->locations->count() > 0)
<div class="row mt-4">
    <div>
        <div class="card p-5">
            <div class="text-sm font-semibold text-ink mb-3">
                <h5 class="text-sm font-semibold text-ink">关联地址 ({{ $campus->locations->count() }})</h5>
            </div>
            <div>
                <div class="overflow-x-auto">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>地址名称</th>
                                <th>建筑类型</th>
                                <th>建筑代码</th>
                                <th>排序</th>
                                <th>状态</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($campus->locations as $location)
                            <tr>
                                <td>{{ $location->id }}</td>
                                <td>{{ $location->name }}</td>
                                <td><span class="badge bg-slate-100 text-slate-600">{{ $location->building_type_text }}</span></td>
                                <td>{{ $location->building_code ?: '-' }}</td>
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
@endif
@endsection