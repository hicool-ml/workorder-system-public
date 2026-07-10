@extends('layouts.app')

@section('title', '编辑校区')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">编辑校区</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="{{ route('locations.campuses') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> 返回列表
        </a>
    </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div class="">
        <div class="card p-5">
            <div >
                <form action="{{ route('locations.update-campus', $campus->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="mb-4">
                        <label for="name" class="label">校区名称 <span class="text-red-500">*</span></label>
                        <input type="text" class="input @error('name') is-invalid @enderror"
                               id="name" name="name" value="{{ old('name', $campus->name) }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="description" class="label">校区描述</label>
                        <textarea class="input @error('description') is-invalid @enderror"
                                  id="description" name="description" rows="3">{{ old('description', $campus->description) }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-3">
                        <div class="">
                            <label for="sort_order" class="label">排序顺序</label>
                            <input type="number" class="input @error('sort_order') is-invalid @enderror"
                                   id="sort_order" name="sort_order" value="{{ old('sort_order', $campus->sort_order) }}" min="0">
                            @error('sort_order')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">数字越小排序越前</div>
                        </div>
                        <div class="">
                            <label for="status" class="label">状态 <span class="text-red-500">*</span></label>
                            <select class="input @error('status') is-invalid @enderror" id="status" name="status" required>
                                <option value="active" {{ old('status', $campus->status) == 'active' ? 'selected' : '' }}>启用</option>
                                <option value="inactive" {{ old('status', $campus->status) == 'inactive' ? 'selected' : '' }}>禁用</option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="d-flex justify-content-end">
                        <a href="{{ route('locations.campuses') }}" class="btn btn-secondary me-2">取消</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> 更新校区
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection