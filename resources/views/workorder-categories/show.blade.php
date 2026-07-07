@extends('layouts.app')

@section('title', '分类详情 - ' . $workorderCategory->name)

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">分类详情</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="{{ route('workorder-categories.edit', $workorderCategory->id) }}" class="btn btn-warning me-2">
            <i class="fas fa-edit"></i> 编辑
        </a>
        <a href="{{ route('workorder-categories.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> 返回列表
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <!-- 基本信息 -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">基本信息</h5>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>分类名称：</strong>{{ $workorderCategory->name }}
                    </div>
                    <div class="col-md-6">
                        <strong>分类编码：</strong>{{ $workorderCategory->code }}
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>分类层级：</strong>
                        <span class="badge bg-{{ $workorderCategory->level == 1 ? 'primary' : ($workorderCategory->level == 2 ? 'info' : 'secondary') }}">
                            {{ $workorderCategory->level_text }}
                        </span>
                    </div>
                    <div class="col-md-6">
                        <strong>排序：</strong>{{ $workorderCategory->sort_order }}
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>状态：</strong>
                        <span class="badge bg-{{ $workorderCategory->status ? 'success' : 'danger' }}">
                            {{ $workorderCategory->status_text }}
                        </span>
                    </div>
                    <div class="col-md-6">
                        <strong>完整路径：</strong>
                        <span class="text-muted">{{ $workorderCategory->full_path }}</span>
                    </div>
                </div>
                
                @if($workorderCategory->description)
                <div class="mb-3">
                    <strong>分类描述：</strong>
                    <div class="mt-2 p-3 bg-light rounded">
                        {{ nl2br($workorderCategory->description) }}
                    </div>
                </div>
                @endif
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>创建时间：</strong>{{ $workorderCategory->created_at->format('Y-m-d H:i:s') }}
                    </div>
                    <div class="col-md-6">
                        <strong>最后更新：</strong>{{ $workorderCategory->updated_at->format('Y-m-d H:i:s') }}
                    </div>
                </div>
                
                @if($workorderCategory->parent)
                <div class="mb-3">
                    <strong>父分类：</strong>
                    <a href="{{ route('workorder-categories.show', $workorderCategory->parent->id) }}" 
                       class="text-decoration-none">
                        {{ $workorderCategory->parent->name }}
                    </a>
                    <span class="badge bg-info ms-2">{{ $workorderCategory->parent->level_text }}</span>
                </div>
                @endif
            </div>
        </div>
        
        <!-- 子分类 -->
        @if($workorderCategory->children()->count() > 0)
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">子分类 ({{ $workorderCategory->children()->count() }})</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    @foreach($workorderCategory->children as $child)
                    <div class="col-md-6 mb-3">
                        <div class="border rounded p-3">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h6 class="mb-0">{{ $child->name }}</h6>
                                <span class="badge bg-{{ $child->status == 'active' ? 'success' : 'danger' }}">
                                    {{ $child->status_text }}
                                </span>
                            </div>
                            <div class="mb-2">
                                <small class="text-muted">编码：{{ $child->code }}</small><br>
                                <small class="text-muted">层级：{{ $child->level_text }}</small>
                            </div>
                            <div class="d-flex justify-content-between">
                                <a href="{{ route('workorder-categories.show', $child->id) }}" 
                                   class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-eye"></i> 查看
                                </a>
                                <a href="{{ route('workorder-categories.edit', $child->id) }}" 
                                   class="btn btn-sm btn-outline-warning">
                                    <i class="fas fa-edit"></i> 编辑
                                </a>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif
        
        <!-- 相关工单 -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">相关工单</h5>
                <span class="badge bg-primary">{{ $workorderCategory->workorders()->count() }}</span>
            </div>
            <div class="card-body">
                @if($workorderCategory->workorders()->count() > 0)
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>工单号</th>
                                <th>标题</th>
                                <th>状态</th>
                                <th>创建人</th>
                                <th>创建时间</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($workorderCategory->workorders()->latest()->limit(10)->get() as $workorder)
                            <tr>
                                <td>
                                    <a href="{{ route('workorders.show', $workorder->id) }}" 
                                       class="text-decoration-none">
                                        {{ $workorder->ticket_no }}
                                    </a>
                                </td>
                                <td>{{ Str::limit($workorder->title, 30) }}</td>
                                <td>
                                    <span class="badge bg-{{ $workorder->status == 'closed' ? 'success' : 'info' }}">
                                        {{ $workorder->status_text }}
                                    </span>
                                </td>
                                <td>{{ $workorder->creator->name }}</td>
                                <td>{{ $workorder->created_at->format('m-d H:i') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="text-center py-4 text-muted">
                    <i class="fas fa-inbox fa-2x mb-2"></i>
                    <p>暂无相关工单</p>
                </div>
                @endif
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <!-- 分类统计 -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="card-title mb-0">分类统计</h6>
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
                
                <div class="row text-center">
                    <div class="col-6 mb-3">
                        <div class="border rounded p-2">
                            <strong>{{ $workorderCategory->workorders()->whereIn('status', ['resolved', 'closed'])->count() }}</strong>
                            <br><small class="text-muted">已完成</small>
                        </div>
                    </div>
                    <div class="col-6 mb-3">
                        <div class="border rounded p-2">
                            <strong>{{ $workorderCategory->children()->count() }}</strong>
                            <br><small class="text-muted">子分类数</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 分类属性 -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="card-title mb-0">分类属性</h6>
            </div>
            <div class="card-body">
                <div class="mb-2">
                    <strong>是否根分类：</strong>
                    @if($workorderCategory->isRoot())
                        <i class="fas fa-check text-success"></i> 是
                    @else
                        <i class="fas fa-times text-danger"></i> 否
                    @endif
                </div>
                <div class="mb-2">
                    <strong>是否叶子节点：</strong>
                    @if($workorderCategory->isLeaf())
                        <i class="fas fa-check text-success"></i> 是
                    @else
                        <i class="fas fa-times text-danger"></i> 否
                    @endif
                </div>
                <div class="mb-2">
                    <strong>分类ID：</strong>{{ $workorderCategory->id }}
                </div>
            </div>
        </div>
        
        <!-- 操作按钮 -->
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">快速操作</h6>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="{{ route('workorder-categories.edit', $workorderCategory->id) }}" 
                       class="btn btn-warning">
                        <i class="fas fa-edit"></i> 编辑分类
                    </a>
                    
                    @if($workorderCategory->children()->count() == 0)
                    <a href="{{ route('workorders.create', ['category_id' => $workorderCategory->id]) }}" 
                       class="btn btn-primary">
                        <i class="fas fa-plus"></i> 创建工单
                    </a>
                    @endif
                    
                </div>
            </div>
        </div>
    </div>
</div>
@endsection