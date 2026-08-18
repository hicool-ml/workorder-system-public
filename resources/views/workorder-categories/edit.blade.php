@extends('layouts.app')

@section('title', '编辑工单分类 - ' . $workorderCategory->name)

@section('content')
<div class="flex items-center justify-between mb-6 gap-3 flex-wrap">
    <div>
        <h1 class="text-xl font-semibold text-ink">编辑工单分类</h1>
        <p class="text-sm text-ink-muted mt-0.5">{{ $workorderCategory->name }}</p>
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ route('workorder-categories.show', $workorderCategory->id) }}" class="btn btn-secondary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z M12 9a3 3 0 1 0 0 6 3 3 0 0 0 0-6z"/></svg>
            <span>查看详情</span>
        </a>
        <a href="{{ route('workorder-categories.index') }}" class="btn btn-secondary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7 7-7M3 12h18"/></svg>
            <span>返回列表</span>
        </a>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2">
        <form method="POST" action="{{ route('workorder-categories.update', $workorderCategory->id) }}" class="card p-5 space-y-5">
            @csrf
            @method('PUT')

            <div>
                <h3 class="text-sm font-semibold text-ink mb-3">层级设置</h3>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="sm:col-span-1">
                        <label class="label" for="parent_id">父分类</label>
                        <select class="input" id="parent_id" name="parent_id">
                            <option value="">无（一级分类）</option>
                            @foreach($parentCategories as $category)
                            <option value="{{ $category->id }}" data-level="{{ $category->depth + 1 }}" {{ old('parent_id', $workorderCategory->parent_id) == $category->id ? 'selected' : '' }}>{{ str_repeat('　　', $category->depth) }}{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="label" for="level">层级</label>
                        <input type="text" class="input" id="level" name="level" value="{{ old('level', $workorderCategory->level) }}" readonly>
                    </div>
                    <div>
                        <label class="label" for="sort_order">排序</label>
                        <input type="number" class="input" id="sort_order" name="sort_order" value="{{ old('sort_order', $workorderCategory->sort_order) }}" min="0">
                    </div>
                </div>
            </div>

            <div class="pt-4 border-t border-border">
                <h3 class="text-sm font-semibold text-ink mb-3">基本信息</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="label" for="name">分类名称 <span class="text-red-500">*</span></label>
                        <input type="text" class="input" id="name" name="name" value="{{ old('name', $workorderCategory->name) }}" required maxlength="100">
                        @error('name')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="label" for="code">分类编码 <span class="text-red-500">*</span></label>
                        <input type="text" class="input" id="code" name="code" value="{{ old('code', $workorderCategory->code) }}" required maxlength="50">
                        @error('code')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>
                <div class="mt-4">
                    <label class="label" for="description">分类描述</label>
                    <textarea class="input" id="description" name="description" rows="4">{{ old('description', $workorderCategory->description) }}</textarea>
                </div>
            </div>

            <div class="pt-4 border-t border-border">
                <h3 class="text-sm font-semibold text-ink mb-3">状态设置</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="label" for="status">状态 <span class="text-red-500">*</span></label>
                        <select class="input" id="status" name="status" required>
                            @foreach(\App\Models\WorkorderCategory::getStatusOptions() as $key => $value)
                            <option value="{{ $key }}" {{ old('status', $workorderCategory->status ? 'active' : 'inactive') == $key ? 'selected' : '' }}>{{ $value }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2 border-t border-border">
                <a href="{{ route('workorder-categories.show', $workorderCategory->id) }}" class="btn btn-secondary">取消</a>
                <button type="submit" class="btn btn-primary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    <span>保存更改</span>
                </button>
            </div>
        </form>
    </div>

    <div class="lg:col-span-1 space-y-4">
        <div class="card p-5">
            <h3 class="text-sm font-semibold text-ink mb-3">分类信息</h3>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between"><dt style="color: var(--c-ink-muted);">分类ID</dt><dd class="text-ink">{{ $workorderCategory->id }}</dd></div>
                <div class="flex justify-between items-center"><dt style="color: var(--c-ink-muted);">当前层级</dt><dd><span class="badge bg-blue-100 text-blue-700">{{ $workorderCategory->level_text }}</span></dd></div>
                <div class="flex justify-between items-center"><dt style="color: var(--c-ink-muted);">当前状态</dt><dd>@if($workorderCategory->status)<span class="badge bg-green-100 text-green-700">{{ $workorderCategory->status_text }}</span>@else<span class="badge bg-red-100 text-red-700">{{ $workorderCategory->status_text }}</span>@endif</dd></div>
                <div class="flex justify-between"><dt style="color: var(--c-ink-muted);">创建时间</dt><dd class="text-ink">{{ $workorderCategory->created_at ? $workorderCategory->created_at->format('Y-m-d H:i') : '-' }}</dd></div>
                @if($workorderCategory->parent)
                <div class="flex justify-between"><dt style="color: var(--c-ink-muted);">父分类</dt><dd class="text-ink">{{ $workorderCategory->parent->name }}</dd></div>
                @endif
            </dl>
        </div>

        @if($workorderCategory->children()->count() > 0)
        <div class="card p-5">
            <h3 class="text-sm font-semibold text-ink mb-3">子分类</h3>
            <div class="space-y-2">
                @foreach($workorderCategory->children as $child)
                <div class="flex items-center justify-between gap-2 p-2 rounded-lg" style="background-color: var(--c-muted);">
                    <div class="min-w-0">
                        <p class="font-medium text-ink truncate">{{ $child->name }}</p>
                        <p class="text-xs" style="color: var(--c-ink-subtle);">{{ $child->code }}</p>
                    </div>
                    @if($child->status)<span class="badge bg-green-100 text-green-700">{{ $child->status_text }}</span>@else<span class="badge bg-red-100 text-red-700">{{ $child->status_text }}</span>@endif
                </div>
                @endforeach
            </div>
        </div>
        @endif

        <div class="card p-5">
            <h3 class="text-sm font-semibold text-ink mb-3">工单统计</h3>
            <div class="grid grid-cols-2 gap-3">
                <div class="text-center p-3 rounded-lg" style="background-color: var(--c-muted);">
                    <p class="text-xl font-bold text-ink">{{ $workorderCategory->workorders()->count() }}</p>
                    <p class="text-xs mt-0.5" style="color: var(--c-ink-muted);">总工单数</p>
                </div>
                <div class="text-center p-3 rounded-lg" style="background-color: var(--c-muted);">
                    <p class="text-xl font-bold text-amber-600">{{ $workorderCategory->workorders()->whereIn('status', ['pending', 'assigned', 'processing'])->count() }}</p>
                    <p class="text-xs mt-0.5" style="color: var(--c-ink-muted);">待处理</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
var currentLevel = {{ $workorderCategory->level }};
var currentId = {{ $workorderCategory->id }};
var originalParent = '{{ $workorderCategory->parent_id }}';

document.getElementById('parent_id').addEventListener('change', function() {
    var selected = this.options[this.selectedIndex];
    var levelInput = document.getElementById('level');
    if (selected && selected.value) {
        if (selected.value == currentId) {
            alert('不能将分类设置为自己的父分类');
            this.value = originalParent;
            levelInput.value = currentLevel;
            return;
        }
        var parentLevel = parseInt(selected.getAttribute('data-level'));
        var newLevel = parentLevel + 1;
        if (newLevel > 3) {
            alert('分类层级最多支持3级，请选择其他父分类');
            this.value = originalParent;
            levelInput.value = currentLevel;
        } else {
            levelInput.value = newLevel;
        }
    } else {
        levelInput.value = 1;
    }
});
</script>
@endsection