@extends('layouts.app')

@section('title', '工单详情 - ' . $workorder->ticket_no)

@include('workorders._permission_checks')

@section('content')

<?php
    $statusStyles = [
        'pending' => 'bg-amber-100 text-amber-700',
        'assigned' => 'bg-blue-100 text-blue-700',
        'processing' => 'bg-indigo-100 text-indigo-700',
        'resolved' => 'bg-green-100 text-green-700',
        'completed' => 'bg-teal-100 text-teal-700',
        'closed' => 'bg-slate-100 text-slate-600',
    ];
    $priorityStyles = ['high' => 'bg-red-100 text-red-700', 'medium' => 'bg-amber-100 text-amber-700', 'low' => 'bg-green-100 text-green-700'];
?>

{{-- Header with actions --}}
<div class="flex items-center justify-between mb-6 gap-3 flex-wrap">
    <div>
        <h1 class="text-xl font-semibold text-ink">{{ $workorder->ticket_no }}</h1>
        <div class="flex items-center gap-1.5 mt-1 flex-wrap">
            @if($workorder->is_emergency)<span class="badge bg-red-100 text-red-700">紧急</span>@endif
            @if($workorder->phone_assisted)<span class="badge bg-blue-100 text-blue-700">电话协助</span>@endif
            @if($workorder->isOverdue())<span class="badge bg-orange-100 text-orange-700">超时</span>@endif
            <span class="badge {{ $priorityStyles[$workorder->priority] ?? '' }}">{{ $workorder->priority_text }}</span>
            <span class="badge {{ $statusStyles[$workorder->status] ?? '' }}">{{ $workorder->status_text }}</span>
        </div>
    </div>
    <div class="flex items-center gap-2 flex-wrap">
        <a href="{{ route('workorders.index') }}" class="btn btn-secondary btn-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7 7-7M3 12h18"/></svg>
            <span>列表</span>
        </a>
        @if($workorder->creator_id == auth()->id() && $workorder->status == 'pending')
        <a href="{{ route('workorders.edit', $workorder->id) }}" class="btn btn-secondary btn-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7 M18.5 2.5a2.1 2.1 0 0 1 3 3L12 15l-4 1 1-4z"/></svg>
            <span>编辑</span>
        </a>
        @endif
        @if($workorder->canBeAssigned() && auth()->user()->canAssignWorkorders())
        <button type="button" onclick="openModal('assignModal')" class="btn btn-primary btn-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2 M9 7a4 4 0 1 0 0-8 4 4 0 0 0 0 8z M19 8v6 M22 11h-6"/></svg>
            <span>分配</span>
        </button>
        @elseif($workorder->canBeAssigned() && auth()->user()->isEngineer() && !$workorder->assignee_id)
        <form method="POST" action="{{ route('workorders.claim', $workorder->id) }}" class="inline-block">
            @csrf
            <button type="submit" class="btn btn-primary btn-sm" onclick="return confirm('确认接单吗？')">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 11V6a2 2 0 0 1 4 0v5 M9 11V8a2 2 0 0 1 4 0v3 M13 11V9a2 2 0 0 1 4 0v6a6 6 0 0 1-6 6h-1a6 6 0 0 1-5-3l-1-2"/></svg>
                <span>接单</span>
            </button>
        </form>
        @endif
        @if($workorder->canBeStarted() && ($workorder->assignee_id == auth()->id() || auth()->user()->isAdmin() || auth()->user()->isWorkorderManager()))
        <form method="POST" action="{{ route('workorders.start', $workorder->id) }}" class="inline-block">
            @csrf
            <button type="submit" class="btn btn-secondary btn-sm" onclick="return confirm('确认开始处理？')">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 3l14 9-14 9V3z"/></svg>
                <span>开始</span>
            </button>
        </form>
        @endif
        @if(canResolveWorkorder($workorder))
        <button type="button" onclick="openModal('resolveModal')" class="btn btn-primary btn-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4 M12 22a10 10 0 1 1 0-20 10 10 0 0 1 0 20z"/></svg>
            <span>解决</span>
        </button>
        @endif
        @if($workorder->requires_signature && !$workorder->hasSignature() && in_array($workorder->status, ["processing", "resolved"]))
        <a href="{{ route("workorders.signature.create", $workorder->id) }}" class="btn btn-primary btn-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2h11a2 2 0 0 0 2-2v-5m-1.414-9.414a2 2 0 1 1 2.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
            <span>签单</span>
        </a>
        @endif
        @if($workorder->hasSignature())
        <a href="{{ route("workorders.signature.html", $workorder->id) }}" target="_blank" class="btn btn-secondary btn-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2z"/></svg>
            <span>处理单</span>
        </a>
        @endif
        @if(canInviteCollaboration($workorder))
        <button type="button" onclick="openModal('inviteModal')" class="btn btn-secondary btn-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2 M9 7a4 4 0 1 0 0-8 4 4 0 0 0 0 8z M19 8v6 M22 11h-6"/></svg>
            <span>协作</span>
        </button>
        @endif
        @if($workorder->canBeCompleted() && ($workorder->assignee_id == auth()->id() || auth()->user()->isAdmin() || auth()->user()->isWorkorderManager()))
        <button type="button" onclick="openModal('completeModal')" class="btn btn-primary btn-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M22 11.08V12a10 10 0 1 1-5.93-9.14 M22 4L12 14.01l-3-3"/></svg>
            <span>完结</span>
        </button>
        @endif
        @if($workorder->canBeClosed() && auth()->user()->canCloseWorkorders())
        <form method="POST" action="{{ route('workorders.close', $workorder->id) }}" class="inline-block">
            @csrf
            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('确认关闭？')">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18 6 6 18M6 6l12 12"/></svg>
               <span>关闭</span>
           </button>
       </form>
       @endif
        @if(auth()->user()->canRollbackWorkorder() && !empty($workorder->getRollbackOptions()))
        <button type="button" onclick="openModal('rollbackModal')" class="btn btn-secondary btn-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 11v4a1 1 0 0 0 1 1h5m9-9V7a1 1 0 0 0-1-1h-5M9 21l3-3-3-3M15 3l-3 3 3 3"/></svg>
            <span>回滚</span>
        </button>
        @endif
        @if(auth()->user()->canForceDeleteWorkorders())
        <button type="button" onclick="var i=document.getElementById('force_delete_ticket_input'); if(i) i.value=''; var e=document.getElementById('forceDeleteTicketError'); if(e) e.classList.add('hidden'); openModal('forceDeleteModal')" class="btn btn-danger btn-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0 1 16.138 21H7.862a2 2 0 0 1-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1v3M4 7h16"/></svg>
            <span>彻底删除</span>
        </button>
        @endif
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    {{-- Main column --}}
    <div class="lg:col-span-2 space-y-4">

        {{-- Workorder info --}}
        <div class="card p-5">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-3 text-sm mb-4">
                <div class="flex justify-between"><span style="color: var(--c-ink-subtle);">分类</span><span class="text-ink text-right">{{ $workorder->category?->name ?? '未设置' }}</span></div>
                <div class="flex justify-between"><span style="color: var(--c-ink-subtle);">来源</span><span class="text-ink">{{ $workorder->source_text }}</span></div>
                <div class="flex justify-between"><span style="color: var(--c-ink-subtle);">创建人</span><span class="text-ink">{{ $workorder->creator?->name ?? '--' }}</span></div>
                <div class="flex justify-between"><span style="color: var(--c-ink-subtle);">创建时间</span><span class="text-ink">{{ $workorder->created_at->format('Y-m-d H:i') }}</span></div>
            </div>

            <div>
                <p class="label">问题描述</p>
                <div class="p-3 rounded-lg text-sm" style="background-color: var(--c-muted); color: var(--c-ink);">{!! nl2br(e($workorder->description)) !!}</div>
            </div>

            @if($workorder->failure_description)
            <div class="mt-3">
                <p class="label">具体故障现象</p>
                <div class="p-3 rounded-lg text-sm bg-amber-50 border border-amber-200" style="color: var(--c-ink);">{!! nl2br(e($workorder->failure_description)) !!}</div>
            </div>
            @endif

            @if($workorder->solution)
            <div class="mt-3">
                <p class="label">解决方案</p>
                <div class="p-3 rounded-lg text-sm bg-green-50 border border-green-200" style="color: var(--c-ink);">{!! nl2br(e($workorder->solution)) !!}</div>
            </div>
            @endif

            @if($workorder->remarks)
            <div class="mt-3">
                <p class="label">备注</p>
                <div class="p-3 rounded-lg text-sm bg-blue-50 border border-blue-200" style="color: var(--c-ink);">{!! nl2br(e($workorder->remarks)) !!}</div>
            </div>
            @endif

            {{-- Materials --}}
            <div class="mt-3">
                <div class="flex items-center justify-between mb-1">
                    <p class="label mb-0">备件耗材</p>
                    @if(canEditMaterialsUsage($workorder))
                    <button type="button" onclick="openModal('materialsModal')" class="btn btn-ghost btn-sm">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7 M18.5 2.5a2.1 2.1 0 0 1 3 3L12 15l-4 1 1-4z"/></svg>
                        <span>编辑</span>
                    </button>
                    @endif
                </div>
                @if($workorder->materials_usage)
                <div class="p-3 rounded-lg text-sm bg-amber-50 border border-amber-200" style="color: var(--c-ink);">{!! nl2br(e($workorder->materials_usage)) !!}</div>
                @else
                <p class="text-sm" style="color: var(--c-ink-subtle);">暂无记录</p>
                @endif
            </div>

            {{-- Contact + location --}}
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-4 pt-4 border-t border-border text-sm">
                <div><span class="block text-xs" style="color: var(--c-ink-subtle);">联系人</span><span class="text-ink font-medium">{{ $workorder->contact_name }}</span></div>
                <div><span class="block text-xs" style="color: var(--c-ink-subtle);">电话</span><span class="text-ink font-medium">{{ $workorder->contact_phone }}</span></div>
                <div><span class="block text-xs" style="color: var(--c-ink-subtle);">邮箱</span><span class="text-ink font-medium">{{ $workorder->contact_email ?: '--' }}</span></div>
               <div><span class="block text-xs" style="color: var(--c-ink-subtle);">地点</span><span class="text-ink font-medium">
                   @if($workorder->campus_name){{ $workorder->campus_name }}@endif
                    @if($workorder->building_name) - {{ $workorder->building_name }}@endif
                   @if($workorder->location_detail) {{ $workorder->location_detail }}@endif
               </span></div>
            </div>
        </div>

        {{-- Processing logs --}}
        <div class="card">
            <div class="flex items-center justify-between p-5 pb-3">
                <h2 class="text-sm font-semibold text-ink">处理记录</h2>
                <button type="button" onclick="openModal('addLogModal')" class="btn btn-secondary btn-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/></svg>
                    <span>添加</span>
                </button>
            </div>
            <div class="px-5 pb-5">
                @if($workorder->logs->count() > 0)
                <div class="space-y-3">
                    @foreach($workorder->logs as $log)
                    <div class="flex gap-3">
                        <div class="flex flex-col items-center shrink-0">
                            <div class="w-2.5 h-2.5 rounded-full mt-1 {{ $log->is_system ? 'bg-slate-300' : 'bg-brand-600' }}"></div>
                            @if(!$loop->last)<div class="w-0.5 flex-1 mt-1" style="background-color: var(--c-border);"></div>@endif
                        </div>
                        <div class="flex-1 pb-3">
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-ink">{{ $log->action_text }}</p>
                                    @if($log->content)<p class="text-xs mt-0.5" style="color: var(--c-ink-muted);">{{ $log->content }}</p>@endif
                                </div>
                                <div class="text-right shrink-0">
                                    <p class="text-xs text-ink">{{ $log->user ? $log->user->name : '系统' }}</p>
                                    <p class="text-xs" style="color: var(--c-ink-subtle);">{{ $log->created_at->format('m-d H:i') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <div class="text-center py-6">
                    <svg class="w-10 h-10 mx-auto text-ink-subtle" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3 M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/></svg>
                    <p class="text-sm mt-2" style="color: var(--c-ink-muted);">暂无处理记录</p>
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Sidebar --}}
    <div class="lg:col-span-1 space-y-4">

        {{-- Assignee --}}
        <div class="card p-5">
            <h3 class="text-sm font-semibold text-ink mb-3">处理人</h3>
            @if($workorder->assignee)
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full flex items-center justify-center shrink-0 bg-brand-600 text-white font-medium">{{ mb_substr($workorder->assignee->name, 0, 1) }}</div>
                <div class="min-w-0">
                    <p class="text-sm font-medium text-ink truncate">{{ $workorder->assignee->name }}</p>
                    <p class="text-xs" style="color: var(--c-ink-subtle);">{{ $workorder->assignee->department?->name ?? '' }}</p>
                </div>
            </div>
            @else
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full flex items-center justify-center shrink-0" style="background-color: var(--c-muted);">
                    <svg class="w-5 h-5 text-ink-subtle" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2 M12 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8z"/></svg>
                </div>
                <p class="text-sm" style="color: var(--c-ink-muted);">未分配</p>
            </div>
            @endif
        </div>

        {{-- Stats --}}
        <div class="card p-5">
            <h3 class="text-sm font-semibold text-ink mb-3">工单统计</h3>
            <div class="grid grid-cols-2 gap-3">
                <div class="text-center p-3 rounded-lg" style="background-color: var(--c-muted);">
                    <p class="text-lg font-semibold text-ink">{{ $workorder->response_duration ?? '--' }}</p>
                    <p class="text-xs" style="color: var(--c-ink-subtle);">响应(分)</p>
                </div>
                <div class="text-center p-3 rounded-lg" style="background-color: var(--c-muted);">
                    <p class="text-lg font-semibold text-ink">{{ $workorder->processing_duration ?? '--' }}</p>
                    <p class="text-xs" style="color: var(--c-ink-subtle);">处理(分)</p>
                </div>
            </div>
            @if($workorder->visits->count() > 0)
            <div class="text-center mt-3 pt-3 border-t border-border">
                <p class="text-xs" style="color: var(--c-ink-subtle);">满意度评分</p>
                <p class="text-xl font-semibold text-brand-600">{{ $workorder->visits->first()->average_score ?? '--' }}</p>
            </div>
            @endif
        </div>

       {{-- Attachments --}}
        <div class="card p-5 scroll-mt-24" id="attachments-card">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-semibold text-ink">附件</h3>
                @if(canUploadAttachment($workorder))
                <button type="button" onclick="openModal('uploadAttachmentModal')" class="btn btn-secondary btn-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/></svg>
                    <span>上传</span>
                </button>
                @endif
            </div>
            @if($workorder->attachments->count() > 0)
            <div class="space-y-2">
                @foreach($workorder->attachments as $attachment)
                <div class="flex items-center gap-3 p-2.5 rounded-lg border border-border">
                    <div class="w-10 h-10 rounded-lg overflow-hidden shrink-0 flex items-center justify-center" style="background-color: var(--c-muted);">
                        @if($attachment->isImage())
                        <img src="{{ route('attachments.preview', $attachment->id) }}?v={{ $attachment->updated_at ? $attachment->updated_at->timestamp : $attachment->id }}" alt="{{ $attachment->original_name }}" class="w-full h-full object-cover cursor-pointer" data-file-preview data-id="{{ $attachment->id }}" data-type="{{ $attachment->preview_type }}" data-name="{{ $attachment->description ?: $attachment->original_name }}">
                        @else
                        <button type="button" data-file-preview data-id="{{ $attachment->id }}" data-type="{{ $attachment->preview_type }}" data-name="{{ $attachment->description ?: $attachment->original_name }}" class="w-full h-full flex flex-col items-center justify-center gap-0.5 cursor-pointer">
                            <?php
                                $ext = strtolower($attachment->extension);
                                $fileIcons = [
                                    'pdf'  => ['label' => 'PDF',  'color' => 'text-red-600',   'bg' => 'bg-red-50'],
                                    'doc'  => ['label' => 'DOC',  'color' => 'text-blue-600',  'bg' => 'bg-blue-50'],
                                    'docx' => ['label' => 'DOC',  'color' => 'text-blue-600',  'bg' => 'bg-blue-50'],
                                    'xls'  => ['label' => 'XLS',  'color' => 'text-green-600', 'bg' => 'bg-green-50'],
                                    'xlsx' => ['label' => 'XLS',  'color' => 'text-green-600', 'bg' => 'bg-green-50'],
                                    'ppt'  => ['label' => 'PPT',  'color' => 'text-orange-600','bg' => 'bg-orange-50'],
                                    'pptx' => ['label' => 'PPT',  'color' => 'text-orange-600','bg' => 'bg-orange-50'],
                                    'txt'  => ['label' => 'TXT',  'color' => 'text-slate-600', 'bg' => 'bg-slate-100'],
                                    'md'   => ['label' => 'MD',   'color' => 'text-slate-600', 'bg' => 'bg-slate-100'],
                                    'zip'  => ['label' => 'ZIP',  'color' => 'text-amber-600', 'bg' => 'bg-amber-50'],
                                    'rar'  => ['label' => 'RAR',  'color' => 'text-amber-600', 'bg' => 'bg-amber-50'],
                                    '7z'   => ['label' => '7Z',   'color' => 'text-amber-600', 'bg' => 'bg-amber-50'],
                                    'mp4'  => ['label' => 'MP4',  'color' => 'text-purple-600','bg' => 'bg-purple-50'],
                                    'avi'  => ['label' => 'AVI',  'color' => 'text-purple-600','bg' => 'bg-purple-50'],
                                    'mov'  => ['label' => 'MOV',  'color' => 'text-purple-600','bg' => 'bg-purple-50'],
                                    'mp3'  => ['label' => 'MP3',  'color' => 'text-pink-600',  'bg' => 'bg-pink-50'],
                                    'wav'  => ['label' => 'WAV',  'color' => 'text-pink-600',  'bg' => 'bg-pink-50'],
                                ];
                                $icon = $fileIcons[$ext] ?? ['label' => strtoupper($ext ?: 'FILE'), 'color' => 'text-slate-500', 'bg' => 'bg-slate-100'];
                            ?>
                            <span class="text-[10px] font-bold {{ $icon['color'] }} leading-none">{{ $icon['label'] }}</span>
                        </button>
                        @endif
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-ink truncate">{{ $attachment->description ?: $attachment->original_name }}</p>
                        <p class="text-xs" style="color: var(--c-ink-subtle);">{{ $attachment->formatted_file_size }}</p>
                    </div>
                    <div class="flex items-center gap-1 shrink-0">
                        <a href="{{ route('attachments.download', $attachment->id) }}" class="btn btn-ghost btn-icon btn-sm" title="下载">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4 M7 10l5 5 5-5 M12 15V3"/></svg>
                        </a>
                        @if(Auth::user()->canDeleteWorkorders() || Auth::id() === $workorder->creator_id || Auth::id() === $workorder->assignee_id)
                        <form method="POST" action="{{ route('attachments.destroy', $attachment->id) }}" class="inline" onsubmit="return confirm('确定删除？')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-ghost btn-icon btn-sm text-red-500" title="删除">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18 M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                            </button>
                        </form>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <div class="text-center py-4">
                <svg class="w-8 h-8 mx-auto text-ink-subtle" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>
                <p class="text-xs mt-1" style="color: var(--c-ink-muted);">暂无附件</p>
            </div>
            @endif
        </div>

        {{-- Personnel --}}
        @if($workorder->assignee || $workorder->collaborations()->count() > 0)
        <div class="card p-5">
            <h3 class="text-sm font-semibold text-ink mb-3">处理人员</h3>
            @if($workorder->assignee)
            <div class="flex items-center gap-2 p-2 rounded-lg bg-blue-50 mb-2">
                <div class="w-8 h-8 rounded-full flex items-center justify-center shrink-0 bg-brand-600 text-white text-xs font-medium">{{ mb_substr($workorder->assignee->name, 0, 1) }}</div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-ink truncate">{{ $workorder->assignee->name }}</p>
                </div>
                <span class="badge bg-blue-100 text-blue-700">负责人</span>
            </div>
            @endif
            @foreach($workorder->collaborations as $collaboration)
            <div class="flex items-center gap-2 p-2 rounded-lg bg-slate-50 mb-2">
                <div class="w-8 h-8 rounded-full flex items-center justify-center shrink-0" style="background-color: var(--c-muted); color: var(--c-ink-muted); font-size: 0.75rem; font-weight: 500;">{{ mb_substr($collaboration->collaborator->name, 0, 1) }}</div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-ink truncate">{{ $collaboration->collaborator->name }}</p>
                    @if($collaboration->accepted_at)<p class="text-xs text-green-600">已接受 {{ $collaboration->accepted_at->format('m-d H:i') }}</p>@endif
                </div>
                @if($collaboration->status === 'pending' && $collaboration->collaborator_id === auth()->id())
                <div class="flex items-center gap-1">
                    <form method="POST" action="{{ route('workorders.collaborations.accept', $collaboration->id) }}" class="inline">@csrf<button type="submit" class="btn btn-primary btn-sm" onclick="return confirm('确认接受？')"><span>接受</span></button></form>
                    <form method="POST" action="{{ route('workorders.collaborations.reject', $collaboration->id) }}" class="inline">@csrf<button type="submit" class="btn btn-secondary btn-sm" onclick="return confirm('确认拒绝？')"><span>拒绝</span></button></form>
                </div>
               @else
               <span class="badge bg-slate-100 text-slate-600">{{ $collaboration->status_text }}</span>
               @endif
               {{-- 负责人/管理员可取消待接受的协作邀请（对方接受后不可取消） --}}
               @if($collaboration->canBeCancelledBy(auth()->user()))
               <form method="POST" action="{{ route('workorders.collaborations.cancel', $collaboration->id) }}" class="inline">@csrf<button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('确认取消对此工程师的协作邀请？')"><span>取消邀请</span></button></form>
               @endif
           </div>
           @endforeach
        </div>
        @endif

        {{-- Visit --}}
        @if($workorder->status === 'resolved' && !$workorder->visits()->exists())
        <div class="card p-5">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm font-semibold text-ink">工单回访</h3>
                <button type="button" onclick="openModal('visitModal')" class="btn btn-secondary btn-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/></svg>
                    <span>回访</span>
                </button>
            </div>
            <p class="text-sm" style="color: var(--c-ink-muted);">该工单已解决，请添加回访记录。</p>
        </div>
        @endif

        {{-- Visit records --}}
        @if($workorder->visits()->count() > 0)
        <div class="card p-5">
            <h3 class="text-sm font-semibold text-ink mb-3">回访记录</h3>
            <div class="space-y-3">
                @foreach($workorder->visits as $visit)
                <div class="p-3 rounded-lg border border-border text-sm">
                    <div class="flex items-center justify-between mb-1">
                        <span class="font-medium text-ink">{{ $visit->visit_time ? $visit->visit_time->format('Y-m-d H:i') : '--' }}</span>
                        <span class="badge bg-slate-100 text-slate-600">{{ $visit->status_text }}</span>
                    </div>
                    <p class="text-xs" style="color: var(--c-ink-muted);">{{ $visit->visitor?->name ?? '--' }} · {{ $visit->visit_method_text }}</p>
                    @if($visit->satisfaction_score)<p class="text-xs text-brand-600 mt-1">满意度 {{ $visit->average_score }} 分</p>@endif
                    @if($visit->feedback)<p class="text-xs mt-1" style="color: var(--c-ink-muted);">{{ $visit->feedback }}</p>@endif
                </div>
                @endforeach
            </div>
        </div>
       @endif
 
        {{-- 报修人短信满意度（工单完结后，仅发过调查短信时显示） --}}
        @if($workorder->sms_survey_sent_at)
        <div class="card p-5">
            <h3 class="text-sm font-semibold text-ink mb-3">报修人评价</h3>
            <div class="flex items-center gap-4">
                @if($workorder->sms_satisfaction === 1)
                    <span class="badge bg-green-100 text-green-700">满意</span>
                @elseif($workorder->sms_satisfaction === 0)
                    <span class="badge bg-red-100 text-red-700">不满意</span>
                @else
                    <span class="badge bg-slate-100 text-slate-500">未回复</span>
                @endif
                <span class="text-xs" style="color: var(--c-ink-muted);">
                    调查发送于 {{ $workorder->sms_survey_sent_at->format('Y-m-d H:i') }}
                    @if($workorder->sms_satisfaction_at)
                        · 回复于 {{ $workorder->sms_satisfaction_at->format('Y-m-d H:i') }}
                    @endif
                </span>
            </div>
        </div>
        @endif
    </div>
</div>

{{-- ============ MODALS ============ --}}

{{-- Assign modal --}}
<div id="assignModal" class="hidden fixed inset-0 z-50 items-center justify-center p-4 bg-black/50" data-modal>
    <div class="card w-full max-w-md p-5">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-semibold text-ink">分配工单</h3>
            <button type="button" onclick="closeModal('assignModal')" class="btn btn-ghost btn-icon btn-sm"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18 6 6 18M6 6l12 12"/></svg></button>
        </div>
        <form method="POST" action="{{ route('workorders.assign', $workorder->id) }}">
            @csrf
            <label class="label" for="modal_assignee_id">处理人</label>
            <select class="input mb-4" id="modal_assignee_id" name="assignee_id" required>
                <option value="">请选择</option>
                @foreach(\App\Models\User::getAssignableEngineers() as $engineer)
                <option value="{{ $engineer->id }}">{{ $engineer->name }} - {{ $engineer->department?->name }}</option>
                @endforeach
            </select>
            <div class="flex items-center justify-end gap-2">
                <button type="button" onclick="closeModal('assignModal')" class="btn btn-secondary">取消</button>
                <button type="submit" class="btn btn-primary">确认分配</button>
            </div>
        </form>
    </div>
</div>

{{-- Resolve modal --}}
<div id="resolveModal" class="hidden fixed inset-0 z-50 items-center justify-center p-4 bg-black/50" data-modal>
    <div class="card w-full max-w-md p-5 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-semibold text-ink">解决工单</h3>
            <button type="button" onclick="closeModal('resolveModal')" class="btn btn-ghost btn-icon btn-sm"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18 6 6 18M6 6l12 12"/></svg></button>
        </div>
        <form method="POST" action="{{ route('workorders.resolve', $workorder->id) }}" id="resolveForm">
            @csrf
            <label class="label" for="solution">解决方案 <span class="text-red-500">*</span></label>
            <textarea class="input mb-4" id="solution" name="solution" rows="4" required placeholder="请描述解决方案"></textarea>
            <label class="label" for="resolve_materials_usage">备件耗材使用</label>
            <label class="flex items-center gap-2 mb-2 cursor-pointer">
                <input type="checkbox" id="no_materials" name="no_materials" value="1" class="rounded border-border-strong w-4 h-4">
                <span class="text-sm" style="color: var(--c-ink-muted);">无备件耗材</span>
            </label>
            <div id="materials_usage_div">
                <textarea class="input" id="resolve_materials_usage" name="materials_usage" rows="3" placeholder="名称、规格、数量等"></textarea>
            </div>
            <div class="flex items-center justify-end gap-2 mt-4">
                <button type="button" onclick="closeModal('resolveModal')" class="btn btn-secondary">取消</button>
                <button type="submit" class="btn btn-primary">确认解决</button>
            </div>
        </form>
    </div>
</div>

{{-- Complete modal --}}
<div id="completeModal" class="hidden fixed inset-0 z-50 items-center justify-center p-4 bg-black/50" data-modal>
    <div class="card w-full max-w-md p-5">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-semibold text-ink">完结工单</h3>
            <button type="button" onclick="closeModal('completeModal')" class="btn btn-ghost btn-icon btn-sm"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18 6 6 18M6 6l12 12"/></svg></button>
        </div>
        <form method="POST" action="{{ route('workorders.complete', $workorder->id) }}">
            @csrf
            <label class="label" for="completion_note">完结说明 <span class="text-red-500">*</span></label>
            <textarea class="input mb-4" id="completion_note" name="completion_note" rows="4" required placeholder="请输入完结说明"></textarea>
            <div class="flex items-center justify-end gap-2">
                <button type="button" onclick="closeModal('completeModal')" class="btn btn-secondary">取消</button>
                <button type="submit" class="btn btn-primary">确认完结</button>
            </div>
        </form>
    </div>
</div>

{{-- Add log modal --}}
<div id="addLogModal" class="hidden fixed inset-0 z-50 items-center justify-center p-4 bg-black/50" data-modal>
    <div class="card w-full max-w-md p-5">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-semibold text-ink">添加处理记录</h3>
            <button type="button" onclick="closeModal('addLogModal')" class="btn btn-ghost btn-icon btn-sm"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18 6 6 18M6 6l12 12"/></svg></button>
        </div>
        <form method="POST" action="{{ route('workorders.logs.add', $workorder->id) }}">
            @csrf
            <label class="label" for="log_content">记录内容 <span class="text-red-500">*</span></label>
            <textarea class="input mb-4" id="log_content" name="content" rows="4" required placeholder="请输入处理记录"></textarea>
            <div class="flex items-center justify-end gap-2">
                <button type="button" onclick="closeModal('addLogModal')" class="btn btn-secondary">取消</button>
                <button type="submit" class="btn btn-primary">添加记录</button>
            </div>
        </form>
    </div>
</div>

{{-- Upload attachment modal --}}
@if(canUploadAttachment($workorder))
<div id="uploadAttachmentModal" class="hidden fixed inset-0 z-50 items-center justify-center p-4 bg-black/50" data-modal>
    <div class="card w-full max-w-md p-5">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-semibold text-ink">上传附件</h3>
            <button type="button" onclick="closeModal('uploadAttachmentModal')" class="btn btn-ghost btn-icon btn-sm"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18 6 6 18M6 6l12 12"/></svg></button>
        </div>
       <form method="POST" action="{{ route('workorders.attachments.upload', $workorder->id) }}" enctype="multipart/form-data" id="attachmentUploadForm">
           @csrf
           <label class="label">选择文件或拍照</label>
           <div class="flex gap-2 mb-1">
               <button type="button" onclick="openCameraModal('new_attachments')" class="btn btn-secondary flex-1">
                   <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z M12 17a4 4 0 1 0 0-8 4 4 0 0 0 0 8z"/></svg>
                   <span>拍照</span>
               </button>
               <button type="button" onclick="document.getElementById('new_attachments').click()" class="btn btn-secondary flex-1">
                   <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4 M7 10l5 5 5-5 M12 15V3"/></svg>
                   <span>选择文件</span>
               </button>
           </div>
           <input type="file" class="sr-only" id="new_attachments" name="attachments[]" multiple accept="*/*" onchange="handleAttachmentSelect(this)">

           <div id="attachmentFileName" class="text-xs mt-1" style="color: var(--c-ink-subtle);">未选择文件</div>
           <p class="text-xs" style="color: var(--c-ink-subtle);">单个最大 10MB，最多 5 个</p>
           <div id="newAttachmentPreview" class="mt-3 space-y-2"></div>
           <div class="flex items-center justify-end gap-2 mt-4">
               <button type="button" onclick="closeModal('uploadAttachmentModal')" class="btn btn-secondary">取消</button>
                <button type="submit" class="btn btn-primary" id="attachmentUploadBtn">上传</button>
           </div>
          <div id="attachmentUploadProgress" class="hidden mt-3">
              <div class="flex items-center justify-between mb-1 text-xs" style="color: var(--c-ink-subtle);">
                  <span id="attachmentUploadStatus">上传中...</span>
                  <span id="attachmentUploadPercent">0%</span>
              </div>
              <div class="w-full bg-surface-muted rounded-full h-2 overflow-hidden">
                  <div id="attachmentUploadBar" class="h-2 rounded-full transition-all duration-200" style="width:0%;background:linear-gradient(90deg,#3b82f6,#6366f1);"></div>
              </div>
          </div>
     </form>
    </div>
</div>
@endif

{{-- Materials edit modal --}}
@if(canEditMaterialsUsage($workorder))
<div id="materialsModal" class="hidden fixed inset-0 z-50 items-center justify-center p-4 bg-black/50" data-modal>
    <div class="card w-full max-w-md p-5">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-semibold text-ink">编辑备件耗材</h3>
            <button type="button" onclick="closeModal('materialsModal')" class="btn btn-ghost btn-icon btn-sm"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18 6 6 18M6 6l12 12"/></svg></button>
        </div>
        <form method="POST" action="{{ route('workorders.materials.update', $workorder->id) }}">
            @csrf
            <label class="label" for="edit_materials_usage">备件耗材使用 <span class="text-red-500">*</span></label>
            <textarea class="input mb-4" id="edit_materials_usage" name="materials_usage" rows="5" required placeholder="名称、规格、数量等">{{ $workorder->materials_usage ?? '' }}</textarea>
            <div class="flex items-center justify-end gap-2">
                <button type="button" onclick="closeModal('materialsModal')" class="btn btn-secondary">取消</button>
                <button type="submit" class="btn btn-primary">保存</button>
            </div>
        </form>
    </div>
</div>
@endif

{{-- Invite collaboration modal --}}
@if(canInviteCollaboration($workorder))
<div id="inviteModal" class="hidden fixed inset-0 z-50 items-center justify-center p-4 bg-black/50" data-modal>
    <div class="card w-full max-w-md p-5">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-semibold text-ink">邀请协作工程师</h3>
            <button type="button" onclick="closeModal('inviteModal')" class="btn btn-ghost btn-icon btn-sm"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18 6 6 18M6 6l12 12"/></svg></button>
        </div>
        <form method="POST" action="{{ route('workorders.invite.collaborator', $workorder->id) }}">
            @csrf
            <label class="label" for="collaborator_id">选择工程师 <span class="text-red-500">*</span></label>
            <select class="input mb-4" id="collaborator_id" name="collaborator_id" required>
                <option value="">请选择</option>
                @foreach(\App\Models\User::getAssignableEngineers() as $engineer)
                @if($engineer->id != auth()->id() && $engineer->id != $workorder->assignee_id)
                <option value="{{ $engineer->id }}">{{ $engineer->name }} - {{ $engineer->department?->name }}</option>
                @endif
                @endforeach
            </select>
            <label class="label" for="invitation_reason">邀请原因</label>
            <textarea class="input mb-4" id="invitation_reason" name="invitation_reason" rows="3" placeholder="请说明邀请原因"></textarea>
            <div class="flex items-center justify-end gap-2">
               <button type="button" onclick="closeModal('inviteModal')" class="btn btn-secondary">取消</button>
               <button type="submit" class="btn btn-primary">发送邀请</button>
           </div>
       </form>
   </div>
</div>
@endif
{{-- Rollback status modal (工单管理员 / 系统管理员) --}}
@if(auth()->user()->canRollbackWorkorder() && !empty($workorder->getRollbackOptions()))
<div id="rollbackModal" class="hidden fixed inset-0 z-50 items-center justify-center p-4 bg-black/50" data-modal>
    <div class="card w-full max-w-md p-5">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-semibold text-ink">回滚工单状态</h3>
            <button type="button" onclick="closeModal('rollbackModal')" class="btn btn-ghost btn-icon btn-sm"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18 6 6 18M6 6l12 12"/></svg></button>
        </div>
        <form method="POST" action="{{ route('workorders.rollback', $workorder->id) }}">
            @csrf
            <div class="p-3 rounded-lg mb-4 text-sm bg-amber-50 border border-amber-200" style="color: var(--c-ink);">
                当前状态：<span class="font-medium">{{ $workorder->status_text }}</span><br>
                <span style="color: var(--c-ink-muted);">回滚会清除目标节点之后产生的处理记录（处理人、处理时间、协作邀请等），并写入一条带原因的审计日志。</span>
            </div>
            <label class="label" for="target_status">回滚到 <span class="text-red-500">*</span></label>
            <select class="input mb-4" id="target_status" name="target_status" required>
                <option value="">请选择回滚节点</option>
                @foreach($workorder->getRollbackOptions() as $status => $label)
                <option value="{{ $status }}">{{ $label }}</option>
                @endforeach
            </select>
            <label class="label" for="rollback_reason">回滚原因</label>
            <textarea class="input mb-4" id="rollback_reason" name="reason" rows="3" placeholder="请说明回滚原因（将记入审计日志）"></textarea>
            <div class="flex items-center justify-end gap-2">
                <button type="button" onclick="closeModal('rollbackModal')" class="btn btn-secondary">取消</button>
                <button type="submit" class="btn btn-danger" onclick="return confirm('确认回滚？目标节点之后产生的处理记录将被清除。')">确认回滚</button>
            </div>
        </form>
    </div>
</div>
@endif


{{-- Visit modal --}}
@if($workorder->status === 'resolved' && !$workorder->visits()->exists())
<div id="visitModal" class="hidden fixed inset-0 z-50 items-center justify-center p-4 bg-black/50" data-modal>
    <div class="card w-full max-w-2xl p-5 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-semibold text-ink">工单回访</h3>
            <button type="button" onclick="closeModal('visitModal')" class="btn btn-ghost btn-icon btn-sm"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18 6 6 18M6 6l12 12"/></svg></button>
        </div>
        <form method="POST" action="{{ route('workorders.visit.store', $workorder->id) }}">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="label" for="visit_method">回访方式 <span class="text-red-500">*</span></label>
                    <select class="input" id="visit_method" name="visit_method" required>
                        <option value="">请选择</option>
                        @foreach(\App\Models\WorkorderVisit::getVisitMethodOptions() as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="label" for="visit_time">回访时间 <span class="text-red-500">*</span></label>
                    <input type="datetime-local" class="input" id="visit_time" name="visit_time" required>
                </div>
            </div>
            <label class="label" for="visit_content">回访内容 <span class="text-red-500">*</span></label>
            <textarea class="input mb-4" id="visit_content" name="visit_content" rows="3" required placeholder="请记录回访内容"></textarea>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-4">
                @foreach(['satisfaction_score' => '响应速度', 'service_quality_score' => '服务质量', 'professional_score' => '专业水平', 'overall_score' => '总体满意度'] as $field => $labelText)
                <div>
                    <label class="label" for="{{ $field }}">{{ $labelText }}</label>
                    <select class="input" id="{{ $field }}" name="{{ $field }}">
                        <option value="">评分</option>
                        @foreach(\App\Models\WorkorderVisit::getScoreOptions() as $score => $text)<option value="{{ $score }}">{{ $text }}</option>@endforeach
                    </select>
                </div>
                @endforeach
            </div>
            <label class="label" for="feedback">用户反馈</label>
            <textarea class="input mb-4" id="feedback" name="feedback" rows="2" placeholder="请记录用户反馈"></textarea>
            <label class="flex items-center gap-2 mb-2 cursor-pointer">
                <input type="checkbox" id="need_follow_up" name="need_follow_up" value="1" class="rounded border-border-strong w-4 h-4">
                <span class="text-sm" style="color: var(--c-ink-muted);">需要跟进</span>
            </label>
            <label class="label" for="follow_up_note">跟进说明</label>
            <textarea class="input mb-4" id="follow_up_note" name="follow_up_note" rows="2" placeholder="如需跟进，请说明"></textarea>
            <div class="flex items-center justify-end gap-2">
                <button type="button" onclick="closeModal('visitModal')" class="btn btn-secondary">取消</button>
                <button type="submit" class="btn btn-primary">提交回访</button>
            </div>
        </form>
    </div>
</div>
@endif

{{-- Force delete modal（管理员二次确认：输入工单编号） --}}
<div id="forceDeleteModal" class="hidden fixed inset-0 z-50 items-center justify-center p-4 bg-black/50" data-modal>
    <div class="card w-full max-w-md shadow-xl">
        <div class="flex items-center justify-between px-5 py-3 border-b border-border">
            <h3 class="font-medium text-red-600">彻底删除工单</h3>
            <button type="button" onclick="closeModal('forceDeleteModal')" class="btn btn-ghost btn-icon btn-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18 6 6 18M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="p-5 space-y-4">
            <p class="text-sm text-ink-muted">
                即将彻底删除工单 <strong class="text-red-600">{{ $workorder->ticket_no }}</strong>，
                并同时删除其附件及所有关联记录，<span class="text-red-600 font-medium">不可恢复</span>。
            </p>
            <div>
                <label class="label" for="force_delete_ticket_input">
                    请输入工单编号 <strong class="text-red-600">{{ $workorder->ticket_no }}</strong> 以确认
                </label>
                <input type="text" class="input" id="force_delete_ticket_input" placeholder="输入工单编号确认" autocomplete="off">
                <p id="forceDeleteTicketError" class="hidden text-xs text-red-600 mt-1">输入的工单编号不正确</p>
            </div>
        </div>
        <form method="POST" action="{{ route('workorders.force-delete', $workorder->id) }}" id="forceDeleteForm" data-prevent-double-submit>
            @csrf
            <div class="flex items-center justify-end gap-2 px-5 py-3 border-t border-border">
                <button type="button" onclick="closeModal('forceDeleteModal')" class="btn btn-secondary">取消</button>
                <button type="submit" class="btn btn-danger">确认彻底删除</button>
            </div>
        </form>
    </div>
</div>

{{-- File preview modal --}}
<div id="filePreviewModal" class="hidden fixed inset-0 z-[60] flex items-center justify-center p-4 bg-black/70" onclick="if(event.target===this)closeFilePreview()">
    <div class="card max-w-4xl w-full max-h-[90vh] overflow-hidden flex flex-col">
        <div class="flex items-center justify-between px-5 py-3 border-b border-border shrink-0">
            <span id="filePreviewTitle" class="text-sm font-medium text-ink truncate"></span>
            <button type="button" onclick="closeFilePreview()" class="btn btn-ghost btn-icon btn-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18 6 6 18M6 6l12 12"/></svg>
            </button>
        </div>
        <div id="filePreviewBody" class="flex-1 overflow-auto" style="background-color: var(--c-muted);"></div>
        <div class="flex items-center justify-end gap-2 px-5 py-3 border-t border-border shrink-0">
            <a id="filePreviewDownload" href="#" class="btn btn-primary btn-sm" download>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4 M7 10l5 5 5-5 M12 15V3"/></svg>
                <span>下载</span>
            </a>
            <button type="button" onclick="closeFilePreview()" class="btn btn-secondary btn-sm">关闭</button>
        </div>
    </div>
</div>

@include('workorders._camera')
{{-- End modals --}}
@endsection

@section('scripts')
@include('partials._double_submit_guard')
<script>
// openModal/closeModal 由 layouts/app 全局提供
// Close on backdrop click
document.querySelectorAll('[data-modal]').forEach(function(modal) {
    modal.addEventListener('click', function(e) { if (e.target === this) closeModal(this.id); });
});
// ESC to close any modal
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        document.querySelectorAll('[data-modal]').forEach(function(m) { if (!m.classList.contains('hidden')) closeModal(m.id); });
        closeFilePreview();
    }
});

// 彻底删除：输入工单编号二次确认
(function() {
    var form = document.getElementById('forceDeleteForm');
    if (!form) return;
    form.addEventListener('submit', function(e) {
        var input = document.getElementById('force_delete_ticket_input');
        var err = document.getElementById('forceDeleteTicketError');
        var expected = '{{ $workorder->ticket_no }}';
        if (!input || input.value.trim() !== expected) {
            e.preventDefault();
            if (err) err.classList.remove('hidden');
            if (input) input.focus();
            return false;
        }
        if (err) err.classList.add('hidden');
    });
})();

// No materials checkbox
document.getElementById('no_materials')?.addEventListener('change', function() {
    var div = document.getElementById('materials_usage_div');
    if (div) div.style.display = this.checked ? 'none' : 'block';
});

// New attachment preview in upload modal
// Attachment upload via AJAX with progress bar; disable button while uploading, close modal on success.
(function() {
    function initAttachmentUpload() {
        var form = document.getElementById('attachmentUploadForm');
        var btn = document.getElementById('attachmentUploadBtn');
        var progWrap = document.getElementById('attachmentUploadProgress');
        var progressBar = document.getElementById('attachmentUploadBar');
        var progPercent = document.getElementById('attachmentUploadPercent');
        var progStatus = document.getElementById('attachmentUploadStatus');
        if (!form || !btn || form.dataset.ajaxBound) return;
        form.dataset.ajaxBound = '1';
        // Show the progress bar the moment files are chosen, so the user sees the
        // upload is wired up even before tapping the upload button (matters on slow links).
        var fileInput = document.getElementById('new_attachments');
        if (fileInput && !fileInput.dataset.progBound) {
            fileInput.dataset.progBound = '1';
            fileInput.addEventListener('change', function() {
                if (fileInput.files && fileInput.files.length) {
                    progWrap.classList.remove('hidden');
                    progStatus.textContent = '已选择，点击上传';
                    progPercent.textContent = '0%';
                    progressBar.style.width = '0%';
                }
            });
        }
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            // Guard against double-submit: once uploading, the button is disabled
            // and further taps are ignored.
            if (btn.disabled) return;
            var formData = new FormData(form);
            var xhr = new XMLHttpRequest();
            xhr.open('POST', form.getAttribute('action'));
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            var token = document.querySelector('meta[name="csrf-token"]');
            if (token) xhr.setRequestHeader('X-CSRF-TOKEN', token.getAttribute('content'));
            // disable button (prevents repeat clicks) + reset progress UI
            btn.disabled = true;
            btn.dataset.originalText = btn.textContent;
            btn.innerHTML = '<svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg> 上传中...';
            progWrap.classList.remove('hidden');
            progressBar.style.width = '0%';
            progPercent.textContent = '0%';
            progStatus.textContent = '上传中...';
            xhr.upload.addEventListener('progress', function(ev) {
                if (ev.lengthComputable) {
                    var pct = Math.round((ev.loaded / ev.total) * 100);
                    progressBar.style.width = pct + '%';
                    progPercent.textContent = pct + '%';
                    if (pct < 100) progStatus.textContent = '上传中...';
                }
            });
            // Bytes are uploaded at 100%; the server still needs time to save the files.
            // Show a clear "processing" state so users do not assume it stalled.
            xhr.upload.addEventListener('load', function() {
                progressBar.style.width = '100%';
                progPercent.textContent = '100%';
                progStatus.textContent = '上传完成，正在保存...';
            });
            xhr.addEventListener('load', function() {
                var data = null;
                try { data = xhr.responseText ? JSON.parse(xhr.responseText) : null; } catch (e) {}
                if (xhr.status >= 200 && xhr.status < 300 && (!data || data.success)) {
                    progPercent.textContent = '100%';
                    progStatus.textContent = '上传成功';
                    progressBar.style.width = '100%';
                    // Close the modal and refresh immediately, scrolling to the attachments card.
                    closeModal('uploadAttachmentModal');
                    location.href = location.pathname + '?t=' + Date.now() + '#attachments-card';
                } else {
                    resetButton();
                    progWrap.classList.add('hidden');
                    var msg = (data && data.message) ? data.message : ('上传失败，请重试（' + xhr.status + '）');
                    alert(msg);
                }
            });
            xhr.addEventListener('error', function() {
                resetButton();
                progWrap.classList.add('hidden');
                alert('网络错误，上传失败，请重试');
            });
            function resetButton() {
                btn.disabled = false;
                btn.textContent = btn.dataset.originalText || '上传';
            }
            xhr.send(formData);
        });
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAttachmentUpload);
    } else {
        initAttachmentUpload();
    }
})();

// Highlight attachments card after reload to #attachments-card (post-upload)
(function() {
    function highlightAttachments() {
        var el = document.getElementById('attachments-card');
        if (!el) return;
        if (el.scrollIntoView) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
        el.classList.add('highlight-flash');
        setTimeout(function() { el.classList.remove('highlight-flash'); }, 2600);
    }
    if (location.hash === '#attachments-card') {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', highlightAttachments);
        } else {
            setTimeout(highlightAttachments, 80);
        }
    }
})();

// Handle attachment selection (file picker or camera)
function handleAttachmentSelect(input) {
    var preview = document.getElementById('newAttachmentPreview');
    var nameDiv = document.getElementById('attachmentFileName');
    preview.innerHTML = "";
    var allFiles = [];
    var fileInput = document.getElementById('new_attachments');
    var camInput = document.getElementById('camera_attachments');
    if (fileInput && fileInput.files.length) allFiles = allFiles.concat(Array.from(fileInput.files));
    if (camInput && camInput.files.length) allFiles = allFiles.concat(Array.from(camInput.files));
    if (nameDiv) {
        nameDiv.textContent = allFiles.length > 0 ? ("已选择 " + allFiles.length + " 个文件") : "未选择文件";
    }
    for (var i = 0; i < allFiles.length; i++) {
        (function(file, idx) {
            var sizeMB = (file.size / 1024 / 1024).toFixed(2);
            var compress = file.type.startsWith('image/') && file.size > 2 * 1024 * 1024;
            var item = document.createElement('div');
            item.className = 'p-2.5 rounded-lg border border-border text-sm';
            item.innerHTML = '<div class="flex items-center gap-2"><span class="text-ink">' + file.name + '</span><span class="text-xs text-ink-subtle">' + sizeMB + ' MB</span>' + (compress ? '<span class="badge bg-blue-100 text-blue-700">压缩</span>' : '') + '</div>' +
                '<input type="text" class="input mt-2" name="attachment_descriptions[' + idx + ']" placeholder="附件描述（选填）" maxlength="200">';
            preview.appendChild(item);
        })(allFiles[i], i);
    }
}

// File preview
function showFilePreview(fileId, previewType, previewUrl, fileName) {
    var body = document.getElementById('filePreviewBody');
    var title = document.getElementById('filePreviewTitle');
    var dl = document.getElementById('filePreviewDownload');
    title.textContent = fileName || '文件预览';
    dl.href = '/attachments/' + fileId + '/download';

    if (previewType === 'image') {
        var img = document.createElement('img');
        img.src = '/attachments/' + fileId + '/preview?t=' + Date.now();
        img.alt = fileName || '预览';
        img.className = 'max-h-[75vh] rounded-lg';
        img.onerror = function() { img.style.display = 'none'; };
        var wrap = document.createElement('div');
        wrap.className = 'flex items-center justify-center p-4';
        wrap.appendChild(img);
        body.innerHTML = '';
        body.appendChild(wrap);
    } else if (previewType === 'pdf') {
        body.innerHTML = '<iframe src="/attachments/' + fileId + '/preview?t=' + Date.now() + '" class="w-full border-none" style="height: 75vh;" title="PDF预览"></iframe>';
    } else if (previewType === 'text') {
        body.innerHTML = '<div class="p-4"><div id="textLoading" class="text-center text-ink-muted">加载中...</div><pre id="textContent" class="hidden p-3 rounded-lg overflow-auto text-sm" style="background-color: var(--c-card); color: var(--c-ink); max-height: 70vh;"></pre></div>';
        fetch('/attachments/' + fileId + '/info').then(function(r) { return r.json(); }).then(function(data) {
            document.getElementById('textLoading').classList.add('hidden');
            var pre = document.getElementById('textContent');
            pre.classList.remove('hidden');
            pre.textContent = data.content || '(空文件)';
        }).catch(function() {
            document.getElementById('textLoading').textContent = '加载失败';
        });
    } else {
        body.innerHTML = '<div class="flex flex-col items-center justify-center p-12"><svg class="w-12 h-12 text-ink-subtle mb-3" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z M14 2v6h6"/></svg><p class="text-sm text-ink-muted">无法预览此文件类型</p><p class="text-xs text-ink-subtle mt-1">请下载后查看</p></div>';
    }

    var modal = document.getElementById('filePreviewModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.body.classList.add('overflow-hidden');
}
function closeFilePreview() {
    var modal = document.getElementById('filePreviewModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    document.body.classList.remove('overflow-hidden');
    document.getElementById('filePreviewBody').innerHTML = '';
}

// 附件预览事件委托：文件名等用户输入经 dataset 读取 + textContent 渲染，杜绝 onclick 字符串注入
document.addEventListener('click', function(e) {
    var el = e.target.closest('[data-file-preview]');
    if (!el) return;
    showFilePreview(
        parseInt(el.dataset.id, 10),
        el.dataset.type,
        '',
        el.dataset.name
    );
});
</script>
@endsection
