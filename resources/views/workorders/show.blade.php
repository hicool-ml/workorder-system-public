@extends('layouts.app')

@section('title', '工单详情 - ' . $workorder->ticket_no)

@include('workorders._permission_checks')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">工单详情</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="{{ \App\Helpers\UrlHelper::relative_url('/workorders') }}" class="btn btn-secondary me-2">
            <i class="fas fa-arrow-left"></i> 返回列表
        </a>
        
        @if($workorder->creator_id == auth()->id() && $workorder->status == 'pending')
        <a href="{{ route('workorders.edit', $workorder->id) }}" class="btn btn-warning me-2">
            <i class="fas fa-edit"></i> 编辑
        </a>
        @endif
        
        @if($workorder->canBeAssigned() && auth()->user()->canAssignWorkorders())
        <button type="button" class="btn btn-success me-2" data-bs-toggle="modal" data-bs-target="#assignModal">
            <i class="fas fa-user-plus"></i> 分配
        </button>
        @elseif($workorder->canBeAssigned() && auth()->user()->isEngineer() && !$workorder->assignee_id)
        <form method="POST" action="{{ route('workorders.claim', $workorder->id) }}" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-success me-2"
                    onclick="return confirm('确认接单吗？')">
                <i class="fas fa-hand-paper"></i> 接单
            </button>
        </form>
        @endif
        
        @if($workorder->canBeStarted() &&
           ($workorder->assignee_id == auth()->id() || auth()->user()->isAdmin() || auth()->user()->isWorkorderManager()))
        <form method="POST" action="{{ route('workorders.start', $workorder->id) }}" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-warning me-2" 
                    onclick="return confirm('确认开始处理此工单吗？')">
                <i class="fas fa-play"></i> 开始处理
            </button>
        </form>
        @endif
        
        @if(canResolveWorkorder($workorder))
        <button type="button" class="btn btn-info me-2" data-bs-toggle="modal" data-bs-target="#resolveModal">
            <i class="fas fa-check"></i> 解决
        </button>
        @endif
        
        @if(canInviteCollaboration($workorder))
        <button type="button" class="btn btn-info me-2" data-bs-toggle="modal" data-bs-target="#inviteModal">
            <i class="fas fa-user-plus"></i> 邀请协作
        </button>
        @endif
        
        @if($workorder->canBeCompleted() &&
           ($workorder->assignee_id == auth()->id() || auth()->user()->isAdmin() || auth()->user()->isWorkorderManager()))
        <button type="button" class="btn btn-success me-2" data-bs-toggle="modal" data-bs-target="#completeModal">
            <i class="fas fa-check-circle"></i> 完结
        </button>
        @endif
        
        @if($workorder->canBeClosed() && auth()->user()->canCloseWorkorders())
        <form method="POST" action="{{ route('workorders.close', $workorder->id) }}" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-danger"
                    onclick="return confirm('确认关闭此工单吗？')">
                <i class="fas fa-times"></i> 关闭
            </button>
        </form>
        @endif
    </div>
</div>

<div class="row">
    <!-- 工单基本信息 -->
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    {{ $workorder->ticket_no }}
                    @if($workorder->is_emergency)
                    <span class="badge bg-danger ms-2">紧急</span>
                    @endif
                    @if($workorder->phone_assisted)
                    <span class="badge bg-info ms-2">电话协助完成</span>
                    @endif
                    @if($workorder->isOverdue())
                    <span class="badge bg-warning ms-2">已超时</span>
                    @endif
                </h5>
                <div>
                    <span class="badge priority-{{ $workorder->priority }}">
                        {{ $workorder->priority_text }}
                    </span>
                    <span class="badge bg-{{ $workorder->status == 'closed' ? 'success' : ($workorder->status == 'pending' ? 'warning' : ($workorder->status == 'completed' ? 'primary' : 'info')) }}">
                        {{ $workorder->status_text }}
                    </span>
                </div>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>工单分类：</strong>
                        @if($workorder->category)
                        <span class="badge bg-secondary">{{ $workorder->category->name }}</span>
                        @if($workorder->category->parent)
                        <small class="text-muted">({{ $workorder->category->parent->name }})</small>
                        @endif
                        @else
                        <span class="text-muted">未设置</span>
                        @endif
                    </div>
                    <div class="col-md-6">
                        <strong>工单编号：</strong>{{ $workorder->ticket_no }}
                    </div>
                </div>
                
                <div class="mb-3">
                    <strong>问题描述：</strong>
                    <div class="mt-2 p-3 bg-light rounded">
                        {{ nl2br($workorder->description) }}
                    </div>
                </div>
                
                @if($workorder->failure_description)
                <div class="mb-3">
                    <strong>具体故障现象：</strong>
                    <div class="mt-2 p-3 bg-warning bg-opacity-10 rounded">
                        {{ nl2br($workorder->failure_description) }}
                    </div>
                </div>
                @endif
                
                <div class="row mb-3">
                    <div class="col-md-4">
                        <strong>联系人：</strong>{{ $workorder->contact_name }}
                    </div>
                    <div class="col-md-4">
                        <strong>联系电话：</strong>{{ $workorder->contact_phone }}
                    </div>
                    <div class="col-md-4">
                        <strong>联系邮箱：</strong>{{ $workorder->contact_email ?: '无' }}
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>故障地点：</strong>
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
                    <div class="col-md-6">
                        <strong>详细地址：</strong>{{ $workorder->location_detail ?: '无' }}
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-3">
                        <strong>工单来源：</strong>{{ $workorder->source_text }}
                    </div>
                    <div class="col-md-3">
                        <strong>创建时间：</strong>{{ $workorder->created_at->format('Y-m-d H:i:s') }}
                    </div>
                    <div class="col-md-3">
                        <strong>预计完成：</strong>{{ $workorder->expected_complete_at?->format('Y-m-d H:i') ?: '未设置' }}
                    </div>
                    <div class="col-md-3">
                        <strong>创建人：</strong>{{ $workorder->creator->name }}
                    </div>
                </div>
                
                @if($workorder->solution)
                <div class="mb-3">
                    <strong>解决方案：</strong>
                    <div class="mt-2 p-3 bg-success bg-opacity-10 rounded">
                        {{ nl2br($workorder->solution) }}
                    </div>
                </div>
                @endif
                
                @if($workorder->remarks)
                <div class="mb-3">
                    <strong>备注：</strong>
                    <div class="mt-2 p-3 bg-info bg-opacity-10 rounded">
                        {{ nl2br($workorder->remarks) }}
                    </div>
                </div>
                @endif
                
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <strong>备件耗材使用情况：</strong>
                        @if(canEditMaterialsUsage($workorder))
                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#materialsModal">
                            <i class="fas fa-edit"></i> 编辑
                        </button>
                        @endif
                    </div>
                    @if($workorder->materials_usage)
                    <div class="mt-2 p-3 bg-warning bg-opacity-10 rounded">
                        {{ nl2br($workorder->materials_usage) }}
                    </div>
                    @else
                    <div class="mt-2 p-3 bg-light rounded text-muted">
                        暂无备件耗材使用记录
                    </div>
                    @endif
                </div>
            </div>
        </div>
        
        <!-- 处理记录 -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">处理记录</h5>
                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addLogModal">
                    <i class="fas fa-plus"></i> 添加记录
                </button>
            </div>
            <div class="card-body">
                @if($workorder->logs->count() > 0)
                <div class="timeline">
                    @foreach($workorder->logs as $log)
                    <div class="timeline-item">
                        <div class="timeline-marker bg-{{ $log->is_system ? 'secondary' : 'primary' }}"></div>
                        <div class="timeline-content">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <strong>{{ $log->action_text }}</strong>
                                    @if($log->content)
                                    <div class="text-muted mt-1">{{ $log->content }}</div>
                                    @endif
                                </div>
                                <div class="text-end">
                                    <small class="text-muted">
                                        {{ $log->user ? $log->user->name : '系统' }}
                                        <br>{{ $log->created_at->format('m-d H:i') }}
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <div class="text-center py-4 text-muted">
                    <i class="fas fa-history fa-2x mb-2"></i>
                    <p>暂无处理记录</p>
                </div>
                @endif
            </div>
        </div>
    </div>
    
    <!-- 右侧信息栏 -->
    <div class="col-md-4">
        <!-- 处理人信息 -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="card-title mb-0">处理人信息</h6>
            </div>
            <div class="card-body">
                @if($workorder->assignee)
                <div class="text-center">
                    <div class="mb-2">
                        <i class="fas fa-user fa-3x text-primary"></i>
                    </div>
                    <h6>{{ $workorder->assignee->name }}</h6>
                    <p class="text-muted">{{ $workorder->assignee->department?->name }}</p>
                    <p class="text-muted">{{ $workorder->assignee->phone }}</p>
                </div>
                @else
                <div class="text-center text-muted">
                    <i class="fas fa-user-slash fa-2x mb-2"></i>
                    <p>未分配处理人</p>
                </div>
                @endif
            </div>
        </div>
        
        <!-- 附件列表 -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="card-title mb-0">附件列表</h6>
                @if(canUploadAttachment($workorder))
                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#uploadAttachmentModal">
                    <i class="fas fa-plus"></i> 上传附件
                </button>
                @endif
            </div>
            <div class="card-body">
                @if($workorder->attachments->count() > 0)
                @foreach($workorder->attachments as $attachment)
                <div class="attachment-item mb-3 p-3 border rounded">
                    <div class="d-flex">
                        <!-- 左侧缩略图和按钮 -->
                        <div class="d-flex flex-column align-items-center me-3">
                            <!-- 缩略图 -->
                            <div class="attachment-thumbnail mb-2">
                                @if($attachment->isImage())
                                    <img src="{{ route('attachments.preview', $attachment->id) }}"
                                         class="img-thumbnail attachment-preview-img"
                                         alt="{{ $attachment->description ?: $attachment->original_name }}"
                                         data-attachment-id="{{ $attachment->id }}"
                                         data-preview-type="{{ $attachment->preview_type }}"
                                         data-preview-url="{{ route('attachments.preview', $attachment->id) }}"
                                         data-filename="{{ $attachment->description ?: $attachment->original_name }}"
                                         style="width: 60px; height: 60px; object-fit: cover; cursor: pointer;">
                                @else
                                    <div class="file-icon-wrapper attachment-preview-trigger"
                                         data-attachment-id="{{ $attachment->id }}"
                                         data-preview-type="{{ $attachment->preview_type }}"
                                         data-preview-url="{{ route('attachments.preview', $attachment->id) }}"
                                         data-filename="{{ $attachment->description ?: $attachment->original_name }}"
                                         style="width: 60px; height: 60px; display: flex; align-items: center; justify-content: center; background-color: #f8f9fa; border-radius: 8px; cursor: pointer;">
                                        <i class="{{ $attachment->getFileIcon() }} fa-2x" aria-hidden="true"></i>
                                    </div>
                                @endif
                            </div>
                            
                            <!-- 操作按钮 -->
                            <div class="d-flex flex-column gap-1">
                                @if($attachment->canPreview())
                                <a href="#" class="btn btn-sm btn-outline-primary attachment-action-btn"
                                   data-bs-toggle="modal"
                                   data-bs-target="#attachmentPreviewModal{{ $attachment->id }}"
                                   title="预览">
                                    预览
                                </a>
                                @endif
                                <a href="{{ route('attachments.download', $attachment->id) }}"
                                   class="btn btn-sm btn-outline-success attachment-action-btn"
                                   title="下载">
                                    下载
                                </a>
                                @if (Auth::user()->canDeleteWorkorders() || Auth::id() === $workorder->creator_id || Auth::id() === $workorder->assignee_id)
                                <form action="{{ route('attachments.destroy', $attachment->id) }}"
                                      method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger attachment-action-btn"
                                            title="删除"
                                            onclick="return confirm('确定要删除这个附件吗？')">
                                        删除
                                    </button>
                                </form>
                                @endif
                            </div>
                        </div>
                        
                        <!-- 右侧文件信息 -->
                        <div class="attachment-info flex-grow-1">
                            <h6 class="mb-1 attachment-filename">
                                {{ \App\Helpers\FileHelper::truncateFilename($attachment->description ?: $attachment->original_name, 16) }}
                            </h6>
                            <div class="mb-2">
                                <small class="text-muted">
                                    <span class="badge bg-light text-dark me-1">{{ $attachment->file_type_description }}</span>
                                    附件格式：{{ \App\Helpers\FileHelper::getFileExtension($attachment->original_name) }}
                                </small>
                            </div>
                            <small class="text-muted d-block">
                                附件大小：{{ $attachment->formatted_file_size }}
                            </small>
                            <small class="text-muted d-block">
                                上传者：{{ $attachment->user ? $attachment->user->name : '未知' }}
                            </small>
                            <small class="text-muted d-block">
                                上传日期：{{ $attachment->created_at->format('Y/m/d') }}
                            </small>
                            <small class="text-muted d-block">
                                上传时间：{{ $attachment->created_at->format('H:i') }}
                            </small>
                            @if($attachment->description && $attachment->description !== $attachment->original_name)
                            <small class="text-muted d-block">文件名：{{ $attachment->original_name }}</small>
                            @endif
                        </div>
                    </div>
                </div>
                @endforeach
                @else
                <div class="text-center text-muted">
                    <i class="fas fa-paperclip fa-2x mb-2"></i>
                    <p>暂无附件</p>
                </div>
                @endif
            </div>
        </div>
        
        <!-- 工单统计 -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="card-title mb-0">工单统计</h6>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-6 mb-3">
                        <div class="border rounded p-2">
                            <strong>{{ $workorder->response_duration ?? '--' }}</strong>
                            <br><small class="text-muted">响应时长(分)</small>
                        </div>
                    </div>
                    <div class="col-6 mb-3">
                        <div class="border rounded p-2">
                            <strong>{{ $workorder->processing_duration ?? '--' }}</strong>
                            <br><small class="text-muted">处理时长(分)</small>
                        </div>
                    </div>
                </div>
                
                @if($workorder->visits->count() > 0)
                <div class="text-center">
                    <small class="text-muted">满意度评分</small>
                    <div class="h4 text-primary">
                        {{ $workorder->visits->first()->average_score ?? '--' }}
                    </div>
                </div>
                @endif
            </div>
        </div>
        
        <!-- 工单处理人员名单 -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="card-title mb-0">工单处理人员名单</h6>
            </div>
            <div class="card-body">
                <!-- 工单负责人 -->
                @if($workorder->assignee)
                <div class="d-flex align-items-center mb-3 p-2 bg-primary bg-opacity-10 rounded">
                    <div class="me-2">
                        <i class="fas fa-user-tie fa-lg text-primary"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="mb-0">{{ $workorder->assignee->name }}</h6>
                        <small class="text-muted">{{ $workorder->assignee->department?->name }}</small>
                        <br><small class="text-primary">工单负责人</small>
                    </div>
                    <div class="ms-auto">
                        <span class="badge bg-primary">负责人</span>
                    </div>
                </div>
                @endif
                
                <!-- 协作工程师 -->
                @if($workorder->collaborations()->count() > 0)
                @foreach($workorder->collaborations as $collaboration)
                <div class="d-flex align-items-center mb-2 p-2 bg-info bg-opacity-10 rounded">
                    <div class="me-2">
                        <i class="fas fa-user fa-lg text-info"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="mb-0">{{ $collaboration->collaborator->name }}</h6>
                        <small class="text-muted">{{ $collaboration->collaborator->department?->name }}</small>
                        @if($collaboration->accepted_at)
                        <br><small class="text-success">接受时间：{{ $collaboration->accepted_at->format('m-d H:i') }}</small>
                        @endif
                        @if($collaboration->invitation_reason)
                        <br><small class="text-info">邀请原因：{{ $collaboration->invitation_reason }}</small>
                        @endif
                    </div>
                    <div class="ms-auto">
                        @if($collaboration->status === 'pending' && $collaboration->collaborator_id === auth()->id())
                        <div class="btn-group btn-group-sm">
                            <form method="POST" action="{{ route('workorders.collaborations.accept', $collaboration->id) }}" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-success"
                                        onclick="return confirm('确认接受协作邀请吗？')">
                                    <i class="fas fa-check"></i> 接受
                                </button>
                            </form>
                            <form method="POST" action="{{ route('workorders.collaborations.reject', $collaboration->id) }}" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-danger"
                                        onclick="return confirm('确认拒绝协作邀请吗？')">
                                    <i class="fas fa-times"></i> 拒绝
                                </button>
                            </form>
                        </div>
                        @else
                        <span class="badge bg-{{ $collaboration->status_color }}">{{ $collaboration->status_text }}</span>
                        @endif
                    </div>
                </div>
                @endforeach
                @else
                @if($workorder->assignee)
                <div class="text-center text-muted py-2">
                    <small>暂无协作工程师</small>
                </div>
                @endif
                @endif
            </div>
        </div>
        
        <!-- 回访功能 -->
        @if($workorder->status === 'resolved' && !$workorder->visits()->exists())
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">工单回访</h5>
                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#visitModal">
                    <i class="fas fa-plus"></i> 添加回访
                </button>
            </div>
            <div class="card-body">
                <div class="text-center text-muted">
                    <i class="fas fa-phone fa-2x mb-2"></i>
                    <p>该工单已完成，但尚未进行回访。请添加回访记录以了解用户满意度。</p>
                </div>
            </div>
        </div>
        @endif
        
        <!-- 回访记录列表 -->
        @if($workorder->visits()->count() > 0)
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title">回访记录</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>回访时间</th>
                                <th>回访方式</th>
                                <th>回访人</th>
                                <th>满意度</th>
                                <th>回访状态</th>
                                <th>回访内容</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($workorder->visits as $visit)
                            <tr>
                                <td>{{ $visit->visit_time ? $visit->visit_time->format('Y-m-d H:i') : '--' }}</td>
                                <td><span class="badge bg-info">{{ $visit->visit_method_text }}</span></td>
                                <td>{{ $visit->visitor ? $visit->visitor->name : '--' }}</td>
                                <td>
                                    @if($visit->satisfaction_score)
                                        <span class="badge bg-{{ $visit->satisfaction_color }}">{{ $visit->average_score }}分</span>
                                    @else
                                        <span class="text-muted">--</span>
                                    @endif
                                </td>
                                <td><span class="badge bg-{{ $visit->status == 'completed' ? 'success' : ($visit->status == 'failed' ? 'danger' : 'warning') }}">{{ $visit->status_text }}</span></td>
                                <td>{{ $visit->feedback ?: '--' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>

<!-- 分配工单模态框 -->
<div class="modal fade" id="assignModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">分配工单</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="关闭分配工单对话框"></button>
            </div>
            <form method="POST" action="{{ route('workorders.assign', $workorder->id) }}">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="modal_assignee_id" class="form-label">选择处理人</label>
                        <select class="form-select" id="modal_assignee_id" name="assignee_id" required>
                            <option value="">请选择处理人</option>
                            @foreach(\App\Models\User::getAssignableEngineers() as $engineer)
                            <option value="{{ $engineer->id }}">{{ $engineer->name }} - {{ $engineer->department?->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                    <button type="submit" class="btn btn-primary">确认分配</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 解决工单模态框 -->
<div class="modal fade" id="resolveModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">解决工单</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="关闭解决工单对话框"></button>
            </div>
            <form method="POST" action="{{ route('workorders.resolve', $workorder->id) }}" id="resolveForm">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="solution" class="form-label">解决方案 <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="solution" name="solution" rows="5" required
                                  placeholder="请详细描述解决方案..." autocomplete="off"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="resolve_materials_usage" class="form-label">备件耗材使用情况 <span class="text-danger">*</span></label>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" id="no_materials" name="no_materials" value="1" autocomplete="off">
                            <label class="form-check-label" for="no_materials">
                                无备件耗材使用
                            </label>
                        </div>
                        <div id="materials_usage_div">
                            <label for="resolve_materials_usage" class="form-label">请填写备件耗材使用情况</label>
                            <textarea class="form-control" id="resolve_materials_usage" name="materials_usage" rows="4"
                                      placeholder="请详细描述使用的备件和耗材情况..." autocomplete="off"></textarea>
                            <div class="form-text">
                                请记录使用的备件名称、规格、数量等信息
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                    <button type="submit" class="btn btn-primary">确认解决</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 完结工单模态框 -->
<div class="modal fade" id="completeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">完结工单</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="关闭完结工单对话框"></button>
            </div>
            <form method="POST" action="{{ route('workorders.complete', $workorder->id) }}">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="completion_note" class="form-label">完结说明</label>
                        <textarea class="form-control" id="completion_note" name="completion_note" rows="5" required
                                  placeholder="请输入工单完结说明..." autocomplete="off"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                    <button type="submit" class="btn btn-success">确认完结</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 添加处理记录模态框 -->
<div class="modal fade" id="addLogModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">添加处理记录</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="关闭添加处理记录对话框"></button>
            </div>
            <form method="POST" action="{{ route('workorders.logs.add', $workorder->id) }}">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="content" class="form-label">记录内容</label>
                        <textarea class="form-control" id="content" name="content" rows="4" required
                                  placeholder="请输入处理记录..." autocomplete="off"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                    <button type="submit" class="btn btn-primary">添加记录</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 上传附件模态框 -->
@if((auth()->user()->isAdmin() || auth()->user()->isWorkorderManager() || $workorder->assignee_id == auth()->id() || $workorder->collaborators()->where('collaborator_id', auth()->id())->exists()) && in_array($workorder->status, ['pending', 'processing', 'assigned']))
<div class="modal fade" id="uploadAttachmentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">上传附件</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="关闭上传附件对话框"></button>
            </div>
            <form method="POST" action="{{ route('workorders.attachments.upload', $workorder->id) }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="new_attachments" class="form-label">选择文件</label>
                        <input type="file" class="form-control" id="new_attachments" name="attachments[]"
                               multiple accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.txt" required autocomplete="off">
                        <div class="form-text">
                            支持上传图片、文档等文件，单个文件最大10MB，最多5个文件<br>
                            <small class="text-info">大图片将自动压缩以减少文件大小，提高上传成功率</small>
                        </div>
                        <div id="newAttachmentPreview" class="mt-2"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                    <button type="submit" class="btn btn-primary">上传</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

<!-- 备件耗材编辑模态框 -->
@if(canEditMaterialsUsage($workorder))
<div class="modal fade" id="materialsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">编辑备件耗材使用情况</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="关闭编辑备件耗材对话框"></button>
            </div>
            <form method="POST" action="{{ route('workorders.materials.update', $workorder->id) }}">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_materials_usage" class="form-label">备件耗材使用情况</label>
                        <textarea class="form-control" id="edit_materials_usage" name="materials_usage" rows="6" required
                                  placeholder="请详细描述使用的备件和耗材情况..." autocomplete="off">{{ $workorder->materials_usage ?? '' }}</textarea>
                        <div class="form-text">
                            请记录使用的备件名称、规格、数量等信息
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                    <button type="submit" class="btn btn-primary">保存</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

<!-- 邀请协作模态框 -->
@if(canInviteCollaboration($workorder))
<div class="modal fade" id="inviteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">邀请协作工程师</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="关闭邀请协作对话框"></button>
            </div>
            <form method="POST" action="{{ route('workorders.invite.collaborator', $workorder->id) }}">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="collaborator_id" class="form-label">选择工程师</label>
                        <select class="form-select" id="collaborator_id" name="collaborator_id" required>
                            <option value="">请选择工程师</option>
                            @foreach(\App\Models\User::getAssignableEngineers() as $engineer)
                            @if($engineer->id != auth()->id() && $engineer->id != $workorder->assignee_id)
                            <option value="{{ $engineer->id }}">{{ $engineer->name }} - {{ $engineer->department?->name }}</option>
                            @endif
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="invitation_reason" class="form-label">邀请原因</label>
                        <textarea class="form-control" id="invitation_reason" name="invitation_reason" rows="3"
                                  placeholder="请说明邀请协作的原因..." autocomplete="off"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                    <button type="submit" class="btn btn-primary">发送邀请</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

<!-- 回访模态框 -->
@if($workorder->status === 'resolved' && !$workorder->visits()->exists())
<div class="modal fade" id="visitModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">工单回访</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="关闭工单回访对话框"></button>
            </div>
            <form method="POST" action="{{ route('workorders.visit.store', $workorder->id) }}">
                @csrf
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="visit_method" class="form-label">回访方式</label>
                            <select class="form-select" id="visit_method" name="visit_method" required>
                                <option value="">请选择回访方式</option>
                                @foreach(\App\Models\WorkorderVisit::getVisitMethodOptions() as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="visit_time" class="form-label">回访时间</label>
                            <input type="datetime-local" class="form-control" id="visit_time" name="visit_time" required autocomplete="off">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="visit_content" class="form-label">回访内容</label>
                        <textarea class="form-control" id="visit_content" name="visit_content" rows="4" required
                                  placeholder="请记录回访内容，如用户反馈、问题解决情况等" autocomplete="off"></textarea>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label for="satisfaction_score" class="form-label">响应速度评分 (1-5分)</label>
                            <select class="form-select" id="satisfaction_score" name="satisfaction_score">
                                <option value="">请评分</option>
                                @foreach(\App\Models\WorkorderVisit::getScoreOptions() as $score => $text)
                                <option value="{{ $score }}">{{ $text }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="service_quality_score" class="form-label">服务质量评分 (1-5分)</label>
                            <select class="form-select" id="service_quality_score" name="service_quality_score">
                                <option value="">请评分</option>
                                @foreach(\App\Models\WorkorderVisit::getScoreOptions() as $score => $text)
                                <option value="{{ $score }}">{{ $text }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="professional_score" class="form-label">专业水平评分 (1-5分)</label>
                            <select class="form-select" id="professional_score" name="professional_score">
                                <option value="">请评分</option>
                                @foreach(\App\Models\WorkorderVisit::getScoreOptions() as $score => $text)
                                <option value="{{ $score }}">{{ $text }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="overall_score" class="form-label">总体满意度评分 (1-5分)</label>
                            <select class="form-select" id="overall_score" name="overall_score">
                                <option value="">请评分</option>
                                @foreach(\App\Models\WorkorderVisit::getScoreOptions() as $score => $text)
                                <option value="{{ $score }}">{{ $text }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="feedback" class="form-label">用户反馈</label>
                        <textarea class="form-control" id="feedback" name="feedback" rows="3"
                                  placeholder="请记录用户反馈意见" autocomplete="off"></textarea>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="form-check">
                                <input type="checkbox" id="need_follow_up" name="need_follow_up" value="1" autocomplete="off">
                                <label class="form-check-label" for="need_follow_up">
                                    需要跟进
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="follow_up_note" class="form-label">跟进说明</label>
                            <textarea class="form-control" id="follow_up_note" name="follow_up_note" rows="2"
                                      placeholder="如需跟进，请说明跟进内容" autocomplete="off"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">提交回访记录</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection

@section('scripts')
<!-- 引入共享的解决工单模态框脚本 -->
<script src="{{ asset('js/workorder-resolve.js') }}"></script>
<script>
// 新附件预览
$('#new_attachments').change(function() {
    var preview = $('#newAttachmentPreview');
    preview.empty();
    
    var files = this.files;
    for (var i = 0; i < files.length; i++) {
        var file = files[i];
        var fileSize = (file.size / 1024 / 1024).toFixed(2) + ' MB';
        var fileIndex = i;
        var willBeCompressed = false;
        
        // 检查是否为大图片
        if (file.type.startsWith('image/') && file.size > 2 * 1024 * 1024) {
            willBeCompressed = true;
        }
        
        var fileDiv = $('<div class="alert alert-info mb-2">');
        var fileInfoHtml = '<span><i class="fas fa-file"></i> ' + file.name + ' (' + fileSize + ')';
        
        // 如果是大图片，添加压缩提示
        if (willBeCompressed) {
            fileInfoHtml += ' <span class="badge bg-info ms-1">将自动压缩</span>';
        }
        
        fileInfoHtml += '</span>';
        
        fileDiv.html(
            '<div class="d-flex justify-content-between align-items-center">' +
                fileInfoHtml +
            '</div>' +
            '<div class="mt-2">' +
                '<label class="form-label small">附件描述（选填）</label>' +
                '<input type="text" class="form-control form-control-sm new-attachment-desc-input" ' +
                       'data-file-index="' + fileIndex + '" ' +
                       'name="attachment_descriptions[' + fileIndex + ']" ' +
                       'placeholder="请输入附件描述，如不填写将显示文件名"' +
                       'maxlength="200" autocomplete="off">' +
            '</div>'
        );
        
        preview.append(fileDiv);
    }
});

// 附件预览事件委托
$(document).on('click', '.attachment-preview-img, .attachment-preview-trigger, .attachment-preview-btn', function(e) {
    e.preventDefault();
    
    var $element = $(this);
    var attachmentId = $element.data('attachment-id');
    var previewType = $element.data('preview-type');
    var previewUrl = $element.data('preview-url');
    var fileName = $element.data('filename');
    
    showFilePreview(attachmentId, previewType, previewUrl, fileName);
});

// 显示文件预览模态框
function showFilePreview(fileId, previewType, previewUrl, fileName) {
    // 移除已存在的模态框
    $('#filePreviewModal').remove();
    
    var modalHtml = '<div class="modal fade" id="filePreviewModal" tabindex="-1">' +
        '<div class="modal-dialog modal-xl modal-dialog-centered">' +
            '<div class="modal-content">' +
                '<div class="modal-header">' +
                    '<h5 class="modal-title">文件预览 - ' + fileName + '</h5>' +
                    '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="关闭"></button>' +
                '</div>' +
                '<div class="modal-body p-0" style="max-height: 80vh; overflow: auto;">';
    
    if (previewType === 'image') {
        // 确保使用正确的预览路由
        var imagePreviewUrl = '/attachments/' + fileId + '/preview';
        modalHtml += '<div class="text-center p-3">' +
            '<img src="' + imagePreviewUrl + '" class="img-fluid" alt="' + fileName + '" style="max-height: 70vh; object-fit: contain;" onerror="this.onerror=null; this.src=\'/images/file-icon.png\'; this.alt=\'图片加载失败\';">' +
        '</div>';
    } else if (previewType === 'pdf') {
        // 对于PDF，使用预览路由，增大显示尺寸，特别针对Safari优化
        var pdfPreviewUrl = '/attachments/' + fileId + '/preview';
        modalHtml += '<div class="pdf-preview-container" style="width: 100%; height: 85vh; overflow: hidden;">' +
            '<iframe src="' + pdfPreviewUrl + '" class="pdf-preview-iframe" style="width: 100%; height: 100%; border: none; min-height: 85vh;" title="PDF预览"></iframe>' +
        '</div>';
    } else if (previewType === 'text') {
        modalHtml += '<div class="p-3">' +
            '<div id="textLoadingSpinner" class="spinner-border text-primary" role="status">' +
                '<span class="visually-hidden">加载中...</span>' +
            '</div>' +
            '<div id="textPreviewContent" class="mt-3" style="display: none;"></div>' +
        '</div>';
    } else {
        modalHtml += '<div class="text-center p-5">' +
            '<i class="fas fa-file fa-4x text-muted mb-3" aria-hidden="true"></i>' +
            '<h5>无法预览此文件类型</h5>' +
            '<p class="text-muted">请下载文件后查看</p>' +
        '</div>';
    }
    
    modalHtml += '</div>' +
        '<div class="modal-footer">' +
            '<a href="/attachments/' + fileId + '/download" class="btn btn-primary" download>' +
                '<i class="fas fa-download" aria-hidden="true"></i> 下载' +
            '</a>' +
            '<button type="button" class="btn btn-info safari-pdf-btn" style="display: none;" onclick="window.open(\'/attachments/' + fileId + '/preview\', \'_blank\')">' +
                '<i class="fas fa-external-link-alt" aria-hidden="true"></i> 在新窗口中打开' +
            '</button>' +
            '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">关闭</button>' +
        '</div>' +
            '</div>' +
        '</div>' +
    '</div>';
    
    $('body').append(modalHtml);
    
    // 显示模态框
    var modal = new bootstrap.Modal(document.getElementById('filePreviewModal'));
    modal.show();
    
    // 如果是文本文件，加载内容
    if (previewType === 'text') {
        $.get('/attachments/' + fileId + '/info', function(data) {
            // 隐藏加载指示器
            $('#textLoadingSpinner').hide();
            // 显示文本内容
            $('#textPreviewContent').show().html('<pre class="bg-light p-3 rounded" style="max-height: 60vh; overflow-y: auto;">' +
                data.content + '</pre>');
        }).fail(function() {
            // 隐藏加载指示器
            $('#textLoadingSpinner').hide();
            // 显示错误信息
            $('#textPreviewContent').show().html('<div class="alert alert-warning">无法加载文件内容</div>');
        });
    }
    
    // 模态框关闭时移除DOM
    $('#filePreviewModal').on('hidden.bs.modal', function () {
        $(this).remove();
    });
    
    // 模态框显示前处理aria-hidden问题
    $('#filePreviewModal').on('show.bs.modal', function () {
        // 确保模态框没有aria-hidden属性，避免与焦点元素冲突
        $(this).removeAttr('aria-hidden');
        
        // 检测 Safari 浏览器并优化 PDF 显示
        const isSafari = /^((?!chrome|android).)*safari/i.test(navigator.userAgent);
        if (isSafari) {
            const pdfIframe = $(this).find('.pdf-preview-iframe');
            if (pdfIframe.length) {
                // 为 Safari 添加特定样式
                pdfIframe.css({
                    'min-height': '90vh',
                    'height': '90vh',
                    '-webkit-transform': 'scale(1.0)',
                    'transform': 'scale(1.0)',
                    '-webkit-transform-origin': '0 0',
                    'transform-origin': '0 0'
                });
                
                // 调整容器
                const container = $(this).find('.pdf-preview-container');
                if (container.length) {
                    container.css({
                        'min-height': '650px',
                        'height': '90vh'
                    });
                }
                
                console.log('检测到 Safari 浏览器，已应用 PDF 显示优化');
                
                // 显示 Safari 专用的"在新窗口中打开"按钮
                const safariBtn = $(this).find('.safari-pdf-btn');
                if (safariBtn.length) {
                    safariBtn.show();
                }
            }
        }
    });
    
    // 模态框隐藏前处理焦点问题
    $('#filePreviewModal').on('hide.bs.modal', function () {
        // 在隐藏前移除焦点，避免aria-hidden与焦点冲突
        $(this).find(':focus').blur();
    });
    
    // ESC键关闭模态框
    $(document).on('keydown', function(e) {
        if (e.keyCode === 27) { // ESC key
            var modalElement = document.getElementById('filePreviewModal');
            if (modalElement) {
                var modalInstance = bootstrap.Modal.getInstance(modalElement);
                if (modalInstance) {
                    // 在隐藏前先移除焦点
                    $(modalElement).find(':focus').blur();
                    modalInstance.hide();
                }
            }
        }
    });
}

// 保持向后兼容的图片预览函数
function showImagePreview(imageSrc, fileName) {
    showFilePreview('', 'image', imageSrc, fileName);
}

// 解决工单模态框显示时初始化
$('#resolveModal').on('show.bs.modal', function (e) {
    // 调用共享的初始化函数
    window.initResolveModal({{ $workorder->id }});
});
</script>
@endsection

@section('styles')
<style>
.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 15px;
    top: 0;
    bottom: 0;
    width: 2px;
    background-color: #e9ecef;
}

.timeline-item {
    position: relative;
    margin-bottom: 20px;
}

.timeline-marker {
    position: absolute;
    left: -23px;
    top: 5px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    border: 2px solid #fff;
}

.timeline-content {
    background-color: #f8f9fa;
    padding: 10px 15px;
    border-radius: 5px;
    border-left: 3px solid #007bff;
}

.attachment-preview-img {
    width: 60px;
    height: 60px;
    object-fit: cover;
    cursor: pointer;
    transition: transform 0.2s ease;
}

.attachment-preview-img:hover {
    transform: scale(1.05);
}

.attachment-preview-trigger {
    width: 60px;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    background-color: #f8f9fa;
    border-radius: 8px;
    transition: background-color 0.2s ease;
}

.attachment-preview-trigger:hover {
    background-color: #e9ecef;
}

.attachment-preview-btn {
    margin-top: 0.5rem;
}

/* 改善文件名显示 - 智能截断保留扩展名 */
.attachment-info h6 {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 150px;
    line-height: 1.4;
}

.attachment-info small.text-muted {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 150px;
    line-height: 1.3;
    display: inline-block;
}

/* 文件名智能截断显示 - 保留开头和扩展名 */
.attachment-filename {
    max-width: 150px;
    white-space: nowrap;
    overflow: hidden;
    line-height: 1.4;
    padding: 0.25rem 0;
    position: relative;
}

.attachment-filename::after {
    content: attr(data-fullname);
    position: absolute;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background: inherit;
    color: transparent;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    padding: inherit;
}

.attachment-filename .filename-short {
    display: inline-block;
    max-width: 100%;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

/* 附件按钮组样式修复 */
.attachment-actions {
    display: flex;
    align-items: flex-start;
}

.attachment-action-btn {
    width: 60px;
    text-align: center;
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
    line-height: 1.2;
    min-height: 28px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0;
    box-sizing: border-box;
}

/* 确保表单内的按钮也有相同的样式 */
form.d-inline {
    margin: 0;
    padding: 0;
    display: block;
}

form.d-inline .attachment-action-btn {
    width: 60px;
    margin: 0;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* 附件缩略图样式 */
.attachment-thumbnail {
    flex-shrink: 0;
}

.attachment-preview-img:hover {
    transform: scale(1.05);
}

.attachment-preview-trigger:hover {
    background-color: #e9ecef;
}

/* 附件信息区域 */
.attachment-info {
    min-width: 0; /* 允许文本截断 */
}

.attachment-filename {
    max-width: 200px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    line-height: 1.4;
}

/* PDF 预览优化 - 特别是针对 Safari 浏览器 */
.pdf-preview-container {
    position: relative;
    width: 100%;
    height: 85vh;
    min-height: 600px;
    overflow: hidden;
    background-color: #f8f9fa;
    border-radius: 0.375rem;
}

.pdf-preview-iframe {
    width: 100%;
    height: 100%;
    border: none;
    min-height: 85vh;
    /* Safari 特定优化 */
    -webkit-transform: scale(1.0);
    transform: scale(1.0);
    -webkit-transform-origin: 0 0;
    transform-origin: 0 0;
    /* 确保在 Safari 中正确显示 */
    display: block;
    margin: 0;
    padding: 0;
}

/* Safari 特定媒体查询 */
@supports (-webkit-appearance: none) {
    .pdf-preview-iframe {
        /* Safari 特定缩放 */
        -webkit-transform: scale(1.0);
        transform: scale(1.0);
        min-height: 90vh;
        height: 90vh;
    }
    
    .pdf-preview-container {
        min-height: 650px;
        height: 90vh;
    }
}

/* 检测 Safari 浏览器 */
_::-webkit-full-page-media, _:future, :root .pdf-preview-iframe {
    min-height: 90vh;
    height: 90vh;
}
</style>
@endsection