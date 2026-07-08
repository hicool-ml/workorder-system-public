@extends('layouts.app')

@section('title', '编辑工单 - ' . $workorder->ticket_no)

@section('content')

{{-- Page header --}}
<div class="flex items-center justify-between mb-6 gap-3 flex-wrap">
    <div>
        <h1 class="text-xl font-semibold text-ink">编辑工单</h1>
        <p class="text-sm text-ink-muted mt-0.5">{{ $workorder->ticket_no }}</p>
    </div>
    <a href="{{ route('workorders.show', $workorder->id) }}" class="btn btn-secondary">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7 7-7M3 12h18"/></svg>
        <span>返回详情</span>
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    {{-- Main form --}}
    <div class="lg:col-span-2 space-y-4">
        <form method="POST" action="{{ route('workorders.update', $workorder->id) }}" enctype="multipart/form-data" id="workorderEditForm">
            @csrf
            @method('PUT')

            {{-- Categories + description --}}
            <div class="card p-5">
                <h2 class="text-sm font-semibold text-ink mb-4 flex items-center gap-2">
                    <svg class="w-4 h-4 text-brand-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
                    工单信息
                </h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="label" for="category_main">工单大类 <span class="text-red-500">*</span></label>
                        <select class="input" id="category_main" name="category_main" required>
                            <option value="">请选择工单大类</option>
                            @foreach($categories['main'] as $category)
                            <option value="{{ $category->id }}" data-prefix="{{ $category->ticket_prefix }}" data-hours="{{ $category->default_hours }}" {{ old('category_main') == $category->id || ($workorder->category && $workorder->category->parent_id == null && $workorder->category->id == $category->id) ? 'selected' : '' }}>{{ $category->name }}</option>
                            @endforeach
                        </select>
                        @error('category_main')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="label" for="category_sub">故障分类 <span class="text-red-500">*</span></label>
                        <select class="input" id="category_sub" name="category_sub" required>
                            <option value="">请先选择工单大类</option>
                            @if($workorder->category && $workorder->category->parent_id)
                            <option value="{{ $workorder->category->id }}" selected>{{ $workorder->category->name }}</option>
                            @endif
                        </select>
                        @error('category_sub')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>
                <div class="mt-4">
                    <label class="label" for="description">问题描述 <span class="text-red-500">*</span></label>
                    <textarea class="input" id="description" name="description" rows="5" required placeholder="请详细描述问题">{{ old('description', $workorder->description) }}</textarea>
                </div>
            </div>

            {{-- Contact info --}}
            <div class="card p-5">
                <h2 class="text-sm font-semibold text-ink mb-4 flex items-center gap-2">
                    <svg class="w-4 h-4 text-brand-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2 M12 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8z"/></svg>
                    联系信息
                </h2>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="label" for="contact_name">联系人 <span class="text-red-500">*</span></label>
                        <input type="text" class="input" id="contact_name" name="contact_name" value="{{ old('contact_name', $workorder->contact_name) }}" required maxlength="100">
                    </div>
                    <div>
                        <label class="label" for="contact_phone">联系电话 <span class="text-red-500">*</span></label>
                        <input type="tel" class="input" id="contact_phone" name="contact_phone" value="{{ old('contact_phone', $workorder->contact_phone) }}" required maxlength="20">
                    </div>
                    <div>
                        <label class="label" for="contact_email">联系邮箱</label>
                        <input type="email" class="input" id="contact_email" name="contact_email" value="{{ old('contact_email', $workorder->contact_email) }}" maxlength="100">
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
                    <div>
                        <label class="label" for="source">工单来源 <span class="text-red-500">*</span></label>
                        <select class="input" id="source" name="source" required>
                            <option value="phone" {{ old('source', $workorder->source) == 'phone' ? 'selected' : '' }}>电话</option>
                            <option value="web" {{ old('source', $workorder->source) == 'web' ? 'selected' : '' }}>网络</option>
                            <option value="scene" {{ old('source', $workorder->source) == 'scene' ? 'selected' : '' }}>现场</option>
                            <option value="email" {{ old('source', $workorder->source) == 'email' ? 'selected' : '' }}>邮件</option>
                            <option value="other" {{ old('source', $workorder->source) == 'other' ? 'selected' : '' }}>其他</option>
                            <option value="custom" {{ old('source', $workorder->source) == 'custom' ? 'selected' : '' }}>添加新渠道</option>
                        </select>
                    </div>
                    <div>
                        <label class="label">优先级 <span class="text-red-500">*</span></label>
                        <div class="flex items-center gap-2 mt-1">
                            @foreach(['low' => '低', 'medium' => '中', 'high' => '高'] as $val => $label)
                            <label class="flex-1 cursor-pointer">
                                <input type="radio" name="priority" value="{{ $val }}" class="peer sr-only" {{ old('priority', $workorder->priority) == $val ? 'checked' : '' }}>
                                <span class="block text-center py-2 px-3 rounded-lg border border-border-strong text-sm font-medium transition-colors peer-checked:bg-brand-600 peer-checked:text-white peer-checked:border-brand-600" style="color: var(--c-ink-muted);">{{ $label }}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div id="custom_source_row" class="hidden mt-4">
                    <label class="label" for="custom_source">新渠道名称 <span class="text-red-500">*</span></label>
                    <input type="text" class="input" id="custom_source" name="custom_source" value="{{ old('custom_source', $workorder->custom_source) }}" maxlength="50" placeholder="请输入新的报修渠道名称">
                </div>
            </div>

            {{-- Location --}}
            <div class="card p-5">
                <h2 class="text-sm font-semibold text-ink mb-4 flex items-center gap-2">
                    <svg class="w-4 h-4 text-brand-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z M12 13a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/></svg>
                    位置信息
                </h2>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="label" for="campus">校区 <span class="text-red-500">*</span></label>
                        <select class="input" id="campus" name="campus" required>
                            <option value="">请选择校区</option>
                            <option value="old_campus" {{ old('campus', $workorder->campus) == 'old_campus' ? 'selected' : '' }}>老校区</option>
                            <option value="new_campus" {{ old('campus', $workorder->campus) == 'new_campus' ? 'selected' : '' }}>新校区</option>
                            <option value="asean_campus" {{ old('campus', $workorder->campus) == 'asean_campus' ? 'selected' : '' }}>东盟校区</option>
                        </select>
                    </div>
                    <div>
                        <label class="label" for="building">楼栋 <span class="text-red-500">*</span></label>
                        <select class="input" id="building" name="building" required>
                            <option value="">请先选择校区</option>
                        </select>
                    </div>
                    <div>
                        <label class="label" for="location_detail">详细地址</label>
                        <input type="text" class="input" id="location_detail" name="location_detail" value="{{ old('location_detail', $workorder->location_detail) }}" maxlength="500" placeholder="如：301室">
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
                    <div>
                        <label class="label" for="appointment_time">预约时间</label>
                        <input type="datetime-local" class="input" id="appointment_time" name="appointment_time" value="{{ old('appointment_time', $workorder->appointment_time ? $workorder->appointment_time->format('Y-m-d\TH:i') : '') }}">
                    </div>
                    <div>
                        <label class="label" for="time_limit_hours">处理时限（小时）</label>
                        <input type="number" class="input" id="time_limit_hours" name="time_limit_hours" value="{{ old('time_limit_hours', $workorder->time_limit_hours) }}" min="1" max="168" step="1" placeholder="默认根据工单类型设置">
                    </div>
                </div>
            </div>

            {{-- Attributes --}}
            <div class="card p-5">
                <h2 class="text-sm font-semibold text-ink mb-4 flex items-center gap-2">
                    <svg class="w-4 h-4 text-brand-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5a1.99 1.99 0 0 1 1.4.6l7 7a2 2 0 0 1 0 2.8l-5.6 5.6a2 2 0 0 1-2.8 0l-7-7A2 2 0 0 1 3 12V7a4 4 0 0 1 4-4z"/></svg>
                    工单属性
                </h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="label" for="department_id">所属部门</label>
                        <select class="input" id="department_id" name="department_id">
                            <option value="">请选择部门</option>
                            @foreach($departments as $department)
                            <option value="{{ $department->id }}" {{ old('department_id', $workorder->department_id) == $department->id ? 'selected' : '' }}>{{ $department->full_path ?? $department->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex flex-col gap-2 pt-6">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" id="need_visit" name="need_visit" value="1" class="rounded border-border-strong w-4 h-4" {{ old('need_visit', $workorder->need_visit) ? 'checked' : '' }}>
                            <span class="text-sm" style="color: var(--c-ink-muted);">需要回访</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" id="is_emergency" name="is_emergency" value="1" class="rounded border-border-strong w-4 h-4" {{ old('is_emergency', $workorder->is_emergency) ? 'checked' : '' }}>
                            <span class="text-sm" style="color: var(--c-ink-muted);">紧急工单</span>
                        </label>
                    </div>
                </div>
            </div>

            @if(in_array($workorder->status, ['processing', 'resolved', 'completed', 'closed']))
            {{-- Processing info --}}
            <div class="card p-5">
                <h2 class="text-sm font-semibold text-ink mb-4 flex items-center gap-2">
                    <svg class="w-4 h-4 text-brand-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    处理信息
                </h2>
                <div class="mb-4">
                    <label class="label" for="materials_usage">备件耗材使用</label>
                    <textarea class="input" id="materials_usage" name="materials_usage" rows="3" placeholder="名称、规格、数量等">{{ old('materials_usage', $workorder->materials_usage) }}</textarea>
                </div>
                @if(in_array($workorder->status, ['resolved', 'completed', 'closed']))
                <div>
                    <label class="label" for="solution">解决方案</label>
                    <textarea class="input" id="solution" name="solution" rows="4" placeholder="请描述问题的解决方案">{{ old('solution', $workorder->solution) }}</textarea>
                </div>
                @endif
            </div>
            @endif

            {{-- Remarks --}}
            <div class="card p-5">
                <h2 class="text-sm font-semibold text-ink mb-4 flex items-center gap-2">
                    <svg class="w-4 h-4 text-brand-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                    备注
                </h2>
                <textarea class="input" id="remarks" name="remarks" rows="3" placeholder="其他需要说明的信息">{{ old('remarks', $workorder->remarks) }}</textarea>
            </div>

            {{-- Attachments --}}
            @if($workorder->attachments->count() > 0 || ((auth()->user()->isAdmin() || $workorder->assignee_id == auth()->id()) && in_array($workorder->status, ['pending', 'processing'])))
            <div class="card p-5">
                <h2 class="text-sm font-semibold text-ink mb-4 flex items-center gap-2">
                    <svg class="w-4 h-4 text-brand-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>
                    附件管理
                </h2>
                @if($workorder->attachments->count() > 0)
                <div class="space-y-2 mb-4">
                    @foreach($workorder->attachments as $attachment)
                    <div class="flex items-center gap-3 p-3 rounded-lg border border-border">
                        <div class="w-10 h-10 rounded-lg overflow-hidden shrink-0 flex items-center justify-center" style="background-color: var(--c-muted);">
                            @if($attachment->isImage())
                                <img src="{{ $attachment->preview_url }}" alt="{{ $attachment->original_name }}" class="w-full h-full object-cover cursor-pointer" onclick="showImagePreview('{{ $attachment->preview_url }}', '{{ $attachment->original_name }}')">
                            @else
                                <svg class="w-5 h-5 text-ink-subtle" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z M14 2v6h6"/></svg>
                            @endif
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-ink truncate">{{ $attachment->original_name }}</p>
                            <p class="text-xs text-ink-subtle">{{ $attachment->formatted_file_size }}</p>
                        </div>
                        <div class="flex items-center gap-1 shrink-0">
                            @if($attachment->isImage())
                            <button type="button" class="btn btn-ghost btn-icon btn-sm" onclick="showImagePreview('{{ $attachment->preview_url }}', '{{ $attachment->original_name }}')" title="预览">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z M12 9a3 3 0 1 0 0 6 3 3 0 0 0 0-6z"/></svg>
                            </button>
                            @endif
                            <a href="{{ $attachment->download_url }}" class="btn btn-ghost btn-icon btn-sm" title="下载">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4 M7 10l5 5 5-5 M12 15V3"/></svg>
                            </a>
                            @if((auth()->user()->isAdmin() || $workorder->creator_id == auth()->id() || $workorder->assignee_id == auth()->id()) && in_array($workorder->status, ['pending', 'processing']))
                            <form method="POST" action="{{ route('attachments.destroy', $attachment->id) }}" class="inline" onsubmit="return confirm('确定要删除这个附件吗？')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-ghost btn-icon btn-sm text-red-500" title="删除">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18 M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                </button>
                            </form>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif

                @if((auth()->user()->isAdmin() || $workorder->assignee_id == auth()->id()) && in_array($workorder->status, ['pending', 'processing']))
                <div>
                    <label class="label" for="new_attachments">上传新附件</label>
                    <input type="file" class="input" id="new_attachments" name="new_attachments[]" multiple accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.txt">
                    <p class="text-xs mt-1.5" style="color: var(--c-ink-subtle);">单个文件最大 10MB，最多 5 个文件</p>
                    <div id="newAttachmentPreview" class="mt-3 space-y-2"></div>
                </div>
                @endif
            </div>
            @endif

            {{-- Submit --}}
            <div class="flex items-center justify-end gap-2">
                <a href="{{ route('workorders.show', $workorder->id) }}" class="btn btn-secondary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18 6 6 18M6 6l12 12"/></svg>
                    <span>取消</span>
                </a>
                <button type="submit" class="btn btn-primary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    <span>保存更改</span>
                </button>
            </div>
        </form>
    </div>

    {{-- Sidebar --}}
    <div class="lg:col-span-1 space-y-4">
        <div class="card p-5">
            <h3 class="text-sm font-semibold text-ink mb-3">工单状态</h3>
            <dl class="space-y-2.5 text-sm">
                <div class="flex justify-between"><dt style="color: var(--c-ink-subtle);">编号</dt><dd class="font-medium text-ink">{{ $workorder->ticket_no }}</dd></div>
                <div class="flex justify-between items-center">
                    <dt style="color: var(--c-ink-subtle);">状态</dt>
                    <dd>
                        @php($statusStyles = ['pending' => 'bg-amber-100 text-amber-700', 'assigned' => 'bg-blue-100 text-blue-700', 'processing' => 'bg-indigo-100 text-indigo-700', 'resolved' => 'bg-green-100 text-green-700', 'completed' => 'bg-teal-100 text-teal-700', 'closed' => 'bg-slate-100 text-slate-600'])
                        <span class="badge {{ $statusStyles[$workorder->status] ?? 'bg-slate-100 text-slate-600' }}">{{ $workorder->status_text }}</span>
                    </dd>
                </div>
                <div class="flex justify-between"><dt style="color: var(--c-ink-subtle);">创建</dt><dd class="text-ink">{{ $workorder->created_at->format('Y-m-d H:i') }}</dd></div>
                @if($workorder->assignee)
                <div class="flex justify-between"><dt style="color: var(--c-ink-subtle);">处理人</dt><dd class="text-ink">{{ $workorder->assignee->name }}</dd></div>
                @endif
                @if($workorder->assigned_at)
                <div class="flex justify-between"><dt style="color: var(--c-ink-subtle);">分配</dt><dd class="text-ink">{{ $workorder->assigned_at->format('Y-m-d H:i') }}</dd></div>
                @endif
            </dl>
            @if(auth()->user()->isAdmin())
            <div class="mt-4 pt-4 border-t border-border">
                <label class="label" for="created_at">修改创建时间</label>
                <input type="datetime-local" class="input" id="created_at" name="created_at" value="{{ old('created_at', $workorder->created_at ? $workorder->created_at->format('Y-m-d\TH:i') : '') }}">
                <p class="text-xs mt-1" style="color: var(--c-ink-subtle);">管理员可修改</p>
            </div>
            @endif
        </div>

        <div class="card p-5">
            <h3 class="text-sm font-semibold text-ink mb-3">编辑提示</h3>
            <ul class="space-y-2 text-sm" style="color: var(--c-ink-muted);">
                <li class="flex gap-2"><svg class="w-4 h-4 shrink-0 mt-0.5 text-brand-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>只有创建人可编辑未分配工单</li>
                <li class="flex gap-2"><svg class="w-4 h-4 shrink-0 mt-0.5 text-brand-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>已处理工单请联系管理员修改</li>
                <li class="flex gap-2"><svg class="w-4 h-4 shrink-0 mt-0.5 text-brand-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>所有修改都会记录</li>
            </ul>
        </div>
    </div>
</div>

{{-- Image preview modal --}}
<div id="imagePreviewModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60" onclick="if(event.target===this)closeImagePreview()">
    <div class="card max-w-3xl w-full overflow-hidden">
        <div class="flex items-center justify-between px-5 py-3 border-b border-border">
            <span id="imagePreviewTitle" class="text-sm font-medium text-ink"></span>
            <button type="button" onclick="closeImagePreview()" class="btn btn-ghost btn-icon">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18 6 6 18M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="p-4 flex items-center justify-center" style="background-color: var(--c-muted);">
            <img id="imagePreviewImg" src="" alt="" class="max-h-[70vh] rounded-lg">
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
var campusBuildings = @json(\App\Models\Location::getCampusBuildings());
var categoryData = @json($categories);

(function() {
    // Category cascade
    var mainSel = document.getElementById('category_main');
    var subSel = document.getElementById('category_sub');
    var timeLimitInput = document.getElementById('time_limit_hours');

    // Preserve existing sub-category selection
    var existingSubId = null;
    var existingSubName = null;
    @if($workorder->category && $workorder->category->parent_id)
    existingSubId = {{ $workorder->category->id }};
    existingSubName = '{{ addslashes($workorder->category->name) }}';
    @endif

    mainSel.addEventListener('change', function() {
        var id = this.value;
        subSel.innerHTML = '<option value="">请选择故障分类</option>';
        if (id && categoryData.sub[id]) {
            categoryData.sub[id].forEach(function(c) {
                var opt = document.createElement('option');
                opt.value = c.id;
                opt.textContent = c.name;
                if (c.id == existingSubId) opt.selected = true;
                subSel.appendChild(opt);
            });
        }
        var mainCat = categoryData.main.find(function(c) { return c.id == id; });
        if (mainCat && !timeLimitInput.value) {
            timeLimitInput.value = mainCat.default_hours;
        }
    });

    // Campus -> Building
    var campusSel = document.getElementById('campus');
    var buildingSel = document.getElementById('building');
    campusSel.addEventListener('change', function() {
        var campus = this.value;
        buildingSel.innerHTML = '<option value="">请选择楼栋</option>';
        if (campus && campusBuildings[campus]) {
            campusBuildings[campus].forEach(function(b) {
                var opt = document.createElement('option');
                opt.value = b.id;
                opt.textContent = b.name;
                buildingSel.appendChild(opt);
            });
        }
    });

    // Initialize current category
    @if($workorder->category)
    var currentCategoryId = {{ $workorder->category->id }};
    var currentParentId = {{ $workorder->category->parent_id ?? 'null' }};
    if (currentParentId) {
        mainSel.value = currentParentId;
        mainSel.dispatchEvent(new Event('change'));
    } else {
        mainSel.value = currentCategoryId;
        mainSel.dispatchEvent(new Event('change'));
    }
    @endif

    // Initialize current campus/building
    @if($workorder->campus)
    campusSel.value = '{{ $workorder->campus }}';
    campusSel.dispatchEvent(new Event('change'));
    @if($workorder->building)
    var currentBuildingId = {{ $workorder->building }};
    setTimeout(function() { buildingSel.value = currentBuildingId; }, 50);
    @endif
    @endif

    // Custom source toggle
    var sourceSel = document.getElementById('source');
    var customRow = document.getElementById('custom_source_row');
    var customInput = document.getElementById('custom_source');
    function syncCustomSource() {
        var isCustom = sourceSel.value === 'custom';
        customRow.classList.toggle('hidden', !isCustom);
        if (isCustom) customInput.setAttribute('required', 'required');
        else customInput.removeAttribute('required');
    }
    sourceSel.addEventListener('change', syncCustomSource);
    syncCustomSource();

    // New attachment preview
    var newFileInput = document.getElementById('new_attachments');
    if (newFileInput) {
        var previewDiv = document.getElementById('newAttachmentPreview');
        newFileInput.addEventListener('change', function() {
            previewDiv.innerHTML = '';
            var files = this.files;
            for (var i = 0; i < files.length; i++) {
                (function(file) {
                    var sizeMB = (file.size / 1024 / 1024).toFixed(2);
                    var item = document.createElement('div');
                    item.className = 'flex items-center gap-3 p-2 rounded-lg border border-border';
                    var info = document.createElement('div');
                    info.className = 'flex-1 min-w-0';
                    info.innerHTML = '<span class="text-sm text-ink">' + file.name + '</span> <span class="text-xs text-ink-subtle ml-2">' + sizeMB + ' MB</span>';
                    item.appendChild(info);
                    previewDiv.appendChild(item);
                })(files[i]);
            }
        });
    }
})();

// Image preview
function showImagePreview(src, name) {
    var modal = document.getElementById('imagePreviewModal');
    document.getElementById('imagePreviewImg').src = src;
    document.getElementById('imagePreviewTitle').textContent = name || '图片预览';
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.body.classList.add('overflow-hidden');
}
function closeImagePreview() {
    var modal = document.getElementById('imagePreviewModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    document.body.classList.remove('overflow-hidden');
}
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeImagePreview();
});
</script>
@endsection
