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

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">地址信息</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('locations.store') }}">
                    @csrf
                    
                    <div class="row g-3 mb-4">
                        <div class="col-md-12">
                            <label for="name" class="form-label">地址名称 <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" autocomplete="off"
                                   value="{{ old('name') }}" required maxlength="255" autocomplete="off"
                                   placeholder="如：老校区1-7教" autocomplete="street-address">
                        </div>
                    </div>
                    
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label for="campus_id" class="form-label">校区 <span class="text-danger">*</span></label>
                            <select class="form-select" id="campus_id" name="campus_id" required>
                                <option value="">请选择校区</option>
                                @foreach($campuses as $id => $name)
                                <option value="{{ $id }}" {{ old('campus_id') == $id ? 'selected' : '' }}>
                                    {{ $name }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="building_type" class="form-label">建筑类型 <span class="text-danger">*</span></label>
                            <select class="form-select" id="building_type" name="building_type" required>
                                <option value="">请选择建筑类型</option>
                                @foreach(\App\Models\Location::BUILDING_TYPES as $key => $value)
                                <option value="{{ $key }}" {{ old('building_type') == $key ? 'selected' : '' }}>
                                    {{ $value }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="status" class="form-label">状态 <span class="text-danger">*</span></label>
                            <select class="form-select" id="status" name="status" required>
                                @foreach(\App\Models\Location::STATUSES as $key => $value)
                                <option value="{{ $key }}" {{ old('status', 'active') == $key ? 'selected' : '' }}>
                                    {{ $value }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="sort_order" class="form-label">排序</label>
                            <input type="number" class="form-control" id="sort_order" name="sort_order"
                                   value="{{ old('sort_order', 0) }}" min="0" autocomplete="off">
                            <div class="form-text">数字越小排序越靠前</div>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="description" class="form-label">描述</label>
                        <textarea class="form-control" id="description" name="description" rows="3"
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
    
    <div class="col-md-4">
        <!-- 说明 -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="card-title mb-0">填写说明</h6>
            </div>
            <div class="card-body">
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
            <div class="card-header">
                <h6 class="card-title mb-0">校区说明</h6>
            </div>
            <div class="card-body">
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