@extends('layouts.app')

@section('title', '仪表板')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">仪表板</h1>
</div>

<div class="row">
    <!-- 统计卡片 -->
    <div class="col-md-3 mb-4">
        <div class="card text-center">
            <div class="card-body">
                <i class="fas fa-list fa-2x text-primary mb-3"></i>
                <h5 class="card-title">总工单数</h5>
                <p class="card-text display-4">{{ App\Models\Workorder::count() }}</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-4">
        <div class="card text-center">
            <div class="card-body">
                <i class="fas fa-clock fa-2x text-warning mb-3"></i>
                <h5 class="card-title">待处理</h5>
                <p class="card-text display-4">
                    {{ App\Models\Workorder::whereIn('status', ['pending', 'assigned', 'processing'])->count() }}
                </p>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-4">
        <div class="card text-center">
            <div class="card-body">
                <i class="fas fa-check-circle fa-2x text-success mb-3"></i>
                <h5 class="card-title">已完成</h5>
                <p class="card-text display-4">
                    {{ App\Models\Workorder::whereIn('status', ['resolved', 'closed'])->count() }}
                </p>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-4">
        <div class="card text-center">
            <div class="card-body">
                <i class="fas fa-users fa-2x text-info mb-3"></i>
                <h5 class="card-title">总用户数</h5>
                <p class="card-text display-4">{{ App\Models\User::count() }}</p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- 最近工单 -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">最近工单</h5>
            </div>
            <div class="card-body">
                @if($recentWorkorders = App\Models\Workorder::with(['creator', 'assignee', 'type'])->latest()->limit(5)->get())
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>工单号</th>
                                <th>标题</th>
                                <th>状态</th>
                                <th>处理人</th>
                                <th>创建时间</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentWorkorders as $workorder)
                            <tr>
                                <td>
                                    <a href="{{ \App\Helpers\UrlHelper::relative_url('/workorders/' . $workorder->id) }}"
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
                                <td>
                                    {{ $workorder->assignee?->name ?: '未分配' }}
                                </td>
                                <td>{{ $workorder->created_at->format('m-d H:i') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="text-center py-4 text-muted">
                    <i class="fas fa-inbox fa-2x mb-2"></i>
                    <p>暂无工单</p>
                </div>
                @endif
            </div>
        </div>
    </div>
    
    <!-- 快速操作 -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">快速操作</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    @if(auth()->user()->canCreateWorkorders())
                    <a href="{{ \App\Helpers\UrlHelper::relative_url('/workorders/create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>创建工单
                    </a>
                    @endif
                    
                    @if(auth()->user()->canAssignWorkorders())
                    <a href="{{ route('workorders.index', ['status' => 'pending']) }}" class="btn btn-warning">
                        <i class="fas fa-user-plus me-2"></i>待分配工单
                    </a>
                    @endif
                    
                    @if(auth()->user()->canHandleWorkorders())
                    <a href="{{ route('workorders.index', ['assignee_id' => auth()->id()]) }}" class="btn btn-info">
                        <i class="fas fa-tasks me-2"></i>我的工单
                    </a>
                    @endif
                    
                    @if(auth()->user()->canViewReports())
                    <a href="{{ route('reports.index') }}" class="btn btn-success">
                        <i class="fas fa-chart-bar me-2"></i>统计报表
                    </a>
                    @endif
                </div>
            </div>
        </div>
        
        <!-- 系统信息 -->
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="card-title mb-0">系统信息</h5>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-6 mb-3">
                        <div class="border rounded p-2">
                            <strong>{{ App\Models\Department::count() }}</strong>
                            <br><small class="text-muted">部门数</small>
                        </div>
                    </div>
                    <div class="col-6 mb-3">
                        <div class="border rounded p-2">
                            <strong>{{ App\Models\WorkorderType::count() }}</strong>
                            <br><small class="text-muted">工单类型</small>
                        </div>
                    </div>
                </div>
                <div class="text-center mt-3">
                    <small class="text-muted">
                        系统版本：v1.0.0<br>
                        Laravel版本：{{ app()->version() }}
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection