@extends(\$useEdgeLayout ? 'layouts.edge-compatible' : 'layouts.app')

@section('title', '工单列表')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">工单列表</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        @if(!\$useEdgeLayout)
        <a href="/workorders/create" class="btn btn-primary">
            <i class="fas fa-plus"></i> 创建工单
        </a>
        @else
        <a href="/workorders/create" class="btn btn-primary">
            <i class="fas fa-plus"></i> 创建工单
        </a>
        @endif
    </div>
</div>

<!-- 简化的搜索表单 -->
<div class="card mb-4">
    <div class="card-header">
        <h6 class="mb-0">搜索筛选</h6>
    </div>
    <div class="card-body">
        <form method="GET" action="/workorders" id="searchForm">
            <div class="row g-3">
                <div class="col-md-3">
                    <label for="keyword" class="form-label">关键词</label>
                    <input type="text" class="form-control" id="keyword" name="keyword"
                           value="{{ request('keyword') }}" placeholder="工单号、描述、联系人">
                </div>
                <div class="col-md-2">
                    <label for="status" class="form-label">状态</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">请选择</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}">待处理</option>
                        <option value="assigned" {{ request('status') == 'assigned' ? 'selected' : '' }}">已分配</option>
                        <option value="processing" {{ request('status') == 'processing' ? 'selected' : '' }}">处理中</option>
                        <option value="resolved" {{ request('status') == 'resolved' ? 'selected' : '' }}">已解决</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="priority" class="form-label">优先级</label>
                    <select class="form-select" id="priority" name="priority">
                        <option value="">全部优先级</option>
                        <option value="high" {{ request('priority') == 'high' ? 'selected' : '' }}">高</option>
                        <option value="medium" {{ request('priority') == 'medium' ? 'selected' : '' }}">中</option>
                        <option value="low" {{ request('priority') == 'low' ? 'selected' : '' }}">低</option>
                    </select>
                </div>
            </div>
            <div class="row g-3 mt-3">
                <div class="col-md-8">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="fas fa-search"></i> 搜索
                    </button>
                    <a href="/workorders" class="btn btn-secondary">
                        <i class="fas fa-redo"></i> 重置
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- 工单列表 -->
<div class="card">
    <div class="card-body">
        @forelse(\$workorders as \$workorder)
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>工单号</th>
                        <th>地址</th>
                        <th>类型+问题描述</th>
                        <th>报修人</th>
                        <th>联系方式</th>
                        <th>优先级</th>
                        <th>状态</th>
                        <th>处理人</th>
                        <th>创建历时</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach(\$workorders as \$workorder)
                    <tr>
                        <td>
                            <a href="/workorders/{{ \$workorder->id }}"
                               class="text-decoration-none">
                                {{ \$workorder->ticket_no }}
                            </a>
                            @if(\$workorder->is_emergency)
                            <i class="fas fa-exclamation-triangle text-danger" title="紧急工单"></i>
                            @endif
                        </td>
                        <td>
                            <small>
                                @if(\$workorder->campus)
                                    {{ $workorder->campus }}
                                @endif
                                @if(\$workorder->building)
                                    {{ \$workorder->building }}
                                @endif
                            </small>
                        </td>
                        <td>
                            @if(\$workorder->category)
                                <span class="badge bg-secondary me-1">{{ \$workorder->category->name }}</span>
                            @endif
                            <a href="/workorders/{{ \$workorder->id }}"
                               class="text-decoration-none">
                                {{ Str::limit(\$workorder->description, 30) }}
                            </a>
                        </td>
                        <td>{{ \$workorder->contact_name }}</td>
                        <td>{{ \$workorder->contact_phone }}</td>
                        <td>
                            <span class="badge priority-{{ \$workorder->priority }}">
                                {{ \$workorder->priority_text }}
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-{{ \$workorder->status == 'resolved' ? 'success' : (\$workorder->status == 'pending' ? 'warning' : 'info') }}">
                                {{ \$workorder->status_text }}
                            </span>
                        </td>
                        <td>{{ \$workorder->assignee_name }}</td>
                        <td>
                            <small>
                                {{ \$workorder->created_duration }}
                            </small>
                        </td>
                        <td>
                            @if(!\$useEdgeLayout)
                            <div class="btn-group btn-group-sm">
                                <a href="/workorders/{{ \$workorder->id }}"
                                   class="btn btn-outline-primary" title="查看">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </div>
                            @else
                            <a href="/workorders/{{ \$workorder->id }}"
                                   class="btn btn-outline-primary" title="查看">
                                    <i class="fas fa-eye"></i>
                                </a>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @empty
        <div class="text-center py-4">
            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
            <h5 class="text-muted">暂无工单</h5>
            <p class="text-muted">
                <a href="/workorders/create" class="btn btn-primary">
                    创建第一个工单
                </a>
            </p>
        </div>
        @endforelse
    </div>
</div>

<!-- 分页 -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-center mt-3">
    <div class="text-muted mb-2 mb-md-0">
        显示 {{ \$workorders->firstItem() }} - {{ \$workorders->lastItem() }}
        共 {{ \$workorders->total() }} 条记录
    </div>
    <div class="d-flex justify-content-center">
        {{ \$workorders->appends(request()->query())->links() }}
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // 搜索表单不需要JavaScript处理，让浏览器自动提交
    // $('#searchForm').submit(function(e) {
    //     // 移除协议强制转换，让浏览器自动处理
    // });
});
</script>
@endpush
