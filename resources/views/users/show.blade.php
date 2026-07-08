@extends('layouts.app')

@section('title', '用户详情 - ' . $user->name)

@section('content')
<div class="flex items-center justify-between mb-6 gap-3 flex-wrap">
    <h1 class="text-xl font-semibold text-ink">用户详情</h1>
    <div class="flex items-center gap-2">
        @if(auth()->user()->hasRole('admin'))
        <a href="{{ route('users.edit', $user->id) }}" class="btn btn-primary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7 M18.5 2.5a2.1 2.1 0 0 1 3 3L12 15l-4 1 1-4z"/></svg>
            <span>编辑</span>
        </a>
        @endif
        <a href="{{ route('users.index') }}" class="btn btn-secondary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7 7-7M3 12h18"/></svg>
            <span>返回列表</span>
        </a>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    {{-- User info card --}}
    <div class="lg:col-span-1">
        <div class="card p-5">
            <h3 class="text-sm font-semibold text-ink mb-4">用户信息</h3>
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between gap-2"><dt style="color: var(--c-ink-muted);">姓名</dt><dd class="font-medium text-ink text-right">{{ $user->name }}</dd></div>
                <div class="flex justify-between gap-2"><dt style="color: var(--c-ink-muted);">用户名</dt><dd class="text-ink text-right">{{ $user->username }}</dd></div>
                <div class="flex justify-between gap-2"><dt style="color: var(--c-ink-muted);">邮箱</dt><dd class="text-ink text-right break-all">{{ $user->email }}</dd></div>
                <div class="flex justify-between gap-2 items-center"><dt style="color: var(--c-ink-muted);">角色</dt><dd class="text-right"><span class="badge bg-blue-100 text-blue-700">{{ $user->role_text }}</span></dd></div>
                <div class="flex justify-between gap-2"><dt style="color: var(--c-ink-muted);">部门</dt><dd class="text-ink text-right">{{ $user->department ? $user->department->name : '-' }}</dd></div>
                <div class="flex justify-between gap-2"><dt style="color: var(--c-ink-muted);">联系电话</dt><dd class="text-ink text-right">{{ $user->phone ?? '-' }}</dd></div>
                <div class="flex justify-between gap-2"><dt style="color: var(--c-ink-muted);">工号</dt><dd class="text-ink text-right">{{ $user->employee_id ?? '-' }}</dd></div>
                <div class="flex justify-between gap-2 items-center"><dt style="color: var(--c-ink-muted);">状态</dt><dd class="text-right">@if($user->is_active)<span class="badge bg-green-100 text-green-700">启用</span>@else<span class="badge bg-red-100 text-red-700">禁用</span>@endif</dd></div>
                <div class="flex justify-between gap-2"><dt style="color: var(--c-ink-muted);">创建时间</dt><dd class="text-ink text-right">{{ $user->created_at->format('Y-m-d H:i') }}</dd></div>
                <div class="flex justify-between gap-2"><dt style="color: var(--c-ink-muted);">最后登录</dt><dd class="text-ink text-right">{{ $user->last_login_at ? $user->last_login_at->format('Y-m-d H:i') : '-' }}</dd></div>
            </dl>
            @if(auth()->user()->hasRole('admin') && $user->id !== auth()->id())
            <form method="POST" action="{{ route('users.destroy', $user->id) }}" class="mt-4" onsubmit="return confirm('确定要删除该用户吗？')">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-danger w-full">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18 M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                    <span>删除用户</span>
                </button>
            </form>
            @endif
        </div>
    </div>

    {{-- Stats + workorders --}}
    <div class="lg:col-span-2 space-y-6">
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div class="card p-4 text-center">
                <p class="text-2xl font-bold text-ink">{{ $user->createdWorkorders()->count() }}</p>
                <p class="text-xs mt-1" style="color: var(--c-ink-muted);">创建工单</p>
            </div>
            <div class="card p-4 text-center">
                <p class="text-2xl font-bold text-ink">{{ $user->assignedWorkorders()->count() }}</p>
                <p class="text-xs mt-1" style="color: var(--c-ink-muted);">处理工单</p>
            </div>
            <div class="card p-4 text-center">
                <p class="text-2xl font-bold text-green-600">{{ $user->assignedWorkorders()->where('status', 'resolved')->count() }}</p>
                <p class="text-xs mt-1" style="color: var(--c-ink-muted);">已解决</p>
            </div>
            <div class="card p-4 text-center">
                <p class="text-2xl font-bold text-amber-600">{{ $user->assignedWorkorders()->whereIn('status', ['assigned', 'processing'])->count() }}</p>
                <p class="text-xs mt-1" style="color: var(--c-ink-muted);">处理中</p>
            </div>
        </div>

        <div class="card">
            <div class="px-5 py-4 border-b border-border">
                <h3 class="text-sm font-semibold text-ink">最近工单</h3>
            </div>
            @if($recentWorkorders->count() > 0)
            <div class="md:hidden divide-y divide-border">
                @foreach($recentWorkorders as $workorder)
                <a href="{{ route('workorders.show', $workorder->id) }}" class="block p-4">
                    <div class="flex items-center justify-between gap-2 mb-1">
                        <span class="font-medium text-ink truncate">{{ $workorder->title }}</span>
                        <span class="badge {{ $workorder->status == 'closed' ? 'bg-gray-100 text-gray-600' : ($workorder->status == 'resolved' ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700') }}">{{ $workorder->status_text }}</span>
                    </div>
                    <p class="text-xs" style="color: var(--c-ink-subtle);">{{ $workorder->ticket_no }} · {{ $workorder->created_at->format('m-d H:i') }}</p>
                </a>
                @endforeach
            </div>
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-sm">
                    <thead><tr class="border-b border-border text-left">
                        <th class="px-5 py-3 font-medium" style="color: var(--c-ink-muted);">工单编号</th>
                        <th class="px-5 py-3 font-medium" style="color: var(--c-ink-muted);">标题</th>
                        <th class="px-5 py-3 font-medium" style="color: var(--c-ink-muted);">状态</th>
                        <th class="px-5 py-3 font-medium" style="color: var(--c-ink-muted);">优先级</th>
                        <th class="px-5 py-3 font-medium" style="color: var(--c-ink-muted);">创建时间</th>
                    </tr></thead>
                    <tbody>
                    @foreach($recentWorkorders as $workorder)
                    <tr class="border-b border-border">
                        <td class="px-5 py-3"><a href="{{ route('workorders.show', $workorder->id) }}" class="text-brand-600 hover:underline">{{ $workorder->ticket_no }}</a></td>
                        <td class="px-5 py-3 text-ink">{{ $workorder->title }}</td>
                        <td class="px-5 py-3"><span class="badge {{ $workorder->status == 'closed' ? 'bg-gray-100 text-gray-600' : ($workorder->status == 'resolved' ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700') }}">{{ $workorder->status_text }}</span></td>
                        <td class="px-5 py-3"><span class="badge {{ $workorder->priority == 'high' ? 'bg-red-100 text-red-700' : ($workorder->priority == 'medium' ? 'bg-amber-100 text-amber-700' : 'bg-blue-100 text-blue-700') }}">{{ $workorder->priority_text }}</span></td>
                        <td class="px-5 py-3 text-ink">{{ $workorder->created_at->format('m-d H:i') }}</td>
                    </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="p-8 text-center text-sm" style="color: var(--c-ink-muted);">该用户暂无工单记录</div>
            @endif
        </div>
    </div>
</div>
@endsection