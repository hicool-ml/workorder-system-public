@extends('layouts.app')

@section('title', '新增地址')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">新增地址</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="{{ route('locations.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> 返回列表
        </a>
    </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div class="">
        <div class="card p-5">
            <div class="text-sm font-semibold text-ink mb-3">
                <h5 class="card-title mb-0">地址信息</h5>
            </div>
            <div >
                <form method="POST" action="{{ route('locations.store') }}">
                    @csrf
                    
                    <div class="row g-3 mb-4">
                        <div class="">
                            <label for="name" class="label">地址名称 <span class="text-red-500">*</span></label>
                            <input type="text" class="input" id="name" name="name" autocomplete="off"
                                   value="{{ old('name') }}" required maxlength="255" autocomplete="off"
                                   placeholder="如：老校区1-7教" autocomplete="street-address">
                        </div>
                    </div>
                    
                    <div class="row g-3 mb-4">
                        <div class="">
                            <label for="campus_id" class="label">校区 <span class="text-red-500">*</span></label>
                            <select class="input" id="campus_id" name="campus_id" required>
                                <option value="">请选择校区</option>
                                @foreach($campuses as $id => $name)
                                <option value="{{ $id }}" {{ old('campus_id') == $id ? 'selected' : '' }}>
                                    {{ $name }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="">
                            <label for="building_type" class="label">建筑类型 <span class="text-red-500">*</span></label>
                            <select class="input" id="building_type" name="building_type" required>
                                <option value="">请选择建筑类型</option>
                                @foreach(\App\Models\Location::BUILDING_TYPES as $key => $value)
                                <option value="{{ $key }}" {{ old('building_type') == $key ? 'selected' : '' }}>
                                    {{ $value }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="">
                            <label for="status" class="label">状态 <span class="text-red-500">*</span></label>
                            <select class="input" id="status" name="status" required>
                                @foreach(\App\Models\Location::STATUSES as $key => $value)
                                <option value="{{ $key }}" {{ old('status', 'active') == $key ? 'selected' : '' }}>
                                    {{ $value }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    
                    <div class="row g-3 mb-4">
                        <div class="">
                            <label for="sort_order" class="label">排序</label>
                            <input type="number" class="input" id="sort_order" name="sort_order"
                                   value="{{ old('sort_order', 0) }}" min="0" autocomplete="off">
                            <div class="form-text">数字越小排序越靠前</div>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="description" class="label">描述</label>
                        <textarea class="input" id="description" name="description" rows="3"
                                  placeholder="地址的详细描述" autocomplete="off">{{ old('description') }}</textarea>
                    </div>
                    
                    <div class="d-flex justify-content-end">
                        <a href="{{ route('locations.index') }}" class="btn btn-secondary me-2">
                            <i class="fas fa-times"></i> 取消
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> 保存
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="">
        <!-- 说明 -->
        <div class="card mb-4">
            <div class="text-sm font-semibold text-ink mb-3">
                <h6 class="card-title mb-0">填写说明</h6>
            </div>
            <div >
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
                <h6 class="card-title mb-0">校区说明</h6>
            </div>
            <div >
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