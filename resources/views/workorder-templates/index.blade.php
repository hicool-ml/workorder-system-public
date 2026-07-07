@extends('layouts.app')

@section('title', '工单模板管理')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-file-alt"></i> 工单模板管理
                    </h3>
                    
                    <div class="card-tools">
                        <a href="{{ route('workorder-templates.create') }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus"></i> 新建模板
                        </a>
                    </div>
                </div>
                
                <div class="card-body">
                    <!-- 搜索表单 -->
                    <form method="GET" action="{{ route('workorder-templates.index') }}" class="mb-3">
                        <div class="row">
                            <div class="col-md-4">
                                <input type="text" name="keyword" class="form-control" placeholder="搜索模板名称或描述" value="{{ request('keyword') }}">
                            </div>
                            <div class="col-md-3">
                                <select name="category_id" class="form-control">
                                    <option value="">全部分类</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                                            {{ $category->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> 搜索
                                </button>
                                <a href="{{ route('workorder-templates.index') }}" class="btn btn-secondary">
                                    <i class="fas fa-redo"></i> 重置
                                </a>
                            </div>
                        </div>
                    </form>
                    
                    <!-- 模板列表 -->
                    @if($templates->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>模板名称</th>
                                        <th>分类</th>
                                        <th>优先级</th>
                                        <th>创建人</th>
                                        <th>状态</th>
                                        <th>创建时间</th>
                                        <th>操作</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($templates as $template)
                                        <tr>
                                            <td>
                                                <strong>{{ $template->name }}</strong>
                                                <br>
                                                <small class="text-muted">{{ Str::limit($template->description, 100) }}</small>
                                            </td>
                                            <td>
                                                @if($template->category)
                                                    <span class="badge badge-info">{{ $template->category->name }}</span>
                                                @else
                                                    <span class="text-muted">未设置</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge badge-{{ $template->priority == 'high' ? 'danger' : ($template->priority == 'medium' ? 'warning' : 'success') }}">
                                                    {{ $template->priority_text }}
                                                </span>
                                            </td>
                                            <td>{{ $template->creator->name }}</td>
                                            <td>
                                                @if($template->is_active)
                                                    <span class="badge badge-success">启用</span>
                                                @else
                                                    <span class="badge badge-secondary">禁用</span>
                                                @endif
                                            </td>
                                            <td>{{ $template->created_at->format('Y-m-d H:i') }}</td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="{{ route('workorder-templates.createFromTemplate', $template->id) }}"
                                                       class="btn btn-xs btn-primary" title="使用模板创建工单">
                                                        <i class="fas fa-plus"></i>
                                                    </a>
                                                    
                                                    <a href="{{ route('workorder-templates.edit', $template->id) }}" 
                                                       class="btn btn-xs btn-info" title="编辑模板">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    
                                                    <button type="button" class="btn btn-xs btn-warning" 
                                                            onclick="toggleStatus({{ $template->id }})" title="切换状态">
                                                        <i class="fas fa-power-off"></i>
                                                    </button>
                                                    
                                                    <button type="button" class="btn btn-xs btn-danger" 
                                                            onclick="deleteTemplate({{ $template->id }})" title="删除模板">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="d-flex justify-content-center">
                            {{ $templates->links() }}
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                            <h5>暂无工单模板</h5>
                            <p class="text-muted">还没有创建任何工单模板</p>
                            <a href="{{ route('workorder-templates.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus"></i> 创建第一个模板
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// 切换模板状态
function toggleStatus(templateId) {
    if (confirm('确定要切换此模板的状态吗？')) {
        axios.post(`/workorder-templates/${templateId}/toggle-status`)
            .then(response => {
                if (response.data.success) {
                    location.reload();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('操作失败');
            });
    }
}

// 删除模板
function deleteTemplate(templateId) {
    if (confirm('确定要删除此模板吗？此操作不可恢复。')) {
        axios.delete(`/workorder-templates/${templateId}`)
            .then(response => {
                location.reload();
            })
            .catch(error => {
                console.error('Error:', error);
                alert('删除失败');
            });
    }
}
</script>
@endpush