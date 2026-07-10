@extends('layouts.app')

@section('title', '地址详情')

@section('content')
<div class="flex items-center justify-between mb-6 pb-4 border-b border-border">
    <h1 class="text-xl font-semibold text-ink">地址详情</h1>
    <div class="flex gap-2">
        <a href="{{ route('locations.index') }}" class="btn btn-secondary mr-2">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 12H5 M12 19l-7-7 7-7"/></svg> 返回列表
        </a>
        <a href="{{ route('locations.edit', $location->id) }}" class="btn btn-primary">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7 M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg> 编辑
        </a>
    </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <div class="card p-5">
            <div class="text-sm font-semibold text-ink mb-3">
                <h5 class="text-sm font-semibold text-ink">地址信息</h5>
            </div>
            <div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-3">
                    <div class="col-sm-3 text-ink-muted">地址名称：</div>
                    <div class="col-sm-9">{{ $location->name }}</div>
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-3">
                    <div class="col-sm-3 text-ink-muted">校区：</div>
                    <div class="col-sm-9">
                        <span class="badge bg-blue-100 text-blue-700">{{ $location->campus_text }}</span>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-3">
                    <div class="col-sm-3 text-ink-muted">建筑类型：</div>
                    <div class="col-sm-9">
                        <span class="badge bg-slate-100 text-slate-600">{{ $location->building_type_text }}</span>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-3">
                    <div class="col-sm-3 text-ink-muted">状态：</div>
                    <div class="col-sm-9">
                        @if($location->status === 'active')
                            <span class="badge bg-green-100 text-green-700">{{ $location->status_text }}</span>
                        @else
                            <span class="badge bg-red-100 text-red-700">{{ $location->status_text }}</span>
                        @endif
                    </div>
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-3">
                    <div class="col-sm-3 text-ink-muted">排序：</div>
                    <div class="col-sm-9">{{ $location->sort_order ?: 0 }}</div>
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-3">
                    <div class="col-sm-3 text-ink-muted">描述：</div>
                    <div class="col-sm-9">{{ $location->description ?: '-' }}</div>
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-3">
                    <div class="col-sm-3 text-ink-muted">创建时间：</div>
                    <div>{{ $location->created_at ? $location->created_at->format('Y-m-d H:i:s') : '-' }}</div>
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-3">
                    <div class="col-sm-3 text-ink-muted">更新时间：</div>
                    <div>{{ $location->updated_at ? $location->updated_at->format('Y-m-d H:i:s') : '-' }}</div>
                </div>
                
                <div class="flex justify-end mt-4">
                    <a href="{{ route('locations.index') }}" class="btn btn-secondary mr-2">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 12H5 M12 19l-7-7 7-7"/></svg> 返回列表
                    </a>
                    <a href="{{ route('locations.edit', $location->id) }}" class="btn btn-primary">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7 M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg> 编辑
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div>
        <!-- 完整地址 -->
        <div class="card mb-4">
            <div class="text-sm font-semibold text-ink mb-3">
                <h6 class="text-sm font-semibold text-ink">完整地址</h6>
            </div>
            <div>
                <p class="mb-0">{{ $location->full_name }}</p>
            </div>
        </div>
        
        <!-- 校区说明 -->
        <div class="card mb-4">
            <div class="text-sm font-semibold text-ink mb-3">
                <h6 class="text-sm font-semibold text-ink">校区说明</h6>
            </div>
            <div>
                <div class="mb-2">
                    <strong>老校区：</strong>包含1-7教学楼、1-10学生宿舍
                </div>
                <div class="mb-2">
                    <strong>新校区：</strong>包含8-14教学楼、11-18学生宿舍
                </div>
                <div class="mb-2">
                    <strong>东盟校区：</strong>包含A-J教学楼、19-20学生宿舍
                </div>
            </div>
        </div>
        
        <!-- 操作按钮 -->
        <div class="card p-5">
            <div class="text-sm font-semibold text-ink mb-3">
                <h6 class="text-sm font-semibold text-ink">操作</h6>
            </div>
            <div>
                <div class="d-grid gap-2">
                    <a href="{{ route('locations.edit', $location->id) }}" class="btn btn-primary">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7 M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg> 编辑地址
                    </a>
                    <form action="{{ route('locations.destroy', $location->id) }}" method="POST" onsubmit="return confirm('确定要删除这个地址吗？')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger w-100">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18 M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2 M10 11v6 M14 11v6"/></svg> 删除地址
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
                <div class="flex justify-end mt-4">
