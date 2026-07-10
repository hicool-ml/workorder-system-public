@extends('layouts.app')

@section('title', '新增校区')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">新增校区</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="{{ route('locations.campuses') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> 返回列表
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <form action="{{ route('locations.store-campus') }}" method="POST">
                    @csrf

                    <div class="mb-3">
                        <label for="name" class="form-label">校区名称 <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror"
                               id="name" name="name" value="{{ old('name') }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">校区描述</label>
                        <textarea class="form-control @error('description') is-invalid @enderror"
                                  id="description" name="description" rows="3">{{ old('description') }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="sort_order" class="form-label">排序顺序</label>
                            <input type="number" class="form-control @error('sort_order') is-invalid @enderror"
                                   id="sort_order" name="sort_order" value="{{ old('sort_order', 0) }}" min="0">
                            @error('sort_order')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">数字越小排序越前</div>
                        </div>
                        <div class="col-md-6">
                            <label for="status" class="form-label">状态 <span class="text-danger">*</span></label>
                            <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
                                <option value="active" {{ old('status', 'active') == 'active' ? 'selected' : '' }}>启用</option>
                                <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>禁用</option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="d-flex justify-content-end">
                        <a href="{{ route('locations.campuses') }}" class="btn btn-secondary me-2">取消</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> 保存校区
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection