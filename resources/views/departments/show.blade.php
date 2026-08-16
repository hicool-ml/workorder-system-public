@extends('layouts.app')

@section('title', '部门详情 - ' . $department->name)

@section('content')
<div class="flex items-center justify-between mb-6 gap-3 flex-wrap">
    <h1 class="text-xl font-semibold text-ink">部门详情</h1>
    <div class="flex items-center gap-2">
        @if(auth()->user()->hasRole('admin'))
        <a href="{{ route('departments.edit', $department->id) }}" class="btn btn-primary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7 M18.5 2.5a2.1 2.1 0 0 1 3 3L12 15l-4 1 1-4z"/></svg>
            <span>编辑</span>
        </a>
        @endif
        <a href="{{ route('departments.index') }}" class="btn btn-secondary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7 7-7M3 12h18"/></svg>
            <span>返回列表</span>
        </a>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    {{-- Info card --}}
    <div class="lg:col-span-1">
        <div class="card p-5">
            <h3 class="text-sm font-semibold text-ink mb-4">部门信息</h3>
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between gap-2"><dt style="color: var(--c-ink-muted);">部门名称</dt><dd class="font-medium text-ink text-right">{{ $department->name }}</dd></div>
                <div class="flex justify-between gap-2"><dt style="color: var(--c-ink-muted);">部门编码</dt><dd class="text-ink text-right">{{ $department->code }}</dd></div>
                <div class="flex justify-between gap-2"><dt style="color: var(--c-ink-muted);">负责人</dt><dd class="text-ink text-right">{{ $department->manager ?? '-' }}</dd></div>
                <div class="flex justify-between gap-2"><dt style="color: var(--c-ink-muted);">联系电话</dt><dd class="text-ink text-right">{{ $department->phone ?? '-' }}</dd></div>
                <div class="flex justify-between gap-2"><dt style="color: var(--c-ink-muted);">邮箱</dt><dd class="text-ink text-right">{{ $department->email ?? '-' }}</dd></div>
                <div class="flex justify-between gap-2"><dt style="color: var(--c-ink-muted);">排序</dt><dd class="text-ink text-right">{{ $department->sort_order }}</dd></div>
                <div class="flex justify-between gap-2"><dt style="color: var(--c-ink-muted);">状态</dt><dd class="text-right">@if($department->is_active)<span class="badge bg-green-100 text-green-700">启用</span>@else<span class="badge bg-red-100 text-red-700">禁用</span>@endif</dd></div>
                <div class="flex justify-between gap-2"><dt style="color: var(--c-ink-muted);">创建时间</dt><dd class="text-ink text-right">{{ $department->created_at?->format('Y-m-d H:i') ?? '—' }}</dd></div>
            </dl>
            @if($department->description)
            <div class="mt-4 pt-4 border-t border-border">
                <p class="text-xs font-medium mb-1" style="color: var(--c-ink-muted);">部门描述</p>
                <p class="text-sm text-ink">{{ $department->description }}</p>
            </div>
            @endif
            @if(auth()->user()->hasRole('admin'))
            <form method="POST" action="{{ route('departments.destroy', $department->id) }}" class="mt-4" onsubmit="return confirm('确定要删除该部门吗？')">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-danger w-full">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18 M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                    <span>删除部门</span>
                </button>
            </form>
            @endif
        </div>
    </div>

    {{-- Stats + members --}}
    <div class="lg:col-span-2 space-y-6">
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
            <div class="card p-4 text-center">
                <p class="text-2xl font-bold text-ink">{{ $department->users()->count() }}</p>
                <p class="text-xs mt-1" style="color: var(--c-ink-muted);">部门人员</p>
            </div>
            <div class="card p-4 text-center">
                <p class="text-2xl font-bold text-ink">{{ $department->workorders()->count() }}</p>
                <p class="text-xs mt-1" style="color: var(--c-ink-muted);">相关工单</p>
            </div>
            <div class="card p-4 text-center">
                <p class="text-2xl font-bold text-green-600">{{ $department->workorders()->where('status', 'resolved')->count() }}</p>
                <p class="text-xs mt-1" style="color: var(--c-ink-muted);">已解决工单</p>
            </div>
        </div>

        <div class="card">
            <div class="px-5 py-4 border-b border-border">
                <h3 class="text-sm font-semibold text-ink">部门人员</h3>
            </div>
            @if($department->users->count() > 0)
            <div class="md:hidden divide-y divide-border">
                @foreach($department->users as $user)
                <div class="p-4">
                    <div class="flex items-center justify-between gap-2 mb-1">
                        <a href="{{ route('users.show', $user->id) }}" class="font-medium text-ink hover:text-brand-600">{{ $user->name }}</a>
                        @if($user->is_active)<span class="badge bg-green-100 text-green-700">启用</span>@else<span class="badge bg-red-100 text-red-700">禁用</span>@endif
                    </div>
                    <p class="text-xs" style="color: var(--c-ink-subtle);">{{ $user->email }} · {{ $user->phone ?? '-' }}</p>
                </div>
                @endforeach
            </div>
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-sm">
                    <thead><tr class="border-b border-border text-left">
                        <th class="px-5 py-3 font-medium" style="color: var(--c-ink-muted);">姓名</th>
                        <th class="px-5 py-3 font-medium" style="color: var(--c-ink-muted);">邮箱</th>
                        <th class="px-5 py-3 font-medium" style="color: var(--c-ink-muted);">电话</th>
                        <th class="px-5 py-3 font-medium" style="color: var(--c-ink-muted);">状态</th>
                    </tr></thead>
                    <tbody>
                    @foreach($department->users as $user)
                    <tr class="border-b border-border">
                        <td class="px-5 py-3"><a href="{{ route('users.show', $user->id) }}" class="font-medium text-ink hover:text-brand-600">{{ $user->name }}</a></td>
                        <td class="px-5 py-3 text-ink">{{ $user->email }}</td>
                        <td class="px-5 py-3 text-ink">{{ $user->phone ?? '-' }}</td>
                        <td class="px-5 py-3">@if($user->is_active)<span class="badge bg-green-100 text-green-700">启用</span>@else<span class="badge bg-red-100 text-red-700">禁用</span>@endif</td>
                    </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="p-8 text-center text-sm" style="color: var(--c-ink-muted);">该部门暂无人员</div>
            @endif
        </div>
    </div>
</div>
@endsection