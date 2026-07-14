@extends('layouts.app')

@section('title', '地址管理')

@section('content')
{{-- Page header --}}
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-xl font-semibold text-ink">地址管理</h1>
        <p class="text-sm text-ink-muted mt-0.5">管理校区下的楼宇地址</p>
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ route('locations.create') }}" class="btn btn-primary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/></svg>
            <span>新增地址</span>
        </a>
    </div>
</div>

{{-- Tabs --}}
<div class="flex gap-1 mb-4">
    <a href="{{ route('locations.campuses') }}" class="px-4 py-2 text-sm font-medium rounded-lg text-ink-muted hover:bg-surface-muted">校区管理</a>
    <a href="{{ route('locations.index') }}" class="px-4 py-2 text-sm font-medium rounded-lg bg-brand-600 text-white">楼宇地址</a>
</div>

{{-- Search filters --}}
<div class="card mb-4">
    <form method="GET" action="{{ route('locations.index') }}">
        <div class="p-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            <div>
                <label class="label" for="keyword">关键词</label>
                <input type="text" class="input" id="keyword" name="keyword"
                       value="{{ request('keyword') }}" placeholder="地址名称、编码" autocomplete="off">
            </div>
            <div>
                <label class="label" for="campus_id">校区</label>
                <select class="input" id="campus_id" name="campus_id">
                    <option value="">全部校区</option>
                    @foreach($campuses as $id => $name)
                        <option value="{{ $id }}" {{ request('campus_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label" for="building_type">建筑类型</label>
                <select class="input" id="building_type" name="building_type">
                    <option value="">全部类型</option>
                    @foreach(\App\Models\Location::BUILDING_TYPES as $key => $value)
                        <option value="{{ $key }}" {{ request('building_type') == $key ? 'selected' : '' }}>{{ $value }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="btn btn-primary flex-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 19a8 8 0 1 0 0-16 8 8 0 0 0 0 16z M21 21l-4.35-4.35"/></svg>
                    <span>搜索</span>
                </button>
                <a href="{{ route('locations.index') }}" class="btn btn-secondary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18 6L6 18M6 6l12 12"/></svg>
                </a>
            </div>
        </div>
    </form>
</div>

{{-- Table --}}
<div class="card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-border bg-surface-muted">
                    <th class="px-4 py-3 text-left font-medium text-ink-muted">地址名称</th>
                    <th class="px-4 py-3 text-left font-medium text-ink-muted">校区</th>
                    <th class="px-4 py-3 text-left font-medium text-ink-muted">建筑类型</th>
                    <th class="px-4 py-3 text-center font-medium text-ink-muted">排序</th>
                    <th class="px-4 py-3 text-center font-medium text-ink-muted">状态</th>
                    <th class="px-4 py-3 text-right font-medium text-ink-muted">操作</th>
                </tr>
            </thead>
            <tbody>
                @forelse($locations as $location)
                <tr class="border-b border-border last:border-0 hover:bg-surface-muted transition-colors">
                    <td class="px-4 py-3 text-ink font-medium">
                        <a href="{{ route('locations.show', $location->id) }}" class="hover:text-brand-600">{{ $location->name }}</a>
                        @if($location->building_code)
                            <span class="text-xs text-ink-subtle ml-1">({{ $location->building_code }})</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <span class="badge bg-blue-100 text-blue-700">{{ $location->campus_text }}</span>
                    </td>
                    <td class="px-4 py-3 text-ink-muted">{{ $location->building_type_text }}</td>
                    <td class="px-4 py-3 text-center text-ink-muted">{{ $location->sort_order }}</td>
                    <td class="px-4 py-3 text-center">
                        @if($location->status == 'active')
                            <span class="badge bg-green-100 text-green-700">启用</span>
                        @else
                            <span class="badge bg-surface-muted text-ink-muted">禁用</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center justify-end gap-1">
                            <a href="{{ route('locations.show', $location->id) }}" class="btn btn-icon btn-ghost" title="查看">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/></svg>
                            </a>
                            <a href="{{ route('locations.edit', $location->id) }}" class="btn btn-icon btn-ghost" title="编辑">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7 M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                            </a>
                            <form action="{{ route('locations.destroy', $location->id) }}" method="POST" onsubmit="return confirm('确定要删除「{{ $location->name }}」吗？')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-icon btn-ghost text-red-600" title="删除">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18 M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2 M10 11v6 M14 11v6"/></svg>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-4 py-12 text-center text-ink-muted">
                        <svg class="w-12 h-12 mx-auto mb-3 text-ink-subtle" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35 M11 19a8 8 0 1 0 0-16 8 8 0 0 0 0 16z"/></svg>
                        <p>暂无地址数据</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($locations->hasPages())
    <div class="px-4 py-3 border-t border-border">
        {{ $locations->appends(request()->query())->links() }}
    </div>
    @endif
</div>
@endsection
