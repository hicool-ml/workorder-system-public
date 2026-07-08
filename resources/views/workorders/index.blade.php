@extends('layouts.app')

@section('title', '工单列表')

@include('workorders._permission_checks')

@section('content')

{{-- Page header --}}
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-xl font-semibold text-ink">工单列表</h1>
        <p class="text-sm text-ink-muted mt-0.5">管理和跟踪所有工单</p>
    </div>
    <a href="{{ route('workorders.create') }}" class="btn btn-primary">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/></svg>
        <span>创建工单</span>
    </a>
</div>

{{-- Search filters --}}
<div class="card mb-4">
    <form method="GET" action="{{ route('workorders.index') }}" id="searchForm">
        <div class="p-4 space-y-4">
            {{-- Primary search row --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <div>
                    <label class="label" for="keyword">关键词</label>
                    <input type="text" class="input" id="keyword" name="keyword"
                           value="{{ request('keyword') }}" placeholder="工单号、描述、联系人" autocomplete="off">
                </div>
                <div>
                    <label class="label" for="status">状态</label>
                    <select class="input" id="status" name="status">
                        <option value="">全部状态</option>
                        <option value="all" {{ request('status') == 'all' ? 'selected' : '' }}>全部</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>待处理</option>
                        <option value="assigned" {{ request('status') == 'assigned' ? 'selected' : '' }}>已分配</option>
                        <option value="processing" {{ request('status') == 'processing' ? 'selected' : '' }}>处理中</option>
                        <option value="resolved" {{ request('status') == 'resolved' ? 'selected' : '' }}>已解决</option>
                        <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>已完结</option>
                        <option value="closed" {{ request('status') == 'closed' ? 'selected' : '' }}>已关闭</option>
                    </select>
                </div>
                <div>
                    <label class="label" for="priority">优先级</label>
                    <select class="input" id="priority" name="priority">
                        <option value="">全部优先级</option>
                        <option value="high" {{ request('priority') == 'high' ? 'selected' : '' }}>高</option>
                        <option value="medium" {{ request('priority') == 'medium' ? 'selected' : '' }}>中</option>
                        <option value="low" {{ request('priority') == 'low' ? 'selected' : '' }}>低</option>
                    </select>
                </div>
                <div>
                    <label class="label" for="category_id">工单分类</label>
                    <select class="input" id="category_id" name="category_id">
                        <option value="">全部分类</option>
                        @foreach($categories['main'] as $category)
                        <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                            {{ $category->name }}
                        </option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- Advanced filters (collapsible) --}}
            <div id="advancedFilters" class="hidden">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 pt-3 border-t border-border">
                    <div>
                        <label class="label" for="date_from">开始日期</label>
                        <input type="date" class="input" id="date_from" name="date_from"
                               value="{{ request('date_from') }}" autocomplete="off">
                    </div>
                    <div>
                        <label class="label" for="date_to">结束日期</label>
                        <input type="date" class="input" id="date_to" name="date_to"
                               value="{{ request('date_to') }}" autocomplete="off">
                    </div>
                    <div>
                        <label class="label" for="campus">校区</label>
                        <select class="input" id="campus" name="campus">
                            <option value="">全部校区</option>
                            <option value="old_campus" {{ request('campus') == 'old_campus' ? 'selected' : '' }}>老校区</option>
                            <option value="new_campus" {{ request('campus') == 'new_campus' ? 'selected' : '' }}>新校区</option>
                            <option value="asean_campus" {{ request('campus') == 'asean_campus' ? 'selected' : '' }}>东盟校区</option>
                        </select>
                    </div>
                    <div>
                        <label class="label" for="source">来源</label>
                        <select class="input" id="source" name="source">
                            <option value="">全部来源</option>
                            <option value="phone" {{ request('source') == 'phone' ? 'selected' : '' }}>电话</option>
                            <option value="web" {{ request('source') == 'web' ? 'selected' : '' }}>网络</option>
                            <option value="scene" {{ request('source') == 'scene' ? 'selected' : '' }}>现场</option>
                            <option value="email" {{ request('source') == 'email' ? 'selected' : '' }}>邮件</option>
                            <option value="other" {{ request('source') == 'other' ? 'selected' : '' }}>其他</option>
                        </select>
                    </div>
                    @if(auth()->user()->canAssignWorkorders())
                    <div>
                        <label class="label" for="filter_assignee_id">处理人</label>
                        <select class="input" id="filter_assignee_id" name="assignee_id">
                            <option value="">全部处理人</option>
                            @foreach($engineers as $engineer)
                            <option value="{{ $engineer->id }}" {{ request('assignee_id') == $engineer->id ? 'selected' : '' }}>
                                {{ $engineer->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                    <div class="flex items-end gap-4">
                        <label class="flex items-center gap-2 text-sm text-ink-muted cursor-pointer">
                            <input type="checkbox" name="show_closed" value="1" class="rounded border-border-strong" {{ request('show_closed') ? 'checked' : '' }}>
                            显示已解决
                        </label>
                        <label class="flex items-center gap-2 text-sm text-ink-muted cursor-pointer">
                            <input type="checkbox" name="show_emergency" value="1" class="rounded border-border-strong" {{ request('show_emergency') ? 'checked' : '' }}>
                            仅紧急
                        </label>
                        <label class="flex items-center gap-2 text-sm text-ink-muted cursor-pointer">
                            <input type="checkbox" name="show_overdue" value="1" class="rounded border-border-strong" {{ request('show_overdue') ? 'checked' : '' }}>
                            仅超时
                        </label>
                    </div>
                </div>
            </div>
        </div>

        {{-- Filter actions --}}
        <div class="flex items-center justify-between px-4 py-3 border-t border-border bg-surface-muted rounded-b-xl">
            <button type="button" id="toggleAdvanced" class="btn btn-ghost btn-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
                <span>高级筛选</span>
                <svg id="adv-chevron" class="w-4 h-4 transition-transform" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div class="flex items-center gap-2">
                <a href="{{ route('workorders.index') }}" class="btn btn-secondary btn-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8 M3 3v5h5"/></svg>
                    <span>重置</span>
                </a>
                <button type="submit" class="btn btn-primary btn-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 1 1-14 0 7 7 0 0 1 14 0z"/></svg>
                    <span>搜索</span>
                </button>
            </div>
        </div>
    </form>
</div>

{{-- Batch operations bar --}}
@if(auth()->user()->canHandleWorkorders())
<div id="batchBar" class="hidden mb-4 card" style="background-color: var(--c-brand-light); border-color: rgba(59,130,246,0.3);">
    <div class="flex items-center justify-between p-3">
        <span class="text-sm font-medium" style="color: var(--c-brand);">已选择 <span id="selectedCount">0</span> 个工单</span>
        <div class="flex items-center gap-2 flex-wrap">
            <button type="button" class="btn btn-secondary btn-sm" id="batchAssignBtn">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2 M9 7a4 4 0 1 0 0-8 4 4 0 0 0 0 8z M19 8v6 M22 11h-6"/></svg>
                批量分配
            </button>
            <button type="button" class="btn btn-secondary btn-sm" id="batchStartBtn">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 3l14 9-14 9V3z"/></svg>
                批量开始
            </button>
            <button type="button" class="btn btn-secondary btn-sm" id="batchResolveBtn">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4 M12 22a10 10 0 1 1 0-20 10 10 0 0 1 0 20z"/></svg>
                批量解决
            </button>
            <button type="button" class="btn btn-danger btn-sm" id="batchCloseBtn">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18 6 6 18M6 6l12 12"/></svg>
                批量关闭
            </button>
            <button type="button" class="btn btn-ghost btn-sm" id="clearSelectionBtn">
                清除选择
            </button>
        </div>
    </div>
</div>
@endif

{{-- Workorder list --}}
<div class="card">
    <div class="p-4 sm:p-0">

        @if(auth()->user()->canHandleWorkorders())
        {{-- Desktop table --}}
        <div class="hidden md:block overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-border text-left text-ink-muted">
                        <th class="px-4 py-3 w-10">
                            <input type="checkbox" id="selectAll" class="rounded border-border-strong" autocomplete="off">
                        </th>
                        <th class="px-4 py-3 font-medium text-ink-muted">地址</th>
                        <th class="px-4 py-3 font-medium text-ink-muted">描述</th>
                        <th class="px-4 py-3 font-medium text-ink-muted">报修</th>
                        <th class="px-4 py-3 font-medium text-ink-muted">优先级</th>
                        <th class="px-4 py-3 font-medium text-ink-muted">状态</th>
                        <th class="px-4 py-3 font-medium text-ink-muted">处理</th>
                        <th class="px-4 py-3 font-medium text-ink-muted">历时</th>
                        <th class="px-4 py-3 font-medium text-ink-muted text-right">操作</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($workorders as $workorder)
                    <tr class="border-b border-border hover:bg-surface-muted transition-colors {{ $workorder->isOverdue() ? 'overdue-row' : '' }}">
                        <td class="px-4 py-3">
                            <input type="checkbox" class="workorder-checkbox rounded border-border-strong" value="{{ $workorder->id }}" autocomplete="off">
                        </td>
                        <td class="px-4 py-3 max-w-[180px]">
                            <div class="text-xs text-ink-muted">
                                @if($workorder->campus)
                                    {{ \App\Models\Location::CAMPUSES[$workorder->campus] ?? $workorder->campus }}
                                @endif
                                @if($workorder->building)
                                    @php
                                        $building = \App\Models\Location::find($workorder->building);
                                        echo ' - ' . ($building ? $building->name : $workorder->building);
                                        if ($workorder->location_detail) echo ' ' . $workorder->location_detail;
                                    @endphp
                                @endif
                            </div>
                        </td>
                        <td class="px-4 py-3 max-w-[200px]">
                            <a href="{{ route('workorders.show', $workorder->id) }}" class="text-brand-600 hover:text-brand-700 hover:underline">
                                {{ Str::limit($workorder->description, 35) }}
                            </a>
                            @if($workorder->is_emergency)
                                <span class="ml-1 inline-flex items-center text-red-500" title="紧急">
                                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2L1 21h22L12 2zm0 6l6.5 11h-13L12 8z"/></svg>
                                </span>
                            @endif
                            @if($workorder->category)
                                <div class="mt-0.5"><span class="badge bg-gray-100 text-gray-600">{{ $workorder->category->name }}</span></div>
                            @endif
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <div class="font-medium text-ink">{{ $workorder->contact_name }}</div>
                            <div class="text-xs text-ink-muted">{{ $workorder->contact_phone }}</div>
                        </td>
                        <td class="px-4 py-3">
                            @if($workorder->priority === 'high')
                                <span class="badge bg-red-100 text-red-700">高</span>
                            @elseif($workorder->priority === 'medium')
                                <span class="badge bg-amber-100 text-amber-700">中</span>
                            @else
                                <span class="badge bg-green-100 text-green-700">低</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @php
                                $statusStyles = [
                                    'pending' => 'bg-amber-100 text-amber-700',
                                    'assigned' => 'bg-blue-100 text-blue-700',
                                    'processing' => 'bg-indigo-100 text-indigo-700',
                                    'resolved' => 'bg-green-100 text-green-700',
                                    'completed' => 'bg-teal-100 text-teal-700',
                                    'closed' => 'bg-gray-100 text-gray-600',
                                ];
                                $style = $statusStyles[$workorder->status] ?? 'bg-gray-100 text-gray-600';
                            @endphp
                            <span class="badge {{ $style }}">{{ $workorder->status_text }}</span>
                            @if($workorder->isOverdue())
                                <span class="badge bg-red-100 text-red-700">超时</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-ink-muted">{{ $workorder->assignee_name }}</td>
                        <td class="px-4 py-3 whitespace-nowrap text-xs text-ink-muted">{{ $workorder->created_duration }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-1">
                                {!! getWorkorderActionButtons($workorder, false) !!}
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="px-4 py-12 text-center">
                            <div class="inline-flex flex-col items-center gap-2 text-ink-muted">
                                <svg class="w-10 h-10 text-ink-subtle" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7v10a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2H5a2 2 0 0 0-2 0z M3 7l2-2h4l2 2"/></svg>
                                <p>暂无工单</p>
                                <a href="{{ route('workorders.create') }}" class="btn btn-primary btn-sm mt-2">创建第一个工单</a>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @endif

        {{-- Mobile card list --}}
        <div class="md:hidden divide-y divide-border">
            @forelse($workorders as $workorder)
            <div class="p-4 {{ $workorder->isOverdue() ? 'overdue-row' : '' }}">
                <div class="flex items-start justify-between gap-3 mb-2">
                    <div class="flex-1 min-w-0">
                        <a href="{{ route('workorders.show', $workorder->id) }}" class="font-medium text-ink hover:text-brand-600">
                            {{ Str::limit($workorder->description, 40) }}
                        </a>
                        @if($workorder->is_emergency)
                            <span class="inline-flex items-center ml-1 text-red-500 align-middle">
                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2L1 21h22L12 2zm0 6l6.5 11h-13L12 8z"/></svg>
                            </span>
                        @endif
                    </div>
                    <span class="text-xs text-ink-subtle whitespace-nowrap shrink-0">{{ $workorder->created_duration }}</span>
                </div>

                {{-- Location --}}
                <div class="text-xs text-ink-muted mb-2 flex items-center gap-1">
                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z M12 13a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/></svg>
                    <span class="truncate">
                        @if($workorder->campus){{ \App\Models\Location::CAMPUSES[$workorder->campus] ?? $workorder->campus }}@endif
                        @if($workorder->building) - {{ \App\Models\Location::find($workorder->building)?->name ?? $workorder->building }}@endif
                    </span>
                </div>

                {{-- Badges --}}
                <div class="flex items-center gap-1.5 flex-wrap mb-3">
                    @if($workorder->priority === 'high')
                        <span class="badge bg-red-100 text-red-700">高</span>
                    @elseif($workorder->priority === 'medium')
                        <span class="badge bg-amber-100 text-amber-700">中</span>
                    @else
                        <span class="badge bg-green-100 text-green-700">低</span>
                    @endif
                    @php
                        $statusStyles = [
                            'pending' => 'bg-amber-100 text-amber-700',
                            'assigned' => 'bg-blue-100 text-blue-700',
                            'processing' => 'bg-indigo-100 text-indigo-700',
                            'resolved' => 'bg-green-100 text-green-700',
                            'completed' => 'bg-teal-100 text-teal-700',
                            'closed' => 'bg-gray-100 text-gray-600',
                        ];
                        $style = $statusStyles[$workorder->status] ?? 'bg-gray-100 text-gray-600';
                    @endphp
                    <span class="badge {{ $style }}">{{ $workorder->status_text }}</span>
                    @if($workorder->category)<span class="badge bg-gray-100 text-gray-600">{{ $workorder->category->name }}</span>@endif
                    @if($workorder->isOverdue())<span class="badge bg-red-100 text-red-700">超时</span>@endif
                </div>

                {{-- Contact + assignee --}}
                <div class="flex items-center justify-between text-xs text-ink-muted mb-3">
                    <div>
                        <span class="text-ink-subtle">报修: </span>
                        <span class="font-medium text-ink">{{ $workorder->contact_name }}</span>
                    </div>
                    <div>
                        <span class="text-ink-subtle">处理: </span>
                        <span class="font-medium text-ink">{{ $workorder->assignee_name }}</span>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="flex items-center gap-1.5 pt-2 border-t border-border">
                    @if(auth()->user()->canHandleWorkorders())
                    <input type="checkbox" class="workorder-checkbox rounded border-border-strong shrink-0" value="{{ $workorder->id }}" autocomplete="off">
                    @endif
                    <div class="flex items-center gap-1.5 flex-wrap flex-1">
                        {!! getWorkorderActionButtons($workorder, true) !!}
                    </div>
                </div>
            </div>
            @empty
            <div class="p-8 text-center">
                <div class="inline-flex flex-col items-center gap-2 text-ink-muted">
                    <svg class="w-10 h-10 text-ink-subtle" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7v10a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2H5a2 2 0 0 0-2 0z M3 7l2-2h4l2 2"/></svg>
                    <p>暂无工单</p>
                    <a href="{{ route('workorders.create') }}" class="btn btn-primary btn-sm mt-2">创建第一个工单</a>
                </div>
            </div>
            @endforelse
        </div>
    </div>
</div>

{{-- Pagination --}}
@if($workorders->hasPages())
<div class="flex flex-col sm:flex-row items-center justify-between gap-3 mt-4">
    <p class="text-sm text-ink-muted">
        显示 {{ $workorders->firstItem() ?? 0 }} - {{ $workorders->lastItem() ?? 0 }}
        共 {{ $workorders->total() }} 条
    </p>
    <div>
        {{ $workorders->appends(request()->query())->links() }}
    </div>
</div>
@endif

{{-- Modals --}}
@include('workorders._assign_modal')
@include('workorders._resolve_modal')
@if(auth()->user()->canHandleWorkorders())
@include('workorders._batch_assign_modal')
@include('workorders._batch_resolve_modal')
@endif

@endsection

@section('scripts')
<script>
// Toggle advanced filters
document.getElementById('toggleAdvanced')?.addEventListener('click', function() {
    var panel = document.getElementById('advancedFilters');
    var chevron = document.getElementById('adv-chevron');
    panel.classList.toggle('hidden');
    chevron.classList.toggle('rotate-180');
});

// Checkbox selection
var selectedWorkorders = [];

function updateSelectedCount() {
    var checked = document.querySelectorAll('.workorder-checkbox:checked');
    selectedWorkorders = Array.from(checked).map(function(cb) { return cb.value; });
    var countEl = document.getElementById('selectedCount');
    var bar = document.getElementById('batchBar');
    if (countEl) countEl.textContent = selectedWorkorders.length;
    if (bar) {
        if (selectedWorkorders.length > 0) bar.classList.remove('hidden');
        else bar.classList.add('hidden');
    }
}

// Select all
document.getElementById('selectAll')?.addEventListener('change', function() {
    document.querySelectorAll('.workorder-checkbox').forEach(function(cb) {
        cb.checked = this.checked;
    }.bind(this));
    updateSelectedCount();
});

// Individual checkboxes
document.querySelectorAll('.workorder-checkbox').forEach(function(cb) {
    cb.addEventListener('change', updateSelectedCount);
});

// Clear selection
document.getElementById('clearSelectionBtn')?.addEventListener('click', function() {
    document.querySelectorAll('.workorder-checkbox').forEach(function(cb) { cb.checked = false; });
    var sa = document.getElementById('selectAll');
    if (sa) sa.checked = false;
    updateSelectedCount();
});

// Batch assign
document.getElementById('batchAssignBtn')?.addEventListener('click', function() {
    openModal('batchAssignModal');
});

// Batch resolve
document.getElementById('batchResolveBtn')?.addEventListener('click', function() {
    openModal('batchResolveModal');
});

// Batch start
document.getElementById('batchStartBtn')?.addEventListener('click', function() {
    if (selectedWorkorders.length === 0) { alert('请先选择工单'); return; }
    if (!confirm('确认批量开始处理 ' + selectedWorkorders.length + ' 个工单？')) return;
    var formData = new FormData();
    formData.append('_token', '{{ csrf_token() }}');
    selectedWorkorders.forEach(function(id) { formData.append('workorder_ids[]', id); });
    fetch('{{ route("workorders.batch.start") }}', { method: 'POST', body: formData, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function(r) { return r.json(); })
        .then(function(d) { alert(d.message || '操作完成'); if (d.success) location.reload(); })
        .catch(function() { alert('请求失败'); });
});

// Batch close
document.getElementById('batchCloseBtn')?.addEventListener('click', function() {
    if (selectedWorkorders.length === 0) { alert('请先选择工单'); return; }
    if (!confirm('确认批量关闭 ' + selectedWorkorders.length + ' 个工单？')) return;
    var formData = new FormData();
    formData.append('_token', '{{ csrf_token() }}');
    selectedWorkorders.forEach(function(id) { formData.append('workorder_ids[]', id); });
    fetch('{{ route("workorders.batch.close") }}', { method: 'POST', body: formData, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function(r) { return r.json(); })
        .then(function(d) { alert(d.message || '操作完成'); if (d.success) location.reload(); })
        .catch(function() { alert('请求失败'); });
});

// Modal helpers
function openModal(id) {
    var modal = document.getElementById(id);
    if (modal) {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.classList.add('overflow-hidden');
    }
}
function closeModal(id) {
    var modal = document.getElementById(id);
    if (modal) {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.classList.remove('overflow-hidden');
    }
}
// Close modal on backdrop click
document.querySelectorAll('[data-modal]').forEach(function(modal) {
    modal.addEventListener('click', function(e) {
        if (e.target === this) closeModal(this.id);
    });
});
// Close button
document.querySelectorAll('[data-modal-close]').forEach(function(btn) {
    btn.addEventListener('click', function() {
        closeModal(this.getAttribute('data-modal-close'));
    });
});

// Assign modal: set workorder ID
document.querySelectorAll('[data-assign-workorder]').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var id = this.getAttribute('data-assign-workorder');
        document.getElementById('assignWorkorderId').value = id;
        var form = document.getElementById('assignForm');
        form.action = '{{ route("workorders.assign", ":id") }}'.replace(':id', id);
        openModal('assignModal');
    });
});

// Resolve modal: set workorder ID
document.querySelectorAll('[data-resolve-workorder]').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var id = this.getAttribute('data-resolve-workorder');
        document.getElementById('resolveWorkorderId').value = id;
        var form = document.getElementById('resolveForm');
        form.action = '{{ route("workorders.resolve", ":id") }}'.replace(':id', id);
        openModal('resolveModal');
    });
});

// No materials checkbox
document.getElementById('no_materials')?.addEventListener('change', function() {
    var div = document.getElementById('materials_usage_div');
    if (div) div.style.display = this.checked ? 'none' : 'block';
});
document.getElementById('batch_no_materials')?.addEventListener('change', function() {
    var div = document.getElementById('batch_materials_usage_div');
    if (div) div.style.display = this.checked ? 'none' : 'block';
});

// Batch assign form submit
document.getElementById('batchAssignForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    if (selectedWorkorders.length === 0) { alert('请先选择工单'); return; }
    var formData = new FormData(this);
    selectedWorkorders.forEach(function(id) { formData.append('workorder_ids[]', id); });
    fetch('{{ route("workorders.batch.assign") }}', { method: 'POST', body: formData, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function(r) { return r.json(); })
        .then(function(d) { alert(d.message || '操作完成'); if (d.success) location.reload(); })
        .catch(function() { alert('请求失败'); });
});

// Batch resolve form submit
document.getElementById('batchResolveForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    if (selectedWorkorders.length === 0) { alert('请先选择工单'); return; }
    var formData = new FormData(this);
    selectedWorkorders.forEach(function(id) { formData.append('workorder_ids[]', id); });
    fetch('{{ route("workorders.batch.resolve") }}', { method: 'POST', body: formData, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function(r) { return r.json(); })
        .then(function(d) { alert(d.message || '操作完成'); if (d.success) location.reload(); })
        .catch(function() { alert('请求失败'); });
});
</script>
@endsection