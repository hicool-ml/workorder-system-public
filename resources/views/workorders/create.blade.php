@extends('layouts.app')

@section('title', '创建工单')

@section('content')
<!-- 模板选择区域 -->
@if(session('from_template'))
<div class="alert alert-info alert-dismissible fade show" role="alert">
    <i class="fas fa-info-circle"></i>
    <strong>使用模板创建工单：</strong> 您已从模板 "{{ session('template_name') }}" 预填充了表单内容，请根据需要修改后提交。
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">创建工单</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="{{ \App\Helpers\UrlHelper::relative_url('/workorders') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> 返回列表
        </a>
        <div class="btn-group ms-2">
            <button type="button" class="btn btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown">
                <i class="fas fa-file-alt"></i> 使用模板
            </button>
            <ul class="dropdown-menu">
                <li><h6 class="dropdown-header">常用模板</h6></li>
                @foreach(\App\Models\WorkorderTemplate::where('is_active', true)->orderBy('name')->limit(5)->get() as $template)
                <li>
                    <a href="#" class="dropdown-item" onclick="useTemplate({{ $template->id }}, '{{ $template->name }}')">
                        <i class="fas fa-file"></i> {{ $template->name }}
                    </a>
                </li>
                @endforeach
                <li><hr class="dropdown-divider"></li>
                <li>
                    <a href="{{ route('workorder-templates.index') }}" class="dropdown-item">
                        <i class="fas fa-list"></i> 管理模板
                    </a>
                </li>
            </ul>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">工单信息</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('workorders.store') }}" enctype="multipart/form-data" id="workorderForm">
                    @csrf
                    
                    <!-- 自报家门：用户信息 -->
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h6 class="mb-0"><i class="fas fa-user"></i> 报修人信息</h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3 mb-3">
                                <div class="col-md-4">
                                    <label for="contact_name" class="form-label">报修人 <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="contact_name" name="contact_name"
                                           value="{{ old('contact_name') }}" required maxlength="100">
                                    @error('contact_name')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-4">
                                    <label for="contact_phone" class="form-label">联系电话 <span class="text-danger">*</span></label>
                                    <input type="tel" class="form-control" id="contact_phone" name="contact_phone"
                                           value="{{ old('contact_phone') }}" required maxlength="20">
                                    @error('contact_phone')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-4">
                                    <label for="contact_email" class="form-label">联系邮箱</label>
                                    <input type="email" class="form-control" id="contact_email" name="contact_email"
                                           value="{{ old('contact_email') }}" maxlength="100">
                                    @error('contact_email')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <!-- 工单来源和优先级 -->
                            <div class="row g-3 mb-3">
                                <div class="col-md-4">
                                    <label for="source" class="form-label">工单来源 <span class="text-danger">*</span></label>
                                    <select class="form-select" id="source" name="source" required onchange="toggleCustomSource()">
                                        <option value="phone" {{ old('source', 'phone') == 'phone' ? 'selected' : '' }}>电话</option>
                                        <option value="web" {{ old('source') == 'web' ? 'selected' : '' }}>网络</option>
                                        <option value="scene" {{ old('source') == 'scene' ? 'selected' : '' }}>现场</option>
                                        <option value="email" {{ old('source') == 'email' ? 'selected' : '' }}>邮件</option>
                                        <option value="other" {{ old('source') == 'other' ? 'selected' : '' }}>其他</option>
                                        <option value="custom" {{ old('source') == 'custom' ? 'selected' : '' }}>添加新渠道</option>
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
                                                   {{ old('priority', 'medium') == 'low' ? 'checked' : '' }}>
                                            <label class="form-check-label" for="priority_low">
                                                <span class="badge bg-success">低</span>
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="priority" id="priority_medium" value="medium"
                                                   {{ old('priority', 'medium') == 'medium' ? 'checked' : '' }}>
                                            <label class="form-check-label" for="priority_medium">
                                                <span class="badge bg-warning">中</span>
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="priority" id="priority_high" value="high"
                                                   {{ old('priority') == 'high' ? 'checked' : '' }}>
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
                                           value="{{ old('custom_source') }}" maxlength="50"
                                           placeholder="请输入新的报修渠道名称">
                                    @error('custom_source')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 自报家门：地址信息 -->
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h6 class="mb-0"><i class="fas fa-map-marker-alt"></i> 地址信息</h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3 mb-3">
                                <div class="col-md-4">
                                    <label for="campus" class="form-label">校区 <span class="text-danger">*</span></label>
                                    <select class="form-select" id="campus" name="campus" required>
                                        <option value="">请选择校区</option>
                                        <option value="old_campus" {{ old('campus') == 'old_campus' ? 'selected' : '' }}>老校区</option>
                                        <option value="new_campus" {{ old('campus') == 'new_campus' ? 'selected' : '' }}>新校区</option>
                                        <option value="asean_campus" {{ old('campus') == 'asean_campus' ? 'selected' : '' }}>东盟校区</option>
                                    </select>
                                    @error('campus')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-4">
                                    <label for="building" class="form-label">楼栋 <span class="text-danger">*</span></label>
                                    <select class="form-select" id="building" name="building" required>
                                        <option value="">请先选择校区</option>
                                    </select>
                                    @error('building')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-4">
                                    <label for="location_detail" class="form-label">详细地址</label>
                                    <input type="text" class="form-control" id="location_detail" name="location_detail"
                                           value="{{ old('location_detail') }}" maxlength="500"
                                           placeholder="如：301室">
                                    @error('location_detail')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label for="department_name" class="form-label">所属部门</label>
                                    <input type="text" class="form-control" id="department_name" name="department_name"
                                           value="{{ old('department_name') }}" maxlength="100"
                                           placeholder="请填写部门名称（选填）">
                                    @error('department_name')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label for="appointment_time" class="form-label">预约时间</label>
                                    <input type="datetime-local" class="form-control" id="appointment_time" name="appointment_time"
                                           value="{{ old('appointment_time') }}">
                                    @error('appointment_time')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 工单信息 -->
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h6 class="mb-0"><i class="fas fa-tools"></i> 工单信息</h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3 mb-3">
                                <div class="col-md-4">
                                    <label for="category_main" class="form-label">工单大类 <span class="text-danger">*</span></label>
                                    <select class="form-select" id="category_main" name="category_main" required>
                                        <option value="">请选择工单大类</option>
                                        @foreach($categories['main'] as $category)
                                        <option value="{{ $category->id }}"
                                                data-prefix="{{ $category->ticket_prefix }}"
                                                data-hours="{{ $category->default_hours }}"
                                                {{ old('category_main') == $category->id ? 'selected' : '' }}>
                                            {{ $category->name }}
                                        </option>
                                        @endforeach
                                    </select>
                                    @error('category_main')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-4">
                                    <label for="category_sub" class="form-label">故障分类 <span class="text-danger">*</span></label>
                                    <select class="form-select" id="category_sub" name="category_sub" required>
                                        <option value="">请先选择工单大类</option>
                                    </select>
                                    @error('category_sub')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="description" class="form-label">问题描述 <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="description" name="description" rows="3" required
                                          placeholder="请简要描述问题">{{ old('description') }}</textarea>
                                @error('description')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="row g-3 mb-3">
                                <div class="col-md-12">
                                    <label for="time_limit_hours" class="form-label">处理时限（小时）</label>
                                    <input type="number" class="form-control" id="time_limit_hours" name="time_limit_hours"
                                           value="{{ old('time_limit_hours') }}" min="1" max="168" step="1"
                                           placeholder="默认根据工单类型设置">
                                    @error('time_limit_hours')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="row g-3 mb-3">
                                <div class="col-md-4">
                                    <div class="form-check mt-2">
                                        <input class="form-check-input" type="checkbox" id="need_visit" name="need_visit"
                                               value="1" {{ old('need_visit') ? 'checked' : '' }}>
                                        <label class="form-check-label" for="need_visit">
                                            需要回访
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check mt-2">
                                        <input class="form-check-input" type="checkbox" id="is_emergency" name="is_emergency"
                                               value="1" {{ old('is_emergency') ? 'checked' : '' }}>
                                        <label class="form-check-label" for="is_emergency">
                                            紧急工单
                                        </label>
                                    </div>
                                </div>
                                @if(auth()->user()->canUsePhoneAssist())
                                <div class="col-md-4">
                                    <div class="form-check mt-2">
                                        <input class="form-check-input" type="checkbox" id="phone_assisted" name="phone_assisted"
                                               value="1" {{ old('phone_assisted') ? 'checked' : '' }}>
                                        <label class="form-check-label" for="phone_assisted">
                                            电话协助完成
                                        </label>
                                    </div>
                                </div>
                                @endif
                            </div>
                            
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label for="assignee_id" class="form-label">指定接单工程师</label>
                                    <select class="form-select" id="assignee_id" name="assignee_id">
                                        <option value="">不指定（工程师自行接单）</option>
                                        @foreach(\App\Models\User::getAssignableEngineers() as $engineer)
                                        <option value="{{ $engineer->id }}" {{ old('assignee_id') == $engineer->id ? 'selected' : '' }}>
                                            {{ $engineer->name }} - {{ $engineer->department?->name }}
                                        </option>
                                        @endforeach
                                        <option value="other">其他部门</option>
                                    </select>
                                    <div class="form-text">可以选择指定工程师，或不指定由工程师自行接单</div>
                                </div>
                                <div class="col-md-6" id="other_reason_div" style="display: none;">
                                    <label for="other_reason" class="form-label">其他部门原因</label>
                                    <textarea class="form-control" id="other_reason" name="other_reason" rows="2"
                                              placeholder="请说明选择其他部门的原因">{{ old('other_reason') }}</textarea>
                                    <div class="form-text">如果选择其他部门，请说明原因</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 附件上传 -->
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h6 class="mb-0"><i class="fas fa-paperclip"></i> 附件上传</h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="attachments" class="form-label">相关附件</label>
                                <input type="file" class="form-control" id="attachments" name="attachments[]"
                                       multiple accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.txt">
                                <div class="form-text">
                                    支持上传图片、文档等文件，单个文件最大10MB，最多5个文件<br>
                                    <small class="text-info">大图片将自动压缩以减少文件大小，提高上传成功率</small>
                                </div>
                                <div id="attachmentPreview" class="mt-2"></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 备注 -->
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h6 class="mb-0"><i class="fas fa-comment"></i> 备注</h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="remarks" class="form-label">其他说明</label>
                                <textarea class="form-control" id="remarks" name="remarks" rows="3"
                                          placeholder="其他需要说明的信息">{{ old('remarks') }}</textarea>
                                @error('remarks')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                    
                    
                    <!-- 提交按钮 -->
                    <div class="d-flex justify-content-end">
                        <a href="{{ \App\Helpers\UrlHelper::relative_url('/workorders') }}" class="btn btn-secondary me-2">
                            <i class="fas fa-times"></i> 取消
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> 创建工单
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <!-- 创建提示 -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="card-title mb-0">创建提示</h6>
            </div>
            <div class="card-body">
                <ul class="mb-0">
                    <li>请尽可能详细地描述问题</li>
                    <li>提供准确的联系方式和位置信息</li>
                    <li>如有相关截图或文件，请上传附件</li>
                    <li>紧急问题请标记为"紧急工单"</li>
                    <li>系统将根据工单类型自动生成工单编号</li>
                    <li>可以设置预约时间，技术人员将按预约时间处理</li>
                </ul>
            </div>
        </div>
        
        <!-- 优先级说明 -->
        <div class="card mb-4">
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
        
        <!-- 常见问题 -->
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">常见问题</h6>
            </div>
            <div class="card-body">
                <div class="accordion" id="faqAccordion">
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                    data-bs-target="#faq1">
                                网络无法连接怎么办？
                            </button>
                        </h2>
                        <div id="faq1" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                请先检查网线是否插好，重启电脑和路由器，如仍无法解决请提交工单。
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                    data-bs-target="#faq2">
                                打印机无法打印怎么办？
                            </button>
                        </h2>
                        <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                请检查打印机电源、纸张和墨盒，重启打印服务，如仍无法解决请提交工单。
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
// 从地址管理获取校区楼栋数据
var campusBuildings = @json(\App\Models\Location::getCampusBuildings());

// 工单分类数据
var categoryData = @json($categories);

$(document).ready(function() {
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
    
    // 故障分类变更时更新处理时限
    $('#category_sub').change(function() {
        var subCategoryId = $(this).val();
        var timeLimitInput = $('#time_limit_hours');
        
        if (subCategoryId) {
            // 可以在这里根据子分类调整时限
        }
    });
    
    // 电话协助完成选项处理
    $('#phone_assisted').change(function() {
        if ($(this).is(':checked')) {
            // 如果选择电话协助完成，可以隐藏一些不必要的字段
            $('#appointment_time').closest('.col-md-6').hide();
            $('#need_visit').closest('.col-md-4').hide();
            // 显示电话协助解决方案输入框
            if (!$('#phone_solution_div').length) {
                var solutionDiv = $('<div class="col-md-12" id="phone_solution_div">');
                solutionDiv.html(
                    '<div class="mb-3">' +
                        '<label for="phone_solution" class="form-label">电话解决方案 <span class="text-danger">*</span></label>' +
                        '<textarea class="form-control" id="phone_solution" name="phone_solution" rows="4" required' +
                                  'placeholder="请详细描述电话解决方案..."></textarea>' +
                        '<div class="form-text">选择电话协助完成后，工单将直接标记为已解决状态</div>' +
                    '</div>'
                );
                $(this).closest('.row').after(solutionDiv);
            }
        } else {
            $('#appointment_time').closest('.col-md-6').show();
            $('#need_visit').closest('.col-md-4').show();
            $('#phone_solution_div').remove();
        }
    });
    
    // 附件预览
    $('#attachments').change(function() {
        var preview = $('#attachmentPreview');
        preview.empty();
        
        var files = this.files;
        for (var i = 0; i < files.length; i++) {
            var file = files[i];
            var fileSize = (file.size / 1024 / 1024).toFixed(2) + ' MB';
            var fileIndex = i;
            var isLargeImage = false;
            var willBeCompressed = false;
            
            // 检查是否为大图片
            if (file.type.startsWith('image/') && file.size > 2 * 1024 * 1024) {
                isLargeImage = true;
                willBeCompressed = true;
            }
            
            var fileDiv = $('<div class="attachment-item mb-3 p-3 border rounded">');
            
            // 创建缩略图或文件图标
            var thumbnailDiv = $('<div class="attachment-thumbnail me-3">');
            
            if (file.type.startsWith('image/')) {
                // 图片文件显示缩略图
                var reader = new FileReader();
                reader.onload = function(e) {
                    var img = $('<img src="' + e.target.result + '" class="img-thumbnail" alt="' + file.name + '" style="cursor: pointer;">');
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
                thumbnailDiv.html('<i class="' + iconClass + ' fa-3x"></i>');
            }
            
            // 文件信息和描述
            var fileInfoDiv = $('<div class="attachment-info flex-grow-1">');
            var fileSizeHtml = '<div class="attachment-size text-muted small">' + fileSize;
            
            // 如果是大图片，添加压缩提示
            if (willBeCompressed) {
                fileSizeHtml += ' <span class="badge bg-info ms-1">将自动压缩</span>';
            }
            
            fileSizeHtml += '</div>';
            
            fileInfoDiv.html(
                '<div class="attachment-name"><strong>' + file.name + '</strong></div>' +
                fileSizeHtml +
                '<div class="attachment-description mt-2">' +
                    '<label class="form-label small">附件描述（选填）</label>' +
                    '<input type="text" class="form-control form-control-sm attachment-desc-input" ' +
                           'data-file-index="' + fileIndex + '" ' +
                           'placeholder="请输入附件描述，如不填写将显示文件名">' +
                           'maxlength="200">' +
                '</div>' +
                '<div class="attachment-actions mt-2">' +
                    '<button type="button" class="btn btn-sm btn-outline-danger remove-attachment" onclick="removeAttachment(this)">' +
                    '<i class="fas fa-times"></i> 移除' +
                    '</button>' +
                '</div>'
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
    
    // 移除附件
    function removeAttachment(button) {
        $(button).closest('.attachment-item').remove();
        
        // 更新文件输入框
        var fileInput = $('#attachments')[0];
        var dt = new DataTransfer();
        var files = Array.from(fileInput.files);
        
        // 移除对应的文件
        var fileName = $(button).closest('.attachment-item').find('.attachment-name strong').text();
        files = files.filter(file => file.name !== fileName);
        
        files.forEach(file => dt.items.add(file));
        fileInput.files = dt.files;
    }
    
    // 表单提交前收集附件描述
    $('#workorderForm').on('submit', function(e) {
        var descriptions = [];
        $('.attachment-desc-input').each(function() {
            descriptions.push($(this).val());
        });
        
        // 创建隐藏字段来存储附件描述
        for (var i = 0; i < descriptions.length; i++) {
            $('<input>').attr({
                type: 'hidden',
                name: 'attachment_descriptions[' + i + ']',
                value: descriptions[i]
            }).appendTo('#workorderForm');
        }
    });
    
    // 指定工程师选择处理
    $('#assignee_id').change(function() {
        var otherReasonDiv = $('#other_reason_div');
        if ($(this).val() === 'other') {
            otherReasonDiv.show();
            $('#other_reason').attr('required', 'required');
        } else {
            otherReasonDiv.hide();
            $('#other_reason').removeAttr('required');
        }
    });
    
    // 初始化其他部门原因显示状态
    if ($('#assignee_id').val() === 'other') {
        $('#other_reason_div').show();
        $('#other_reason').attr('required', 'required');
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
    
    // 初始化自定义来源显示状态
    if ($('#source').val() === 'custom') {
        $('#custom_source_row').show();
        $('#custom_source').attr('required', 'required');
    }
});

// 使用模板创建工单
function useTemplate(templateId, templateName) {
    window.location.href = '{{ \App\Helpers\UrlHelper::relative_url('/workorders/create') }}?template=' + templateId;
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