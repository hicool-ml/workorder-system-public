@extends('layouts.app')

@section('title', '分类详情 - ' . $workorderCategory->name)

@section('content')
<div class="flex items-center justify-between mb-6 gap-3 flex-wrap">
    <h1 class="text-xl font-semibold text-ink">分类详情</h1>
    <div class="flex items-center gap-2">
        <a href="{{ route('workorder-categories.edit', $workorderCategory->id) }}" class="btn btn-primary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7 M18.5 2.5a2.1 2.1 0 0 1 3 3L12 15l-4 1 1-4z"/></svg>
            <span>编辑</span>
        </a>
        <a href="{{ route('workorder-categories.index') }}" class="btn btn-secondary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7 7-7M3 12h18"/></svg>
            <span>返回列表</span>
        </a>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 space-y-6">
        {{-- Basic info --}}
        <div class="card p-5">
            <h3 class="text-sm font-semibold text-ink mb-4">基本信息</h3>
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between gap-2"><dt style="color: var(--c-ink-muted);">分类名称</dt><dd class="font-medium text-ink text-right">{{ $workorderCategory->name }}</dd></div>
                <div class="flex justify-between gap-2"><dt style="color: var(--c-ink-muted);">分类编码</dt><dd class="text-ink text-right">{{ $workorderCategory->code }}</dd></div>
                <div class="flex justify-between gap-2 items-center"><dt style="color: var(--c-ink-muted);">分类层级</dt><dd class="text-right"><span class="badge bg-blue-100 text-blue-700">{{ $workorderCategory->level_text }}</span></dd></div>
                <div class="flex justify-between gap-2"><dt style="color: var(--c-ink-muted);">排序</dt><dd class="text-ink text-right">{{ $workorderCategory->sort_order }}</dd></div>
                <div class="flex justify-between gap-2 items-center"><dt style="color: var(--c-ink-muted);">状态</dt><dd class="text-right">@if($workorderCategory->status)<span class="badge bg-green-100 text-green-700">{{ $workorderCategory->status_text }}</span>@else<span class="badge bg-red-100 text-red-700">{{ $workorderCategory->status_text }}</span>@endif</dd></div>
                <div class="flex justify-between gap-2"><dt style="color: var(--c-ink-muted);">完整路径</dt><dd class="text-ink text-right">{{ $workorderCategory->full_path }}</dd></div>
                @if($workorderCategory->description)
                <div class="pt-3 border-t border-border">
                    <p class="text-xs font-medium mb-1" style="color: var(--c-ink-muted);">分类描述</p>
                    <p class="text-sm text-ink">{!! nl2br(e($workorderCategory->description)) !!}</p>
                </div>
                @endif
                <div class="flex justify-between gap-2"><dt style="color: var(--c-ink-muted);">创建时间</dt><dd class="text-ink text-right">{{ $workorderCategory->created_at->format('Y-m-d H:i') }}</dd></div>
                @if($workorderCategory->parent)
                <div class="flex justify-between gap-2"><dt style="color: var(--c-ink-muted);">父分类</dt><dd class="text-right"><a href="{{ route('workorder-categories.show', $workorderCategory->parent->id) }}" class="text-brand-600 hover:underline">{{ $workorderCategory->parent->name }}</a></dd></div>
                @endif
            </dl>
        </div>

        {{-- Children --}}
        @if($workorderCategory->children()->count() > 0)
        <div class="card">
            <div class="px-5 py-4 border-b border-border flex items-center justify-between">
                <h3 class="text-sm font-semibold text-ink">子分类</h3>
                <span class="badge bg-blue-100 text-blue-700">{{ $workorderCategory->children()->count() }}</span>
            </div>
            <div class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-3">
                @foreach($workorderCategory->children as $child)
                <div class="p-4 rounded-lg border border-border">
                    <div class="flex items-center justify-between gap-2 mb-2">
                        <a href="{{ route('workorder-categories.show', $child->id) }}" class="font-medium text-ink hover:text-brand-600">{{ $child->name }}</a>
                        @if($child->status == 'active')<span class="badge bg-green-100 text-green-700">启用</span>@else<span class="badge bg-red-100 text-red-700">禁用</span>@endif
                    </div>
                    <p class="text-xs" style="color: var(--c-ink-subtle);">编码：{{ $child->code }} · {{ $child->level_text }}</p>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Related workorders --}}
        <div class="card">
            <div class="px-5 py-4 border-b border-border flex items-center justify-between">
                <h3 class="text-sm font-semibold text-ink">相关工单</h3>
                <span class="badge bg-blue-100 text-blue-700">{{ $workorderCategory->workorders()->count() }}</span>
            </div>
            @if($workorderCategory->workorders()->count() > 0)
            <div class="md:hidden divide-y divide-border">
                @foreach($workorderCategory->workorders()->latest()->limit(10)->get() as $workorder)
                <a href="{{ route('workorders.show', $workorder->id) }}" class="block p-4">
                    <div class="flex items-center justify-between gap-2 mb-1">
                        <span class="font-medium text-ink truncate">{{ Str::limit($workorder->title, 30) }}</span>
                        <span class="badge {{ $workorder->status == 'closed' ? 'bg-gray-100 text-gray-600' : 'bg-blue-100 text-blue-700' }}">{{ $workorder->status_text }}</span>
                    </div>
                    <p class="text-xs" style="color: var(--c-ink-subtle);">{{ $workorder->ticket_no }} · {{ $workorder->creator->name }} · {{ $workorder->created_at->format('m-d H:i') }}</p>
                </a>
                @endforeach
            </div>
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-sm">
                    <thead><tr class="border-b border-border text-left">
                        <th class="px-5 py-3 font-medium" style="color: var(--c-ink-muted);">工单号</th>
                        <th class="px-5 py-3 font-medium" style="color: var(--c-ink-muted);">标题</th>
                        <th class="px-5 py-3 font-medium" style="color: var(--c-ink-muted);">状态</th>
                        <th class="px-5 py-3 font-medium" style="color: var(--c-ink-muted);">创建人</th>
                        <th class="px-5 py-3 font-medium" style="color: var(--c-ink-muted);">创建时间</th>
                    </tr></thead>
                    <tbody>
                    @foreach($workorderCategory->workorders()->latest()->limit(10)->get() as $workorder)
                    <tr class="border-b border-border">
                        <td class="px-5 py-3"><a href="{{ route('workorders.show', $workorder->id) }}" class="text-brand-600 hover:underline">{{ $workorder->ticket_no }}</a></td>
                        <td class="px-5 py-3 text-ink">{{ Str::limit($workorder->title, 30) }}</td>
                        <td class="px-5 py-3"><span class="badge {{ $workorder->status == 'closed' ? 'bg-gray-100 text-gray-600' : 'bg-blue-100 text-blue-700' }}">{{ $workorder->status_text }}</span></td>
                        <td class="px-5 py-3 text-ink">{{ $workorder->creator->name }}</td>
                        <td class="px-5 py-3 text-ink">{{ $workorder->created_at->format('m-d H:i') }}</td>
                    </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="p-8 text-center text-sm" style="color: var(--c-ink-muted);">暂无相关工单</div>
            @endif
        </div>
    </div>

    {{-- Sidebar --}}
    <div class="lg:col-span-1 space-y-4">
        <div class="card p-5">
            <h3 class="text-sm font-semibold text-ink mb-3">分类统计</h3>
            <div class="grid grid-cols-2 gap-3">
                <div class="text-center p-3 rounded-lg" style="background-color: var(--c-muted);">
                    <p class="text-xl font-bold text-ink">{{ $workorderCategory->workorders()->count() }}</p>
                    <p class="text-xs mt-0.5" style="color: var(--c-ink-muted);">总工单</p>
                </div>
                <div class="text-center p-3 rounded-lg" style="background-color: var(--c-muted);">
                    <p class="text-xl font-bold text-amber-600">{{ $workorderCategory->workorders()->whereIn('status', ['pending', 'assigned', 'processing'])->count() }}</p>
                    <p class="text-xs mt-0.5" style="color: var(--c-ink-muted);">待处理</p>
                </div>
                <div class="text-center p-3 rounded-lg" style="background-color: var(--c-muted);">
                    <p class="text-xl font-bold text-green-600">{{ $workorderCategory->workorders()->whereIn('status', ['resolved', 'closed'])->count() }}</p>
                    <p class="text-xs mt-0.5" style="color: var(--c-ink-muted);">已完成</p>
                </div>
                <div class="text-center p-3 rounded-lg" style="background-color: var(--c-muted);">
                    <p class="text-xl font-bold text-ink">{{ $workorderCategory->children()->count() }}</p>
                    <p class="text-xs mt-0.5" style="color: var(--c-ink-muted);">子分类</p>
                </div>
            </div>
        </div>

        <div class="card p-5 space-y-3">
            <a href="{{ route('workorder-categories.edit', $workorderCategory->id) }}" class="btn btn-secondary w-full">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7 M18.5 2.5a2.1 2.1 0 0 1 3 3L12 15l-4 1 1-4z"/></svg>
                <span>编辑分类</span>
            </a>
            @if($workorderCategory->children()->count() == 0)
            <a href="{{ route('workorders.create', ['category_id' => $workorderCategory->id]) }}" class="btn btn-primary w-full">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/></svg>
                <span>创建工单</span>
            </a>
            @endif
        </div>
    </div>
</div>
@endsection