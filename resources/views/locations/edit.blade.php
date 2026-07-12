@extends('layouts.app')

@section('title', '编辑地址')

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-xl font-semibold text-ink">编辑地址</h1>
    <a href="{{ route('locations.index') }}" class="btn btn-secondary">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 12H5 M12 19l-7-7 7-7"/></svg>
        <span>返回列表</span>
    </a>
</div>

<div class="max-w-2xl">
    <div class="card p-6">
        <form method="POST" action="{{ route('locations.update', $location->id) }}">
            @csrf
            @method('PUT')
            <div class="space-y-4">
                <div>
                    <label class="label" for="name">地址名称 <span class="text-red-500">*</span></label>
                    <input type="text" class="input" id="name" name="name"
                           value="{{ old('name', $location->name) }}" required maxlength="255"
                           placeholder="如：1教、食堂" autocomplete="off">
                    @error('name') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="label" for="campus_id">所属校区 <span class="text-red-500">*</span></label>
                        <select class="input" id="campus_id" name="campus_id" required>
                            <option value="">请选择校区</option>
                            @foreach($campuses as $id => $name)
                                <option value="{{ $id }}" {{ old('campus_id', $location->campus_id) == $id ? 'selected' : '' }}>{{ $name }}</option>
                            @endforeach
                        </select>
                        @error('campus_id') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="label" for="building_type">建筑类型 <span class="text-red-500">*</span></label>
                        <select class="input" id="building_type" name="building_type" required>
                            <option value="">请选择</option>
                            @foreach(\App\Models\Location::BUILDING_TYPES as $key => $value)
                                <option value="{{ $key }}" {{ old('building_type', $location->building_type) == $key ? 'selected' : '' }}>{{ $value }}</option>
                            @endforeach
                        </select>
                        @error('building_type') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="label" for="building_code">建筑编码</label>
                        <input type="text" class="input" id="building_code" name="building_code"
                               value="{{ old('building_code', $location->building_code) }}" maxlength="50"
                               placeholder="选填" autocomplete="off">
                    </div>
                    <div>
                        <label class="label" for="sort_order">排序</label>
                        <input type="number" class="input" id="sort_order" name="sort_order"
                               value="{{ old('sort_order', $location->sort_order) }}" min="0" autocomplete="off">
                        <p class="text-xs text-ink-muted mt-1">数字越小越靠前</p>
                    </div>
                </div>

                <div>
                    <label class="label" for="status">状态 <span class="text-red-500">*</span></label>
                    <select class="input" id="status" name="status" required>
                        @foreach(\App\Models\Location::STATUSES as $key => $value)
                            <option value="{{ $key }}" {{ old('status', $location->status) == $key ? 'selected' : '' }}>{{ $value }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="label" for="description">描述</label>
                    <textarea class="input" id="description" name="description" rows="3"
                              placeholder="选填，地址的详细描述">{{ old('description', $location->description) }}</textarea>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 mt-6 pt-4 border-t border-border">
                <a href="{{ route('locations.index') }}" class="btn btn-secondary">取消</a>
                <button type="submit" class="btn btn-primary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z M17 21v-8H7v8 M7 3v5h8"/></svg>
                    <span>更新</span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
