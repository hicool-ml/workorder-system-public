@extends('layouts.app')

@section('title', '编辑地址')

@section('content')
<div class="flex items-center justify-between mb-6 gap-3 flex-wrap">
    <div>
        <h1 class="text-xl font-semibold text-ink">编辑地址</h1>
        <p class="text-sm text-ink-muted mt-0.5">{{ $location->name }}</p>
    </div>
    <a href="{{ route('locations.index') }}" class="btn btn-secondary">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7 7-7M3 12h18"/></svg>
        <span>返回列表</span>
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2">
        <form method="POST" action="{{ route('locations.update', $location->id) }}" class="card p-5 space-y-4">
            @csrf
            @method('PUT')
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="label" for="name">地址名称 <span class="text-red-500">*</span></label>
                    <input type="text" class="input" id="name" name="name" value="{{ old('name', $location->name) }}" required maxlength="255" placeholder="如：老校区1-7教">
                    @error('name')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="label" for="building_code">楼栋代码</label>
                    <input type="text" class="input" id="building_code" name="building_code" value="{{ old('building_code', $location->building_code) }}" maxlength="50" placeholder="如：1-7, 8-14, A-J">
                    @error('building_code')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="label" for="campus">校区 <span class="text-red-500">*</span></label>
                    <select class="input" id="campus" name="campus" required>
                        <option value="">请选择校区</option>
                        @foreach(\App\Models\Location::CAMPUSES as $key => $value)
                        <option value="{{ $key }}" {{ old('campus', $location->campus) == $key ? 'selected' : '' }}>{{ $value }}</option>
                        @endforeach
                    </select>
                    @error('campus')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="label" for="building_type">建筑类型 <span class="text-red-500">*</span></label>
                    <select class="input" id="building_type" name="building_type" required>
                        <option value="">请选择建筑类型</option>
                        @foreach(\App\Models\Location::BUILDING_TYPES as $key => $value)
                        <option value="{{ $key }}" {{ old('building_type', $location->building_type) == $key ? 'selected' : '' }}>{{ $value }}</option>
                        @endforeach
                    </select>
                    @error('building_type')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="label" for="status">状态 <span class="text-red-500">*</span></label>
                    <select class="input" id="status" name="status" required>
                        @foreach(\App\Models\Location::STATUSES as $key => $value)
                        <option value="{{ $key }}" {{ old('status', $location->status) == $key ? 'selected' : '' }}>{{ $value }}</option>
                        @endforeach
                    </select>
                    @error('status')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="label" for="sort_order">排序</label>
                    <input type="number" class="input" id="sort_order" name="sort_order" value="{{ old('sort_order', $location->sort_order) }}" min="0">
                    <p class="text-xs mt-1" style="color: var(--c-ink-subtle);">数字越小排序越靠前</p>
                </div>
            </div>
            <div>
                <label class="label" for="description">描述</label>
                <textarea class="input" id="description" name="description" rows="3" placeholder="地址的详细描述">{{ old('description', $location->description) }}</textarea>
                @error('description')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
            </div>
            <div class="flex items-center justify-end gap-2 pt-2 border-t border-border">
                <a href="{{ route('locations.index') }}" class="btn btn-secondary">取消</a>
                <button type="submit" class="btn btn-primary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    <span>更新</span>
                </button>
            </div>
        </form>
    </div>

    <div class="lg:col-span-1 space-y-4">
        <div class="card p-5">
            <h3 class="text-sm font-semibold text-ink mb-3">地址信息</h3>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between"><dt style="color: var(--c-ink-muted);">ID</dt><dd class="text-ink">{{ $location->id }}</dd></div>
                <div class="flex justify-between"><dt style="color: var(--c-ink-muted);">完整地址</dt><dd class="text-ink text-right">{{ $location->full_name }}</dd></div>
                <div class="flex justify-between"><dt style="color: var(--c-ink-muted);">创建时间</dt><dd class="text-ink">{{ $location->created_at->format('Y-m-d') }}</dd></div>
            </dl>
        </div>
        <div class="card p-5">
            <h3 class="text-sm font-semibold text-ink mb-3">校区说明</h3>
            <div class="space-y-2 text-sm" style="color: var(--c-ink-muted);">
                <p><strong style="color: var(--c-ink);">老校区：</strong>1-7教学楼、1-10学生宿舍</p>
                <p><strong style="color: var(--c-ink);">新校区：</strong>8-14教学楼、11-18学生宿舍</p>
                <p><strong style="color: var(--c-ink);">东盟校区：</strong>A-J教学楼、19-20学生宿舍</p>
            </div>
        </div>
    </div>
</div>
@endsection