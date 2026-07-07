@extends('layouts.app')

@section('title', '工单分类管理')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">工单分类管理</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="{{ route('workorder-categories.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> 新建分类
        </a>
    </div>
</div>

<!-- 搜索筛选 -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('workorder-categories.index') }}">
            <div class="row g-3">
                <div class="col-md-3">
                    <label for="keyword" class="form-label">关键词</label>
                    <input type="text" class="form-control" id="keyword" name="keyword" 
                           value="{{ request('keyword') }}" placeholder="分类名称、编码">
                </div>
                <div class="col-md-2">
                    <label for="level" class="form-label">层级</label>
                    <select class="form-select" id="level" name="level">
                        <option value="">全部层级</option>
                        <option value="1" {{ request('level') == '1' ? 'selected' : '' }}>一级分类</option>
                        <option value="2" {{ request('level') == '2' ? 'selected' : '' }}>二级分类</option>
                        <option value="3" {{ request('level') == '3' ? 'selected' : '' }}>三级分类</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="status" class="form-label">状态</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">全部状态</option>
                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>启用</option>
                        <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>禁用</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="fas fa-search"></i> 搜索
                    </button>
                    <a href="{{ route('workorder-categories.index') }}" class="btn btn-secondary">
                        <i class="fas fa-redo"></i> 重置
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- 分类列表 -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>分类名称</th>
                        <th>编码</th>
                        <th>层级</th>
                        <th>父分类</th>
                        <th>状态</th>
                        <th>排序</th>
                        <th>创建时间</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($categories as $category)
                    <tr>
                        <td>
                            <span style="padding-left: {{ ($category->level - 1) * 20 }}px;">
                                {{ $category->name }}
                            </span>
                            @if($category->isLeaf())
                            <i class="fas fa-leaf text-success ms-1" title="叶子节点"></i>
                            @endif
                        </td>
                        <td>{{ $category->code }}</td>
                        <td>
                            <span class="badge bg-{{ $category->level == 1 ? 'primary' : ($category->level == 2 ? 'info' : 'secondary') }}">
                                {{ $category->level_text }}
                            </span>
                        </td>
                        <td>
                            @if($category->parent)
                                {{ $category->parent->name }}
                            @else
                                <span class="text-muted">无</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-{{ $category->status ? 'success' : 'danger' }}">
                                {{ $category->status_text }}
                            </span>
                        </td>
                        <td>{{ $category->sort_order }}</td>
                        <td>{{ $category->created_at ? $category->created_at->format('Y-m-d H:i') : 'N/A' }}</td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('workorder-categories.show', $category->id) }}" 
                                   class="btn btn-outline-primary" title="查看">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('workorder-categories.edit', $category->id) }}" 
                                   class="btn btn-outline-warning" title="编辑">
                                    <i class="fas fa-edit"></i>
                                </a>
                                
                                @if($category->children()->count() == 0 && $category->workorders()->count() == 0)
                                <form method="POST" action="{{ route('workorder-categories.destroy', $category->id) }}" 
                                      class="d-inline" onsubmit="return confirm('确认删除此分类吗？')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger" title="删除">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-4">
                            <i class="fas fa-folder-open fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">暂无分类</h5>
                            <p class="text-muted">
                                <a href="{{ route('workorder-categories.create') }}" class="btn btn-primary">
                                    创建第一个分类
                                </a>
                            </p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- 分页 -->
        <div class="d-flex justify-content-between align-items-center mt-3">
            <div class="text-muted">
                显示 {{ $categories->firstItem() }} - {{ $categories->lastItem() }} 
                共 {{ $categories->total() }} 条记录
            </div>
            {{ $categories->appends(request()->query())->links() }}
        </div>
    </div>
</div>

@endsection