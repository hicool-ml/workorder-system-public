@extends('layouts.app')

@section('title', '仪表板')

@section('content')

@php
    $recentWorkorders = App\Models\Workorder::with(['creator', 'assignee', 'category'])->latest()->limit(6)->get();
    $statusStyles = [
        'pending' => 'bg-amber-100 text-amber-700',
        'assigned' => 'bg-blue-100 text-blue-700',
        'processing' => 'bg-indigo-100 text-indigo-700',
        'resolved' => 'bg-green-100 text-green-700',
        'completed' => 'bg-teal-100 text-teal-700',
        'closed' => 'bg-slate-100 text-slate-600',
    ];
@endphp

<div class="mb-6">
    <h1 class="text-xl font-semibold text-ink">仪表板</h1>
    <p class="text-sm text-ink-muted mt-0.5">系统概览</p>
</div>

{{-- Stats grid --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="card p-4 flex items-center gap-3">
        <div class="w-10 h-10 rounded-lg flex items-center justify-center bg-blue-100 shrink-0">
            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2 M9 3h6v4H9z"/></svg>
        </div>
        <div>
            <p class="text-2xl font-semibold text-ink">{{ App\Models\Workorder::count() }}</p>
            <p class="text-xs" style="color: var(--c-ink-subtle);">总工单</p>
        </div>
    </div>
    <div class="card p-4 flex items-center gap-3">
        <div class="w-10 h-10 rounded-lg flex items-center justify-center bg-amber-100 shrink-0">
            <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3 M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/></svg>
        </div>
        <div>
            <p class="text-2xl font-semibold text-ink">{{ App\Models\Workorder::whereIn('status', ['pending', 'assigned', 'processing'])->count() }}</p>
            <p class="text-xs" style="color: var(--c-ink-subtle);">待处理</p>
        </div>
    </div>
    <div class="card p-4 flex items-center gap-3">
        <div class="w-10 h-10 rounded-lg flex items-center justify-center bg-green-100 shrink-0">
            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4 M12 22a10 10 0 1 1 0-20 10 10 0 0 1 0 20z"/></svg>
        </div>
        <div>
            <p class="text-2xl font-semibold text-ink">{{ App\Models\Workorder::whereIn('status', ['resolved', 'completed', 'closed'])->count() }}</p>
            <p class="text-xs" style="color: var(--c-ink-subtle);">已完成</p>
        </div>
    </div>
    <div class="card p-4 flex items-center gap-3">
        <div class="w-10 h-10 rounded-lg flex items-center justify-center bg-indigo-100 shrink-0">
            <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a4 4 0 0 0-3-3.87 M9 20H4v-2a4 4 0 0 1 3-3.87 M16 3.13a4 4 0 0 1 0 7.75 M12 11a4 4 0 1 1 0-8 4 4 0 0 1 0 8z"/></svg>
        </div>
        <div>
            <p class="text-2xl font-semibold text-ink">{{ App\Models\User::count() }}</p>
            <p class="text-xs" style="color: var(--c-ink-subtle);">用户</p>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    {{-- Recent workorders --}}
    <div class="lg:col-span-2">
        <div class="card">
            <div class="flex items-center justify-between p-5 pb-3">
                <h2 class="text-sm font-semibold text-ink">最近工单</h2>
                <a href="{{ route('workorders.index') }}" class="text-sm text-brand-600 hover:text-brand-700">全部</a>
            </div>
            <div class="px-5 pb-5">
                @if($recentWorkorders->count() > 0)
                <div class="space-y-2">
                    @foreach($recentWorkorders as $workorder)
                    <a href="{{ route('workorders.show', $workorder->id) }}" class="flex items-center gap-3 p-3 rounded-lg border border-border hover:bg-surface-muted transition-colors">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-ink truncate">{{ Str::limit($workorder->description, 40) }}</p>
                            <p class="text-xs" style="color: var(--c-ink-subtle);">{{ $workorder->ticket_no }} · {{ $workorder->created_at->format('m-d H:i') }}</p>
                        </div>
                        <span class="badge {{ $statusStyles[$workorder->status] ?? '' }} shrink-0">{{ $workorder->status_text }}</span>
                    </a>
                    @endforeach
                </div>
                @else
                <div class="text-center py-8">
                    <svg class="w-10 h-10 mx-auto text-ink-subtle" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7v10a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2H5a2 2 0 0 0-2 0z"/></svg>
                    <p class="text-sm mt-2" style="color: var(--c-ink-muted);">暂无工单</p>
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Quick actions + info --}}
    <div class="lg:col-span-1 space-y-4">
        <div class="card p-5">
            <h2 class="text-sm font-semibold text-ink mb-3">快速操作</h2>
            <div class="space-y-2">
                <a href="{{ route('workorders.create') }}" class="btn btn-primary w-full justify-start">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/></svg>
                    <span>创建工单</span>
                </a>
                @if(auth()->user()->canAssignWorkorders())
                <a href="{{ route('workorders.index', ['status' => 'pending']) }}" class="btn btn-secondary w-full justify-start">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/></svg>
                    <span>待分配</span>
                </a>
                @endif
                @if(auth()->user()->canHandleWorkorders())
                <a href="{{ route('workorders.index', ['assignee_id' => auth()->id()]) }}" class="btn btn-secondary w-full justify-start">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/></svg>
                    <span>我的工单</span>
                </a>
                @endif
                @if(auth()->user()->canViewReports())
                <a href="{{ route('reports.index') }}" class="btn btn-secondary w-full justify-start">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3v18h18 M7 14l4-4 4 4 5-5"/></svg>
                    <span>统计报表</span>
                </a>
                @endif
            </div>
        </div>

        <div class="card p-5">
            <h2 class="text-sm font-semibold text-ink mb-3">系统信息</h2>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between"><dt style="color: var(--c-ink-subtle);">部门数</dt><dd class="text-ink font-medium">{{ App\Models\Department::count() }}</dd></div>
                <div class="flex justify-between"><dt style="color: var(--c-ink-subtle);">工单分类</dt><dd class="text-ink font-medium">{{ App\Models\WorkorderCategory::count() }}</dd></div>
                <div class="flex justify-between"><dt style="color: var(--c-ink-subtle);">Laravel</dt><dd class="text-ink">{{ app()->version() }}</dd></div>
            </dl>
        </div>
    </div>
</div>
@endsection
