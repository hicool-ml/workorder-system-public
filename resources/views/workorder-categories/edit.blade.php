@extends('layouts.app')

@section('title', '编辑工单分类 - ' . $workorderCategory->name)

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">编辑工单分类</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="{{ route('workorder-categories.show', $workorderCategory->id) }}" class="btn btn-secondary me-2">
            <i class="fas fa-eye"></i> 查看详情
        </a>
        <a href="{{ route('workorder-categories.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> 返回列表
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">编辑分类信息</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('workorder-categories.update', $workorderCategory->id) }}">
                    @csrf
                    @method('PUT')
                    
                    <!-- 层级设置 -->
                    <h6 class="mb-3">层级设置</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="parent_id" class="form-label">父分类</label>
                            <select class="form-select" id="parent_id" name="parent_id" onchange="updateLevel()">
                                <option value="">无（一级分类）</option>
                                @foreach($parentCategories as $category)
                                <option value="{{ $category->id }}"
                                        {{ old('parent_id', $workorderCategory->parent_id) == $category->id ? 'selected' : '' }}
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
                                   value="{{ old('level', $workorderCategory->level) }}" readonly>
                            <div class="form-text">系统自动计算</div>
                        </div>
                        <div class="col-md-3">
                            <label for="sort_order" class="form-label">排序</label>
                            <input type="number" class="form-control" id="sort_order" name="sort_order"
                                   value="{{ old('sort_order', $workorderCategory->sort_order) }}" min="0">
                            <div class="form-text">数字越小排序越靠前</div>
                        </div>
                    </div>
                    
                    <!-- 基本信息 -->
                    <h6 class="mb-3">基本信息</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="name" class="form-label">分类名称 <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name"
                                   value="{{ old('name', $workorderCategory->name) }}" required maxlength="100"
                                   placeholder="请输入分类名称">
                        </div>
                        <div class="col-md-6">
                            <label for="code" class="form-label">分类编码 <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="code" name="code"
                                   value="{{ old('code', $workorderCategory->code) }}" required maxlength="50"
                                   placeholder="请输入分类编码，如：NETWORK_ISSUE">
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="description" class="form-label">分类描述</label>
                        <textarea class="form-control" id="description" name="description" rows="4"
                                  placeholder="请输入分类描述">{{ old('description', $workorderCategory->description) }}</textarea>
                        <div class="form-text">详细描述该分类的用途和范围</div>
                    </div>
                    
                    <!-- 状态设置 -->
                    <h6 class="mb-3">状态设置</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="status" class="form-label">状态 <span class="text-danger">*</span></label>
                            <select class="form-select" id="status" name="status" required>
                                @foreach(\App\Models\WorkorderCategory::getStatusOptions() as $key => $value)
                                <option value="{{ $key }}" {{ old('status', $workorderCategory->status ? 'active' : 'inactive') == $key ? 'selected' : '' }}>
                                    {{ $value }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    
                    <!-- 提交按钮 -->
                    <div class="d-flex justify-content-end">
                        <a href="{{ route('workorder-categories.show', $workorderCategory->id) }}" class="btn btn-secondary me-2">
                            <i class="fas fa-times"></i> 取消
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> 保存更改
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <!-- 分类信息 -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="card-title mb-0">分类信息</h6>
            </div>
            <div class="card-body">
                <div class="mb-2">
                    <strong>分类ID：</strong>{{ $workorderCategory->id }}
                </div>
                <div class="mb-2">
                    <strong>当前层级：</strong>
                    <span class="badge bg-{{ $workorderCategory->level == 1 ? 'primary' : ($workorderCategory->level == 2 ? 'info' : 'secondary') }}">
                        {{ $workorderCategory->level_text }}
                    </span>
                </div>
                <div class="mb-2">
                    <strong>当前状态：</strong>
                    <span class="badge bg-{{ $workorderCategory->status ? 'success' : 'danger' }}">
                        {{ $workorderCategory->status_text }}
                    </span>
                </div>
                <div class="mb-2">
                    <strong>创建时间：</strong>{{ $workorderCategory->created_at ? $workorderCategory->created_at->format('Y-m-d H:i:s') : '-' }}
                </div>
                <div class="mb-2">
                    <strong>最后更新：</strong>{{ $workorderCategory->updated_at ? $workorderCategory->updated_at->format('Y-m-d H:i:s') : '-' }}
                </div>
                @if($workorderCategory->parent)
                <div class="mb-2">
                    <strong>父分类：</strong>{{ $workorderCategory->parent->name }}
                </div>
                @endif
            </div>
        </div>
        
        <!-- 子分类统计 -->
        @if($workorderCategory->children()->count() > 0)
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="card-title mb-0">子分类</h6>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    @foreach($workorderCategory->children as $child)
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <strong>{{ $child->name }}</strong>
                            <br><small class="text-muted">{{ $child->code }}</small>
                        </div>
                        <span class="badge bg-{{ $child->status ? 'success' : 'danger' }}">
                            {{ $child->status_text }}
                        </span>
                    </li>
                    @endforeach
                </ul>
            </div>
        </div>
        @endif
        
        <!-- 工单统计 -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="card-title mb-0">工单统计</h6>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-6 mb-3">
                        <div class="border rounded p-2">
                            <strong>{{ $workorderCategory->workorders()->count() }}</strong>
                            <br><small class="text-muted">总工单数</small>
                        </div>
                    </div>
                    <div class="col-6 mb-3">
                        <div class="border rounded p-2">
                            <strong>{{ $workorderCategory->workorders()->whereIn('status', ['pending', 'assigned', 'processing'])->count() }}</strong>
                            <br><small class="text-muted">待处理</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 编辑提示 -->
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">编辑提示</h6>
            </div>
            <div class="card-body">
                <ul class="mb-0">
                    <li>修改父分类会自动更新当前分类的层级</li>
                    <li>不能将分类设置为自己的子分类或后代分类</li>
                    <li>系统最多支持3级分类结构</li>
                    <li>有子分类或关联工单的分类不能删除</li>
                    <li>禁用分类不会影响已有工单，但新建工单无法选择</li>
                </ul>
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
    var currentLevel = {{ $workorderCategory->level }};
    var currentId = {{ $workorderCategory->id }};
    
    if (selectedOption && selectedOption.value) {
        var parentLevel = parseInt(selectedOption.getAttribute('data-level'));
        levelInput.value = parentLevel + 1;
        
        // 检查是否超过3级
        if (levelInput.value > 3) {
            alert('分类层级最多支持3级，请选择其他父分类');
            parentSelect.value = '{{ $workorderCategory->parent_id }}';
            levelInput.value = currentLevel;
        }
        
        // 检查是否选择自己为父分类
        if (selectedOption.value == currentId) {
            alert('不能将分类设置为自己的父分类');
            parentSelect.value = '{{ $workorderCategory->parent_id }}';
            levelInput.value = currentLevel;
        }
    } else {
        levelInput.value = 1;
    }
}
</script>
@endsection