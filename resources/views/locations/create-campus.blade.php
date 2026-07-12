@extends('layouts.app')

@section('title', '新增校区')

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-xl font-semibold text-ink">新增校区</h1>
    <a href="{{ route('locations.campuses') }}" class="btn btn-secondary">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 12H5 M12 19l-7-7 7-7"/></svg>
        <span>返回列表</span>
    </a>
</div>

<div class="max-w-2xl">
    <div class="card p-6">
        <form method="POST" action="{{ route('locations.store-campus') }}">
            @csrf
            <div class="space-y-4">
                <div>
                    <label class="label" for="name">校区名称 <span class="text-red-500">*</span></label>
                    <input type="text" class="input" id="name" name="name"
                           value="{{ old('name') }}" required maxlength="255"
                           placeholder="如：新校区、老校区" autocomplete="off">
                    @error('name') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="label" for="description">校区描述</label>
                    <textarea class="input" id="description" name="description" rows="3"
                              placeholder="选填，校区的详细描述">{{ old('description') }}</textarea>
                    @error('description') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="label" for="sort_order">排序顺序</label>
                        <input type="number" class="input" id="sort_order" name="sort_order"
                               value="{{ old('sort_order', 0) }}" min="0" autocomplete="off">
                        <p class="text-xs text-ink-muted mt-1">数字越小排序越前</p>
                        @error('sort_order') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="label" for="status">状态 <span class="text-red-500">*</span></label>
                        <select class="input" id="status" name="status" required>
                            <option value="active" {{ old('status', 'active') == 'active' ? 'selected' : '' }}>启用</option>
                            <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>禁用</option>
                        </select>
                        @error('status') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 mt-6 pt-4 border-t border-border">
                <a href="{{ route('locations.campuses') }}" class="btn btn-secondary">取消</a>
                <button type="submit" class="btn btn-primary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z M17 21v-8H7v8 M7 3v5h8"/></svg>
                    <span>保存校区</span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
