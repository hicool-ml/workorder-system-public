@extends('layouts.app')
@section('title', '地址管理')
@section('content')
<div class="flex items-center justify-between mb-6 gap-3 flex-wrap">
    <h1 class="text-xl font-semibold text-ink">地址管理</h1>
    <a href="{{ route('locations.create') }}" class="btn btn-primary"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/></svg><span>新增地址</span></a>
</div>
<div class="card p-4 mb-4">
    <form method="GET" action="{{ route('locations.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
        <div><label class="label">关键词</label><input type="text" name="keyword" class="input" value="{{ request('keyword') }}" placeholder="名称、楼栋代码"></div>
        <div><label class="label">校区</label><select name="campus" class="input"><option value="">全部</option>@foreach(\App\Models\Location::CAMPUSES as $k => $v)<option value="{{ $k }}" {{ request('campus') == $k ? 'selected' : '' }}>{{ $v }}</option>@endforeach</select></div>
        <div><label class="label">建筑类型</label><select name="building_type" class="input"><option value="">全部</option>@foreach(\App\Models\Location::BUILDING_TYPES as $k => $v)<option value="{{ $k }}" {{ request('building_type') == $k ? 'selected' : '' }}>{{ $v }}</option>@endforeach</select></div>
        <div><label class="label">状态</label><select name="status" class="input"><option value="">全部</option>@foreach(\App\Models\Location::STATUSES as $k => $v)<option value="{{ $k }}" {{ request('status') == $k ? 'selected' : '' }}>{{ $v }}</option>@endforeach</select></div>
        <div class="flex items-end gap-2"><button type="submit" class="btn btn-primary btn-sm"><span>搜索</span></button><a href="{{ route('locations.index') }}" class="btn btn-secondary btn-sm">重置</a></div>
    </form>
</div>
<div class="card">
    <div class="md:hidden divide-y divide-border">
        @forelse($locations as $loc)
        <div class="p-4"><div class="flex items-center justify-between gap-2 mb-1"><p class="font-medium text-ink">{{ $loc->name }}</p>@if($loc->status == 'active')<span class="badge bg-green-100 text-green-700">{{ $loc->status_text }}</span>@else<span class="badge bg-red-100 text-red-700">{{ $loc->status_text }}</span>@endif</div><div class="flex items-center gap-2 flex-wrap"><span class="badge bg-blue-100 text-blue-700">{{ $loc->campus_text }}</span><span class="text-xs" style="color: var(--c-ink-muted);">{{ $loc->building_type_text }}</span>@if($loc->building_code)<span class="text-xs" style="color: var(--c-ink-subtle);">代码: {{ $loc->building_code }}</span>@endif</div><div class="flex items-center gap-1 mt-2"><a href="{{ route('locations.show', $loc->id) }}" class="btn btn-ghost btn-sm">查看</a><a href="{{ route('locations.edit', $loc->id) }}" class="btn btn-ghost btn-sm">编辑</a></div></div>
        @empty<div class="p-8 text-center text-ink-muted">暂无地址</div>@endforelse
    </div>
    <div class="hidden md:block overflow-x-auto">
        <table class="w-full text-sm"><thead><tr class="border-b border-border text-left">
            <th class="px-4 py-3 font-medium" style="color: var(--c-ink-muted);">名称</th><th class="px-4 py-3 font-medium" style="color: var(--c-ink-muted);">校区</th><th class="px-4 py-3 font-medium" style="color: var(--c-ink-muted);">类型</th><th class="px-4 py-3 font-medium" style="color: var(--c-ink-muted);">代码</th><th class="px-4 py-3 font-medium" style="color: var(--c-ink-muted);">状态</th><th class="px-4 py-3 font-medium text-right" style="color: var(--c-ink-muted);">操作</th>
        </tr></thead><tbody>
        @forelse($locations as $loc)
        <tr class="border-b border-border hover:bg-surface-muted">
            <td class="px-4 py-3 font-medium text-ink">{{ $loc->name }}</td>
            <td class="px-4 py-3"><span class="badge bg-blue-100 text-blue-700">{{ $loc->campus_text }}</span></td>
            <td class="px-4 py-3 text-ink">{{ $loc->building_type_text }}</td>
            <td class="px-4 py-3 text-ink">{{ $loc->building_code ?? '-' }}</td>
            <td class="px-4 py-3">@if($loc->status == 'active')<span class="badge bg-green-100 text-green-700">{{ $loc->status_text }}</span>@else<span class="badge bg-red-100 text-red-700">{{ $loc->status_text }}</span>@endif</td>
            <td class="px-4 py-3 text-right"><div class="flex items-center justify-end gap-1"><a href="{{ route('locations.show', $loc->id) }}" class="btn btn-ghost btn-icon btn-sm" title="查看"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z M12 9a3 3 0 1 0 0 6 3 3 0 0 0 0-6z"/></svg></a><a href="{{ route('locations.edit', $loc->id) }}" class="btn btn-ghost btn-icon btn-sm" title="编辑"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7 M18.5 2.5a2.1 2.1 0 0 1 3 3L12 15l-4 1 1-4z"/></svg></a><form method="POST" action="{{ route('locations.destroy', $loc->id) }}" class="inline" onsubmit="return confirm('确定删除？')">@csrf @method('DELETE')<button type="submit" class="btn btn-ghost btn-icon btn-sm text-red-500" title="删除"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18 M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg></button></form></div></td>
        </tr>
        @empty<tr><td colspan="6" class="px-4 py-12 text-center text-ink-muted">暂无地址</td></tr>@endforelse
        </tbody></table>
    </div>
</div>
@if($locations->hasPages())<div class="mt-4">{{ $locations->appends(request()->query())->links() }}</div>@endif
@endsection
