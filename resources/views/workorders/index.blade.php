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
{{-- Search filters --}}
<div class="card mb-4">
    <form method="GET" action="{{ route('workorders.index') }}" id="searchForm">
        <?php
            $activeFilterCount = 0;
            foreach (['keyword','status','priority','category_main','category_sub','date_from','date_to','campus_id','source','assignee_id'] as $f) {
                if (request()->filled($f)) $activeFilterCount++;
            }
            foreach (['show_closed','show_emergency','show_overdue','address_anomaly'] as $f) {
                if (request()->input($f)) $activeFilterCount++;
            }
        ?>
        {{-- Mobile: collapsed toggle button --}}
        <div class="md:hidden">
            <button type="button" id="mobileFilterToggle" class="flex items-center justify-between w-full px-4 py-3 text-sm font-medium text-ink">
                <span class="flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    筛选搜索
                </span>
                <span class="flex items-center gap-2">
                    @if($activeFilterCount > 0)
                    <span class="badge bg-brand-100 text-brand-700">{{ $activeFilterCount }}</span>
                    @endif
                    <svg id="mobileFilterChevron" class="w-4 h-4 transition-transform" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                </span>
            </button>
        </div>

        {{-- Filter container: hidden on mobile, always visible on desktop --}}
        <div id="filterContainer" class="<?php echo $activeFilterCount > 0 ? '' : 'hidden'; ?> md:block">
        <div class="p-4 space-y-4">
            {{-- Primary search row --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                <div>
                    <label class="label" for="keyword">关键词</label>
                    <input type="text" class="input" id="keyword" name="keyword"
                           value="{{ request('keyword') }}" placeholder="工单号、描述、地址、联系人、处理人、部门" autocomplete="off">
                </div>
                <div>
                    <label class="label" for="filter_category_main">工单大类</label>
                    <select class="input" id="filter_category_main" name="category_main">
                        <option value="">全部大类</option>
                        @foreach($categories['main'] as $category)
                        <option value="{{ $category->id }}" {{ request('category_main') == $category->id ? 'selected' : '' }}>
                            {{ $category->name }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="label" for="filter_category_sub">故障分类</label>
                    <select class="input" id="filter_category_sub" name="category_sub">
                        <option value="">全部</option>
                        @php
                            $currentMain = request('category_main');
                            $currentSub = request('category_sub');
                            if ($currentMain && isset($categories['sub'][$currentMain])) {
                                foreach ($categories['sub'][$currentMain] as $sub) {
                                    echo '<option value="' . $sub->id . '"' . ($currentSub == $sub->id ? ' selected' : '') . '>' . e($sub->name) . '</option>';
                                }
                            }
                        @endphp
                    </select>
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
            </div>

            {{-- Date range row --}}
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 pt-3 border-t border-border">
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
                <div class="col-span-2 flex items-end gap-1.5 flex-wrap">
                    <button type="button" data-range="today" class="btn btn-ghost btn-sm date-quick">今天</button>
                    <button type="button" data-range="7d" class="btn btn-ghost btn-sm date-quick">近7天</button>
                    <button type="button" data-range="30d" class="btn btn-ghost btn-sm date-quick">近30天</button>
                    <button type="button" data-range="month" class="btn btn-ghost btn-sm date-quick">本月</button>
                    <button type="button" data-range="clear" class="btn btn-ghost btn-sm date-quick">清除</button>
                </div>
            </div>

            {{-- Advanced filters (collapsible) --}}
            <div id="advancedFilters" class="hidden">
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 pt-3 border-t border-border">
                    <div>
                        <label class="label" for="campus_id">区域</label>
                        <select class="input" id="campus_id" name="campus_id">
                            <option value="">全部区域</option>
                            @foreach($campusOptions as $campusLocationId => $campusName)
                            <option value="{{ $campusLocationId }}" {{ request('campus_id') == $campusLocationId ? 'selected' : '' }}>{{ $campusName }}</option>
                            @endforeach
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
                    <div class="col-span-2 lg:col-span-4 flex items-center gap-4 flex-wrap">
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
                        @if(auth()->user()->isAdmin())
                        <label class="flex items-center gap-2 text-sm text-amber-700 cursor-pointer" title="筛出 location_id 为空或被收容到「未分类」节点下的工单（用于排查历史迁移结果）">
                            <input type="checkbox" name="address_anomaly" value="1" class="rounded border-amber-400" {{ request('address_anomaly') ? 'checked' : '' }}>
                            地址异常
                        </label>
                        @endif
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
        </div>{{-- end #filterContainer --}}
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
            @if(auth()->user()->canForceDeleteWorkorders())
            <button type="button" class="btn btn-danger btn-sm" id="batchForceDeleteBtn">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0 1 16.138 21H7.862a2 2 0 0 1-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1v3M4 7h16"/></svg>
                批量彻底删除
            </button>
            @endif
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
                    <tr class="border-b border-border hover:bg-surface-muted transition-colors {{ $workorder->isOverdue() ? 'overdue-row' : '' }} {{ (auth()->user()->isEngineer() && $workorder->isRelatedToUser()) ? 'my-related-row' : '' }}">
                        <td class="px-4 py-3">
                            <input type="checkbox" class="workorder-checkbox rounded border-border-strong" value="{{ $workorder->id }}" autocomplete="off">
                        </td>
                        <td class="px-4 py-3 max-w-[180px]">
                           <div class="text-xs text-ink-muted">
                               @if($workorder->campus_name)
                                    {{ $workorder->campus_name }}
                                @endif
                               @if($workorder->building_name) - {{ $workorder->building_name }}@endif
                               @if($workorder->location_detail) {{ $workorder->location_detail }}@endif
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
        <div class="md:hidden space-y-2 p-2 sm:p-0">
           @forelse($workorders as $workorder)
            <div class="p-3 rounded-lg border border-border {{ $workorder->isOverdue() ? 'overdue-row' : 'bg-surface-muted' }} {{ (auth()->user()->isEngineer() && $workorder->isRelatedToUser()) ? 'my-related-row' : '' }}">
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
                     @php
                         $addrParts = [];
                         if ($workorder->campus_name) $addrParts[] = $workorder->campus_name;
                         if ($workorder->building_name) $addrParts[] = $workorder->building_name;
                         if ($workorder->location_detail) $addrParts[] = $workorder->location_detail;
                     @endphp
                      {{ implode(' ', $addrParts) }}
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
                <div class="flex items-center justify-between text-xs text-ink-muted mb-2">
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
                <div class="flex items-center gap-1.5 pt-1.5 border-t border-border">
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
@if(auth()->user()->canForceDeleteWorkorders())
@include('workorders._batch_force_delete_modal')
@endif
@endif

<script>
var listCategoryData = @json($categories);
(function() {
    var mainSel = document.getElementById('filter_category_main');
    var subSel  = document.getElementById('filter_category_sub');
    if (!mainSel || !subSel) return;

    mainSel.addEventListener('change', function() {
        var mainId = this.value;
        subSel.innerHTML = '<option value="">全部</option>';
        if (mainId && listCategoryData.sub[mainId]) {
            listCategoryData.sub[mainId].forEach(function(sub) {
                var opt = document.createElement('option');
                opt.value = sub.id;
                opt.textContent = sub.name;
                subSel.appendChild(opt);
            });
        }
    });
})();
</script>

<script>
// 高级筛选展开/收起
(function() {
    var btn = document.getElementById('toggleAdvanced');
    var panel = document.getElementById('advancedFilters');
    var chevron = document.getElementById('adv-chevron');
    if (!btn || !panel) return;
    var hasAdv = ['{{ request('campus_id') }}','{{ request('source') }}','{{ request('assignee_id') }}','{{ request('show_emergency') }}','{{ request('show_overdue') }}','{{ request('address_anomaly') }}'].some(function(v){return v && v.length>0;});
    if (hasAdv) { panel.classList.remove('hidden'); if (chevron) chevron.style.transform = 'rotate(180deg)'; }
    btn.addEventListener('click', function() {
        panel.classList.toggle('hidden');
        if (chevron) chevron.style.transform = panel.classList.contains('hidden') ? '' : 'rotate(180deg)';
    });
})();

// 日期快捷范围
(function() {
    var fmt = function(d) { return d.toISOString().split('T')[0]; };
    document.querySelectorAll('.date-quick').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var from = document.getElementById('date_from');
            var to   = document.getElementById('date_to');
            if (!from || !to) return;
            var range = this.getAttribute('data-range');
            if (range === 'clear') { from.value = ''; to.value = ''; return; }
            var now = new Date(), start = new Date();
            if (range === '7d') { start.setDate(now.getDate() - 7); }
            else if (range === '30d') { start.setDate(now.getDate() - 30); }
            else if (range === 'month') { start = new Date(now.getFullYear(), now.getMonth(), 1); }
            from.value = fmt(start);
            to.value = fmt(now);
        });
    });
})();
</script>

<script>
// 移动端筛选展开/收起
(function() {
    var btn = document.getElementById('mobileFilterToggle');
    var panel = document.getElementById('filterContainer');
    var chevron = document.getElementById('mobileFilterChevron');
    if (!btn || !panel) return;
    btn.addEventListener('click', function() {
        panel.classList.toggle('hidden');
        if (chevron) chevron.style.transform = panel.classList.contains('hidden') ? '' : 'rotate(180deg)';
    });
})();
</script>


<script>
// 工单列表：模态框系统 + 批量操作
document.addEventListener('DOMContentLoaded', function() {
    var csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';

    // openModal/closeModal 由 layouts/app 全局提供

    // data-modal-close 按钮
    document.querySelectorAll('[data-modal-close]').forEach(function(btn) {
        btn.addEventListener('click', function() { closeModal(this.getAttribute('data-modal-close')); });
    });
    // 背景点击关闭
    document.querySelectorAll('[data-modal]').forEach(function(modal) {
        modal.addEventListener('click', function(e) { if (e.target === this) closeModal(this.id); });
    });
    // ESC 关闭
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') document.querySelectorAll('[data-modal]').forEach(function(m) { if (!m.classList.contains('hidden')) closeModal(m.id); });
    });

    // -- 分配按钮：data-assign-workorder --
    document.querySelectorAll('[data-assign-workorder]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var id = this.getAttribute('data-assign-workorder');
            var form = document.getElementById('assignForm');
            var idInput = document.getElementById('assignWorkorderId');
            if (idInput) idInput.value = id;
            if (form) {
                var action = form.getAttribute('action');
                form.setAttribute('action', action.replace(/\/workorders\/\d+\/assign/, '/workorders/' + id + '/assign'));
            }
            openModal('assignModal');
        });
    });

    // -- 解决按钮：data-resolve-workorder --
    document.querySelectorAll('[data-resolve-workorder]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var id = this.getAttribute('data-resolve-workorder');
            var form = document.getElementById('resolveForm');
            var idInput = document.getElementById('resolveWorkorderId');
            if (idInput) idInput.value = id;
            if (form) {
                var action = form.getAttribute('action');
                form.setAttribute('action', action.replace(/\/workorders\/\d+\/resolve/, '/workorders/' + id + '/resolve'));
            }
            // 重置表单
            if (form) form.reset();
            var matsDiv = document.getElementById('materials_usage_div');
            if (matsDiv) matsDiv.style.display = '';
            openModal('resolveModal');
        });
    });

    // 解决模态框：无备件勾选
    var noMats = document.getElementById('no_materials');
    if (noMats) noMats.addEventListener('change', function() {
        var div = document.getElementById('materials_usage_div');
        if (div) div.style.display = this.checked ? 'none' : 'block';
    });

    // -- 复选框选择 --
    var selectedWorkorders = [];
    function updateSelection() {
        var ids = new Set();
        document.querySelectorAll('.workorder-checkbox:checked').forEach(function(cb) { ids.add(cb.value); });
        selectedWorkorders = Array.from(ids);
        var countEl = document.getElementById('selectedCount');
        if (countEl) countEl.textContent = selectedWorkorders.length;
        var bar = document.getElementById('batchBar');
        if (bar) bar.classList.toggle('hidden', selectedWorkorders.length === 0);
    }

    // 全选
    var selectAll = document.getElementById('selectAll');
    if (selectAll) selectAll.addEventListener('change', function() {
        document.querySelectorAll('.workorder-checkbox').forEach(function(cb) { cb.checked = selectAll.checked; });
        updateSelection();
    });
    document.querySelectorAll('.workorder-checkbox').forEach(function(cb) { cb.addEventListener('change', updateSelection); });

    // -- 批量分配 --
    var batchAssignBtn = document.getElementById('batchAssignBtn');
    if (batchAssignBtn) batchAssignBtn.addEventListener('click', function() {
        if (selectedWorkorders.length === 0) { alert('请先选择工单'); return; }
        var idsInput = document.getElementById('batchAssignIds');
        if (idsInput) idsInput.value = selectedWorkorders.join(',');
        openModal('batchAssignModal');
    });

    // 批量分配表单提交
    var batchAssignForm = document.getElementById('batchAssignForm');
    if (batchAssignForm) batchAssignForm.addEventListener('submit', function(e) {
        e.preventDefault();
        var assignee = document.getElementById('batch_assignee_id');
        if (!assignee || !assignee.value) { alert('请选择工程师'); return; }
        var note = document.getElementById('batch_assign_note');
        fetch('{{ \App\Helpers\UrlHelper::relative_route("workorders.batch.assign") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ workorder_ids: selectedWorkorders.join(','), assignee_id: assignee.value, note: note ? note.value : '' })
        }).then(function(r) { return r.json(); }).then(function(data) {
            if (data.success) { closeModal('batchAssignModal'); location.reload(); }
            else { alert('分配失败：' + (data.message || '未知错误')); }
        }).catch(function() { alert('请求失败，请检查网络'); });
    });

    // -- 批量解决 --
    var batchResolveBtn = document.getElementById('batchResolveBtn');
    if (batchResolveBtn) batchResolveBtn.addEventListener('click', function() {
        if (selectedWorkorders.length === 0) { alert('请先选择工单'); return; }
        var idsInput = document.getElementById('batchResolveIds');
        if (idsInput) idsInput.value = selectedWorkorders.join(',');
        openModal('batchResolveModal');
    });

    // 批量解决表单提交
    var batchResolveForm = document.getElementById('batchResolveForm');
    if (batchResolveForm) batchResolveForm.addEventListener('submit', function(e) {
        e.preventDefault();
        var solution = document.getElementById('batch_solution');
        if (!solution || !solution.value.trim()) { alert('请填写解决方案'); return; }
        var noMatsChk = document.getElementById('batch_no_materials');
        var matsUsage = document.getElementById('batch_materials_usage');
        if (!noMatsChk || !noMatsChk.checked) {
            if (!matsUsage || !matsUsage.value.trim()) { alert('请填写备件耗材使用情况或勾选无备件耗材使用'); return; }
        }
        fetch('{{ \App\Helpers\UrlHelper::relative_route("workorders.batch.resolve") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({
                workorder_ids: selectedWorkorders.join(','),
                solution_type: 'common',
                solution: solution.value,
                no_materials: noMatsChk ? noMatsChk.checked : false,
                materials_usage: matsUsage ? matsUsage.value : '',
                no_materials_array: {}, materials_usage_array: {}, solutions: {}
            })
        }).then(function(r) { return r.json(); }).then(function(data) {
            if (data.success) { closeModal('batchResolveModal'); location.reload(); }
            else { alert('解决失败：' + (data.message || '未知错误')); }
        }).catch(function() { alert('请求失败，请检查网络'); });
    });

    // 批量解决：无备件勾选
    var batchNoMats = document.getElementById('batch_no_materials');
    if (batchNoMats) batchNoMats.addEventListener('change', function() {
        var div = document.getElementById('batch_materials_usage_div');
        if (div) div.style.display = this.checked ? 'none' : '';
    });

    // -- 批量开始 --
    var batchStartBtn = document.getElementById('batchStartBtn');
    if (batchStartBtn) batchStartBtn.addEventListener('click', function() {
        if (selectedWorkorders.length === 0) { alert('请先选择工单'); return; }
        if (!confirm('确认开始处理选中的 ' + selectedWorkorders.length + ' 个工单？')) return;
        fetch('{{ \App\Helpers\UrlHelper::relative_route("workorders.batch.start") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ workorder_ids: selectedWorkorders.join(',') })
        }).then(function(r) { return r.json(); }).then(function(data) {
            if (data.success) location.reload();
            else { alert('操作失败：' + (data.message || '未知错误')); }
        }).catch(function() { alert('请求失败，请检查网络'); });
    });

    // -- 批量关闭 --
    var batchCloseBtn = document.getElementById('batchCloseBtn');
    if (batchCloseBtn) batchCloseBtn.addEventListener('click', function() {
        if (selectedWorkorders.length === 0) { alert('请先选择工单'); return; }
        if (!confirm('确认关闭选中的 ' + selectedWorkorders.length + ' 个工单？此操作不可撤销！')) return;
        fetch('{{ \App\Helpers\UrlHelper::relative_route("workorders.batch.close") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ workorder_ids: selectedWorkorders.join(',') })
        }).then(function(r) { return r.json(); }).then(function(data) {
            if (data.success) location.reload();
            else { alert('操作失败：' + (data.message || '未知错误')); }
        }).catch(function() { alert('请求失败，请检查网络'); });
    });

    // -- 批量彻底删除（仅管理员）--
    var batchForceDeleteBtn = document.getElementById('batchForceDeleteBtn');
    var confirmBatchForceDeleteBtn = document.getElementById('confirmBatchForceDeleteBtn');
    var forceDeleteConfirmInput = document.getElementById('force_delete_confirm_input');

    if (batchForceDeleteBtn) batchForceDeleteBtn.addEventListener('click', function() {
        if (selectedWorkorders.length === 0) { alert('请先选择工单'); return; }
        var count = selectedWorkorders.length;
        var countEl = document.getElementById('forceDeleteCount');
        var hintEl = document.getElementById('forceDeleteCountHint');
        if (countEl) countEl.textContent = count;
        if (hintEl) hintEl.textContent = count;
        if (forceDeleteConfirmInput) forceDeleteConfirmInput.value = '';
        var err = document.getElementById('forceDeleteConfirmError');
        if (err) err.classList.add('hidden');
        openModal('batchForceDeleteModal');
    });

    if (confirmBatchForceDeleteBtn) confirmBatchForceDeleteBtn.addEventListener('click', function() {
        if (selectedWorkorders.length === 0) { alert('请先选择工单'); return; }
        var expected = String(selectedWorkorders.length);
        var input = forceDeleteConfirmInput ? forceDeleteConfirmInput.value.trim() : '';
        if (input !== expected) {
            var err = document.getElementById('forceDeleteConfirmError');
            if (err) err.classList.remove('hidden');
            if (forceDeleteConfirmInput) forceDeleteConfirmInput.focus();
            return;
        }
        confirmBatchForceDeleteBtn.disabled = true;
        confirmBatchForceDeleteBtn.innerHTML = '删除中…';
        fetch('{{ \App\Helpers\UrlHelper::relative_route("workorders.batch.force-delete") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ workorder_ids: selectedWorkorders.join(',') })
        }).then(function(r) { return r.json(); }).then(function(data) {
            if (data.success) { closeModal('batchForceDeleteModal'); alert(data.message); location.reload(); }
            else { alert('操作失败：' + (data.message || '未知错误')); }
        }).catch(function() { alert('请求失败，请检查网络'); })
          .finally(function() {
              if (confirmBatchForceDeleteBtn) { confirmBatchForceDeleteBtn.disabled = false; confirmBatchForceDeleteBtn.innerHTML = '确认彻底删除'; }
          });
    });

    // -- 清除选择 --
    var clearBtn = document.getElementById('clearSelectionBtn');
    if (clearBtn) clearBtn.addEventListener('click', function() {
        document.querySelectorAll('.workorder-checkbox').forEach(function(cb) { cb.checked = false; });
        if (selectAll) selectAll.checked = false;
        updateSelection();
    });
});
</script>

@endsection
