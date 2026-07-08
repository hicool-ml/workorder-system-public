@extends('layouts.app')

@section('title', '地址详情 - ' . $location->name)

@section('content')
<div class="flex items-center justify-between mb-6 gap-3 flex-wrap">
    <h1 class="text-xl font-semibold text-ink">地址详情</h1>
    <div class="flex items-center gap-2">
        <a href="{{ route('locations.edit', $location->id) }}" class="btn btn-primary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7 M18.5 2.5a2.1 2.1 0 0 1 3 3L12 15l-4 1 1-4z"/></svg>
            <span>编辑</span>
        </a>
        <a href="{{ route('locations.index') }}" class="btn btn-secondary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7 7-7M3 12h18"/></svg>
            <span>返回列表</span>
        </a>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2">
        <div class="card p-5">
            <h3 class="text-sm font-semibold text-ink mb-4">地址信息</h3>
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between gap-2"><dt style="color: var(--c-ink-muted);">地址名称</dt><dd class="font-medium text-ink text-right">{{ $location->name }}</dd></div>
                <div class="flex justify-between gap-2"><dt style="color: var(--c-ink-muted);">校区</dt><dd class="text-right"><span class="badge bg-blue-100 text-blue-700">{{ $location->campus_text }}</span></dd></div>
                <div class="flex justify-between gap-2"><dt style="color: var(--c-ink-muted);">建筑类型</dt><dd class="text-right"><span class="badge" style="background-color: var(--c-muted); color: var(--c-ink-muted);">{{ $location->building_type_text }}</span></dd></div>
                <div class="flex justify-between gap-2"><dt style="color: var(--c-ink-muted);">楼栋代码</dt><dd class="text-ink text-right">{{ $location->building_code ?: '-' }}</dd></div>
                <div class="flex justify-between gap-2"><dt style="color: var(--c-ink-muted);">状态</dt><dd class="text-right">@if($location->status === 'active')<span class="badge bg-green-100 text-green-700">{{ $location->status_text }}</span>@else<span class="badge bg-red-100 text-red-700">{{ $location->status_text }}</span>@endif</dd></div>
                <div class="flex justify-between gap-2"><dt style="color: var(--c-ink-muted);">排序</dt><dd class="text-ink text-right">{{ $location->sort_order ?: 0 }}</dd></div>
                <div class="flex justify-between gap-2"><dt style="color: var(--c-ink-muted);">描述</dt><dd class="text-ink text-right">{{ $location->description ?: '-' }}</dd></div>
                <div class="flex justify-between gap-2"><dt style="color: var(--c-ink-muted);">创建时间</dt><dd class="text-ink text-right">{{ $location->created_at->format('Y-m-d H:i') }}</dd></div>
                <div class="flex justify-between gap-2"><dt style="color: var(--c-ink-muted);">更新时间</dt><dd class="text-ink text-right">{{ $location->updated_at->format('Y-m-d H:i') }}</dd></div>
            </dl>
        </div>
    </div>

    <div class="lg:col-span-1 space-y-4">
        <div class="card p-5">
            <h3 class="text-sm font-semibold text-ink mb-3">完整地址</h3>
            <p class="text-sm text-ink">{{ $location->full_name }}</p>
        </div>
        <form method="POST" action="{{ route('locations.destroy', $location->id) }}" onsubmit="return confirm('确定要删除这个地址吗？')">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-danger w-full">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18 M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                <span>删除地址</span>
            </button>
        </form>
    </div>
</div>
@endsection