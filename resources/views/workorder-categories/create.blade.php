@extends('layouts.app')

@section('title', '新建工单分类')

@section('content')
<div class="flex items-center justify-between mb-6 gap-3 flex-wrap">
    <div>
        <h1 class="text-xl font-semibold text-ink">新建工单分类</h1>
        <p class="text-sm text-ink-muted mt-0.5">创建分类用于工单归类</p>
    </div>
    <a href="{{ route('workorder-categories.index') }}" class="btn btn-secondary">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7 7-7M3 12h18"/></svg>
        <span>返回列表</span>
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2">
        <form method="POST" action="{{ route('workorder-categories.store') }}" class="card p-5 space-y-5">
            @csrf

            <div class="pt-0">
                <h3 class="text-sm font-semibold text-ink mb-3">层级设置</h3>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="sm:col-span-1">
                        <label class="label" for="parent_id">父分类</label>
                        <select class="input" id="parent_id" name="parent_id">
                            <option value="">无（一级分类）</option>
                            @foreach($parentCategories as $category)
                            <option value="{{ $category->id }}" data-level="{{ $category->level }}" {{ old('parent_id') == $category->id ? 'selected' : '' }}>{{ str_repeat('　　', $category->level - 1) }}{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="label" for="level">层级</label>
                        <input type="text" class="input" id="level" name="level" value="{{ old('level', 1) }}" readonly>
                        <p class="text-xs mt-1" style="color: var(--c-ink-subtle);">系统自动计算</p>
                    </div>
                    <div>
                        <label class="label" for="sort_order">排序</label>
                        <input type="number" class="input" id="sort_order" name="sort_order" value="{{ old('sort_order', 0) }}" min="0">
                        <p class="text-xs mt-1" style="color: var(--c-ink-subtle);">数字越小越靠前</p>
                    </div>
                </div>
            </div>

            <div class="pt-4 border-t border-border">
                <h3 class="text-sm font-semibold text-ink mb-3">基本信息</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="label" for="name">分类名称 <span class="text-red-500">*</span></label>
                        <input type="text" class="input" id="name" name="name" value="{{ old('name') }}" required maxlength="100" placeholder="请输入分类名称">
                        @error('name')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="label" for="code">分类编码 <span class="text-red-500">*</span></label>
                        <input type="text" class="input" id="code" name="code" value="{{ old('code') }}" required maxlength="50" placeholder="如：NETWORK_ISSUE">
                        @error('code')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>
                <div class="mt-4">
                    <label class="label" for="description">分类描述</label>
                    <textarea class="input" id="description" name="description" rows="4" placeholder="请输入分类描述">{{ old('description') }}</textarea>
                </div>
            </div>

            <div class="pt-4 border-t border-border">
                <h3 class="text-sm font-semibold text-ink mb-3">状态设置</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="label" for="status">状态 <span class="text-red-500">*</span></label>
                        <select class="input" id="status" name="status" required>
                            @foreach(\App\Models\WorkorderCategory::getStatusOptions() as $key => $value)
                            <option value="{{ $key }}" {{ old('status', 'active') == $key ? 'selected' : '' }}>{{ $value }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2 border-t border-border">
                <a href="{{ route('workorder-categories.index') }}" class="btn btn-secondary">取消</a>
                <button type="submit" class="btn btn-primary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    <span>创建分类</span>
                </button>
            </div>
        </form>
    </div>

    <div class="lg:col-span-1 space-y-4">
        <div class="card p-5">
            <h3 class="text-sm font-semibold text-ink mb-3">创建提示</h3>
            <ul class="space-y-2 text-sm" style="color: var(--c-ink-muted);">
                <li>分类名称应简洁明了</li>
                <li>分类编码应唯一，建议英文+下划线</li>
                <li>系统最多支持3级分类</li>
                <li>一级分类不能设置父分类</li>
            </ul>
        </div>
        <div class="card p-5">
            <h3 class="text-sm font-semibold text-ink mb-3">层级说明</h3>
            <div class="space-y-3 text-sm">
                <div>
                    <p class="font-medium text-ink">一级分类</p>
        <p class="text-xs mt-0.5" style="color: var(--c-ink-muted);">如：硬件支持、软件与应用</p>
                </div>
                <div>
                    <p class="font-medium text-ink">二级分类</p>
                    <p class="text-xs mt-0.5" style="color: var(--c-ink-muted);">如：无法上网、网络卡顿</p>
                </div>
                <div>
                    <p class="font-medium text-ink">三级分类</p>
                    <p class="text-xs mt-0.5" style="color: var(--c-ink-muted);">如：大屏、电脑、网络优化</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.getElementById('parent_id').addEventListener('change', function() {
    var selected = this.options[this.selectedIndex];
    var levelInput = document.getElementById('level');
    if (selected && selected.value) {
        var parentLevel = parseInt(selected.getAttribute('data-level'));
        var newLevel = parentLevel + 1;
        if (newLevel > 3) {
            alert('分类层级最多支持3级，请选择其他父分类');
            this.value = '';
            levelInput.value = 1;
        } else {
            levelInput.value = newLevel;
        }
    } else {
        levelInput.value = 1;
    }
});
</script>
@endsection
