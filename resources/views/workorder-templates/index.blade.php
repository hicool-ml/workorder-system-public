@extends('layouts.app')
@section('title', '工单模板管理')
@section('content')
@php($prioStyles = ['high' => 'bg-red-100 text-red-700', 'medium' => 'bg-amber-100 text-amber-700', 'low' => 'bg-green-100 text-green-700'])
<div class="flex items-center justify-between mb-6 gap-3 flex-wrap">
    <h1 class="text-xl font-semibold text-ink">工单模板管理</h1>
    <a href="{{ route('workorder-templates.create') }}" class="btn btn-primary"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/></svg><span>新建模板</span></a>
</div>
<div class="card p-4 mb-4">
    <form method="GET" action="{{ route('workorder-templates.index') }}" class="flex flex-wrap items-end gap-3">
        <div class="flex-1 min-w-[200px]"><label class="label">关键词</label><input type="text" name="keyword" class="input" value="{{ request('keyword') }}" placeholder="名称、描述"></div>
        <div><label class="label">分类</label><select name="category_id" class="input"><option value="">全部</option>@foreach($categories as $c)<option value="{{ $c->id }}" {{ request('category_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>@endforeach</select></div>
        <div class="flex items-end gap-2"><button type="submit" class="btn btn-primary btn-sm"><span>搜索</span></button><a href="{{ route('workorder-templates.index') }}" class="btn btn-secondary btn-sm">重置</a></div>
    </form>
</div>
<div class="card">
    @if($templates->count() > 0)
    <div class="md:hidden divide-y divide-border">
        @foreach($templates as $tpl)
        <div class="p-4"><div class="flex items-center justify-between gap-2 mb-1"><p class="font-medium text-ink">{{ $tpl->name }}</p>@if($tpl->is_active)<span class="badge bg-green-100 text-green-700">启用</span>@else<span class="badge bg-red-100 text-red-700">禁用</span>@endif</div><p class="text-xs" style="color: var(--c-ink-muted);">{{ Str::limit($tpl->description, 80) }}</p><div class="flex items-center gap-2 mt-2">@if($tpl->category)<span class="badge bg-slate-100 text-slate-600">{{ $tpl->category->name }}</span>@endif<span class="badge {{ $prioStyles[$tpl->priority] ?? '' }}">{{ $tpl->priority_text }}</span><div class="ml-auto flex items-center gap-1"><a href="{{ route('workorder-templates.createFromTemplate', $tpl->id) }}" class="btn btn-ghost btn-sm">使用</a><a href="{{ route('workorder-templates.edit', $tpl->id) }}" class="btn btn-ghost btn-sm">编辑</a><button type="button" onclick="toggleStatus({{ $tpl->id }})" class="btn btn-ghost btn-sm">切换</button><button type="button" onclick="deleteTemplate({{ $tpl->id }})" class="btn btn-ghost btn-sm text-red-500">删除</button></div></div></div>
        @endforeach
    </div>
    <div class="hidden md:block overflow-x-auto">
        <table class="w-full text-sm"><thead><tr class="border-b border-border text-left">
            <th class="px-4 py-3 font-medium" style="color: var(--c-ink-muted);">名称</th><th class="px-4 py-3 font-medium" style="color: var(--c-ink-muted);">分类</th><th class="px-4 py-3 font-medium" style="color: var(--c-ink-muted);">优先级</th><th class="px-4 py-3 font-medium" style="color: var(--c-ink-muted);">创建人</th><th class="px-4 py-3 font-medium" style="color: var(--c-ink-muted);">状态</th><th class="px-4 py-3 font-medium text-right" style="color: var(--c-ink-muted);">操作</th>
        </tr></thead><tbody>
        @foreach($templates as $tpl)
        <tr class="border-b border-border hover:bg-surface-muted">
            <td class="px-4 py-3"><p class="font-medium text-ink">{{ $tpl->name }}</p><p class="text-xs" style="color: var(--c-ink-subtle);">{{ Str::limit($tpl->description, 60) }}</p></td>
            <td class="px-4 py-3">@if($tpl->category)<span class="badge bg-slate-100 text-slate-600">{{ $tpl->category->name }}</span>@else<span style="color: var(--c-ink-subtle);">-</span>@endif</td>
            <td class="px-4 py-3"><span class="badge {{ $prioStyles[$tpl->priority] ?? '' }}">{{ $tpl->priority_text }}</span></td>
            <td class="px-4 py-3 text-ink">{{ $tpl->creator?->name ?? '-' }}</td>
            <td class="px-4 py-3">@if($tpl->is_active)<span class="badge bg-green-100 text-green-700">启用</span>@else<span class="badge bg-red-100 text-red-700">禁用</span>@endif</td>
            <td class="px-4 py-3 text-right"><div class="flex items-center justify-end gap-1"><a href="{{ route('workorder-templates.createFromTemplate', $tpl->id) }}" class="btn btn-ghost btn-icon btn-sm" title="使用"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/></svg></a><a href="{{ route('workorder-templates.edit', $tpl->id) }}" class="btn btn-ghost btn-icon btn-sm" title="编辑"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7 M18.5 2.5a2.1 2.1 0 0 1 3 3L12 15l-4 1 1-4z"/></svg></a><button type="button" onclick="toggleStatus({{ $tpl->id }})" class="btn btn-ghost btn-icon btn-sm" title="切换状态"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18.36 6.64a9 9 0 1 1-12.72 0 M12 2v10"/></svg></button><button type="button" onclick="deleteTemplate({{ $tpl->id }})" class="btn btn-ghost btn-icon btn-sm text-red-500" title="删除"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18 M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg></button></div></td>
        </tr>
        @endforeach
        </tbody></table>
    </div>
    <div class="p-4 border-t border-border">{{ $templates->appends(request()->query())->links() }}</div>
    @else
    <div class="p-12 text-center"><svg class="w-12 h-12 mx-auto text-ink-subtle" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z M14 2v6h6"/></svg><p class="text-sm mt-2" style="color: var(--c-ink-muted);">暂无模板</p><a href="{{ route('workorder-templates.create') }}" class="btn btn-primary btn-sm mt-3">创建模板</a></div>
    @endif
</div>
@endsection
@section('scripts')
<script>
function toggleStatus(id) { if (!confirm('确定切换状态？')) return; fetch('/workorder-templates/' + id + '/toggle-status', { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content } }).then(function(r) { return r.json(); }).then(function(d) { if (d.success) location.reload(); else alert('失败'); }).catch(function() { alert('失败'); }); }
function deleteTemplate(id) { if (!confirm('确定删除？')) return; fetch('/workorder-templates/' + id, { method: 'DELETE', headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content } }).then(function(r) { location.reload(); }).catch(function() { alert('删除失败'); }); }
</script>
@endsection
