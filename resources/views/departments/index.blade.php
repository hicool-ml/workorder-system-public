@extends('layouts.app')
@section('title', '部门管理')
@section('content')
<div class="flex items-center justify-between mb-6 gap-3 flex-wrap">
    <h1 class="text-xl font-semibold text-ink">部门管理</h1>
    @if(auth()->user()->hasRole('admin'))<a href="{{ route('departments.create') }}" class="btn btn-primary"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/></svg><span>新增部门</span></a>@endif
</div>
<div class="card p-4 mb-4">
    <form method="GET" action="{{ route('departments.index') }}" class="flex flex-wrap items-end gap-3">
        <div class="flex-1 min-w-[200px]"><label class="label">部门名称</label><input type="text" name="name" class="input" value="{{ request('name') }}" placeholder="搜索"></div>
        <div><label class="label">状态</label><select name="is_active" class="input"><option value="">全部</option><option value="1" {{ request('is_active') == '1' ? 'selected' : '' }}>启用</option><option value="0" {{ request('is_active') == '0' ? 'selected' : '' }}>禁用</option></select></div>
        <div class="flex items-end gap-2"><button type="submit" class="btn btn-primary btn-sm"><span>搜索</span></button><a href="{{ route('departments.index') }}" class="btn btn-secondary btn-sm">重置</a></div>
    </form>
</div>
<div class="card">
    <div class="md:hidden divide-y divide-border">
        @forelse($departments as $dept)
        <div class="p-4"><div class="flex items-center justify-between gap-2 mb-1"><p class="font-medium text-ink">{{ $dept->name }}</p>@if($dept->is_active)<span class="badge bg-green-100 text-green-700">启用</span>@else<span class="badge bg-red-100 text-red-700">禁用</span>@endif</div><p class="text-xs" style="color: var(--c-ink-subtle);">{{ $dept->code }} · {{ $dept->manager ?? '-' }} · {{ $dept->phone ?? '-' }}</p><div class="flex items-center gap-1 mt-2"><a href="{{ route('departments.show', $dept->id) }}" class="btn btn-ghost btn-sm">查看</a>@if(auth()->user()->hasRole('admin'))<a href="{{ route('departments.edit', $dept->id) }}" class="btn btn-ghost btn-sm">编辑</a>@endif</div></div>
        @empty<div class="p-8 text-center text-ink-muted">暂无部门</div>@endforelse
    </div>
    <div class="hidden md:block overflow-x-auto">
        <table class="w-full text-sm"><thead><tr class="border-b border-border text-left">
            <th class="px-4 py-3 font-medium" style="color: var(--c-ink-muted);">名称</th><th class="px-4 py-3 font-medium" style="color: var(--c-ink-muted);">编码</th><th class="px-4 py-3 font-medium" style="color: var(--c-ink-muted);">负责人</th><th class="px-4 py-3 font-medium" style="color: var(--c-ink-muted);">电话</th><th class="px-4 py-3 font-medium" style="color: var(--c-ink-muted);">状态</th><th class="px-4 py-3 font-medium text-right" style="color: var(--c-ink-muted);">操作</th>
        </tr></thead><tbody>
        @forelse($departments as $dept)
        <tr class="border-b border-border hover:bg-surface-muted">
            <td class="px-4 py-3 font-medium text-ink">{{ $dept->name }}</td>
            <td class="px-4 py-3 text-ink">{{ $dept->code }}</td>
            <td class="px-4 py-3 text-ink">{{ $dept->manager ?? '-' }}</td>
            <td class="px-4 py-3 text-ink">{{ $dept->phone ?? '-' }}</td>
            <td class="px-4 py-3">@if($dept->is_active)<span class="badge bg-green-100 text-green-700">启用</span>@else<span class="badge bg-red-100 text-red-700">禁用</span>@endif</td>
            <td class="px-4 py-3 text-right"><div class="flex items-center justify-end gap-1"><a href="{{ route('departments.show', $dept->id) }}" class="btn btn-ghost btn-icon btn-sm" title="查看"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z M12 9a3 3 0 1 0 0 6 3 3 0 0 0 0-6z"/></svg></a>@if(auth()->user()->hasRole('admin'))<a href="{{ route('departments.edit', $dept->id) }}" class="btn btn-ghost btn-icon btn-sm" title="编辑"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7 M18.5 2.5a2.1 2.1 0 0 1 3 3L12 15l-4 1 1-4z"/></svg></a><form method="POST" action="{{ route('departments.destroy', $dept->id) }}" class="inline" onsubmit="return confirm('确定删除？')">@csrf @method('DELETE')<button type="submit" class="btn btn-ghost btn-icon btn-sm text-red-500" title="删除"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18 M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg></button></form>@endif</div></td>
        </tr>
        @empty<tr><td colspan="6" class="px-4 py-12 text-center text-ink-muted">暂无部门</td></tr>@endforelse
        </tbody></table>
    </div>
</div>
@if($departments->hasPages())<div class="flex items-center justify-between mt-4 gap-3"><p class="text-sm text-ink-muted">{{ $departments->firstItem() ?? 0 }} - {{ $departments->lastItem() ?? 0 }} / {{ $departments->total() }}</p><div>{{ $departments->appends(request()->query())->links() }}</div></div>@endif
@endsection
