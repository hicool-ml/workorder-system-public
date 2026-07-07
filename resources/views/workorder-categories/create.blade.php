@extends('layouts.app')

@section('title', '新建工单分类')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">新建工单分类</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="{{ route('workorder-categories.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> 返回列表
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">分类信息</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('workorder-categories.store') }}">
                    @csrf
                    
                    <!-- 层级设置 -->
                    <h6 class="mb-3">层级设置</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="parent_id" class="form-label">父分类</label>
                            <select class="form-select" id="parent_id" name="parent_id" onchange="updateLevel()">
                                <option value="">无（一级分类）</option>
                                @foreach($parentCategories as $category)
                                <option value="{{ $category->id }}"
                                        {{ old('parent_id') == $category->id ? 'selected' : '' }}
                                        data-level="{{ $category->level }}">
                                    {{ str_repeat('　　', $category->level - 1) }}{{ $category->name }}
                                </option>
                                @endforeach
                            </select>
                            <div class="form-text">选择父分类后，当前分类将自动设置为子分类</div>
                        </div>
                        <div class="col-md-3">
                            <label for="level" class="form-label">层级</label>
                            <input type="text" class="form-control" id="level" name="level"
                                   value="{{ old('level', 1) }}" readonly>
                            <div class="form-text">系统自动计算</div>
                        </div>
                        <div class="col-md-3">
                            <label for="sort_order" class="form-label">排序</label>
                            <input type="number" class="form-control" id="sort_order" name="sort_order"
                                   value="{{ old('sort_order', 0) }}" min="0">
                            <div class="form-text">数字越小排序越靠前</div>
                        </div>
                    </div>
                    
                    <!-- 基本信息 -->
                    <h6 class="mb-3">基本信息</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="name" class="form-label">分类名称 <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name"
                                   value="{{ old('name') }}" required maxlength="100"
                                   placeholder="请输入分类名称">
                        </div>
                        <div class="col-md-6">
                            <label for="code" class="form-label">分类编码 <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="code" name="code"
                                   value="{{ old('code') }}" required maxlength="50"
                                   placeholder="请输入分类编码，如：NETWORK_ISSUE">
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="description" class="form-label">分类描述</label>
                        <textarea class="form-control" id="description" name="description" rows="4"
                                  placeholder="请输入分类描述">{{ old('description') }}</textarea>
                        <div class="form-text">详细描述该分类的用途和范围</div>
                    </div>
                    
                    <!-- 状态设置 -->
                    <h6 class="mb-3">状态设置</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="status" class="form-label">状态 <span class="text-danger">*</span></label>
                            <select class="form-select" id="status" name="status" required>
                                @foreach(\App\Models\WorkorderCategory::getStatusOptions() as $key => $value)
                                <option value="{{ $key }}" {{ old('status', 'active') == $key ? 'selected' : '' }}>
                                    {{ $value }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    
                    <!-- 提交按钮 -->
                    <div class="d-flex justify-content-end">
                        <a href="{{ route('workorder-categories.index') }}" class="btn btn-secondary me-2">
                            <i class="fas fa-times"></i> 取消
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> 创建分类
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <!-- 创建提示 -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="card-title mb-0">创建提示</h6>
            </div>
            <div class="card-body">
                <ul class="mb-0">
                    <li>分类名称应简洁明了，便于用户理解</li>
                    <li>分类编码应唯一，建议使用英文和下划线</li>
                    <li>系统最多支持3级分类结构</li>
                    <li>一级分类不能设置父分类</li>
                    <li>排序数字越小，显示越靠前</li>
                </ul>
            </div>
        </div>
        
        <!-- 层级说明 -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="card-title mb-0">层级说明</h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <strong>一级分类</strong>
                    <p class="text-muted small mb-0">最高层级，如：网络、多媒体教室、专项工作</p>
                </div>
                <div class="mb-3">
                    <strong>二级分类</strong>
                    <p class="text-muted small mb-0">一级分类的子类，如：无法上网、网络卡顿、常规</p>
                </div>
                <div>
                    <strong>三级分类</strong>
                    <p class="text-muted small mb-0">最细分类，如：大屏、电脑、网络优化</p>
                </div>
            </div>
        </div>
        
        <!-- 示例结构 -->
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">示例结构</h6>
            </div>
            <div class="card-body">
                <div class="example-tree">
                    <div class="tree-node">
                        <i class="fas fa-folder text-primary me-2"></i>网络
                        <div class="tree-children">
                            <div class="tree-node">
                                <i class="fas fa-folder text-info me-2"></i>无法上网
                                <div class="tree-children">
                                    <div class="tree-node">
                                        <i class="fas fa-file text-secondary me-2"></i>网络卡顿
                                    </div>
                                    <div class="tree-node">
                                        <i class="fas fa-file text-secondary me-2"></i>没有无线信号
                                    </div>
                                    <div class="tree-node">
                                        <i class="fas fa-file text-secondary me-2"></i>不会拨号
                                    </div>
                                </div>
                            </div>
                            <div class="tree-node">
                                <i class="fas fa-folder text-info me-2"></i>网络调整
                                <div class="tree-children">
                                    <div class="tree-node">
                                        <i class="fas fa-file text-secondary me-2"></i>网络优化
                                    </div>
                                    <div class="tree-node">
                                        <i class="fas fa-file text-secondary me-2"></i>专线开通
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function updateLevel() {
    var parentSelect = document.getElementById('parent_id');
    var levelInput = document.getElementById('level');
    var selectedOption = parentSelect.options[parentSelect.selectedIndex];
    
    if (selectedOption && selectedOption.value) {
        var parentLevel = parseInt(selectedOption.getAttribute('data-level'));
        levelInput.value = parentLevel + 1;
        
        // 检查是否超过3级
        if (levelInput.value > 3) {
            alert('分类层级最多支持3级，请选择其他父分类');
            parentSelect.value = '';
            levelInput.value = 1;
        }
    } else {
        levelInput.value = 1;
    }
}
</script>

<style>
.example-tree {
    font-family: monospace;
    font-size: 14px;
}

.tree-node {
    padding: 5px 0;
}

.tree-children {
    padding-left: 20px;
}
</style>
@endsection