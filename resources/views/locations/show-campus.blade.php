@extends('layouts.app')

@section('title', '校区详情')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">校区详情</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group" role="group">
            <a href="{{ route('locations.campuses') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> 返回列表
            </a>
            <a href="{{ route('locations.edit-campus', $campus->id) }}" class="btn btn-primary">
                <i class="fas fa-edit"></i> 编辑校区
            </a>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div class="">
        <div class="card p-5">
            <div class="text-sm font-semibold text-ink mb-3">
                <h5 class="card-title mb-0">基本信息</h5>
            </div>
            <div >
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-3">
                    <div class="">
                        <strong>校区ID：</strong>
                    </div>
                    <div class="">
                        {{ $campus->id }}
                    </div>
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-3">
                    <div class="">
                        <strong>校区名称：</strong>
                    </div>
                    <div class="">
                        {{ $campus->name }}
                    </div>
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-3">
                    <div class="">
                        <strong>校区描述：</strong>
                    </div>
                    <div class="">
                        {{ $campus->description ?: '-' }}
                    </div>
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-3">
                    <div class="">
                        <strong>排序顺序：</strong>
                    </div>
                    <div class="">
                        {{ $campus->sort_order }}
                    </div>
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-3">
                    <div class="">
                        <strong>状态：</strong>
                    </div>
                    <div class="">
                        @if($campus->status == 'active')
                            <span class="badge bg-green-100 text-green-700">{{ $campus->status_text }}</span>
                        @else
                            <span class="badge bg-red-100 text-red-700">{{ $campus->status_text }}</span>
                        @endif
                    </div>
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-3">
                    <div class="">
                        <strong>创建时间：</strong>
                    </div>
                    <div class="">
                        {{ $campus->created_at->format('Y-m-d H:i:s') }}
                    </div>
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-3">
                    <div class="">
                        <strong>更新时间：</strong>
                    </div>
                    <div class="">
                        {{ $campus->updated_at->format('Y-m-d H:i:s') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="">
        <div class="card p-5">
            <div class="text-sm font-semibold text-ink mb-3">
                <h5 class="card-title mb-0">操作</h5>
            </div>
            <div >
                <div class="d-grid gap-2">
                    <a href="{{ route('locations.edit-campus', $campus->id) }}" class="btn btn-primary">
                        <i class="fas fa-edit"></i> 编辑校区
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
                        <i class="fas fa-plus"></i> 新增地址
                    </a>
                    
                    <form action="{{ route('locations.destroy-campus', $campus->id) }}" method="POST"
                          onsubmit="return confirm('确定要删除这个校区吗？删除后将无法恢复！')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger w-100" {{ $campus->canBeDeleted() ? '' : 'disabled' }}>
                            <i class="fas fa-trash"></i> 删除校区
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
    <div class="">
        <div class="card p-5">
            <div class="text-sm font-semibold text-ink mb-3">
                <h5 class="card-title mb-0">关联地址 ({{ $campus->locations->count() }})</h5>
            </div>
            <div >
                <div class="table-responsive">
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
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('locations.show', $location->id) }}"
                                           class="btn btn-sm btn-outline-info" title="查看">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('locations.edit', $location->id) }}"
                                           class="btn btn-sm btn-outline-primary" title="编辑">
                                            <i class="fas fa-edit"></i>
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