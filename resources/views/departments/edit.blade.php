@extends('layouts.app')

@section('title', '编辑部门')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">编辑部门</h3>
                    <div class="card-tools">
                        <a href="{{ route('departments.show', $department->id) }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> 返回
                        </a>
                    </div>
                </div>
                <form method="POST" action="{{ route('departments.update', $department->id) }}">
                    @csrf
                    @method('PUT')
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="name">部门名称 <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                           id="name" name="name" value="{{ old('name', $department->name) }}" required>
                                    @error('name')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="code">部门编码</label>
                                    <input type="text" class="form-control @error('code') is-invalid @enderror"
                                           id="code" name="code" value="{{ old('code', $department->code) }}" required>
                                    @error('code')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="manager">负责人</label>
                                    <input type="text" class="form-control @error('manager') is-invalid @enderror"
                                           id="manager" name="manager" value="{{ old('manager', $department->manager) }}">
                                    @error('manager')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="phone">联系电话</label>
                                    <input type="text" class="form-control @error('phone') is-invalid @enderror"
                                           id="phone" name="phone" value="{{ old('phone', $department->phone) }}">
                                    @error('phone')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="location">办公地点</label>
                                    <input type="text" class="form-control @error('location') is-invalid @enderror"
                                           id="location" name="location" value="{{ old('location', $department->location) }}">
                                    @error('location')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="sort_order">排序</label>
                                    <input type="number" class="form-control @error('sort_order') is-invalid @enderror" 
                                           id="sort_order" name="sort_order" value="{{ old('sort_order', $department->sort_order) }}" min="0">
                                    @error('sort_order')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="description">部门描述</label>
                                    <textarea class="form-control @error('description') is-invalid @enderror" 
                                              id="description" name="description" rows="3">{{ old('description', $department->description) }}</textarea>
                                    @error('description')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="status" name="status" value="active" {{ $department->is_active ? 'checked' : '' }}>
                                        <input type="hidden" name="status" id="status_hidden" value="inactive">
                                        <label class="custom-control-label" for="status">启用部门</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> 保存
                        </button>
                        <a href="{{ route('departments.show', $department->id) }}" class="btn btn-secondary">
                            <i class="fas fa-times"></i> 取消
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const statusCheckbox = document.getElementById('status');
    const statusHidden = document.getElementById('status_hidden');
    
    if (statusCheckbox && statusHidden) {
        statusCheckbox.addEventListener('change', function() {
            if (this.checked) {
                // 选中复选框时，隐藏字段值设为 active
                statusHidden.value = 'active';
                statusHidden.disabled = true; // 禁用隐藏字段，避免冲突
            } else {
                // 取消选中复选框时，隐藏字段值设为 inactive
                statusHidden.value = 'inactive';
                statusHidden.disabled = false; // 启用隐藏字段
            }
        });
        
        // 初始化状态
        if (statusCheckbox.checked) {
            statusHidden.value = 'active';
            statusHidden.disabled = true;
        } else {
            statusHidden.value = 'inactive';
            statusHidden.disabled = false;
        }
    }
});
</script>
@endsection