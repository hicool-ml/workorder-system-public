@extends('layouts.app')

@section('title', '编辑地址')

@section('content')
<div class="flex items-center justify-between mb-6 pb-4 border-b border-border">
    <h1 class="text-xl font-semibold text-ink">编辑地址</h1>
    <div class="flex gap-2">
        <a href="{{ route('locations.index') }}" class="btn btn-secondary">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 12H5 M12 19l-7-7 7-7"/></svg> 返回列表
        </a>
    </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <div class="card p-5">
            <div class="text-sm font-semibold text-ink mb-3">
                <h5 class="text-sm font-semibold text-ink">编辑地址信息</h5>
            </div>
            <div>
                <form method="POST" action="{{ route('locations.update', $location->id) }}">
                    @csrf
                    @method('PUT')
                    
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-4">
                        <div>
                            <label for="name" class="label">地址名称 <span class="text-red-500">*</span></label>
                            <input type="text" class="input" id="name" name="name" autocomplete="off"
                                   value="{{ old('name', $location->name) }}" required maxlength="255" autocomplete="off"
                                   placeholder="如：老校区1-7教" autocomplete="street-address">
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-4">
                        <div>
                            <label for="campus_id" class="label">校区 <span class="text-red-500">*</span></label>
                            <select class="input" id="campus_id" name="campus_id" required>
                                <option value="">请选择校区</option>
                                @foreach($campuses as $id => $name)
                                <option value="{{ $id }}" {{ old('campus_id', $location->campus_id) == $id ? 'selected' : '' }}>
                                    {{ $name }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="building_type" class="label">建筑类型 <span class="text-red-500">*</span></label>
                            <select class="input" id="building_type" name="building_type" required>
                                <option value="">请选择建筑类型</option>
                                @foreach(\App\Models\Location::BUILDING_TYPES as $key => $value)
                                <option value="{{ $key }}" {{ old('building_type', $location->building_type) == $key ? 'selected' : '' }}>
                                    {{ $value }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="status" class="label">状态 <span class="text-red-500">*</span></label>
                            <select class="input" id="status" name="status" required>
                                @foreach(\App\Models\Location::STATUSES as $key => $value)
                                <option value="{{ $key }}" {{ old('status', $location->status) == $key ? 'selected' : '' }}>
                                    {{ $value }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-4">
                        <div>
                            <label for="sort_order" class="label">排序</label>
                            <input type="number" class="input" id="sort_order" name="sort_order"
                                   value="{{ old('sort_order', $location->sort_order) }}" min="0" autocomplete="off">
                            <div class="text-xs text-ink-muted mt-1">数字越小排序越靠前</div>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="description" class="label">描述</label>
                        <textarea class="input" id="description" name="description" rows="3"
                                  placeholder="地址的详细描述" autocomplete="off">{{ old('description', $location->description) }}</textarea>
                    </div>
                    
                    <div class="d-flex justify-content-end">
                        <a href="{{ route('locations.index') }}" class="btn btn-secondary mr-2">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18 6L6 18M6 6l12 12"/></svg> 取消
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z M17 21v-8H7v8 M7 3v5h8"/></svg> 更新
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div>
        <!-- 说明 -->
        <div class="card mb-4">
            <div class="text-sm font-semibold text-ink mb-3">
                <h6 class="text-sm font-semibold text-ink">填写说明</h6>
            </div>
            <div>
                <ul class="mb-0">
                    <li><strong>地址名称：</strong>具体的地址描述，如"老校区1-7教"</li>
                    <li><strong>校区：</strong>选择所属校区</li>
                    <li><strong>建筑类型：</strong>选择建筑的功能类型</li>
                    <li><strong>排序：</strong>用于控制地址显示顺序</li>
                </ul>
            </div>
        </div>
        
        <!-- 校区说明 -->
        <div class="card mb-4">
            <div class="text-sm font-semibold text-ink mb-3">
                <h6 class="text-sm font-semibold text-ink">校区说明</h6>
            </div>
            <div>
                <div class="mb-2">
                    <strong>老校区：</strong>包含1-7教学楼、1-10学生宿舍
                </div>
                <div class="mb-2">
                    <strong>新校区：</strong>包含8-14教学楼、11-18学生宿舍
                </div>
                <div class="mb-2">
                    <strong>东盟校区：</strong>包含A-J教学楼、19-20学生宿舍
                </div>
            </div>
        </div>
    </div>
</div>
@endsection