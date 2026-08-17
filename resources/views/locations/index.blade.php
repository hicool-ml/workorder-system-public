@extends('layouts.app')
@section('title', '地址树')
@section('content')
@include('locations._topbar', [
    'active' => 'tree',
    'title' => '地址树',
    'subtitle' => '管理「校区/园区 → 楼栋 → 房间」日常地址；基础地址在「基础地址」Tab 维护',
    'actions' => ($baseInitialized ?? false ? '<a href="' . route('locations.import') . '" class="btn btn-secondary">导入地址</a>' : '')
        . '<a href="' . route('locations.create') . '" class="btn btn-primary">'
        . '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/></svg>'
        . '<span>新增地址</span></a>',
])

@if(session('success'))
    <div class="mb-4 px-4 py-3 rounded-lg bg-green-50 text-green-700 text-sm">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="mb-4 px-4 py-3 rounded-lg bg-red-50 text-red-700 text-sm">{{ session('error') }}</div>
@endif

@unless($baseInitialized)
    <div class="card p-6 mb-4 border-amber-200 bg-amber-50">
        <div class="flex items-center justify-between gap-4 flex-wrap">
            <div class="flex items-center gap-3">
                <svg class="w-6 h-6 text-amber-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3 M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/></svg>
                <div>
                    <h2 class="font-medium text-amber-800">基础地址尚未初始化</h2>
                    <p class="text-sm text-amber-700 mt-0.5">请先填写单位基础地址（示例：XX省 → XX市 → XX区 → XX路 → XX号），完成后才能填写或导入日常地址。</p>
                </div>
            </div>
            <a href="{{ route('locations.base-address') }}" class="btn btn-primary whitespace-nowrap">去初始化基础地址</a>
        </div>
    </div>
@endunless

<div class="card mb-4">
    <form method="GET" action="{{ route('locations.index') }}" class="p-4">
        <div class="flex gap-3">
            <input type="text" class="input" name="keyword" value="{{ request('keyword') }}" placeholder="搜索地址名称或描述" autocomplete="off">
            <button type="submit" class="btn btn-secondary whitespace-nowrap">搜索</button>
            @if(request('keyword'))
                <a href="{{ route('locations.index') }}" class="btn btn-secondary whitespace-nowrap">清除</a>
            @endif
        </div>
    </form>
</div>

@if(isset($results))
    {{-- 搜索结果 --}}
    <div class="card overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-surface-muted text-ink-muted text-xs uppercase">
                <tr>
                    <th class="text-left px-4 py-3 font-medium">地址</th>
                    <th class="text-left px-4 py-3 font-medium">完整路径</th>
                    <th class="text-left px-4 py-3 font-medium">层级</th>
                    <th class="text-right px-4 py-3 font-medium">操作</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border">
                @forelse($results as $loc)
                    <tr class="hover:bg-surface-muted/50">
                        <td class="px-4 py-3 font-medium text-ink">{{ $loc->name }}</td>
                        <td class="px-4 py-3 text-ink-muted">{{ $loc->full_address_delimited }}</td>
                        <td class="px-4 py-3 text-ink-muted">{{ $loc->level?->name ?? '' }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('locations.edit', $loc) }}" class="text-brand-600 hover:underline">编辑</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-8 text-center text-ink-muted">未找到匹配的地址</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $results->links() }}
@else
    {{-- 树形展示 --}}
    <div class="card overflow-hidden">
        <div class="px-4 py-3 border-b border-border flex items-center justify-between">
            <span class="text-sm font-medium text-ink">楼栋地址树</span>
            @if($baseInitialized)
                <div class="flex items-center gap-2">
                    <button type="button" class="btn btn-sm btn-secondary" data-expand-all>展开全部</button>
                    <button type="button" class="btn btn-sm btn-secondary" data-collapse-all>收起全部</button>
                </div>
            @endif
        </div>
        <table class="w-full text-sm">
            <thead class="bg-surface-muted text-ink-muted text-xs uppercase">
                <tr>
                    <th class="text-left px-4 py-3 font-medium">地址名称</th>
                    <th class="text-left px-4 py-3 font-medium">代码</th>
                    <th class="text-left px-4 py-3 font-medium">状态</th>
                    <th class="text-right px-4 py-3 font-medium">操作</th>
                </tr>
            </thead>
            <tbody id="tree-body" class="divide-y divide-border">
                @forelse($projectTrees as $projectRoot)
                    <tr class="tree-row bg-brand-50 dark:bg-brand-900/20" data-id="{{ $projectRoot->id }}"
                        data-parent-id="{{ $projectRoot->parent_id }}"
                        data-collapsible="1"
                        data-default-collapsed="1">
                        <td class="px-4 py-3">
                            <div class="flex items-center">
                                <button type="button" class="tree-toggle w-4 h-4 mr-1.5 flex-shrink-0 text-brand-600" data-id="{{ $projectRoot->id }}" aria-label="折叠/展开">
                                    <svg class="w-3.5 h-3.5 transition-transform duration-150" fill="currentColor" viewBox="0 0 20 20"><path d="M5 7l5 5 5-5H5z"/></svg>
                                </button>
                                <svg class="w-4 h-4 mr-1.5 text-brand-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z M12 13a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/></svg>
                                <span class="font-semibold text-brand-700 dark:text-brand-300 text-sm">{{ $projectRoot->full_address_delimited }}</span>
                                @if($projectRoot->children->isNotEmpty())
                                    <span class="ml-2 inline-flex items-center px-1.5 py-0.5 rounded-full text-xs bg-brand-100 text-brand-700 dark:bg-brand-800 dark:text-brand-200">{{ $projectRoot->children->count() }} 个区域</span>
                                @endif
                            </div>
                        </td>
                        <td colspan="3" class="px-4 py-3 text-right whitespace-nowrap">
                            <a href="{{ route('locations.create', ['parent_id' => $projectRoot->id]) }}" class="text-brand-600 hover:underline text-sm">添加子节点</a>
                            <a href="{{ route('locations.projects.edit', $projectRoot->id) }}" class="text-brand-600 hover:underline text-sm ml-3">编辑门牌</a>
                        </td>
                    </tr>
                    @if($projectRoot->relationLoaded('children') && $projectRoot->children->isNotEmpty())
                        @include('locations._tree-rows', ['nodes' => $projectRoot->children, 'depth' => 1])
                    @endif
                @empty
                    <tr><td colspan="4" class="px-4 py-8 text-center text-ink-muted">暂无项目地址，请先在<a href="{{ route('locations.base-address') }}" class="text-brand-600 underline">基础地址</a>中创建项目</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endif
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var treeBody = document.getElementById('tree-body');
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
