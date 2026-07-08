@extends('layouts.app')

@section('title', '编辑部门')

@section('content')
<div class="flex items-center justify-between mb-6 gap-3 flex-wrap">
    <div>
        <h1 class="text-xl font-semibold text-ink">编辑部门</h1>
        <p class="text-sm text-ink-muted mt-0.5">{{ $department->name }}</p>
    </div>
    <a href="{{ route('departments.show', $department->id) }}" class="btn btn-secondary">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7 7-7M3 12h18"/></svg>
        <span>返回详情</span>
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2">
        <form method="POST" action="{{ route('departments.update', $department->id) }}" class="card p-5 space-y-4">
            @csrf
            @method('PUT')
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="label" for="name">部门名称 <span class="text-red-500">*</span></label>
                    <input type="text" class="input" id="name" name="name" value="{{ old('name', $department->name) }}" required>
                    @error('name')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="label" for="code">部门编码 <span class="text-red-500">*</span></label>
                    <input type="text" class="input" id="code" name="code" value="{{ old('code', $department->code) }}" required>
                    @error('code')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="label" for="manager">负责人</label>
                    <input type="text" class="input" id="manager" name="manager" value="{{ old('manager', $department->manager) }}">
                    @error('manager')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="label" for="phone">联系电话</label>
                    <input type="text" class="input" id="phone" name="phone" value="{{ old('phone', $department->phone) }}">
                    @error('phone')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="label" for="location">办公地点</label>
                    <input type="text" class="input" id="location" name="location" value="{{ old('location', $department->location) }}">
                    @error('location')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="label" for="sort_order">排序</label>
                    <input type="number" class="input" id="sort_order" name="sort_order" value="{{ old('sort_order', $department->sort_order) }}" min="0">
                    @error('sort_order')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                </div>
            </div>
            <div>
                <label class="label" for="description">部门描述</label>
                <textarea class="input" id="description" name="description" rows="3">{{ old('description', $department->description) }}</textarea>
                @error('description')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
            </div>
            <div class="flex items-center gap-2">
                <input type="checkbox" id="status" class="rounded w-4 h-4" {{ old('status', $department->is_active ? 'active' : 'inactive') == 'active' ? 'checked' : '' }} onchange="this.nextElementSibling.value = this.checked ? 'active' : 'inactive'">
                <label class="text-sm cursor-pointer" for="status" style="color: var(--c-ink-muted);">启用部门</label>
                <input type="hidden" name="status" value="{{ old('status', $department->is_active ? 'active' : 'inactive') }}">
            </div>
            <div class="flex items-center justify-end gap-2 pt-2 border-t border-border">
                <a href="{{ route('departments.show', $department->id) }}" class="btn btn-secondary">取消</a>
                <button type="submit" class="btn btn-primary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    <span>保存更改</span>
                </button>
            </div>
        </form>
    </div>

    <div class="lg:col-span-1 space-y-4">
        <div class="card p-5">
            <h3 class="text-sm font-semibold text-ink mb-3">部门信息</h3>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between"><dt style="color: var(--c-ink-muted);">ID</dt><dd class="text-ink">{{ $department->id }}</dd></div>
                <div class="flex justify-between"><dt style="color: var(--c-ink-muted);">当前状态</dt><dd>@if($department->is_active)<span class="badge bg-green-100 text-green-700">启用</span>@else<span class="badge bg-red-100 text-red-700">禁用</span>@endif</dd></div>
                <div class="flex justify-between"><dt style="color: var(--c-ink-muted);">创建时间</dt><dd class="text-ink">{{ $department->created_at->format('Y-m-d') }}</dd></div>
            </dl>
        </div>
    </div>
</div>
@endsection