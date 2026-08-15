@extends('layouts.app')

@section('title', '区域详情')

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-xl font-semibold text-ink">{{ $campus->name }}</h1>
        <p class="text-sm text-ink-muted mt-0.5">区域详情</p>
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ route('locations.campuses') }}" class="btn btn-secondary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 12H5 M12 19l-7-7 7-7"/></svg>
            <span>返回列表</span>
        </a>
        <a href="{{ route('locations.edit-campus', $campus->id) }}" class="btn btn-primary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7 M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            <span>编辑区域</span>
        </a>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    {{-- Basic info --}}
    <div class="lg:col-span-1">
        <div class="card p-6">
            <h3 class="text-sm font-semibold text-ink mb-4">基本信息</h3>
            <div class="space-y-3">
                <div class="flex items-center justify-between py-2 border-b border-border">
                    <span class="text-sm text-ink-muted">区域ID</span>
                    <span class="text-sm font-medium text-ink">{{ $campus->id }}</span>
                </div>
                <div class="flex items-center justify-between py-2 border-b border-border">
                    <span class="text-sm text-ink-muted">区域名称</span>
                    <span class="text-sm font-medium text-ink">{{ $campus->name }}</span>
                </div>
                @if($campus->code)
                <div class="flex items-center justify-between py-2 border-b border-border">
                    <span class="text-sm text-ink-muted">代码</span>
                    <span class="text-sm font-medium text-ink">{{ $campus->code }}</span>
                </div>
                @endif
                <div class="flex items-center justify-between py-2 border-b border-border">
                    <span class="text-sm text-ink-muted">排序</span>
                    <span class="text-sm font-medium text-ink">{{ $campus->sort_order }}</span>
                </div>
                <div class="flex items-center justify-between py-2 border-b border-border">
                    <span class="text-sm text-ink-muted">状态</span>
                    @if($campus->status == 'active')
                        <span class="badge bg-green-100 text-green-700">{{ $campus->status_text }}</span>
                    @else
                        <span class="badge bg-surface-muted text-ink-muted">{{ $campus->status_text }}</span>
                    @endif
                </div>
                @if($campus->description)
                <div class="py-2">
                    <span class="text-sm text-ink-muted block mb-1">描述</span>
                    <span class="text-sm text-ink">{{ $campus->description }}</span>
                </div>
                @endif
                <div class="flex items-center justify-between py-2">
                    <span class="text-sm text-ink-muted">创建时间</span>
                    <span class="text-sm text-ink-muted">{{ $campus->created_at->format('Y-m-d H:i') }}</span>
                </div>
            </div>

            <div class="flex flex-col gap-2 mt-6 pt-4 border-t border-border">
                <form action="{{ route('locations.toggle-campus-status', $campus->id) }}" method="POST">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-outline-warning w-full">
                        @if($campus->status == 'active')
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 8L21 12L17 16 M3 12h18"/></svg>
                            <span>禁用区域</span>
                        @else
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 8L21 12L17 16 M3 12h18"/></svg>
                            <span>启用区域</span>
                        @endif
                    </button>
                </form>
                <a href="{{ route('locations.create') }}?campus_id={{ $campus->id }}" class="btn btn-secondary w-full">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/></svg>
                    <span>新增地址</span>
                </a>
                <form action="{{ route('locations.destroy-campus', $campus->id) }}" method="POST"
                      onsubmit="return confirm('确定要删除这个区域吗？删除后无法恢复！')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger w-full" @if(!$campus->canBeDeleted()) disabled @endif>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18 M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2 M10 11v6 M14 11v6"/></svg>
                        <span>删除区域</span>
                    </button>
                    @if(!$campus->canBeDeleted())
                        <p class="text-xs text-ink-subtle mt-1">有关联地址，无法删除</p>
                    @endif
                </form>
            </div>
        </div>
    </div>

    {{-- Associated locations --}}
    <div class="lg:col-span-2">
        <div class="card overflow-hidden">
            <div class="px-4 py-3 border-b border-border">
                <h3 class="text-sm font-semibold text-ink">关联地址 ({{ $campus->locations->count() }})</h3>
            </div>
            @if($campus->locations->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-border bg-surface-muted">
                            <th class="px-4 py-3 text-left font-medium text-ink-muted">地址名称</th>
                            <th class="px-4 py-3 text-left font-medium text-ink-muted">层级</th>
                            <th class="px-4 py-3 text-left font-medium text-ink-muted">编码</th>
                            <th class="px-4 py-3 text-center font-medium text-ink-muted">排序</th>
                            <th class="px-4 py-3 text-center font-medium text-ink-muted">状态</th>
                            <th class="px-4 py-3 text-right font-medium text-ink-muted">操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($campus->locations as $location)
                        <tr class="border-b border-border last:border-0 hover:bg-surface-muted transition-colors">
                            <td class="px-4 py-3 font-medium text-ink">
                                <a href="{{ route('locations.show', $location->id) }}" class="hover:text-brand-600">{{ $location->name }}</a>
                            </td>
                            <td class="px-4 py-3">
                                <span class="badge bg-slate-100 text-slate-600">{{ $location->level?->name ?? '-' }}</span>
                            </td>
                            <td class="px-4 py-3 text-ink-muted">{{ $location->code ?: '-' }}</td>
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
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="px-4 py-12 text-center text-ink-muted">
                <svg class="w-12 h-12 mx-auto mb-3 text-ink-subtle" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 21h18 M5 21V7l8-4v18 M19 21V11l-6-4"/></svg>
                <p>该区域暂无关联地址</p>
                <a href="{{ route('locations.create') }}?campus_id={{ $campus->id }}" class="btn btn-primary btn-sm mt-3">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/></svg>
                    <span>新增地址</span>
                </a>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
