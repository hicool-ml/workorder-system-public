@extends('layouts.app')

@section('title', '编辑工单 - ' . $workorder->ticket_no)

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">编辑工单</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="{{ route('workorders.show', $workorder->id) }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> 返回详情
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">编辑工单信息</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('workorders.update', $workorder->id) }}">
                    @csrf
                    @method('PUT')
                    
                    <!-- 工单分类 -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="category_main" class="form-label">工单大类 <span class="text-danger">*</span></label>
                            <select class="form-select" id="category_main" name="category_main" required>
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
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="category_sub" class="form-label">故障分类 <span class="text-danger">*</span></label>
                            <select class="form-select" id="category_sub" name="category_sub" required>
                                <option value="">请先选择工单大类</option>
                                @if($workorder->category && $workorder->category->parent_id)
                                <option value="{{ $workorder->category->id }}" selected>
                                    {{ $workorder->category->name }}
                                </option>
                                @endif
                            </select>
                            @error('category_sub')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="description" class="form-label">问题描述 <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="description" name="description" rows="6" required
                                  placeholder="请详细描述遇到的问题，包括现象、影响范围等">{{ old('description', $workorder->description) }}</textarea>
                        <div class="form-text">请尽可能详细地描述问题，以便技术人员快速定位和解决</div>
                    </div>
                    
                    <!-- 联系信息 -->
                    <h6 class="mb-3">联系信息</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label for="contact_name" class="form-label">联系人 <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="contact_name" name="contact_name" 
                                   value="{{ old('contact_name', $workorder->contact_name) }}" required maxlength="100">
                        </div>
                        <div class="col-md-4">
                            <label for="contact_phone" class="form-label">联系电话 <span class="text-danger">*</span></label>
                            <input type="tel" class="form-control" id="contact_phone" name="contact_phone" 
                                   value="{{ old('contact_phone', $workorder->contact_phone) }}" required maxlength="20">
                        </div>
                        <div class="col-md-4">
                            <label for="contact_email" class="form-label">联系邮箱</label>
                            <input type="email" class="form-control" id="contact_email" name="contact_email" 
                                   value="{{ old('contact_email', $workorder->contact_email) }}" maxlength="100">
                        </div>
                        
                        <!-- 工单来源和优先级 -->
                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label for="source" class="form-label">工单来源 <span class="text-danger">*</span></label>
                                <select class="form-select" id="source" name="source" required onchange="toggleCustomSource()">
                                    <option value="phone" {{ old('source', $workorder->source) == 'phone' ? 'selected' : '' }}>电话</option>
                                    <option value="web" {{ old('source', $workorder->source) == 'web' ? 'selected' : '' }}>网络</option>
                                    <option value="scene" {{ old('source', $workorder->source) == 'scene' ? 'selected' : '' }}>现场</option>
                                    <option value="email" {{ old('source', $workorder->source) == 'email' ? 'selected' : '' }}>邮件</option>
                                    <option value="other" {{ old('source', $workorder->source) == 'other' ? 'selected' : '' }}>其他</option>
                                    <option value="custom" {{ old('source', $workorder->source) == 'custom' ? 'selected' : '' }}>添加新渠道</option>
                                </select>
                                @error('source')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">优先级 <span class="text-danger">*</span></label>
                                <div class="d-flex flex-wrap gap-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="priority" id="priority_low" value="low"
                                               {{ old('priority', $workorder->priority) == 'low' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="priority_low">
                                            <span class="badge bg-success">低</span>
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="priority" id="priority_medium" value="medium"
                                               {{ old('priority', $workorder->priority) == 'medium' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="priority_medium">
                                            <span class="badge bg-warning">中</span>
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="priority" id="priority_high" value="high"
                                               {{ old('priority', $workorder->priority) == 'high' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="priority_high">
                                            <span class="badge bg-danger">高</span>
                                        </label>
                                    </div>
                                </div>
                                @error('priority')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <!-- 自定义来源 -->
                        <div class="row g-3 mb-3" id="custom_source_row" style="display: none;">
                            <div class="col-md-12">
                                <label for="custom_source" class="form-label">新渠道名称 <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="custom_source" name="custom_source"
                                       value="{{ old('custom_source', $workorder->custom_source) }}" maxlength="50"
                                       placeholder="请输入新的报修渠道名称">
                                @error('custom_source')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                    
                    <!-- 位置信息 -->
                    <h6 class="mb-3">位置信息</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label for="campus" class="form-label">校区 <span class="text-danger">*</span></label>
                            <select class="form-select" id="campus" name="campus" required>
                                <option value="">请选择校区</option>
                                <option value="old_campus" {{ old('campus', $workorder->campus) == 'old_campus' ? 'selected' : '' }}>老校区</option>
                                <option value="new_campus" {{ old('campus', $workorder->campus) == 'new_campus' ? 'selected' : '' }}>新校区</option>
                                <option value="asean_campus" {{ old('campus', $workorder->campus) == 'asean_campus' ? 'selected' : '' }}>东盟校区</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="building" class="form-label">楼栋 <span class="text-danger">*</span></label>
                            <select class="form-select" id="building" name="building" required>
                                <option value="">请先选择校区</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="location_detail" class="form-label">详细地址</label>
                            <input type="text" class="form-control" id="location_detail" name="location_detail"
                                   value="{{ old('location_detail', $workorder->location_detail) }}" maxlength="500"
                                   placeholder="如：301室">
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="appointment_time" class="form-label">预约时间</label>
                            <input type="datetime-local" class="form-control" id="appointment_time" name="appointment_time"
                                   value="{{ old('appointment_time', $workorder->appointment_time ? $workorder->appointment_time->format('Y-m-d\TH:i') : '') }}">
                            @error('appointment_time')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="time_limit_hours" class="form-label">处理时限（小时）</label>
                            <input type="number" class="form-control" id="time_limit_hours" name="time_limit_hours"
                                   value="{{ old('time_limit_hours', $workorder->time_limit_hours) }}" min="1" max="168" step="1"
                                   placeholder="默认根据工单类型设置">
                            @error('time_limit_hours')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <!-- 工单属性 -->
                    <h6 class="mb-3">工单属性</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="department_id" class="form-label">所属部门</label>
                            <select class="form-select" id="department_id" name="department_id">
                                <option value="">请选择部门</option>
                                @foreach($departments as $department)
                                <option value="{{ $department->id }}"
                                        {{ old('department_id', $workorder->department_id) == $department->id ? 'selected' : '' }}>
                                    {{ $department->full_path ?? $department->name }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">特殊标记</label>
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" id="need_visit" name="need_visit"
                                       value="1" {{ old('need_visit', $workorder->need_visit) ? 'checked' : '' }}>
                                <label class="form-check-label" for="need_visit">
                                    需要回访
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_emergency" name="is_emergency"
                                       value="1" {{ old('is_emergency', $workorder->is_emergency) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_emergency">
                                    紧急工单
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 解决方案和耗材使用 -->
                    @if(in_array($workorder->status, ['processing', 'resolved', 'completed', 'closed']))
                    <h6 class="mb-3">处理信息</h6>
                    @endif
                    
                    @if(in_array($workorder->status, ['processing', 'resolved', 'completed', 'closed']))
                    <div class="mb-4">
                        <label for="materials_usage" class="form-label">备件耗材使用情况</label>
                        <textarea class="form-control" id="materials_usage" name="materials_usage" rows="3"
                                  placeholder="请记录使用的备件、耗材情况，如名称、规格、数量等">{{ old('materials_usage', $workorder->materials_usage) }}</textarea>
                        <div class="form-text">记录维修过程中使用的备件和耗材信息</div>
                    </div>
                    @endif
                    
                    @if(in_array($workorder->status, ['resolved', 'completed', 'closed']))
                    <div class="mb-4">
                        <label for="solution" class="form-label">解决方案</label>
                        <textarea class="form-control" id="solution" name="solution" rows="4"
                                  placeholder="请描述问题的解决方案">{{ old('solution', $workorder->solution) }}</textarea>
                        <div class="form-text">详细说明问题解决的方法和过程</div>
                    </div>
                    @endif
                    
                    <!-- 备注 -->
                    <div class="mb-4">
                        <label for="remarks" class="form-label">备注</label>
                        <textarea class="form-control" id="remarks" name="remarks" rows="3"
                                  placeholder="其他需要说明的信息">{{ old('remarks', $workorder->remarks) }}</textarea>
                    </div>
                    
                    <!-- 附件管理 -->
                    <div class="mb-4">
                        <h6 class="mb-3">附件管理</h6>
                        @if($workorder->attachments->count() > 0)
                        <div class="mb-3">
                            <label class="form-label">当前附件</label>
                            @foreach($workorder->attachments as $attachment)
                            <div class="attachment-item mb-2 p-2 border rounded">
                                <div class="d-flex align-items-start">
                                    <div class="attachment-thumbnail me-3">
                                        @if($attachment->isImage())
                                            <img src="{{ $attachment->preview_url }}"
                                                 class="img-thumbnail"
                                                 alt="{{ $attachment->original_name }}"
                                                 style="width: 50px; height: 50px; object-fit: cover; cursor: pointer;"
                                                 onclick="showImagePreview('{{ $attachment->preview_url }}', '{{ $attachment->original_name }}')">
                                        @else
                                            <i class="{{ $attachment->getFileIcon() }} fa-lg text-muted"></i>
                                        @endif
                                    </div>
                                    <div class="attachment-info flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6 class="mb-1">{{ $attachment->original_name }}</h6>
                                                <small class="text-muted">{{ $attachment->formatted_file_size }}</small>
                                            </div>
                                            <div class="btn-group btn-group-sm">
                                                @if($attachment->isImage())
                                                <button type="button" class="btn btn-outline-primary"
                                                        onclick="showImagePreview('{{ $attachment->preview_url }}', '{{ $attachment->original_name }}')"
                                                        title="预览">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                @endif
                                                <a href="{{ $attachment->download_url }}" class="btn btn-outline-secondary" title="下载">
                                                    <i class="fas fa-download"></i>
                                                </a>
                                                @if((auth()->user()->isAdmin() || $workorder->creator_id == auth()->id() || $workorder->assignee_id == auth()->id()) && in_array($workorder->status, ['pending', 'processing']))
                                                <form method="POST" action="{{ route('attachments.destroy', $attachment->id) }}" class="d-inline"
                                                      onsubmit="return confirm('确定要删除这个附件吗？')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-outline-danger" title="删除">
                                                        <i class="fas fa-trash"></i>
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
                        <div class="mb-3">
                            <label for="new_attachments" class="form-label">上传新附件</label>
                            <input type="file" class="form-control" id="new_attachments" name="new_attachments[]"
                                   multiple accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.txt">
                            <div class="form-text">
                                支持上传图片、文档等文件，单个文件最大10MB，最多5个文件
                            </div>
                            <div id="newAttachmentPreview" class="mt-2"></div>
                        </div>
                        @endif
                    </div>
                    
                    <!-- 提交按钮 -->
                    <div class="d-flex justify-content-end">
                        <a href="{{ route('workorders.show', $workorder->id) }}" class="btn btn-secondary me-2">
                            <i class="fas fa-times"></i> 取消
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> 保存更改
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <!-- 工单状态信息 -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="card-title mb-0">工单状态</h6>
            </div>
            <div class="card-body">
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
                    <label for="created_at" class="form-label">修改创建时间</label>
                    <input type="datetime-local" class="form-control" id="created_at" name="created_at"
                           value="{{ old('created_at', $workorder->created_at ? $workorder->created_at->format('Y-m-d\TH:i') : '') }}">
                    @error('created_at')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                    <div class="form-text">管理员可以修改工单创建时间</div>
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
            <div class="card-header">
                <h6 class="card-title mb-0">编辑提示</h6>
            </div>
            <div class="card-body">
                <ul class="mb-0">
                    <li>只有工单创建人可以编辑未分配的工单</li>
                    <li>已分配或开始处理的工单不能修改基本信息</li>
                    <li>如需修改已处理工单，请联系管理员</li>
                    <li>所有修改都会记录在处理记录中</li>
                </ul>
            </div>
        </div>
        
        <!-- 优先级说明 -->
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">优先级说明</h6>
            </div>
            <div class="card-body">
                <div class="mb-2">
                    <span class="badge bg-danger">高</span>
                    <small>严重影响正常工作或学习</small>
                </div>
                <div class="mb-2">
                    <span class="badge bg-warning">中</span>
                    <small>部分影响正常工作或学习</small>
                </div>
                <div class="mb-2">
                    <span class="badge bg-success">低</span>
                    <small>轻微影响，可延后处理</small>
                </div>
            </div>
        </div>
    </div>
</div>
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
    $('#campus').change(function() {
        var campus = $(this).val();
        var buildingSelect = $('#building');
        
        buildingSelect.empty().append('<option value="">请选择楼栋</option>');
        
        if (campus && campusBuildings[campus]) {
            $.each(campusBuildings[campus], function(index, building) {
                buildingSelect.append('<option value="' + building.id + '">' + building.name + '</option>');
            });
        }
    });
    
    // 初始化当前工单的分类选择
    initializeCurrentCategory();
    
    // 初始化校区楼栋选择
    initializeCampusBuilding();
    
    // 初始化自定义来源显示状态
    if ($('#source').val() === 'custom') {
        $('#custom_source_row').show();
        $('#custom_source').attr('required', 'required');
    }
    
    // 切换自定义来源输入框显示/隐藏
    function toggleCustomSource() {
        var sourceSelect = $('#source');
        var customSourceRow = $('#custom_source_row');
        var customSourceInput = $('#custom_source');
        
        if (sourceSelect.val() === 'custom') {
            customSourceRow.show();
            customSourceInput.attr('required', 'required');
        } else {
            customSourceRow.hide();
            customSourceInput.removeAttr('required');
            customSourceInput.val('');
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
    var campus = $('#campus').val();
    var buildingSelect = $('#building');
    
    // 设置当前工单的校区
    var currentCampus = '{{ $workorder->campus ?? '' }}';
    if (currentCampus) {
        $('#campus').val(currentCampus);
        campus = currentCampus;
    }
    
    buildingSelect.empty().append('<option value="">请选择楼栋</option>');
    
    if (campus && campusBuildings[campus]) {
        $.each(campusBuildings[campus], function(index, building) {
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
$('#new_attachments').change(function() {
    var preview = $('#newAttachmentPreview');
    preview.empty();
    
    var files = this.files;
    for (var i = 0; i < files.length; i++) {
        var file = files[i];
        var fileSize = (file.size / 1024 / 1024).toFixed(2) + ' MB';
        
        var fileDiv = $('<div class="attachment-item mb-2 p-2 border rounded">');
        
        // 创建缩略图或文件图标
        var thumbnailDiv = $('<div class="attachment-thumbnail me-3">');
        
        if (file.type.startsWith('image/')) {
            // 图片文件显示缩略图
            var reader = new FileReader();
            reader.onload = function(e) {
                var img = $('<img src="' + e.target.result + '" class="img-thumbnail" alt="' + file.name + '" style="width: 50px; height: 50px; object-fit: cover; cursor: pointer;">');
                thumbnailDiv.html(img);
                
                // 点击预览大图
                img.click(function(e) {
                    e.preventDefault();
                    showImagePreview(e.target.src, file.name);
                });
            };
            reader.readAsDataURL(file);
        } else {
            // 非图片文件显示文件图标
            var iconClass = getFileIcon(file.name);
            thumbnailDiv.html('<i class="' + iconClass + ' fa-lg"></i>');
        }
        
        // 文件信息
        var fileInfoDiv = $('<div class="attachment-info flex-grow-1">');
        fileInfoDiv.html(
            '<div class="attachment-name"><strong>' + file.name + '</strong></div>' +
            '<div class="attachment-size text-muted small">' + fileSize + '</div>'
        );
        
        fileDiv.append(thumbnailDiv);
        fileDiv.append(fileInfoDiv);
        preview.append(fileDiv);
    }
});

// 获取文件图标
function getFileIcon(filename) {
    var ext = filename.split('.').pop().toLowerCase();
    var iconMap = {
        'pdf': 'fas fa-file-pdf text-danger',
        'doc': 'fas fa-file-word text-primary',
        'docx': 'fas fa-file-word text-primary',
        'xls': 'fas fa-file-excel text-success',
        'xlsx': 'fas fa-file-excel text-success',
        'ppt': 'fas fa-file-powerpoint text-warning',
        'pptx': 'fas fa-file-powerpoint text-warning',
        'txt': 'fas fa-file-alt text-secondary',
        'zip': 'fas fa-file-archive text-info',
        'rar': 'fas fa-file-archive text-info',
        '7z': 'fas fa-file-archive text-info'
    };
    return iconMap[ext] || 'fas fa-file text-muted';
}

// 显示图片预览模态框
function showImagePreview(imageSrc, fileName) {
    // 移除已存在的模态框
    $('#imagePreviewModal').remove();
    
    var modalHtml = '<div class="modal fade" id="imagePreviewModal" tabindex="-1">' +
        '<div class="modal-dialog modal-lg modal-dialog-centered">' +
            '<div class="modal-content">' +
                '<div class="modal-header">' +
                    '<h5 class="modal-title">图片预览 - ' + fileName + '</h5>' +
                    '<button type="button" class="btn-close" data-bs-dismiss="modal"></button>' +
                '</div>' +
                '<div class="modal-body text-center p-0">' +
                    '<div class="d-flex justify-content-center align-items-center" style="min-height: 400px; background-color: #f8f9fa;">' +
                        '<img src="' + imageSrc + '" class="img-fluid" alt="' + fileName + '" style="max-height: 70vh; object-fit: contain;">' +
                    '</div>' +
                '</div>' +
                '<div class="modal-footer">' +
                    '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">关闭</button>' +
                '</div>' +
            '</div>' +
        '</div>' +
    '</div>';
    
    $('body').append(modalHtml);
    
    // 显示模态框
    var modal = new bootstrap.Modal(document.getElementById('imagePreviewModal'));
    modal.show();
    
    // 模态框关闭时移除DOM
    $('#imagePreviewModal').on('hidden.bs.modal', function () {
        $(this).remove();
    });
    
    // ESC键关闭模态框
    $(document).on('keydown', function(e) {
        if (e.keyCode === 27) { // ESC key
            $('#imagePreviewModal').modal('hide');
        }
    });
}
</script>
@endsection