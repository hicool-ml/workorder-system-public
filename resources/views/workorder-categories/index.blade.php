@extends('layouts.app')
@section('title', '工单分类管理')
@section('content')
<div class="flex items-center justify-between mb-6 gap-3 flex-wrap">
    <h1 class="text-xl font-semibold text-ink">工单分类管理</h1>
    <a href="{{ route('workorder-categories.create') }}" class="btn btn-primary"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/></svg><span>新建分类</span></a>
</div>
<div class="card p-4 mb-4">
    <form method="GET" action="{{ route('workorder-categories.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
        <div><label class="label">关键词</label><input type="text" name="keyword" class="input" value="{{ request('keyword') }}" placeholder="搜索分类名称"></div>
        <div><label class="label">层级</label><select name="level" class="input"><option value="">全部</option><option value="1" {{ request('level') == '1' ? 'selected' : '' }}>一级</option><option value="2" {{ request('level') == '2' ? 'selected' : '' }}>二级</option><option value="3" {{ request('level') == '3' ? 'selected' : '' }}>三级</option></select></div>
        <div><label class="label">状态</label><select name="status" class="input"><option value="">全部</option><option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>启用</option><option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>禁用</option></select></div>
        <div class="flex items-end gap-2"><button type="submit" class="btn btn-primary btn-sm"><span>搜索</span></button><a href="{{ route('workorder-categories.index') }}" class="btn btn-secondary btn-sm">重置</a></div>
    </form>
</div>

@if($categories)
{{-- 平铺筛选结果 --}}
<div class="card">
    <div class="md:hidden divide-y divide-border">
        @forelse($categories as $cat)
        <div class="p-4"><div class="flex items-center justify-between gap-2 mb-1"><p class="font-medium text-ink" style="padding-left: {{ ($cat->level - 1) * 16 }}px;">{{ $cat->name }}</p>@if($cat->status)<span class="badge bg-green-100 text-green-700">{{ $cat->status_text }}</span>@else<span class="badge bg-red-100 text-red-700">{{ $cat->status_text }}</span>@endif</div><div class="flex items-center gap-2"><span class="badge bg-slate-100 text-slate-600">{{ $cat->level_text }}</span>@if($cat->parent)<span class="text-xs" style="color: var(--c-ink-muted);">父: {{ $cat->parent->name }}</span>@endif</div><div class="flex items-center gap-1 mt-2"><form method="POST" action="{{ route('workorder-categories.toggle-status', $cat->id) }}" class="inline">@csrf @method('PATCH')<button type="submit" class="btn {{ $cat->status ? 'btn-secondary' : 'btn-primary' }} btn-sm">{{ $cat->status ? '停用' : '启用' }}</button></form><a href="{{ route('workorder-categories.show', $cat->id) }}" class="btn btn-ghost btn-sm">查看</a><a href="{{ route('workorder-categories.edit', $cat->id) }}" class="btn btn-ghost btn-sm">编辑</a></div></div>
        @empty<div class="p-8 text-center text-ink-muted">暂无分类</div>@endforelse
    </div>
    <div class="hidden md:block overflow-x-auto">
        <table class="w-full text-sm"><thead><tr class="border-b border-border text-left">
            <th class="px-4 py-3 font-medium" style="color: var(--c-ink-muted);">名称</th><th class="px-4 py-3 font-medium" style="color: var(--c-ink-muted);">层级</th><th class="px-4 py-3 font-medium" style="color: var(--c-ink-muted);">父分类</th><th class="px-4 py-3 font-medium" style="color: var(--c-ink-muted);">状态</th><th class="px-4 py-3 font-medium text-right" style="color: var(--c-ink-muted);">操作</th>
        </tr></thead><tbody>
        @forelse($categories as $cat)
        <tr class="border-b border-border hover:bg-surface-muted">
            <td class="px-4 py-3 font-medium text-ink" style="padding-left: {{ 16 + ($cat->level - 1) * 24 }}px;">{{ $cat->name }}</td>
            <td class="px-4 py-3"><span class="badge bg-slate-100 text-slate-600">{{ $cat->level_text }}</span></td>
            <td class="px-4 py-3 text-ink">{{ $cat->parent?->name ?? '-' }}</td>
            <td class="px-4 py-3">@if($cat->status)<span class="badge bg-green-100 text-green-700">{{ $cat->status_text }}</span>@else<span class="badge bg-red-100 text-red-700">{{ $cat->status_text }}</span>@endif</td>
            <td class="px-4 py-3 text-right"><div class="flex items-center justify-end gap-1"><form method="POST" action="{{ route('workorder-categories.toggle-status', $cat->id) }}" class="inline" title="{{ $cat->status ? '停用' : '启用' }}">@csrf @method('PATCH')<button type="submit" class="btn btn-ghost btn-icon btn-sm {{ $cat->status ? 'text-green-600' : 'text-slate-400' }}"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5.636 5.636a9 9 0 1 0 12.728 0M12 3v6"/></svg></button></form><a href="{{ route('workorder-categories.show', $cat->id) }}" class="btn btn-ghost btn-icon btn-sm" title="查看"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z M12 9a3 3 0 1 0 0 6 3 3 0 0 0 0-6z"/></svg></a><a href="{{ route('workorder-categories.edit', $cat->id) }}" class="btn btn-ghost btn-icon btn-sm" title="编辑"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7 M18.5 2.5a2.1 2.1 0 0 1 3 3L12 15l-4 1 1-4z"/></svg></a>@if($cat->children()->count() == 0 && $cat->workorders()->count() == 0)<form method="POST" action="{{ route('workorder-categories.destroy', $cat->id) }}" class="inline" onsubmit="return confirm('确认删除？')">@csrf @method('DELETE')<button type="submit" class="btn btn-ghost btn-icon btn-sm text-red-500" title="删除"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18 M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg></button></form>@endif</div></td>
        </tr>
        @empty<tr><td colspan="6" class="px-4 py-12 text-center text-ink-muted">暂无分类</td></tr>@endforelse
        </tbody></table>
    </div>
</div>
@if($categories->hasPages())<div class="flex items-center justify-between mt-4 gap-3"><p class="text-sm text-ink-muted">{{ $categories->firstItem() ?? 0 }} - {{ $categories->lastItem() ?? 0 }} / {{ $categories->total() }}</p><div>{{ $categories->appends(request()->query())->links() }}</div></div>@endif
@else
{{-- 层级树展示（默认收起） --}}
<div class="card overflow-hidden">
    <div class="px-4 py-3 border-b border-border flex items-center justify-between">
        <span class="text-sm font-medium text-ink">分类层级树</span>
        <div class="flex items-center gap-2">
            <button type="button" class="btn btn-sm btn-secondary" data-expand-all>展开全部</button>
            <button type="button" class="btn btn-sm btn-secondary" data-collapse-all>收起全部</button>
        </div>
    </div>
    <table class="w-full text-sm">
        <thead class="bg-surface-muted text-ink-muted text-xs uppercase">
            <tr>
                <th class="text-left px-4 py-3 font-medium">分类名称</th>
                <th class="text-left px-4 py-3 font-medium">编号前缀</th>
                <th class="text-left px-4 py-3 font-medium">状态</th>
                <th class="text-right px-4 py-3 font-medium">操作</th>
            </tr>
        </thead>
        <tbody id="category-tree-body" class="divide-y divide-border">
            @include('workorder-categories._tree-rows', ['nodes' => $roots, 'depth' => 0])
        </tbody>
    </table>
</div>
@endif
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var treeBody = document.getElementById('category-tree-body');
    if (!treeBody) return;

    var rows = Array.from(treeBody.querySelectorAll('tr.tree-row'));

    function sync() {
        var byId = {};
        rows.forEach(function (r) { byId[r.dataset.id] = r; });
        rows.forEach(function (row) {
            var visible = true;
            var pid = row.dataset.parentId;
            while (pid) {
                var p = byId[pid];
                if (!p) break;
                if (p.dataset.collapsed === '1') { visible = false; break; }
                pid = p.dataset.parentId;
            }
            row.style.display = visible ? '' : 'none';
            var icon = row.querySelector('.tree-toggle svg');
            if (icon) icon.style.transform = row.dataset.collapsed === '1' ? 'rotate(-90deg)' : '';
        });
    }

    rows.forEach(function (row) {
        if (row.dataset.defaultCollapsed === '1') row.dataset.collapsed = '1';
        var toggle = row.querySelector('.tree-toggle');
        if (toggle) {
            toggle.addEventListener('click', function () {
                row.dataset.collapsed = row.dataset.collapsed === '1' ? '0' : '1';
                sync();
            });
        }
    });

    var expandAll = document.querySelector('[data-expand-all]');
    if (expandAll) expandAll.addEventListener('click', function () {
        rows.forEach(function (r) { r.dataset.collapsed = '0'; });
        sync();
    });

    var collapseAll = document.querySelector('[data-collapse-all]');
    if (collapseAll) collapseAll.addEventListener('click', function () {
        rows.forEach(function (r) { if (r.dataset.collapsible === '1') r.dataset.collapsed = '1'; });
        sync();
    });

    sync();
});
</script>
@endsection
