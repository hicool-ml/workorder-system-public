@extends('layouts.app')

@section('title', '工单列表')

@include('workorders._permission_checks')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">工单列表</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="{{ \App\Helpers\UrlHelper::relative_url('/workorders/create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> 创建工单
        </a>
    </div>
</div>

<!-- 搜索筛选 -->
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0">搜索筛选</h6>
        <button type="button" class="btn btn-sm btn-outline-secondary" id="toggleAdvancedSearch">
            <i class="fas fa-chevron-down"></i> 高级筛选
        </button>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ \App\Helpers\UrlHelper::relative_url('/workorders') }}" id="searchForm">
            <div class="row g-3">
                <div class="col-md-3">
                    <label for="keyword" class="form-label">关键词</label>
                    <input type="text" class="form-control" id="keyword" name="keyword"
                           value="{{ request('keyword') }}" placeholder="工单号、描述、联系人" autocomplete="off">
                </div>
                <div class="col-md-2">
                    <label for="status" class="form-label">状态</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">请选择</option>
                        <option value="all" {{ request('status') == 'all' ? 'selected' : '' }}>全部</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>待处理</option>
                        <option value="assigned" {{ request('status') == 'assigned' ? 'selected' : '' }}">已分配</option>
                        <option value="processing" {{ request('status') == 'processing' ? 'selected' : '' }}">处理中</option>
                        <option value="resolved" {{ request('status') == 'resolved' ? 'selected' : '' }}">已解决</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="priority" class="form-label">优先级</label>
                    <select class="form-select" id="priority" name="priority">
                        <option value="">全部优先级</option>
                        <option value="high" {{ request('priority') == 'high' ? 'selected' : '' }}>高</option>
                        <option value="medium" {{ request('priority') == 'medium' ? 'selected' : '' }}>中</option>
                        <option value="low" {{ request('priority') == 'low' ? 'selected' : '' }}>低</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="category_id" class="form-label">工单分类</label>
                    <select class="form-select" id="category_id" name="category_id">
                        <option value="">全部分类</option>
                        @foreach($categories['main'] as $category)
                        <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                            {{ $category->name }}
                        </option>
                        @endforeach
                    </select>
                </div>
                @if(auth()->user()->canAssignWorkorders())
                <div class="col-md-2">
                    <label for="filter_assignee_id" class="form-label">处理 </label>
                    <select class="form-select" id="filter_assignee_id" name="assignee_id">
                        <option value="">全部处理人</option>
                        @foreach($engineers as $engineer)
                        <option value="{{ $engineer->id }}" {{ request('assignee_id') == $engineer->id ? 'selected' : '' }}>
                            {{ $engineer->name }}
                        </option>
                        @endforeach
                    </select>
                </div>
                @endif
            </div>
            
            <!-- 高级筛选选项 -->
            <div id="advancedSearch" style="display: none;">
                <hr>
                <div class="row g-3">
                    <div class="col-md-2">
                        <label for="date_from" class="form-label">开始日期</label>
                        <input type="date" class="form-control" id="date_from" name="date_from"
                               value="{{ request('date_from') }}" autocomplete="off">
                    </div>
                    <div class="col-md-2">
                        <label for="date_to" class="form-label">结束日期</label>
                        <input type="date" class="form-control" id="date_to" name="date_to"
                               value="{{ request('date_to') }}" autocomplete="off">
                    </div>
                    <div class="col-md-2">
                        <label for="campus" class="form-label">校区</label>
                        <select class="form-select" id="campus" name="campus">
                            <option value="">全部校区</option>
                            <option value="old_campus" {{ request('campus') == 'old_campus' ? 'selected' : '' }}>老校区</option>
                            <option value="new_campus" {{ request('campus') == 'new_campus' ? 'selected' : '' }}>新校区</option>
                            <option value="asean_campus" {{ request('campus') == 'asean_campus' ? 'selected' : '' }}>东盟校区</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="source" class="form-label">来源</label>
                        <select class="form-select" id="source" name="source">
                            <option value="">全部来源</option>
                            <option value="phone" {{ request('source') == 'phone' ? 'selected' : '' }}>电话</option>
                            <option value="web" {{ request('source') == 'web' ? 'selected' : '' }}>网络</option>
                            <option value="scene" {{ request('source') == 'scene' ? 'selected' : '' }}>现场</option>
                            <option value="email" {{ request('source') == 'email' ? 'selected' : '' }}>邮件</option>
                            <option value="other" {{ request('source') == 'other' ? 'selected' : '' }}>其他</option>
                            <option value="custom" {{ request('source') == 'custom' ? 'selected' : '' }}>自定义渠道</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="is_emergency" class="form-label">特殊标记</label>
                        <select class="form-select" id="is_emergency" name="is_emergency">
                            <option value="">全部</option>
                            <option value="1" {{ request('is_emergency') == '1' ? 'selected' : '' }}>紧急工单</option>
                            <option value="0" {{ request('is_emergency') == '0' ? 'selected' : '' }}>普通工单</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="phone_assisted" class="form-label">处理方式</label>
                        <select class="form-select" id="phone_assisted" name="phone_assisted">
                            <option value="">全部</option>
                            <option value="1" {{ request('phone_assisted') == '1' ? 'selected' : '' }}>电话协助</option>
                            <option value="0" {{ request('phone_assisted') == '0' ? 'selected' : '' }}>现场处理</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="row g-3 mt-3">
                <div class="col-md-8">
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="checkbox" id="show_closed" name="show_closed"
                               value="1" {{ request('show_closed') ? 'checked' : '' }} autocomplete="off">
                        <label class="form-check-label" for="show_closed">
                            显示已解决
                        </label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="checkbox" id="show_overdue" name="show_overdue"
                               value="1" {{ request('show_overdue') ? 'checked' : '' }} autocomplete="off">
                        <label class="form-check-label" for="show_overdue">
                            仅显示超时
                        </label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="checkbox" id="show_emergency" name="show_emergency"
                               value="1" {{ request('show_emergency') ? 'checked' : '' }} autocomplete="off">
                        <label class="form-check-label" for="show_emergency">
                            仅显示紧急
                        </label>
                    </div>
                </div>
                <div class="col-md-4 text-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="fas fa-search"></i> 搜索
                    </button>
                    <a href="{{ \App\Helpers\UrlHelper::relative_url('/workorders') }}" class="btn btn-secondary me-2">
                        <i class="fas fa-redo"></i> 重置
                    </a>
                    <div class="btn-group">
                        <button type="button" class="btn btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown">
                            <i class="fas fa-download"></i> 导出
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#" onclick="exportWorkorders('7days')">最近7天</a></li>
                            <li><a class="dropdown-item" href="#" onclick="exportWorkorders('30days')">最近30天</a></li>
                            <li><a class="dropdown-item" href="#" onclick="exportWorkorders('90days')">最近90天</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="#" onclick="showDateRangeModal()">自定义时间范围</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- 工单列表 -->
<div class="card">
    <div class="card-body">
        @if(auth()->user()->canHandleWorkorders())
        <!-- 批量操作栏 -->
        <div class="row mb-3" id="batchOperations" style="display: none;">
            <div class="col-12">
                <div class="alert alert-info d-flex flex-column flex-md-row justify-content-between align-items-center">
                    <div class="mb-2 mb-md-0">
                        <span>已选择 <span id="selectedCount">0</span> 个工单</span>
                    </div>
                    <div class="btn-group flex-wrap">
                        <button type="button" class="btn btn-sm btn-success mb-1 mb-md-0" id="batchAssignBtn">
                            <i class="fas fa-user-plus"></i> 批量分配
                        </button>
                        <button type="button" class="btn btn-sm btn-warning mb-1 mb-md-0" id="batchStartBtn">
                            <i class="fas fa-play"></i> 批量开始
                        </button>
                        <button type="button" class="btn btn-sm btn-info mb-1 mb-md-0" id="batchResolveBtn">
                            <i class="fas fa-check"></i> 批量解决
                        </button>
                        <button type="button" class="btn btn-sm btn-danger mb-1 mb-md-0" id="batchCloseBtn">
                            <i class="fas fa-times"></i> 批量关闭
                        </button>
                        <button type="button" class="btn btn-sm btn-secondary mb-1 mb-md-0" id="clearSelectionBtn">
                            <i class="fas fa-times-circle"></i> 清除选择
                        </button>
                    </div>
                </div>
            </div>
        </div>
        @endif
        
        <!-- 移动端卡片视图 -->
        <div class="d-md-none">
            @forelse($workorders as $workorder)
            <div class="card mb-3 workorder-card" data-workorder-id="{{ $workorder->id }}" data-status="{{ $workorder->status }}" data-assignee="{{ $workorder->assignee_id }}">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            @if(auth()->user()->canHandleWorkorders())
                            <div class="checkbox-wrapper mb-2">
                                <input type="checkbox" class="form-check-input workorder-checkbox" value="{{ $workorder->id }}" id="mobile-check-{{ $workorder->id }}" name="workorder_checkbox[]" autocomplete="off" onclick="handleWorkorderClick(this)">
                            </div>
                            @endif
                            <h6 class="card-title mb-1">
                                <div class="workorder-description">
                                    <a href="{{ \App\Helpers\UrlHelper::relative_url('/workorders/' . $workorder->id) }}" class="text-decoration-none">
                                        {{ Str::limit($workorder->description, 30) }}
                                    </a>
                                </div>
                                @if($workorder->is_emergency)
                                <i class="fas fa-exclamation-triangle text-danger ms-1" title="紧急工单"></i>
                                @endif
                                <!-- 协作邀请图标提示 -->
                                @if($workorder->collaborations()->where('collaborator_id', auth()->id())->where('status', 'pending')->exists())
                                <i class="fas fa-handshake text-info ms-1" title="您有协作邀请待处理"></i>
                                @endif
                            </h6>
                            <div class="mb-2">
                                <span class="badge priority-{{ $workorder->priority }} me-1">
                                    {{ $workorder->priority_text }}
                                </span>
                                @if($workorder->getOverdueLevel() == 'normal')
                                    <div class="d-inline-block" style="background-color: rgba(40, 167, 69, 0.2) !important; border: 1px solid rgba(40, 167, 69, 0.3); border-radius: 4px; padding: 2px 6px;">
                                @elseif($workorder->getOverdueLevel() == 'warning')
                                    <div class="d-inline-block" style="background-color: rgba(255, 193, 7, 0.2) !important; border: 1px solid rgba(255, 193, 7, 0.3); border-radius: 4px; padding: 2px 6px;">
                                @elseif($workorder->getOverdueLevel() == 'danger')
                                    <div class="d-inline-block" style="background-color: rgba(253, 126, 20, 0.2) !important; border: 1px solid rgba(253, 126, 20, 0.3); border-radius: 4px; padding: 2px 6px;">
                                @elseif($workorder->getOverdueLevel() == 'critical')
                                    <div class="d-inline-block" style="background-color: rgba(220, 53, 69, 0.2) !important; border: 1px solid rgba(220, 53, 69, 0.3); border-radius: 4px; padding: 2px 6px;">
                                @endif
                                <span class="badge bg-{{ $workorder->status == 'resolved' ? 'success' : ($workorder->status == 'pending' ? 'warning' : 'info') }} me-1">
                                    {{ $workorder->status_text }}
                                </span>
                                @if($workorder->getOverdueLevel() == 'normal' || $workorder->getOverdueLevel() == 'warning' || $workorder->getOverdueLevel() == 'danger' || $workorder->getOverdueLevel() == 'critical')
                                    </div>
                                @endif
                                @if($workorder->category)
                                <span class="badge bg-secondary me-1">{{ $workorder->category->name }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="text-end">
                            <small class="text-muted">
                                {{ $workorder->created_duration }}
                            </small>
                        </div>
                    </div>
                    
                    <div class="mb-2">
                        <div class="workorder-location mb-2">
                            <i class="fas fa-map-marker-alt text-muted me-1"></i>
                            <strong>地址:</strong>
                            @if($workorder->campus)
                                {{ \App\Models\Location::CAMPUSES[$workorder->campus] ?? $workorder->campus }}
                            @endif
                            @if($workorder->building)
                                @php
                                    $building = \App\Models\Location::find($workorder->building);
                                    if ($building) {
                                        echo ' - ' . $building->name;
                                        if ($workorder->location_detail) {
                                            echo ' ' . $workorder->location_detail;
                                        }
                                    } else {
                                        echo ' - ' . $workorder->building;
                                    }
                                @endphp
                            @endif
                        </div>
                        <div class="workorder-description">
                            <i class="fas fa-exclamation-triangle text-muted me-1"></i>
                            <strong>描述:</strong>
                            <a href="{{ \App\Helpers\UrlHelper::relative_url('/workorders/' . $workorder->id) }}" class="text-decoration-none">
                                {{ Str::limit($workorder->description, 100) }}
                            </a>
                            @if($workorder->failure_description)
                            <div class="text-muted small mt-1">具体故障: {{ Str::limit($workorder->failure_description, 80) }}</div>
                            @endif
                        </div>
                    </div>
                    
                    <div class="row mb-2">
                        <div class="col-6">
                            <small class="text-muted">报修</small><br>
                            <strong>{{ $workorder->contact_name }}</strong><br>
                            <small class="text-muted">{{ $workorder->contact_phone }}</small>
                        </div>
                        <div class="col-6">
                            <small class="text-muted">处理</small><br>
                            <strong>
                                {{ $workorder->assignee_name }}
                            </strong>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-end">
                        <div class="btn-group btn-group-sm">
                            {!! getWorkorderActionButtons($workorder, true) !!}
                        </div>
                    </div>
                </div>
            </div>
            @empty
            <div class="text-center py-4">
                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">暂无工单</h5>
                <p class="text-muted">
                    <a href="{{ \App\Helpers\UrlHelper::relative_url('/workorders/create') }}" class="btn btn-primary">
                        创建第一个工单
                    </a>
                </p>
            </div>
            @endforelse
        </div>
        
        <!-- 桌面端表格视图 -->
        <div class="table-responsive d-none d-md-block">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        @if(auth()->user()->canHandleWorkorders())
                        <th width="40">
                            <div class="checkbox-wrapper">
                                <input type="checkbox" id="selectAll" name="selectAll" class="form-check-input" autocomplete="off" onclick="handleSelectAllClick(this)">
                            </div>
                        </th>
                        @endif
                        <th>地址</th>
                        <th>类型</th>
                        <th>描述</th>
                        <th>报修</th>
                        <th>优先级</th>
                        <th>状态</th>
                        <th>处理</th>
                        <th>历时</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($workorders as $workorder)
                    <tr data-workorder-id="{{ $workorder->id }}" data-status="{{ $workorder->status }}" data-assignee="{{ $workorder->assignee_id }}">
                        @if(auth()->user()->canHandleWorkorders())
                        <td>
                            <div class="checkbox-wrapper">
                                <input type="checkbox" id="desktop-check-{{ $workorder->id }}" name="workorder_checkbox[]" class="form-check-input workorder-checkbox" value="{{ $workorder->id }}" autocomplete="off" onclick="handleWorkorderClick(this)">
                            </div>
                        </td>
                        @endif
                        <td>
                            <div class="workorder-location">
                                @if($workorder->campus)
                                    {{ \App\Models\Location::CAMPUSES[$workorder->campus] ?? $workorder->campus }}
                                @endif
                                @if($workorder->building)
                                    @php
                                        $building = \App\Models\Location::find($workorder->building);
                                        if ($building) {
                                            echo ' - ' . $building->name;
                                            if ($workorder->location_detail) {
                                                echo ' ' . $workorder->location_detail;
                                            }
                                        } else {
                                            echo ' - ' . $workorder->building;
                                        }
                                    @endphp
                                @endif
                            </div>
                        </td>
                        <td>
                            @if($workorder->category)
                                @php
                                    // 获取工单大类一级类型
                                    $mainCategory = \App\Models\WorkorderCategorySimplified::find($workorder->category->parent_id ?? $workorder->category->id);
                                    if ($mainCategory) {
                                        echo '<span class="badge bg-secondary me-1">' . $mainCategory->name . '</span>';
                                    } else {
                                        echo '<span class="badge bg-secondary me-1">' . $workorder->category->name . '</span>';
                                    }
                                @endphp
                            @endif
                        </td>
                        <td>
                            <div class="workorder-description">
                                <a href="{{ \App\Helpers\UrlHelper::relative_url('/workorders/' . $workorder->id) }}"
                                   class="text-decoration-none">
                                    {{ Str::limit($workorder->description, 30) }}
                                </a>
                            </div>
                            @if($workorder->is_emergency)
                            <i class="fas fa-exclamation-triangle text-danger" title="紧急工单"></i>
                            @endif
                            <!-- 协作邀请图标提示 -->
                            {!! getCollaborationIcon($workorder) !!}
                        </td>
                        <td>{{ $workorder->contact_name }}</td>
                        <td>
                            <span class="badge priority-{{ $workorder->priority }}">
                                {{ $workorder->priority_text }}
                            </span>
                        </td>
                        <td @if($workorder->isOverdue())
                            class="overdue-cell overdue-{{ $workorder->getOverdueLevel() }}"
                            @endif>
                            @if($workorder->getOverdueLevel() == 'normal')
                                <div style="background-color: rgba(40, 167, 69, 0.2) !important; padding: 4px; border-radius: 4px;">
                            @elseif($workorder->getOverdueLevel() == 'warning')
                                <div style="background-color: rgba(255, 193, 7, 0.2) !important; padding: 4px; border-radius: 4px;">
                            @elseif($workorder->getOverdueLevel() == 'danger')
                                <div style="background-color: rgba(253, 126, 20, 0.2) !important; padding: 4px; border-radius: 4px;">
                            @elseif($workorder->getOverdueLevel() == 'critical')
                                <div style="background-color: rgba(220, 53, 69, 0.2) !important; padding: 4px; border-radius: 4px;">
                            @endif
                            <span class="badge status-badge bg-{{ $workorder->status == 'resolved' ? 'success' : ($workorder->status == 'pending' ? 'warning' : 'info') }}">
                                {{ $workorder->status_text }}
                            </span>
                            @if($workorder->getOverdueLevel() == 'normal' || $workorder->getOverdueLevel() == 'warning' || $workorder->getOverdueLevel() == 'danger' || $workorder->getOverdueLevel() == 'critical')
                                </div>
                            @endif
                        </td>
                        <td>
                            {{ $workorder->assignee_name }}
                        </td>
                        <td>
                            <small>
                                {{ $workorder->created_duration }}
                            </small>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                {!! getWorkorderActionButtons($workorder, false) !!}
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ auth()->user()->canHandleWorkorders() ? '10' : '9' }}" class="text-center py-4">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">暂无工单</h5>
                            <p class="text-muted">
                                <a href="{{ \App\Helpers\UrlHelper::relative_url('/workorders/create') }}" class="btn btn-primary">
                                    创建第一个工单
                                </a>
                            </p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        </div>
        
        <!-- 分页 -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mt-3">
            <div class="text-muted mb-2 mb-md-0">
                显示 {{ $workorders->firstItem() }} - {{ $workorders->lastItem() }}
                共 {{ $workorders->total() }} 条记录
            </div>
            <div class="d-flex justify-content-center">
                {{ $workorders->appends(request()->query())->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </div>
</div>

@include('workorders._assign_modal')
@include('workorders._resolve_modal')
@include('workorders._batch_assign_modal')
@include('workorders._batch_resolve_modal')
@endsection

@section('scripts')
<script>
// 使用最简单直接的方法处理勾选框
var selectedWorkorders = [];

// 全选框处理
function handleSelectAllClick(element) {
    console.log('全选框被点击');
    const checkboxes = document.querySelectorAll('.workorder-checkbox');
    
    // 获取点击后的状态（浏览器会自动切换）
    const isChecked = element.checked;
    console.log('全选框状态:', isChecked);
    
    checkboxes.forEach(checkbox => {
        checkbox.checked = isChecked;
    });
    
    updateSelectedCount();
    updateSelectAllCheckbox();
    updateBatchOperationsUI();
}

// 单个勾选框处理
function handleWorkorderClick(element) {
    console.log('单个勾选框被点击:', element.value);
    // 获取点击后的状态（浏览器会自动切换）
    const isChecked = element.checked;
    console.log('勾选框状态:', isChecked);
    
    updateSelectedCount();
    updateSelectAllCheckbox();
    updateBatchOperationsUI();
}

// 更新选中数量
function updateSelectedCount() {
    const checkboxes = document.querySelectorAll('.workorder-checkbox:checked');
    selectedWorkorders = Array.from(checkboxes).map(cb => cb.value);
    
    document.getElementById('selectedCount').textContent = selectedWorkorders.length;
    console.log('更新选中数量:', selectedWorkorders.length, '选中ID:', selectedWorkorders);
}

// 更新全选框状态
function updateSelectAllCheckbox() {
    const totalCheckboxes = document.querySelectorAll('.workorder-checkbox').length;
    const checkedCheckboxes = document.querySelectorAll('.workorder-checkbox:checked').length;
    const selectAll = document.getElementById('selectAll');
    
    selectAll.checked = totalCheckboxes > 0 && totalCheckboxes === checkedCheckboxes;
    console.log('更新全选框状态:', selectAll.checked, '(总数:', totalCheckboxes, ', 已选:', checkedCheckboxes, ')');
}

// 更新批量操作UI
function updateBatchOperationsUI() {
    const batchOps = document.getElementById('batchOperations');
    if (selectedWorkorders.length > 0) {
        batchOps.style.display = 'block';
        console.log('显示批量操作栏');
    } else {
        batchOps.style.display = 'none';
        console.log('隐藏批量操作栏');
    }
}

// 页面加载完成后初始化
document.addEventListener('DOMContentLoaded', function() {
    console.log('页面加载完成，初始化勾选框功能');
    
    // 初始化状态
    updateSelectedCount();
    updateSelectAllCheckbox();
    updateBatchOperationsUI();
    
    console.log('勾选框功能初始化完成');
});

$(document).ready(function() {
    // 搜索表单不需要JavaScript处理，让浏览器自动提交
    // $('#searchForm').submit(function(e) {
    //     // 移除协议强制转换，让浏览器自动处理
    // });
    // 高级筛选切换
    $('#toggleAdvancedSearch').click(function() {
        $('#advancedSearch').slideToggle();
        var icon = $(this).find('i');
        if (icon.hasClass('fa-chevron-down')) {
            icon.removeClass('fa-chevron-down').addClass('fa-chevron-up');
            $(this).html('<i class="fas fa-chevron-up"></i> 收起筛选');
        } else {
            icon.removeClass('fa-chevron-up').addClass('fa-chevron-down');
            $(this).html('<i class="fas fa-chevron-down"></i> 高级筛选');
        }
    });
    
    // 导出功能
    $('#exportBtn').click(function() {
        var form = $('form').clone();
        form.attr('action', '{{ route("reports.export") }}');
        form.attr('target', '_blank');
        form.submit();
    });
    
});

// 自定义时间范围导出
function showDateRangeModal() {
    const modal = new bootstrap.Modal(document.getElementById('dateRangeModal'));
    modal.show();
}

function exportWorkorders(days) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '{{ route("reports.export") }}';
    form.style.display = 'none';
    
    // 添加CSRF令牌
    const csrfToken = document.createElement('input');
    csrfToken.type = 'hidden';
    csrfToken.name = '_token';
    csrfToken.value = '{{ csrf_token() }}';
    form.appendChild(csrfToken);
    
    // 添加隐藏字段
    const formatInput = document.createElement('input');
    formatInput.type = 'hidden';
    formatInput.name = 'format';
    formatInput.value = 'xlsx';
    form.appendChild(formatInput);
    
    const daysInput = document.createElement('input');
    daysInput.type = 'hidden';
    daysInput.name = 'days';
    daysInput.value = days;
    form.appendChild(daysInput);
    
    document.body.appendChild(form);
    form.submit();
}

function exportCustomDateRange() {
    const startDate = document.getElementById('customStartDate').value;
    const endDate = document.getElementById('customEndDate').value;
    
    if (!startDate || !endDate) {
        alert('请选择开始日期和结束日期');
        return;
    }
    
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '{{ route("reports.export") }}';
    form.style.display = 'none';
    
    // 添加CSRF令牌
    const csrfToken = document.createElement('input');
    csrfToken.type = 'hidden';
    csrfToken.name = '_token';
    csrfToken.value = '{{ csrf_token() }}';
    form.appendChild(csrfToken);
    
    // 添加隐藏字段
    const formatInput = document.createElement('input');
    formatInput.type = 'hidden';
    formatInput.name = 'format';
    formatInput.value = 'xlsx';
    form.appendChild(formatInput);
    
    const startDateInput = document.createElement('input');
    startDateInput.type = 'hidden';
    startDateInput.name = 'start_date';
    startDateInput.value = startDate;
    form.appendChild(startDateInput);
    
    const endDateInput = document.createElement('input');
    endDateInput.type = 'hidden';
    endDateInput.name = 'end_date';
    endDateInput.value = endDate;
    form.appendChild(endDateInput);
    
    document.body.appendChild(form);
    form.submit();
}
</script>

@include('workorders._resolve_modal_scripts')
@include('workorders._modal_scripts')
@endsection

@push('styles')
<style>
/* 工单列表优化样式 */
.workorder-description {
    position: relative;
}

/* 确保勾选框可以被点击 - 最简单有效的方法 */
.checkbox-wrapper {
    position: relative;
    display: inline-block;
    z-index: 99999;
}

.checkbox-wrapper input[type="checkbox"] {
    position: relative;
    z-index: 99999;
    cursor: pointer;
    pointer-events: auto;
}

/* 防止其他元素覆盖勾选框 */
.table tbody tr td:first-child,
.table thead tr th:first-child {
    position: relative;
    z-index: 99999;
}

/* 确保没有覆盖层 */
.table tbody tr::before,
.table tbody tr::after {
    display: none !important;
    content: none !important;
}

/* 超时工单状态单元格背景色提示 - 最高优先级 */
body .table.table-striped.table-hover tbody tr td.overdue-cell.overdue-normal {
    background-color: rgba(40, 167, 69, 0.2) !important; /* 浅绿色：1小时以内 */
    box-shadow: inset 0 0 0 1000px rgba(40, 167, 69, 0.2) !important;
}

body .table.table-striped.table-hover tbody tr td.overdue-cell.overdue-warning {
    background-color: rgba(255, 193, 7, 0.2) !important; /* 浅黄色：1小时 */
    box-shadow: inset 0 0 0 1000px rgba(255, 193, 7, 0.2) !important;
}

body .table.table-striped.table-hover tbody tr td.overdue-cell.overdue-danger {
    background-color: rgba(253, 126, 20, 0.2) !important; /* 浅橙色：4小时 */
    box-shadow: inset 0 0 0 1000px rgba(253, 126, 20, 0.2) !important;
}

body .table.table-striped.table-hover tbody tr td.overdue-cell.overdue-critical {
    background-color: rgba(220, 53, 69, 0.2) !important; /* 浅红色：8小时+ */
    box-shadow: inset 0 0 0 1000px rgba(220, 53, 69, 0.2) !important;
}

/* 覆盖Bootstrap的条纹样式 */
.table-striped > tbody > tr:nth-of-type(odd) > td.overdue-cell.overdue-normal,
.table-striped > tbody > tr:nth-of-type(even) > td.overdue-cell.overdue-normal {
    background-color: rgba(40, 167, 69, 0.2) !important;
}

.table-striped > tbody > tr:nth-of-type(odd) > td.overdue-cell.overdue-warning,
.table-striped > tbody > tr:nth-of-type(even) > td.overdue-cell.overdue-warning {
    background-color: rgba(255, 193, 7, 0.2) !important;
}

.table-striped > tbody > tr:nth-of-type(odd) > td.overdue-cell.overdue-danger,
.table-striped > tbody > tr:nth-of-type(even) > td.overdue-cell.overdue-danger {
    background-color: rgba(253, 126, 20, 0.2) !important;
}

.table-striped > tbody > tr:nth-of-type(odd) > td.overdue-cell.overdue-critical,
.table-striped > tbody > tr:nth-of-type(even) > td.overdue-cell.overdue-critical {
    background-color: rgba(220, 53, 69, 0.2) !important;
}

/* 移动端卡片视图状态背景色 */
.overdue-cell.overdue-normal {
    background-color: rgba(40, 167, 69, 0.2) !important; /* 浅绿色：1小时以内 */
    border-radius: 4px;
    padding: 2px 6px;
    border: 1px solid rgba(40, 167, 69, 0.3);
}

.overdue-cell.overdue-warning {
    background-color: rgba(255, 193, 7, 0.2) !important; /* 浅黄色：1小时 */
    border-radius: 4px;
    padding: 2px 6px;
    border: 1px solid rgba(255, 193, 7, 0.3);
}

.overdue-cell.overdue-danger {
    background-color: rgba(253, 126, 20, 0.2) !important; /* 浅橙色：4小时 */
    border-radius: 4px;
    padding: 2px 6px;
    border: 1px solid rgba(253, 126, 20, 0.3);
}

.overdue-cell.overdue-critical {
    background-color: rgba(220, 53, 69, 0.2) !important; /* 浅红色：8小时+ */
    border-radius: 4px;
    padding: 2px 6px;
    border: 1px solid rgba(220, 53, 69, 0.3);
}

/* 强调地点和故障描述 */
.workorder-location {
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 8px;
    font-size: 14px;
}

.workorder-description {
    color: #34495e;
    line-height: 1.4;
    margin-bottom: 8px;
}

/* 桌面端表格优化 - 根据新的列顺序调整 */
.table td:nth-child(2) { /* 地址列 */
    font-weight: 600;
    color: #2c3e50;
    min-width: 150px;
}

.table td:nth-child(3) { /* 类型列 */
    width: 100px;
}

.table td:nth-child(4) { /* 描述列 */
    max-width: 250px;
}

.table td:nth-child(5) { /* 报修列 */
    width: 80px;
}

.table td:nth-child(6) { /* 优先级列 */
    width: 80px;
}

.table td:nth-child(7) { /* 状态列 */
    width: 100px;
}

.table td:nth-child(8) { /* 处理列 */
    width: 80px;
}

.table td:nth-child(9) { /* 历时列 */
    width: 80px;
}

.table td:nth-child(10) { /* 操作列 */
    width: 180px;
}

/* 协作邀请图标动画 */
.fa-handshake {
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.1); }
    100% { transform: scale(1); }
}

/* 移动端优化 */
@media (max-width: 767.98px) {
    .workorder-location {
        font-size: 13px;
    }
    
    .workorder-description {
        font-size: 13px;
    }
    
    .workorder-ticket-no {
        top: -20px;
        font-size: 11px;
    }
}

/* 表格行悬停效果 */
.table tbody tr:hover {
    background-color: rgba(0, 123, 255, 0.05);
}

/* 卡片悬停效果 */
.workorder-card:hover {
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    transform: translateY(-2px);
    transition: all 0.3s ease;
}

/* 操作按钮组优化 */
.btn-group-sm > .btn {
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
}

/* 优先级标签优化 */
.priority-high {
    background-color: #dc3545 !important;
}

.priority-medium {
    background-color: #ffc107 !important;
    color: #212529 !important;
}

.priority-low {
    background-color: #28a745 !important;
}

/* 状态标签优化 */
.status-badge {
    min-width: 60px;
    text-align: center;
}
</style>
@endpush

@push('scripts')
<div class="modal fade" id="dateRangeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">自定义导出时间范围</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="关闭导出时间设置对话框"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="customStartDate" class="form-label">开始日期</label>
                    <input type="date" class="form-control" id="customStartDate" max="{{ now()->format('Y-m-d') }}" autocomplete="off">
                </div>
                <div class="mb-3">
                    <label for="customEndDate" class="form-label">结束日期</label>
                    <input type="date" class="form-control" id="customEndDate" max="{{ now()->format('Y-m-d') }}" autocomplete="off">
                </div>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    支持导出最近11个月内的工单数据
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                <button type="button" class="btn btn-primary" onclick="exportCustomDateRange()">导出</button>
            </div>
        </div>
    </div>
</div>
@endpush
