@extends('layouts.app')
@section('title', '编辑层级')
@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-xl font-semibold text-ink">编辑地址层级</h1>
    <a href="{{ route('location-levels.index') }}" class="btn btn-secondary">返回列表</a>
</div>

<div class="max-w-2xl">
    <div class="card p-6">
        <form method="POST" action="{{ route('location-levels.update', $locationLevel) }}">
            @csrf @method('PUT')
            <div class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="label" for="name">层级名称 <span class="text-red-500">*</span></label>
                        <input type="text" class="input" id="name" name="name" value="{{ old('name', $locationLevel->name) }}" required maxlength="50">
                        @error('name') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="label" for="code">层级代码 <span class="text-red-500">*</span></label>
                        <input type="text" class="input" id="code" name="code" value="{{ old('code', $locationLevel->code) }}" required maxlength="30">
                        @error('code') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="label" for="level">层级深度 <span class="text-red-500">*</span></label>
                        <input type="number" class="input" id="level" name="level" value="{{ old('level', $locationLevel->level) }}" required min="1">
                        @error('level') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="label" for="sort_order">排序</label>
                        <input type="number" class="input" id="sort_order" name="sort_order" value="{{ old('sort_order', $locationLevel->sort_order) }}" min="0">
                    </div>
                </div>
                <div>
                    <label class="label" for="description">描述</label>
                    <textarea class="input" id="description" name="description" rows="2" maxlength="200">{{ old('description', $locationLevel->description) }}</textarea>
                </div>
                <div>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $locationLevel->is_active) ? 'checked' : '' }} class="rounded border-border">
                        <span class="text-sm">启用该层级</span>
                    </label>
                </div>
                <div>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_daily_use" value="1" {{ old('is_daily_use', $locationLevel->is_daily_use) ? 'checked' : '' }} class="rounded border-border">
                        <span class="text-sm">日常使用（工单/报表级联选择时展示）</span>
                    </label>
                    <p class="text-xs text-ink-muted mt-1">不勾选则视为基础地址层级（如省、市、区县、街道、门牌），初始化后固定存在、日常选择省略</p>
                    @error('is_daily_use') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <a href="{{ route('location-levels.index') }}" class="btn btn-secondary">取消</a>
                <button type="submit" class="btn btn-primary">保存</button>
            </div>
        </form>
    </div>
</div>
@endsection