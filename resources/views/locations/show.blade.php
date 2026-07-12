@extends('layouts.app')

@section('title', '地址详情')

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-xl font-semibold text-ink">{{ $location->name }}</h1>
        <p class="text-sm text-ink-muted mt-0.5">{{ $location->full_name }}</p>
    </div>
    <a href="{{ route('locations.index') }}" class="btn btn-secondary">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 12H5 M12 19l-7-7 7-7"/></svg>
        <span>返回列表</span>
    </a>
</div>

<div class="max-w-2xl">
    <div class="card p-6 mb-4">
        <h3 class="text-sm font-semibold text-ink mb-4">基本信息</h3>
        <div class="space-y-3">
            <div class="flex items-center justify-between py-2 border-b border-border">
                <span class="text-sm text-ink-muted">所属校区</span>
                <span class="text-sm font-medium text-ink">{{ $location->campus_text }}</span>
            </div>
            <div class="flex items-center justify-between py-2 border-b border-border">
                <span class="text-sm text-ink-muted">建筑类型</span>
                <span class="text-sm font-medium text-ink">{{ $location->building_type_text }}</span>
            </div>
            <div class="flex items-center justify-between py-2 border-b border-border">
                <span class="text-sm text-ink-muted">建筑编码</span>
                <span class="text-sm font-medium text-ink">{{ $location->building_code ?: '-' }}</span>
            </div>
            <div class="flex items-center justify-between py-2 border-b border-border">
                <span class="text-sm text-ink-muted">排序</span>
                <span class="text-sm font-medium text-ink">{{ $location->sort_order }}</span>
            </div>
            <div class="flex items-center justify-between py-2 border-b border-border">
                <span class="text-sm text-ink-muted">状态</span>
                @if($location->status == 'active')
                    <span class="badge bg-green-100 text-green-700">启用</span>
                @else
                    <span class="badge bg-surface-muted text-ink-muted">禁用</span>
                @endif
            </div>
            @if($location->description)
            <div class="py-2">
                <span class="text-sm text-ink-muted block mb-1">描述</span>
                <span class="text-sm text-ink">{{ $location->description }}</span>
            </div>
            @endif
            <div class="flex items-center justify-between py-2">
                <span class="text-sm text-ink-muted">创建时间</span>
                <span class="text-sm text-ink-muted">{{ $location->created_at ? $location->created_at->format('Y-m-d H:i') : '-' }}</span>
            </div>
        </div>
    </div>

    <div class="flex items-center gap-2">
        <a href="{{ route('locations.edit', $location->id) }}" class="btn btn-primary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7 M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            <span>编辑</span>
        </a>
    </div>
</div>
@endsection
