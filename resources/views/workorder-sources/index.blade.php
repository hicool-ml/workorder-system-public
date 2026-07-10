@extends('layouts.app')

@section('title', '工单来源管理')

@push('styles')
<link href="{{ asset('css/workorder-sources-enhanced.css?v=' . time()) }}" rel="stylesheet">
@endpush

@section('content')
<div>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div>
            <div class="card">
                <div class="px-4 py-3 border-b border-border bg-surface-muted rounded-t-xl">
                    <h3 class="text-sm font-semibold text-ink">工单来源管理</h3>
                    <div class="card-tools">
                        <a href="{{ route('workorder-sources.create') }}" class="btn btn-primary btn-sm">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/></svg> 新增工单来源
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="mb-4 p-3 rounded-lg bg-green-50 border border-green-200 text-green-800 text-sm">
                            <button type="button" class="text-ink-muted hover:text-ink">&times;</button>
                            {{ session('success') }}
                        </div>
                    @endif
                    
                    @if(session('error'))
                        <div class="mb-4 p-3 rounded-lg bg-red-50 border border-red-200 text-red-800 text-sm">
                            <button type="button" class="text-ink-muted hover:text-ink">&times;</button>
                            {{ session('error') }}
                        </div>
                    @endif

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr>
                                    <th style="width: 8%">
                                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 9h16 M4 15h16 M10 3L8 21 M16 3l-2 18"/></svg> ID
                                    </th>
                                    <th style="width: 35%">
                                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z M7 7h.01"/></svg> 来源名称
                                    </th>
                                    <th style="width: 12%">
                                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18 M7 12h10 M10 18h4"/></svg> 排序
                                    </th>
                                    <th style="width: 15%">
                                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 8L21 12L17 16 M3 12h18"/></svg> 状态
                                    </th>
                                    <th style="width: 30%">
                                        <i class="fas fa-cogs"></i> 操作
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($sources as $source)
                                    <tr class="{{ $source->is_active ? '' : 'table-secondary' }}">
                                        <td>
                                            <span class="badge bg-surface-muted text-ink">{{ $source->id }}</span>
                                        </td>
                                        <td>
                                            <strong>{{ $source->name }}</strong>
                                        </td>
                                        <td>
                                            <span class="badge bg-blue-100 text-blue-700">{{ $source->sort_order }}</span>
                                        </td>
                                        <td>
                                            @if($source->is_active)
                                                <span class="badge bg-green-100 text-green-700">
                                                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M22 11.08V12a10 10 0 1 1-5.93-9.14 M22 4L12 14.01l-3-3"/></svg> 启用
                                                </span>
                                            @else
                                                <span class="badge bg-surface-muted text-ink-muted">
                                                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 22a10 10 0 1 0 0-20 10 10 0 0 0 0 20z M15 9l-6 6 M9 9l6 6"/></svg> 禁用
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="flex gap-1" role="group" style="flex-wrap: nowrap;">
                                                <a href="{{ route('workorder-sources.edit', $source) }}"
                                                   class="btn btn-outline-primary"
                                                   title="编辑来源：{{ $source->name }}"
                                                   style="white-space: nowrap;">
                                                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7 M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                                    <span class="d-none d-md-inline">编辑</span>
                                                </a>

                                                <form action="{{ route('workorder-sources.toggle-status', $source) }}"
                                                      method="POST" style="display: inline-block;">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit"
                                                            class="btn {{ $source->is_active ? 'btn-outline-warning' : 'btn-outline-success' }}"
                                                            title="{{ $source->is_active ? '禁用来源：' . $source->name : '启用来源：' . $source->name }}"
                                                            style="white-space: nowrap;">
                                                        <i class="fas {{ $source->is_active ? 'fa-eye-slash' : 'fa-eye' }}"></i>
                                                        <span class="d-none d-md-inline">{{ $source->is_active ? '禁用' : '启用' }}</span>
                                                    </button>
                                                </form>

                                                @if(!$source->workorders()->exists())
                                                    <form action="{{ route('workorder-sources.destroy', $source) }}"
                                                          method="POST" style="display: inline-block;"
                                                          onsubmit="return confirm('确定要删除工单来源「' . $source->name . '」吗？此操作不可撤销！');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit"
                                                                class="btn btn-outline-danger"
                                                                title="删除来源：{{ $source->name }}"
                                                                style="white-space: nowrap;">
                                                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18 M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2 M10 11v6 M14 11v6"/></svg>
                                                            <span class="d-none d-md-inline">删除</span>
                                                        </button>
                                                    </form>
                                                @else
                                                    <button class="btn btn-outline-secondary" disabled
                                                            title="已有工单使用此来源，无法删除"
                                                            style="white-space: nowrap;">
                                                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18 M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2 M10 11v6 M14 11v6"/></svg>
                                                        <span class="d-none d-md-inline">删除</span>
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-4">
                                            <div class="text-ink-muted">
                                                <svg class="w-8 h-8 text-4xl mb-3 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M22 12h-6l-2 3h-4l-2-3H2 M5.45 5.11L2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"/></svg>
                                                <h5>暂无工单来源数据</h5>
                                                <p>请点击"新增工单来源"按钮添加第一个工单来源</p>
                                                <a href="{{ route('workorder-sources.create') }}" class="btn btn-primary mt-2">
                                                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/></svg> 新增工单来源
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <div class="text-ink-muted">
                            <small>
                                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 22a10 10 0 1 0 0-20 10 10 0 0 0 0 20z M12 16v-4 M12 8h.01"/></svg>
                                共 {{ $sources->count() }} 个工单来源，
                                {{ $sources->where('is_active', true)->count() }} 个已启用
                            </small>
                        </div>
                        <div>
                            {{ $sources->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection