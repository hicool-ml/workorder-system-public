@extends('layouts.app')
@section('title', '用户管理')
@section('content')
@php($roleBadges = ['admin' => 'bg-red-100 text-red-700', 'workorder_manager' => 'bg-blue-100 text-blue-700', 'engineer' => 'bg-amber-100 text-amber-700', 'user' => 'bg-slate-100 text-slate-600'])
<div class="flex items-center justify-between mb-6 gap-3 flex-wrap">
    <h1 class="text-xl font-semibold text-ink">用户管理</h1>
    @if(auth()->user()->hasRole('admin'))
    <a href="{{ route('users.create') }}" class="btn btn-primary"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/></svg><span>新增用户</span></a>
    @endif
</div>
<div class="card p-4 mb-4">
    <form method="GET" action="{{ route('users.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
        <div><label class="label">姓名/用户名</label><input type="text" name="keyword" class="input" value="{{ request('keyword') }}" placeholder="搜索"></div>
        <div><label class="label">邮箱</label><input type="text" name="email" class="input" value="{{ request('email') }}" placeholder="邮箱"></div>
        <div><label class="label">角色</label><select name="role" class="input"><option value="">全部</option>@foreach(['admin' => '管理员', 'workorder_manager' => '工单管理员', 'engineer' => '工程师', 'user' => '普通用户'] as $k => $v)<option value="{{ $k }}" {{ request('role') == $k ? 'selected' : '' }}>{{ $v }}</option>@endforeach</select></div>
        <div><label class="label">部门</label><select name="department_id" class="input"><option value="">全部</option>@foreach($departments as $d)<option value="{{ $d->id }}" {{ request('department_id') == $d->id ? 'selected' : '' }}>{{ $d->name }}</option>@endforeach</select></div>
        <div class="flex items-end gap-2"><button type="submit" class="btn btn-primary btn-sm"><span>搜索</span></button><a href="{{ route('users.index') }}" class="btn btn-secondary btn-sm">重置</a></div>
    </form>
</div>
<div class="card">
    <div class="md:hidden divide-y divide-border">
        @forelse($users as $user)
        <div class="p-4"><div class="flex items-center justify-between gap-2 mb-1"><p class="font-medium text-ink">{{ $user->name }}</p><span class="badge {{ $roleBadges[$user->role] ?? '' }}">{{ $user->role_text }}</span></div><p class="text-xs" style="color: var(--c-ink-subtle);">{{ $user->username }} · {{ $user->email }}</p><div class="flex items-center gap-2 mt-2"><span class="text-xs" style="color: var(--c-ink-muted);">{{ $user->department?->name ?? '-' }}</span>@if($user->is_active)<span class="badge bg-green-100 text-green-700">启用</span>@else<span class="badge bg-red-100 text-red-700">禁用</span>@endif<div class="ml-auto flex items-center gap-1"><a href="{{ route('users.show', $user->id) }}" class="btn btn-ghost btn-sm">查看</a>@if(auth()->user()->hasRole('admin'))<a href="{{ route('users.edit', $user->id) }}" class="btn btn-ghost btn-sm">编辑</a>@endif</div></div></div>
        @empty
        <div class="p-8 text-center text-ink-muted">暂无用户</div>
        @endforelse
    </div>
    <div class="hidden md:block overflow-x-auto">
        <table class="w-full text-sm"><thead><tr class="border-b border-border text-left">
            <th class="px-4 py-3 font-medium" style="color: var(--c-ink-muted);">姓名</th><th class="px-4 py-3 font-medium" style="color: var(--c-ink-muted);">用户名</th><th class="px-4 py-3 font-medium" style="color: var(--c-ink-muted);">角色</th><th class="px-4 py-3 font-medium" style="color: var(--c-ink-muted);">部门</th><th class="px-4 py-3 font-medium" style="color: var(--c-ink-muted);">电话</th><th class="px-4 py-3 font-medium" style="color: var(--c-ink-muted);">状态</th><th class="px-4 py-3 font-medium text-right" style="color: var(--c-ink-muted);">操作</th>
        </tr></thead><tbody>
        @forelse($users as $user)
        <tr class="border-b border-border hover:bg-surface-muted">
            <td class="px-4 py-3"><div class="flex items-center gap-2"><div class="w-8 h-8 rounded-full flex items-center justify-center bg-brand-600 text-white text-xs font-medium shrink-0">{{ mb_substr($user->name, 0, 1) }}</div><div><p class="text-ink font-medium">{{ $user->name }}</p><p class="text-xs" style="color: var(--c-ink-subtle);">{{ $user->email }}</p></div></div></td>
            <td class="px-4 py-3 text-ink">{{ $user->username }}</td>
            <td class="px-4 py-3"><span class="badge {{ $roleBadges[$user->role] ?? '' }}">{{ $user->role_text }}</span></td>
            <td class="px-4 py-3 text-ink">{{ $user->department?->name ?? '-' }}</td>
            <td class="px-4 py-3 text-ink">{{ $user->phone ?? '-' }}</td>
            <td class="px-4 py-3">@if($user->is_active)<span class="badge bg-green-100 text-green-700">启用</span>@else<span class="badge bg-red-100 text-red-700">禁用</span>@endif</td>
            <td class="px-4 py-3 text-right"><div class="flex items-center justify-end gap-1"><a href="{{ route('users.show', $user->id) }}" class="btn btn-ghost btn-icon btn-sm" title="查看"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z M12 9a3 3 0 1 0 0 6 3 3 0 0 0 0-6z"/></svg></a>@if(auth()->user()->hasRole('admin'))<a href="{{ route('users.edit', $user->id) }}" class="btn btn-ghost btn-icon btn-sm" title="编辑"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7 M18.5 2.5a2.1 2.1 0 0 1 3 3L12 15l-4 1 1-4z"/></svg></a>@if($user->id !== auth()->id())<form method="POST" action="{{ route('users.destroy', $user->id) }}" class="inline" onsubmit="return confirm('确定删除？')">@csrf @method('DELETE')<button type="submit" class="btn btn-ghost btn-icon btn-sm text-red-500" title="删除"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18 M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg></button></form>@endif@endif</div></td>
        </tr>
        @empty
        <tr><td colspan="7" class="px-4 py-12 text-center text-ink-muted">暂无用户</td></tr>
        @endforelse
        </tbody></table>
    </div>
</div>
@if($users->hasPages())<div class="flex items-center justify-between mt-4 gap-3"><p class="text-sm text-ink-muted">{{ $users->firstItem() ?? 0 }} - {{ $users->lastItem() ?? 0 }} / {{ $users->total() }}</p><div>{{ $users->appends(request()->query())->links() }}</div></div>@endif
@endsection
