@extends('layouts.app')

@section('title', '删除分类确认')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">删除分类确认</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="{{ route('workorder-categories.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> 返回分类列表
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0">
                    <i class="fas fa-exclamation-triangle"></i> 删除分类确认
                </h5>
            </div>
            <div class="card-body">
                <div class="alert alert-warning">
                    <h6 class="alert-heading">分类信息</h6>
                    <table class="table table-sm">
                        <tr>
                            <th width="120">分类名称：</th>
                            <td>{{ $workorderCategory->name }}</td>
                        </tr>
                        <tr>
                            <th>分类层级：</th>
                            <td>
                                <span class="badge bg-{{ $workorderCategory->level == 1 ? 'primary' : 'info' }}">
                                    {{ $workorderCategory->level_text }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>父分类：</th>
                            <td>
                                @if($workorderCategory->parent)
                                    {{ $workorderCategory->parent->name }}
                                @else
                                    <span class="text-muted">无（顶级分类）</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>工单数量：</th>
                            <td>
                                <span class="badge bg-danger">{{ $workorderCount }}</span> 个工单关联到此分类
                            </td>
                        </tr>
                    </table>
                </div>

                @if($workorderCount > 0)
                <div class="alert alert-danger">
                    <h6 class="alert-heading">
                        <i class="fas fa-exclamation-circle"></i> 警告
                    </h6>
                    <p>此分类关联了 <strong>{{ $workorderCount }}</strong> 个工单，删除前请选择处理方式：</p>
                </div>

                <form method="POST" action="{{ route('workorder-categories.destroy', $workorderCategory->id) }}">
                    @csrf
                    @method('DELETE')
                    
                    <div class="mb-4">
                        <h6>请选择处理方式：</h6>
                        
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="radio" name="delete_action" id="action_move" value="move" checked>
                            <label class="form-check-label" for="action_move">
                                <strong>移动工单到其他分类</strong>
                                <p class="form-text text-muted mb-0">将所有关联工单移动到指定的其他分类，然后删除当前分类</p>
                            </label>
                        </div>
                        
                        <div class="ms-4 mb-4" id="move_options">
                            <label for="target_category_id" class="form-label">选择目标分类：</label>
                            <select class="form-select" name="target_category_id" id="target_category_id" required>
                                <option value="">请选择目标分类</option>
                                @foreach($otherCategories as $cat)
                                    <option value="{{ $cat->id }}">{{ str_repeat('— ', $cat->getLevelAttribute()-1) }}{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="radio" name="delete_action" id="action_disable" value="disable">
                            <label class="form-check-label" for="action_disable">
                                <strong>禁用分类（推荐）</strong>
                                <p class="form-text text-muted mb-0">保留分类和工单关联，但将分类状态设置为禁用，不再允许创建新工单</p>
                            </label>
                        </div>
                        
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="radio" name="delete_action" id="action_force" value="force">
                            <label class="form-check-label" for="action_force">
                                <strong>强制删除</strong>
                                <p class="form-text text-danger mb-0">删除分类，并将所有关联工单的分类设置为空（工单不会删除）</p>
                            </label>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between">
                        <button type="submit" class="btn btn-danger" id="confirm_delete">
                            <i class="fas fa-trash"></i> 确认删除
                        </button>
                        <a href="{{ route('workorder-categories.index') }}" class="btn btn-secondary">
                            <i class="fas fa-times"></i> 取消
                        </a>
                    </div>
                </form>
                @else
                <div class="alert alert-success">
                    <h6 class="alert-heading">
                        <i class="fas fa-check-circle"></i> 可以安全删除
                    </h6>
                    <p>此分类没有关联任何工单，可以安全删除。</p>
                </div>

                <form method="POST" action="{{ route('workorder-categories.destroy', $workorderCategory->id) }}">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="delete_action" value="direct">
                    
                    <div class="d-flex justify-content-between">
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash"></i> 确认删除
                        </button>
                        <a href="{{ route('workorder-categories.index') }}" class="btn btn-secondary">
                            <i class="fas fa-times"></i> 取消
                        </a>
                    </div>
                </form>
                @endif
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">关联工单列表</h6>
            </div>
            <div class="card-body">
                @if($workorderCount > 0)
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>工单编号</th>
                                    <th>标题</th>
                                    <th>状态</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($workorderCategory->workorders()->take(10)->get() as $workorder)
                                <tr>
                                    <td>{{ $workorder->ticket_no }}</td>
                                    <td>
                                        <a href="{{ route('workorders.show', $workorder->id) }}" target="_blank">
                                            {{ Str::limit($workorder->title, 30) }}
                                        </a>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $workorder->status == 'resolved' ? 'success' : ($workorder->status == 'processing' ? 'warning' : 'info') }}">
                                            {{ $workorder->status_text }}
                                        </span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if($workorderCount > 10)
                    <div class="text-center">
                        <small class="text-muted">显示前10个工单，共{{ $workorderCount }}个</small>
                    </div>
                    @endif
                @else
                    <p class="text-muted text-center">无关联工单</p>
                @endif
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="mb-0">操作说明</h6>
            </div>
            <div class="card-body">
                <ul class="list-unstyled mb-0">
                    <li class="mb-2">
                        <i class="fas fa-info-circle text-info"></i>
                        <strong>移动工单：</strong>工单将完整保留，只是更换分类
                    </li>
                    <li class="mb-2">
                        <i class="fas fa-info-circle text-warning"></i>
                        <strong>禁用分类：</strong>最安全的方式，保留历史数据
                    </li>
                    <li class="mb-2">
                        <i class="fas fa-info-circle text-danger"></i>
                        <strong>强制删除：</strong>工单将失去分类，可能影响统计
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const actionRadios = document.querySelectorAll('input[name="delete_action"]');
    const moveOptions = document.getElementById('move_options');
    const targetCategorySelect = document.getElementById('target_category_id');
    const confirmButton = document.getElementById('confirm_delete');
    
    function toggleMoveOptions() {
        const selectedAction = document.querySelector('input[name="delete_action"]:checked').value;
        if (selectedAction === 'move') {
            moveOptions.style.display = 'block';
            targetCategorySelect.setAttribute('required', 'required');
        } else {
            moveOptions.style.display = 'none';
            targetCategorySelect.removeAttribute('required');
        }
    }
    
    function validateForm() {
        const selectedAction = document.querySelector('input[name="delete_action"]:checked').value;
        if (selectedAction === 'move' && !targetCategorySelect.value) {
            confirmButton.disabled = true;
            confirmButton.innerHTML = '<i class="fas fa-exclamation-triangle"></i> 请选择目标分类';
            confirmButton.classList.remove('btn-danger');
            confirmButton.classList.add('btn-warning');
        } else {
            confirmButton.disabled = false;
            confirmButton.innerHTML = '<i class="fas fa-trash"></i> 确认删除';
            confirmButton.classList.remove('btn-warning');
            confirmButton.classList.add('btn-danger');
        }
    }
    
    actionRadios.forEach(function(radio) {
        radio.addEventListener('change', function() {
            toggleMoveOptions();
            validateForm();
        });
    });
    
    targetCategorySelect.addEventListener('change', validateForm);
    
    // 初始化
    toggleMoveOptions();
    validateForm();
});
</script>
@endsection