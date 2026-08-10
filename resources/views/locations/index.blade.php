@extends('layouts.app')
@section('title', '地址管理')
@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-xl font-semibold text-ink">地址管理</h1>
        <p class="text-sm text-ink-muted mt-0.5">按自定义层级组织的地址树</p>
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ route('location-levels.index') }}" class="btn btn-secondary">层级定义</a>
        <a href="{{ route('locations.campuses') }}" class="btn btn-secondary">区域管理</a>
        <a href="{{ route('locations.create') }}" class="btn btn-primary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/></svg>
            <span>新增地址</span>
        </a>
    </div>
</div>

@if(session('success'))
    <div class="mb-4 px-4 py-3 rounded-lg bg-green-50 text-green-700 text-sm">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="mb-4 px-4 py-3 rounded-lg bg-red-50 text-red-700 text-sm">{{ session('error') }}</div>
@endif

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
        <table class="w-full text-sm">
            <thead class="bg-surface-muted text-ink-muted text-xs uppercase">
                <tr>
                    <th class="text-left px-4 py-3 font-medium">地址名称</th>
                    <th class="text-left px-4 py-3 font-medium">代码</th>
                    <th class="text-left px-4 py-3 font-medium">状态</th>
                    <th class="text-right px-4 py-3 font-medium">操作</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border">
                @include('locations._tree-rows', ['nodes' => $tree, 'depth' => 0])
            </tbody>
        </table>
    </div>
@endif
@endsection