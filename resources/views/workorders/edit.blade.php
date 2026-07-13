@extends('layouts.app')

@section('title', '编辑工单 - ' . $workorder->ticket_no)

@section('content')
<div class="flex items-center justify-between mb-6 pb-4 border-b border-border">
    <h1 class="text-xl font-semibold text-ink">编辑工单</h1>
    <div class="flex gap-2">
        <a href="{{ route('workorders.show', $workorder->id) }}" class="btn btn-secondary">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 12H5 M12 19l-7-7 7-7"/></svg> 返回详情
        </a>
    </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <div class="card p-5">
            <div class="text-sm font-semibold text-ink mb-3">
                <h5 class="text-sm font-semibold text-ink">编辑工单信息</h5>
            </div>
            <div>
                <form method="POST" action="{{ route('workorders.update', $workorder->id) }}">
                    @csrf
                    @method('PUT')
                    
                    <!-- 工单分类 -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-4">
                        <div>
                            <label for="category_main" class="label">工单大类 <span class="text-red-500">*</span></label>
                            <select class="input" id="category_main" name="category_main" required>
                                <option value="">请选择工单大类</option>
                                @foreach($categories['main'] as $category)
                                <option value="{{ $category->id }}"
                                        data-prefix="{{ $category->ticket_prefix }}"
                                        data-hours="{{ $category->default_hours }}"
                                        {{ old('category_main') == $category->id || ($workorder->category && $workorder->category->parent_id == null && $workorder->category->id == $category->id) ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                                @endforeach
                            </select>
                            @error('category_main')
                                <div class="text-xs text-red-600 mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        <div>
                            <label for="category_sub" class="label">故障分类 <span class="text-red-500">*</span></label>
                            <select class="input" id="category_sub" name="category_sub" required>
                                <option value="">请先选择工单大类</option>
                                @if($workorder->category && $workorder->category->parent_id)
                                <option value="{{ $workorder->category->id }}" selected>
                                    {{ $workorder->category->name }}
                                </option>
                                @endif
                            </select>
                            @error('category_sub')
                                <div class="text-xs text-red-600 mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="description" class="label">问题描述 <span class="text-red-500">*</span></label>
                        <textarea class="input" id="description" name="description" rows="6" required
                                  placeholder="请详细描述遇到的问题，包括现象、影响范围等">{{ old('description', $workorder->description) }}</textarea>
                        <div class="text-xs text-ink-muted mt-1">请尽可能详细地描述问题，以便技术人员快速定位和解决</div>
                    </div>
                    
                    <!-- 联系信息 -->
                    <h6 class="mb-4">联系信息</h6>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-4">
                        <div>
                            <label for="contact_name" class="label">联系人 <span class="text-red-500">*</span></label>
                            <input type="text" class="input" id="contact_name" name="contact_name"
                                   value="{{ old('contact_name', $workorder->contact_name) }}" required maxlength="100" autocomplete="name">
                        </div>
                        <div>
                            <label for="contact_phone" class="label">联系电话 <span class="text-red-500">*</span></label>
                            <input type="tel" class="input" id="contact_phone" name="contact_phone"
                                   value="{{ old('contact_phone', $workorder->contact_phone) }}" required maxlength="20" autocomplete="tel">
                        </div>
                        <div>
                            <label for="contact_email" class="label">联系邮箱</label>
                            <input type="email" class="input" id="contact_email" name="contact_email"
                                   value="{{ old('contact_email', $workorder->contact_email) }}" maxlength="100" autocomplete="email">
                        </div>
                        
                        <!-- 工单来源和优先级 -->
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-3">
                            <div>
                                <label for="source" class="label">工单来源 <span class="text-red-500">*</span></label>
                                <select class="input" id="source" name="source" required onchange="toggleCustomSource()">
                                    @foreach(\App\Models\WorkorderSource::getActiveSources() as $source)
                                        <option value="{{ $source->name }}" {{ old('source', $workorder->source) == $source->name ? 'selected' : '' }}>
                                            {{ $source->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('source')
                                    <div class="text-xs text-red-600 mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div>
                                <label class="label">优先级 <span class="text-red-500">*</span></label>
                                <div class="flex flex-wrap gap-3">
                                    <div class="flex items-center gap-2">
                                        <input class="rounded border-border-strong w-4 h-4" type="radio" name="priority" id="priority_low" value="low"
                                               {{ old('priority', $workorder->priority) == 'low' ? 'checked' : '' }} autocomplete="off">
                                        <label class="text-sm" for="priority_low">
                                            <span class="badge bg-green-100 text-green-700">低</span>
                                        </label>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <input class="rounded border-border-strong w-4 h-4" type="radio" name="priority" id="priority_medium" value="medium"
                                               {{ old('priority', $workorder->priority) == 'medium' ? 'checked' : '' }} autocomplete="off">
                                        <label class="text-sm" for="priority_medium">
                                            <span class="badge bg-amber-100 text-amber-700">中</span>
                                        </label>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <input class="rounded border-border-strong w-4 h-4" type="radio" name="priority" id="priority_high" value="high"
                                               {{ old('priority', $workorder->priority) == 'high' ? 'checked' : '' }} autocomplete="off">
                                        <label class="text-sm" for="priority_high">
                                            <span class="badge bg-red-100 text-red-700">高</span>
                                        </label>
                                    </div>
                                </div>
                                @error('priority')
                                    <div class="text-xs text-red-600 mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <!-- 其他来源说明 -->
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-3" id="other_source_row" style="display: none;">
                            <div>
                                <label for="other_source" class="label">其他来源说明 <span class="text-red-500">*</span></label>
                                <input type="text" class="input" id="other_source" name="other_source" autocomplete="off"
                                       value="{{ old('other_source', $workorder->other_source) }}" maxlength="50" autocomplete="off"
                                       placeholder="请说明具体的报修来源" autocomplete="off">
                                @error('other_source')
                                    <div class="text-xs text-red-600 mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                    
                    <!-- 位置信息 -->
                    <h6 class="mb-4">位置信息</h6>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-4">
                        <div>
                            <label for="campus" class="label">校区 <span class="text-red-500">*</span></label>
                            <label for="campus_id" class="label">校区 <span class="text-red-500">*</span></label>
                            <select class="input" id="campus_id" name="campus_id" required>
                                <option value="">请选择校区</option>
                                @foreach(\App\Models\Campus::where('status', 'active')->orderBy('sort_order')->orderBy('name')->get() as $campus)
                                <option value="{{ $campus->id }}" {{ old('campus_id', $workorder->campus_id) == $campus->id ? 'selected' : '' }}>{{ $campus->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="building" class="label">楼栋 <span class="text-red-500">*</span></label>
                            <select class="input" id="building" name="building" required>
                                <option value="">请先选择校区</option>
                            </select>
                        </div>
                        <div>
                            <label for="location_detail" class="label">详细地址</label>
                            <input type="text" class="input" id="location_detail" name="location_detail" autocomplete="street-address"
                                   value="{{ old('location_detail', $workorder->location_detail) }}" maxlength="500" autocomplete="street-address"
                                   placeholder="如：301室" autocomplete="street-address">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-3">
                        <div>
                            <label for="appointment_time_start" class="label">预约时间</label>
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <input type="datetime-local" class="input" id="appointment_time_start" name="appointment_time_start"
                                           value="{{ old('appointment_time_start', $workorder->appointment_time_start ? $workorder->appointment_time_start->format('Y-m-d\TH:i') : '') }}" placeholder="开始时间" autocomplete="off">
                                </div>
                                <div>
                                    <input type="datetime-local" class="input" id="appointment_time_end" name="appointment_time_end"
                                           value="{{ old('appointment_time_end', $workorder->appointment_time_end ? $workorder->appointment_time_end->format('Y-m-d\TH:i') : '') }}" placeholder="结束时间" autocomplete="off">
                                </div>
                            </div>
                            <div class="text-xs text-ink-muted mt-1">请选择具体的预约时间段，如：12月15日 14:00 - 12月15日 16:00</div>
                            @error('appointment_time_start')
                                <div class="text-xs text-red-600 mt-1">{{ $message }}</div>
                            @enderror
                            @error('appointment_time_end')
                                <div class="text-xs text-red-600 mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        <div>
                            <label for="time_limit_hours" class="label">处理时限（小时）</label>
                            <input type="number" class="input" id="time_limit_hours" name="time_limit_hours"
                                   value="{{ old('time_limit_hours', $workorder->time_limit_hours) }}" min="1" max="168" step="1"
                                   placeholder="默认根据工单类型设置" autocomplete="off">
                            @error('time_limit_hours')
                                <div class="text-xs text-red-600 mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <!-- 工单属性 -->
                    <h6 class="mb-4">工单属性</h6>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-4">
                        <div>
                            <label for="department_id" class="label">所属部门</label>
                            <select class="input" id="department_id" name="department_id">
                                <option value="">请选择部门</option>
                                @foreach($departments as $department)
                                <option value="{{ $department->id }}"
                                        {{ old('department_id', $workorder->department_id) == $department->id ? 'selected' : '' }}>
                                    {{ $department->full_path ?? $department->name }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="label">特殊标记</label>
                            <div class="flex items-center gap-2 mt-2">
                                <input class="rounded border-border-strong w-4 h-4" type="checkbox" id="need_visit" name="need_visit"
                                       value="1" {{ old('need_visit', $workorder->need_visit) ? 'checked' : '' }} autocomplete="off">
                                <label class="text-sm" for="need_visit">
                                    需要回访
                                </label>
                            </div>
                            <div class="flex items-center gap-2">
                                <input class="rounded border-border-strong w-4 h-4" type="checkbox" id="is_emergency" name="is_emergency"
                                       value="1" {{ old('is_emergency', $workorder->is_emergency) ? 'checked' : '' }} autocomplete="off">
                                <label class="text-sm" for="is_emergency">
                                    紧急工单
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 解决方案和耗材使用 -->
                    @if(in_array($workorder->status, ['processing', 'resolved', 'completed', 'closed']))
                    <h6 class="mb-4">处理信息</h6>
                    @endif
                    
                    @if(in_array($workorder->status, ['processing', 'resolved', 'completed', 'closed']))
                    <div class="mb-4">
                        <label for="materials_usage" class="label">备件耗材使用情况</label>
                        <textarea class="input" id="materials_usage" name="materials_usage" rows="3"
                                  placeholder="请记录使用的备件、耗材情况，如名称、规格、数量等">{{ old('materials_usage', $workorder->materials_usage) }}</textarea>
                        <div class="text-xs text-ink-muted mt-1">记录维修过程中使用的备件和耗材信息</div>
                    </div>
                    @endif
                    
                    @if(in_array($workorder->status, ['resolved', 'completed', 'closed']))
                    <div class="mb-4">
                        <label for="solution" class="label">解决方案</label>
                        <textarea class="input" id="solution" name="solution" rows="4"
                                  placeholder="请描述问题的解决方案" autocomplete="off">{{ old('solution', $workorder->solution) }}</textarea>
                        <div class="text-xs text-ink-muted mt-1">详细说明问题解决的方法和过程</div>
                    </div>
                    @endif
                    
                    <!-- 备注 -->
                    <div class="mb-4">
                        <label for="remarks" class="label">备注</label>
                        <textarea class="input" id="remarks" name="remarks" rows="3"
                                  placeholder="其他需要说明的信息" autocomplete="off">{{ old('remarks', $workorder->remarks) }}</textarea>
                    </div>
                    
                    <!-- 附件管理 -->
                    <div class="mb-4">
                        <h6 class="mb-4">附件管理</h6>
                        @if($workorder->attachments->count() > 0)
                        <div class="mb-4">
                            <label class="label">当前附件</label>
                            @foreach($workorder->attachments as $attachment)
                            <div class="attachment-item mb-2 p-2 border rounded">
                                <div class="flex items-start gap-3">
                                    <div class="attachment-thumbnail mr-3">
                                        @if($attachment->isImage())
                                            <img src="{{ $attachment->preview_url }}"
                                                 class="rounded-lg border border-border"
                                                 alt="{{ $attachment->original_name }}"
                                                 style="width: 50px; height: 50px; object-fit: cover; cursor: pointer;"
                                                 onclick="showImagePreview('{{ $attachment->preview_url }}', '{{ $attachment->original_name }}')">
                                        @else
                                            <i class="{{ $attachment->getFileIcon() }} text-lg text-ink-muted"></i>
                                        @endif
                                    </div>
                                    <div class="attachment-info flex-grow-1">
                                        <div class="flex justify-between items-center">
                                            <div>
                                                <h6 class="mb-1">{{ $attachment->original_name }}</h6>
                                                <small class="text-ink-muted">{{ $attachment->formatted_file_size }}</small>
                                            </div>
                                            <div class="flex gap-1">
                                                @if($attachment->isImage())
                                                <button type="button" class="btn btn-secondary"
                                                        onclick="showImagePreview('{{ $attachment->preview_url }}', '{{ $attachment->original_name }}')"
                                                        title="预览">
                                                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/></svg>
                                                </button>
                                                @endif
                                                <a href="{{ $attachment->download_url }}" class="btn btn-ghost" title="下载">
                                                    <i class="fas fa-download"></i>
                                                </a>
                                                @if((auth()->user()->isAdmin() || $workorder->creator_id == auth()->id() || $workorder->assignee_id == auth()->id()) && in_array($workorder->status, ['pending', 'processing']))
                                                <form method="POST" action="{{ route('attachments.destroy', $attachment->id) }}" class="d-inline"
                                                      onsubmit="return confirm('确定要删除这个附件吗？')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-outline-danger" title="删除">
                                                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18 M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2 M10 11v6 M14 11v6"/></svg>
                                                    </button>
                                                </form>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        @endif
                        
                        @if((auth()->user()->isAdmin() || $workorder->assignee_id == auth()->id()) && in_array($workorder->status, ['pending', 'processing']))
                        <div class="mb-4">
                            <label for="new_attachments" class="label">上传新附件</label>
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
                                <input type="file" class="sr-only" id="new_attachments" name="new_attachments[]" multiple accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.txt" onchange="document.getElementById('attEditName').textContent=this.files.length?'已选择 '+this.files.length+' 个文件':'未选择文件'">

                                <div id="attEditName" class="text-xs mt-1" style="color: var(--c-ink-subtle);">未选择文件</div>
                                   multiple accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.txt" autocomplete="off">
                            <div class="text-xs text-ink-muted mt-1">
                                支持上传图片、文档等文件，单个文件最大10MB，最多5个文件
                            </div>
                            <div id="newAttachmentPreview" class="mt-2"></div>
                        </div>
                        @endif
                    </div>
                    
                    <!-- 提交按钮 -->
                    <div class="d-flex justify-content-end">
                        <a href="{{ route('workorders.show', $workorder->id) }}" class="btn btn-secondary mr-2">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18 6L6 18M6 6l12 12"/></svg> 取消
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z M17 21v-8H7v8 M7 3v5h8"/></svg> 保存更改
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div>
        <!-- 工单状态信息 -->
        <div class="card mb-4">
            <div class="text-sm font-semibold text-ink mb-3">
                <h6 class="text-sm font-semibold text-ink">工单状态</h6>
            </div>
            <div>
                <div class="mb-2">
                    <strong>工单编号：</strong>{{ $workorder->ticket_no }}
                </div>
                <div class="mb-2">
                    <strong>当前状态：</strong>
                    <span class="badge bg-{{ $workorder->status == 'closed' ? 'success' : ($workorder->status == 'pending' ? 'warning' : 'info') }}">
                        {{ $workorder->status_text }}
                    </span>
                </div>
                <div class="mb-2">
                    <strong>创建时间：</strong>{{ $workorder->created_at->format('Y-m-d H:i:s') }}
                </div>
                @if(auth()->user()->isAdmin())
                <div class="mb-2">
                    <label for="created_at" class="label">修改创建时间</label>
                    <input type="datetime-local" class="input" id="created_at" name="created_at"
                           value="{{ old('created_at', $workorder->created_at ? $workorder->created_at->format('Y-m-d\TH:i') : '') }}" autocomplete="off">
                    @error('created_at')
                        <div class="text-xs text-red-600 mt-1">{{ $message }}</div>
                    @enderror
                    <div class="text-xs text-ink-muted mt-1">管理员可以修改工单创建时间</div>
                </div>
                @endif
                @if($workorder->assignee)
                <div class="mb-2">
                    <strong>处理人：</strong>{{ $workorder->assignee->name }}
                </div>
                @endif
                @if($workorder->assigned_at)
                <div class="mb-2">
                    <strong>分配时间：</strong>{{ $workorder->assigned_at->format('Y-m-d H:i:s') }}
                </div>
                @endif
            </div>
        </div>
        
        <!-- 编辑提示 -->
        <div class="card mb-4">
            <div class="text-sm font-semibold text-ink mb-3">
                <h6 class="text-sm font-semibold text-ink">编辑提示</h6>
            </div>
            <div>
                <ul class="mb-0">
                    <li>只有工单创建人可以编辑未分配的工单</li>
                    <li>已分配或开始处理的工单不能修改基本信息</li>
                    <li>如需修改已处理工单，请联系管理员</li>
                    <li>所有修改都会记录在处理记录中</li>
                </ul>
            </div>
        </div>
        
        <!-- 优先级说明 -->
        <div class="card p-5">
            <div class="text-sm font-semibold text-ink mb-3">
                <h6 class="text-sm font-semibold text-ink">优先级说明</h6>
            </div>
            <div>
                <div class="mb-2">
                    <span class="badge bg-red-100 text-red-700">高</span>
                    <small>严重影响正常工作或学习</small>
                </div>
                <div class="mb-2">
                    <span class="badge bg-amber-100 text-amber-700">中</span>
                    <small>部分影响正常工作或学习</small>
                </div>
                <div class="mb-2">
                    <span class="badge bg-green-100 text-green-700">低</span>
                    <small>轻微影响，可延后处理</small>
                </div>
            </div>
        </div>
    </div>
</div>
@include('workorders._camera')
@endsection

@section('scripts')
<script>
// 工单分类数据
var categoryData = @json($categories);

// 从地址管理获取校区楼栋数据
var campusBuildings = @json(\App\Models\Location::getCampusBuildings());

$(document).ready(function() {
    // 工单大类变更时更新故障分类
    $('#category_main').change(function() {
        var mainCategoryId = $(this).val();
        var subSelect = $('#category_sub');
        var timeLimitInput = $('#time_limit_hours');
        
        subSelect.empty().append('<option value="">请选择故障分类</option>');
        
        if (mainCategoryId && categoryData.sub[mainCategoryId]) {
            $.each(categoryData.sub[mainCategoryId], function(index, category) {
                subSelect.append('<option value="' + category.id + '">' + category.name + '</option>');
            });
            
            // 设置默认处理时限
            var mainCategory = categoryData.main.find(function(cat) {
                return cat.id == mainCategoryId;
            });
            
            if (mainCategory && !timeLimitInput.val()) {
                timeLimitInput.val(mainCategory.default_hours);
            }
        }
    });
    
    // 校区变更时更新楼栋选项
    $('#campus_id').change(function() {
        var campusId = $(this).val();
        var buildingSelect = $('#building');
        
        buildingSelect.empty().append('<option value="">请选择楼栋</option>');
        
        if (campusId && campusBuildings[campusId]) {
            $.each(campusBuildings[campusId].buildings, function(index, building) {
                buildingSelect.append('<option value="' + building.id + '">' + building.name + '</option>');
            });
        }
    });
    
    // 初始化当前工单的分类选择
    initializeCurrentCategory();
    
    // 初始化校区楼栋选择
    initializeCampusBuilding();
    
    // 初始化其他来源显示状态
    if ($('#source').val() === '其他来源') {
        $('#other_source_row').show();
        $('#other_source').attr('required', 'required');
    }
    
    // 切换其他来源输入框显示/隐藏
    window.toggleCustomSource = function() {
        var sourceSelect = $('#source');
        var otherSourceRow = $('#other_source_row');
        var otherSourceInput = $('#other_source');
        
        if (sourceSelect.val() === 'other') {
        if (sourceSelect.val() === '其他来源') {
            otherSourceRow.show();
            otherSourceInput.attr('required', 'required');
        } else {
            otherSourceRow.hide();
            otherSourceInput.removeAttr('required');
            otherSourceInput.val('');
        }
    }
});

function initializeCurrentCategory() {
    // 如果工单有分类，初始化选择
    @if($workorder->category)
    var currentCategoryId = {{ $workorder->category->id }};
    var currentParentId = {{ $workorder->category->parent_id ?? 'null' }};
    
    if (currentParentId) {
        // 如果是子分类，先设置父分类
        $('#category_main').val(currentParentId);
        
        // 触发父分类变更事件，加载子分类
        $('#category_main').trigger('change');
        
        // 设置当前选中的子分类
        setTimeout(function() {
            $('#category_sub').val(currentCategoryId);
        }, 100);
    } else {
        // 如果是父分类，直接设置
        $('#category_main').val(currentCategoryId);
        $('#category_main').trigger('change');
    }
    @endif
}
    
function initializeCampusBuilding() {
    var campusId = $('#campus_id').val();
    var buildingSelect = $('#building');
    
    // 设置当前工单的校区
    var currentCampusId = '{{ $workorder->campus_id ?? '' }}';
    if (currentCampusId) {
        $('#campus_id').val(currentCampusId);
        campusId = currentCampusId;
    }
    
    buildingSelect.empty().append('<option value="">请选择楼栋</option>');
    
    if (campusId && campusBuildings[campusId]) {
        $.each(campusBuildings[campusId].buildings, function(index, building) {
            buildingSelect.append('<option value="' + building.id + '">' + building.name + '</option>');
        });
    }
    
    // 设置当前工单的楼栋
    var currentBuildingId = {{ $workorder->building ?? 'null' }};
    if (currentBuildingId) {
        buildingSelect.val(currentBuildingId);
    }
}

// 新附件预览
$('#new_attachments').change(async function() {
    var preview = $('#newAttachmentPreview');
    preview.empty();
    
    var files = this.files;
    var processedFiles = [];
    
    // 处理每个文件
    for (let index = 0; index < files.length; index++) {
        const file = files[index];
        const fileIndex = index;
        const originalSize = file.size;
        const originalName = file.name;
        
        // 显示处理中状态
        var processingDiv = $('<div class="p-3 rounded-lg bg-amber-50 text-amber-700 text-sm mb-2">');
        processingDiv.html('<span><i class="fas animate-spinner animate-spin"></i> 正在处理 ' + file.name + '...</span>');
        preview.append(processingDiv);
        
        try {
            // 检查是否为需要压缩的图片（降低阈值到2MB）
            if (file.type.startsWith('image/') && file.size > 2 * 1024 * 1024) {
                // 压缩图片
                const compressedFile = await compressImageAsync(file, 4);
                const compressedSize = compressedFile.size;
                const originalSizeMB = (originalSize / 1024 / 1024).toFixed(2);
                const compressedSizeMB = (compressedSize / 1024 / 1024).toFixed(2);
                const compressionRatio = ((originalSize - compressedSize) / originalSize * 100).toFixed(1);
                
                // 移除处理中状态
                processingDiv.remove();
                
                // 显示压缩后的预览
                createAttachmentPreview(compressedFile, fileIndex, preview, {
                    isCompressed: true,
                    originalSize: originalSizeMB + ' MB',
                    compressedSize: compressedSizeMB + ' MB',
                    compressionRatio: compressionRatio + '%',
                    originalName: originalName
                });
                
                processedFiles[fileIndex] = compressedFile;
            } else {
                // 不需要压缩的文件直接处理
                const fileSizeMB = (file.size / 1024 / 1024).toFixed(2);
                
                // 移除处理中状态
                processingDiv.remove();
                
                createAttachmentPreview(file, fileIndex, preview, {
                    isCompressed: false,
                    fileSize: fileSizeMB + ' MB'
                });
                
                processedFiles[fileIndex] = file;
            }
        } catch (error) {
            // 移除处理中状态
            processingDiv.remove();
            
            // 显示错误信息
            var errorDiv = $('<div class="p-3 rounded-lg bg-red-50 text-red-700 text-sm mb-2">');
            errorDiv.html('<span><svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z M12 9v4 M12 17h.01"/></svg> 处理 ' + file.name + ' 时出错: ' + error.message + '</span>');
            preview.append(errorDiv);
            
            // 即使出错也要保留原始文件
            processedFiles[fileIndex] = file;
        }
    }
    
    // 更新文件输入框
    updateFileInput(processedFiles);
});

// 创建附件预览
function createAttachmentPreview(file, fileIndex, previewContainer, options) {
    var fileDiv = $('<div class="attachment-item mb-2 p-2 border rounded">');
    var thumbnailDiv = $('<div class="attachment-thumbnail mr-3">');
    
    if (file.type.startsWith('image/')) {
        // 图片文件显示缩略图
        var reader = new FileReader();
        reader.onload = function(e) {
            var img = $('<img src="' + e.target.result + '" class="rounded-lg border border-border" alt="' + file.name + '" style="width: 50px; height: 50px; object-fit: cover; cursor: pointer;">');
            thumbnailDiv.html(img);
            
            // 点击预览大图
            img.click(function(e) {
                e.preventDefault();
                showImagePreview(e.target.src, options.originalName || file.name);
            });
        };
        reader.readAsDataURL(file);
    } else {
        // 非图片文件显示文件图标
        var iconClass = getFileIcon(file.name);
        thumbnailDiv.html('<i class="' + iconClass + ' text-lg"></i>');
    }
    
    // 文件信息和描述
    var fileInfoDiv = $('<div class="attachment-info flex-grow-1">');
    var fileSize = (file.size / 1024 / 1024).toFixed(2) + ' MB';
    var fileSizeHtml = '<div class="attachment-size text-ink-muted small">' + fileSize;
    
    // 如果是压缩后的图片，显示压缩信息
    if (options.isCompressed) {
        fileSizeHtml += ' <span class="badge bg-green-100 text-green-700">已压缩</span>';
        fileSizeHtml += '<div class="text-ink-muted small">原始: ' + options.originalSize + ' → 压缩后: ' + options.compressedSize + '</div>';
    }
    
    fileSizeHtml += '</div>';
    
    fileInfoDiv.html(
        '<div class="attachment-name"><strong>' + (options.originalName || file.name) + '</strong></div>' +
        fileSizeHtml
    );
    
    fileDiv.append(thumbnailDiv);
    fileDiv.append(fileInfoDiv);
    previewContainer.append(fileDiv);
}

// 更新文件输入框
function updateFileInput(files) {
    var fileInput = $('#new_attachments')[0];
    var dt = new DataTransfer();
    
    // 添加处理后的文件
    files.forEach(function(file) {
        if (file) {
            dt.items.add(file);
        }
    });
    
    fileInput.files = dt.files;
}

// 生成基于日期时间的文件名
function generateTimestampFilename(originalFilename) {
    const now = new Date();
    const timestamp = now.getFullYear() +
                    String(now.getMonth() + 1).padStart(2, '0') +
                    String(now.getDate()).padStart(2, '0') + '_' +
                    String(now.getHours()).padStart(2, '0') +
                    String(now.getMinutes()).padStart(2, '0') +
                    String(now.getSeconds()).padStart(2, '0');
    
    // 获取文件扩展名
    const lastDotIndex = originalFilename.lastIndexOf('.');
    const extension = lastDotIndex > -1 ? originalFilename.substring(lastDotIndex) : '';
    const baseName = lastDotIndex > -1 ? originalFilename.substring(0, lastDotIndex) : originalFilename;
    
    // 限制基础名称长度，避免文件名过长
    const maxBaseNameLength = 20;
    const truncatedBaseName = baseName.length > maxBaseNameLength ?
                             baseName.substring(0, maxBaseNameLength) : baseName;
    
    return truncatedBaseName + '_' + timestamp + extension;
}

// 异步图片压缩函数
function compressImageAsync(file, maxSizeMB) {
    return new Promise((resolve, reject) => {
        if (!file.type.startsWith('image/')) {
            resolve(file);
            return;
        }
        
        var reader = new FileReader();
        reader.onload = function(e) {
            var img = new Image();
            img.onload = function() {
                try {
                    var canvas = document.createElement('canvas');
                    var ctx = canvas.getContext('2d');
                    
                    // 计算压缩比例
                    var maxWidth = 1920;
                    var maxHeight = 1080;
                    var width = img.width;
                    var height = img.height;
                    
                    // 如果图片尺寸大于最大尺寸，按比例缩放
                    if (width > maxWidth || height > maxHeight) {
                        var ratio = Math.min(maxWidth / width, maxHeight / height);
                        width *= ratio;
                        height *= ratio;
                    }
                    
                    canvas.width = width;
                    canvas.height = height;
                    
                    // 绘制图片
                    ctx.drawImage(img, 0, 0, width, height);
                    
                    // 尝试不同的质量级别，确保文件大小不超过4MB
                    var quality = 0.8;
                    var attemptCompress = function() {
                        canvas.toBlob(function(blob) {
                            var sizeInMB = blob.size / 1024 / 1024;
                            
                            if (sizeInMB <= maxSizeMB || quality <= 0.1) {
                                // 生成新的文件名
                                const newFilename = generateTimestampFilename(file.name);
                                
                                // 创建新的File对象
                                var compressedFile = new File([blob], newFilename, {
                                    type: file.type,
                                    lastModified: Date.now()
                                });
                                resolve(compressedFile);
                            } else {
                                // 继续降低质量
                                quality -= 0.1;
                                attemptCompress();
                            }
                        }, file.type, quality);
                    };
                    
                    attemptCompress();
                } catch (error) {
                    reject(error);
                }
            };
            img.onerror = function() {
                reject(new Error('图片加载失败'));
            };
            img.src = e.target.result;
        };
        reader.onerror = function() {
            reject(new Error('文件读取失败'));
        };
        reader.readAsDataURL(file);
    });
}

// 保留原有的回调式压缩函数以兼容其他代码
function compressImage(file, maxSizeMB, callback) {
    compressImageAsync(file, maxSizeMB)
        .then(callback)
        .catch(function(error) {
            console.error('图片压缩失败:', error);
            callback(file); // 压缩失败时返回原文件
        });
}

// 获取文件图标
function getFileIcon(filename) {
    var ext = filename.split('.').pop().toLowerCase();
    var iconMap = {
        'pdf': 'fas fa-file-pdf text-red-500',
        'doc': 'fas fa-file-word text-brand-600',
        'docx': 'fas fa-file-word text-brand-600',
        'xls': 'fas fa-file-excel text-green-600',
        'xlsx': 'fas fa-file-excel text-green-600',
        'ppt': 'fas fa-file-powerpoint text-amber-600',
        'pptx': 'fas fa-file-powerpoint text-amber-600',
        'txt': 'fas fa-file-alt text-secondary',
        'zip': 'fas fa-file-archive text-blue-600',
        'rar': 'fas fa-file-archive text-blue-600',
        '7z': 'fas fa-file-archive text-blue-600'
    };
    return iconMap[ext] || 'fas fa-file text-ink-muted';
}

// 显示图片预览模态框
function showImagePreview(imageSrc, fileName) {
    $('#imagePreviewModal').remove();

    var modalHtml = '<div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4" id="imagePreviewModal" onclick="if(event.target===this)this.remove()">' +
        '<div class="relative w-full max-w-3xl card shadow-2xl">' +
            '<div class="flex items-center justify-between px-5 py-3 border-b border-border">' +
                '<h5 class="text-sm font-semibold text-ink">\u56fe\u7247\u9884\u89c8 - ' + fileName + '</h5>' +
                '<button type="button" class="btn btn-icon btn-ghost" onclick="document.getElementById(\'imagePreviewModal\').remove()">' +
                    '<svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>' +
                '</button>' +
            '</div>' +
            '<div class="p-5 flex items-center justify-center" style="min-height: 300px;">' +
                '<img src="' + imageSrc + '" class="max-w-full h-auto rounded-lg" alt="' + fileName + '" style="max-height: 70vh; object-fit: contain;">' +
            '</div>' +
        '</div>' +
    '</div>';

    $('body').append(modalHtml);

    $(document).on('keydown.imgPreview', function(e) {
        if (e.keyCode === 27) { $('#imagePreviewModal').remove(); $(this).off('keydown.imgPreview'); }
    });
}
</script>
@endsection

