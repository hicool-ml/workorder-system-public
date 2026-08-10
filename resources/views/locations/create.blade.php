@extends('layouts.app')
@section('title', '新增地址')
@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-xl font-semibold text-ink">新增地址</h1>
    <a href="{{ route('locations.index') }}" class="btn btn-secondary">返回列表</a>
</div>

<div class="max-w-2xl">
    @if(session('error'))
        <div class="mb-4 px-4 py-3 rounded-lg bg-red-50 text-red-700 text-sm">{{ session('error') }}</div>
    @endif

    <div class="card p-6">
        <form method="POST" action="{{ route('locations.store') }}">
            @csrf
            <div class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="label" for="level_id">所属层级 <span class="text-red-500">*</span></label>
                        <select class="input" id="level_id" name="level_id" required>
                            <option value="">请选择层级</option>
                            @foreach($levels as $lv)
                                <option value="{{ $lv->id }}" {{ old('level_id') == $lv->id ? 'selected' : '' }}>{{ $lv->name }}（第{{ $lv->level }}层）</option>
                            @endforeach
                        </select>
                        @error('level_id') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="label" for="parent_id">上级地址</label>
                        <select class="input" id="parent_id" name="parent_id">
                            <option value="">无（顶层节点）</option>
                            @foreach($parentOptions as $id => $label)
                                <option value="{{ $id }}" {{ old('parent_id', request('parent_id')) == $id ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        <p class="text-xs text-ink-muted mt-1">父节点的层级必须高于当前层级</p>
                    </div>
                </div>

                <div>
                    <label class="label" for="name">地址名称 <span class="text-red-500">*</span></label>
                    <input type="text" class="input" id="name" name="name" value="{{ old('name') }}" required maxlength="255" placeholder="如：锦江区、天府大道、1号办公楼" autocomplete="off">
                    @error('name') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="label" for="code">编码</label>
                        <input type="text" class="input" id="code" name="code" value="{{ old('code') }}" maxlength="50">
                    </div>
                    <div>
                        <label class="label" for="sort_order">排序</label>
                        <input type="number" class="input" id="sort_order" name="sort_order" value="{{ old('sort_order', 0) }}" min="0">
                    </div>
                    <div>
                        <label class="label" for="status">状态 <span class="text-red-500">*</span></label>
                        <select class="input" id="status" name="status" required>
                            <option value="active" {{ old('status', 'active') == 'active' ? 'selected' : '' }}>启用</option>
                            <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>禁用</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="label" for="description">描述</label>
                    <textarea class="input" id="description" name="description" rows="2" maxlength="500">{{ old('description') }}</textarea>
                </div>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <a href="{{ route('locations.index') }}" class="btn btn-secondary">取消</a>
                <button type="submit" class="btn btn-primary">保存</button>
            </div>
        </form>
    </div>
</div>
@endsection