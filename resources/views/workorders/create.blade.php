@extends('layouts.app')

@section('title', '创建工单')

@section('content')
<!-- 模板选择区域 -->
@if(session('from_template'))
<div class="p-3 rounded-lg bg-blue-50 text-blue-700 text-sm" role="alert">
    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 22a10 10 0 1 0 0-20 10 10 0 0 0 0 20z M12 16v-4 M12 8h.01"/></svg>
    <strong>使用模板创建工单：</strong> 您已从模板 "{{ session('template_name') }}" 预填充了表单内容，请根据需要修改后提交。
    <button type="button"  aria-label="关闭模板提示"></button>
</div>
@endif

<div class="flex items-center justify-between mb-6 pb-4 border-b border-border">
    <h1 class="text-xl font-semibold text-ink">创建工单</h1>
    <div class="flex gap-2">
        <a href="{{ \App\Helpers\UrlHelper::relative_url('/workorders') }}" class="btn btn-secondary">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 12H5 M12 19l-7-7 7-7"/></svg> 返回列表
        </a>
        <div class="flex gap-2">
            <button type="button" class="btn btn-secondary">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z M14 2v6h6 M16 13H8 M16 17H8 M10 9H8"/></svg> 使用模板
            </button>
            <ul class="card shadow-lg py-1 min-w-48 absolute right-0 mt-2 z-50">
                <li><h6 class="px-3 py-1 text-xs font-medium text-ink-muted">常用模板</h6></li>
                @foreach(\App\Models\WorkorderTemplate::where('is_active', true)->orderBy('name')->limit(5)->get() as $template)
                <li>
                    <a href="#" class="block px-3 py-2 text-sm text-ink hover:bg-surface-muted rounded-lg mx-1" onclick="useTemplate({{ $template->id }}, '{{ $template->name }}')">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z M14 2v6h6"/></svg> {{ $template->name }}
                    </a>
                </li>
                @endforeach
                <li><hr class="border-t border-border my-1"></li>
                <li>
                    <a href="{{ route('workorder-templates.index') }}" class="block px-3 py-2 text-sm text-ink hover:bg-surface-muted rounded-lg mx-1">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 6h13 M8 12h13 M8 18h13 M3 6h.01 M3 12h.01 M3 18h.01"/></svg> 管理模板
                    </a>
                </li>
            </ul>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <div class="card p-5">
            <div class="text-sm font-semibold text-ink mb-3">
                <h5 class="text-sm font-semibold text-ink">工单信息</h5>
            </div>
            <div>
                <form method="POST" action="{{ route('workorders.store') }}" enctype="multipart/form-data" id="workorderForm">
                    @csrf
                    
                    <!-- 自报家门：用户信息 -->
                    <div class="card mb-4">
                        <div class="px-4 py-3 border-b border-border bg-surface-muted rounded-t-xl">
                            <h6 class="mb-0"><svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2 M12 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8z"/></svg> 报修人信息</h6>
                        </div>
                        <div>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-3">
                                <div>
                                    <label for="contact_name" class="label">报修人 <span class="text-red-500">*</span></label>
                                    <input type="text" class="input" id="contact_name" name="contact_name"
                                           value="{{ old('contact_name') }}" required maxlength="100" autocomplete="name">
                                    @error('contact_name')
                                        <div class="text-xs text-red-600 mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div>
                                    <label for="contact_phone" class="label">联系电话 <span class="text-red-500">*</span></label>
                                    <input type="tel" class="input" id="contact_phone" name="contact_phone"
                                           value="{{ old('contact_phone') }}" required maxlength="20" autocomplete="tel">
                                    @error('contact_phone')
                                        <div class="text-xs text-red-600 mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div>
                                    <label for="contact_email" class="label">联系邮箱</label>
                                    <input type="email" class="input" id="contact_email" name="contact_email"
                                           value="{{ old('contact_email') }}" maxlength="100" autocomplete="email">
                                    @error('contact_email')
                                        <div class="text-xs text-red-600 mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <!-- 工单来源和优先级 -->
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-3">
                                <div>
                                    <label for="source" class="label">工单来源 <span class="text-red-500">*</span></label>
                                    <select class="input" id="source" name="source" required onchange="toggleCustomSource()">
                                        @foreach(\App\Models\WorkorderSource::getActiveSources() as $source)
                                            <option value="{{ $source->name }}" {{ old('source', '电话报修') == $source->name ? 'selected' : '' }}>
                                                {{ $source->name }}
                                            </option>
                                        @endforeach
                                   </select>
                                    @error('source')
                                        <div class="text-xs text-red-600 mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="sm:col-span-2">
                                    <label for="priority_low" class="label">优先级 <span class="text-red-500">*</span></label>
                                    <div class="flex flex-nowrap items-center gap-4">
                                        <div class="flex items-center gap-2">
                                            <input class="rounded border-border-strong w-4 h-4" type="radio" name="priority" id="priority_low" value="low"
                                                   {{ old('priority', 'medium') == 'low' ? 'checked' : '' }} autocomplete="off" required>
                                            <label class="text-sm" for="priority_low">
                                                <span class="badge bg-green-100 text-green-700">低</span>
                                            </label>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <input class="rounded border-border-strong w-4 h-4" type="radio" name="priority" id="priority_medium" value="medium"
                                                   {{ old('priority', 'medium') == 'medium' ? 'checked' : '' }} autocomplete="off" required>
                                            <label class="text-sm" for="priority_medium">
                                                <span class="badge bg-amber-100 text-amber-700">中</span>
                                            </label>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <input class="rounded border-border-strong w-4 h-4" type="radio" name="priority" id="priority_high" value="high"
                                                   {{ old('priority') == 'high' ? 'checked' : '' }} autocomplete="off" required>
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
                                           value="{{ old('other_source') }}" maxlength="50" autocomplete="off"
                                           placeholder="请说明具体的报修来源" autocomplete="off">
                                    @error('other_source')
                                        <div class="text-xs text-red-600 mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 自报家门：地址信息 -->
                    <div class="card mb-4">
                        <div class="px-4 py-3 border-b border-border bg-surface-muted rounded-t-xl">
                            <h6 class="mb-0"><svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z M12 13a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/></svg> 地址信息</h6>
                        </div>
                        <div>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-3">
                                <div>
                                    <label for="campus_id" class="label">校区 <span class="text-red-500">*</span></label>
                                    <select class="input" id="campus_id" name="campus_id" required>
                                        <option value="">请选择校区</option>
                                        @foreach(\App\Models\Campus::where('status', 'active')->orderBy('sort_order')->orderBy('name')->get() as $campus)
                                        <option value="{{ $campus->id }}" {{ old('campus_id') == $campus->id ? 'selected' : '' }}>{{ $campus->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('campus_id')
                                        <div class="text-xs text-red-600 mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div>
                                    <label for="building" class="label">楼栋 <span class="text-red-500">*</span></label>
                                    <select class="input" id="building" name="building" required>
                                        <option value="">请先选择校区</option>
                                    </select>
                                    @error('building')
                                        <div class="text-xs text-red-600 mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div>
                                    <label for="location_detail" class="label">详细地址</label>
                                    <input type="text" class="input" id="location_detail" name="location_detail" autocomplete="street-address"
                                           value="{{ old('location_detail') }}" maxlength="500" autocomplete="street-address"
                                           placeholder="如：301室" autocomplete="street-address">
                                    @error('location_detail')
                                        <div class="text-xs text-red-600 mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-3">
                                <div>
                                    <label for="department_name" class="label">所属部门</label>
                                    <input type="text" class="input" id="department_name" name="department_name" autocomplete="organization"
                                           value="{{ old('department_name') }}" maxlength="100" autocomplete="organization"
                                           placeholder="请填写部门名称（选填）" autocomplete="organization">
                                    @error('department_name')
                                        <div class="text-xs text-red-600 mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div>
                                    <label for="appointment_time_start" class="label">预约时间</label>
                                    <div class="flex gap-2">
                                        <input type="datetime-local" class="input flex-1" id="appointment_time_start" name="appointment_time_start"
                                               value="{{ old('appointment_time_start') }}" placeholder="开始" autocomplete="off">
                                        <input type="datetime-local" class="input flex-1" id="appointment_time_end" name="appointment_time_end"
                                               value="{{ old('appointment_time_end') }}" placeholder="结束" autocomplete="off">
                                    </div>

                                    @error('appointment_time_start')
                                        <div class="text-xs text-red-600 mt-1">{{ $message }}</div>
                                    @enderror
                                    @error('appointment_time_end')
                                        <div class="text-xs text-red-600 mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 工单信息 -->
                    <div class="card mb-4">
                        <div class="px-4 py-3 border-b border-border bg-surface-muted rounded-t-xl">
                            <h6 class="mb-0"><svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg> 工单信息</h6>
                        </div>
                        <div>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-3">
                                <div>
                                    <label for="category_main" class="label">工单大类 <span class="text-red-500">*</span></label>
                                    <select class="input" id="category_main" name="category_main" required>
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
                                        <div class="text-xs text-red-600 mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div>
                                    <label for="category_sub" class="label">故障分类 <span class="text-red-500">*</span></label>
                                    <select class="input" id="category_sub" name="category_sub" required>
                                        <option value="">请先选择工单大类</option>
                                    </select>
                                    @error('category_sub')
                                        <div class="text-xs text-red-600 mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label for="description" class="label">问题描述 <span class="text-red-500">*</span></label>
                                <textarea class="input" id="description" name="description" rows="3" required
                                          placeholder="请简要描述问题" autocomplete="off">{{ old('description') }}</textarea>
                                @error('description')
                                    <div class="text-xs text-red-600 mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-3">
                                <div>
                                    <label for="time_limit_hours" class="label">处理时限（小时）</label>
                                    <input type="number" class="input" id="time_limit_hours" name="time_limit_hours"
                                           value="{{ old('time_limit_hours') }}" min="1" max="168" step="1"
                                           placeholder="默认根据工单类型设置" autocomplete="off">
                                    @error('time_limit_hours')
                                        <div class="text-xs text-red-600 mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-3">
                                <div>
                                    <div class="flex items-center gap-2 mt-2">
                                        <input class="rounded border-border-strong w-4 h-4" type="checkbox" id="need_visit" name="need_visit"
                                               value="1" {{ old('need_visit') ? 'checked' : '' }} autocomplete="off">
                                        <label class="text-sm" for="need_visit">
                                            需要回访
                                        </label>
                                    </div>
                                </div>
                                <div>
                                    <div class="flex items-center gap-2 mt-2">
                                        <input class="rounded border-border-strong w-4 h-4" type="checkbox" id="is_emergency" name="is_emergency"
                                               value="1" {{ old('is_emergency') ? 'checked' : '' }} autocomplete="off">
                                        <label class="text-sm" for="is_emergency">
                                            紧急工单
                                        </label>
                                    </div>
                                </div>
                                <div>
                                    <div class="flex items-center gap-2 mt-2">
                                        <input class="rounded border-border-strong w-4 h-4" type="checkbox" id="requires_signature" name="requires_signature"
                                               value="1" {{ old('requires_signature') ? 'checked' : '' }} autocomplete="off">
                                        <label class="text-sm" for="requires_signature">
                                            需签单
                                        </label>
                                    </div>



                                </div>
                                @if(auth()->user()->canUsePhoneAssist())
                                <div>
                                    <div class="flex items-center gap-2 mt-2">
                                        <input class="rounded border-border-strong w-4 h-4" type="checkbox" id="phone_assisted" name="phone_assisted"
                                               value="1" {{ old('phone_assisted') ? 'checked' : '' }} autocomplete="off">
                                        <label class="text-sm" for="phone_assisted">
                                            电话协助完成
                                        </label>
                                    </div>
                                </div>
                                @endif
                            </div>
                            
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-3">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <label for="assignee_id" class="label whitespace-nowrap shrink-0">指定接单工程师</label>
                                        <select class="input" id="assignee_id" name="assignee_id">
                                            <option value="">不指定（工程师自行接单）</option>
                                            @foreach(\App\Models\User::getAssignableEngineers() as $engineer)
                                            <option value="{{ $engineer->id }}" {{ old('assignee_id') == $engineer->id ? 'selected' : '' }}>
                                                {{ $engineer->name }} - {{ $engineer->department?->name }}
                                            </option>
                                            @endforeach
                                            <option value="other">其他部门</option>
                                        </select>
                                    </div>
                                </div>
                                <div id="other_reason_div" style="display: none;">
                                    <label for="other_reason" class="label">其他部门原因</label>
                                    <textarea class="input" id="other_reason" name="other_reason" rows="2"
                                              placeholder="请说明选择其他部门的原因">{{ old('other_reason') }}</textarea>
                                    <div class="text-xs text-ink-muted mt-1">如果选择其他部门，请说明原因</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 附件上传 -->
                    <div class="card mb-4">
                        <div class="px-4 py-3 border-b border-border bg-surface-muted rounded-t-xl">
                            <h6 class="mb-0"><i class="fas fa-paperclip"></i> 附件上传</h6>
                        </div>
                        <div>
                            <div class="mb-4">
                                <label for="attachments" class="label">相关附件</label>
                                <div class="flex gap-2 mb-1">
                                    <button type="button" onclick="openCameraModal('attachments')" class="btn btn-secondary flex-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z M12 17a4 4 0 1 0 0-8 4 4 0 0 0 0 8z"/></svg>
                                        <span>拍照</span>
                                    </button>
                                    <button type="button" onclick="document.getElementById('attachments').click()" class="btn btn-secondary flex-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4 M7 10l5 5 5-5 M12 15V3"/></svg>
                                        <span>选择文件</span>
                                    </button>
                                </div>
                                <input type="file" class="sr-only" id="attachments" name="attachments[]" multiple accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.txt" onchange="document.getElementById('attCreateName').textContent=this.files.length? '已选择 '+this.files.length+' 个文件':'未选择文件'">

                                <div id="attCreateName" class="text-xs mt-1" style="color: var(--c-ink-subtle);">未选择文件</div>





                                <div id="attachmentPreview" class="mt-2"></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 备注 -->
                    <div class="card mb-4">
                        <div class="px-4 py-3 border-b border-border bg-surface-muted rounded-t-xl">
                            <h6 class="mb-0"><i class="fas fa-comment"></i> 备注</h6>
                        </div>
                        <div>
                            <div class="mb-4">
                                <label for="remarks" class="label">其他说明</label>
                                <textarea class="input" id="remarks" name="remarks" rows="3"
                                          placeholder="其他需要说明的信息" autocomplete="off">{{ old('remarks') }}</textarea>
                                @error('remarks')
                                    <div class="text-xs text-red-600 mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                    
                    
                    
                    <!-- 提交按钮 -->
                    <div class="d-flex justify-content-end">
                        <a href="{{ \App\Helpers\UrlHelper::relative_url('/workorders') }}" class="btn btn-secondary mr-2">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18 6L6 18M6 6l12 12"/></svg> 取消
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z M17 21v-8H7v8 M7 3v5h8"/></svg> 创建工单
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div>
        <!-- 创建提示 -->
        <div class="card mb-4">
            <div class="text-sm font-semibold text-ink mb-3">
                <h6 class="text-sm font-semibold text-ink">创建提示</h6>
            </div>
            <div>
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
        
        <!-- 常见问题 -->
        <div class="card p-5">
            <div class="text-sm font-semibold text-ink mb-3">
                <h6 class="text-sm font-semibold text-ink">常见问题</h6>
            </div>
            <div>
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
@include('workorders._camera')
@endsection

@section('scripts')
<script>
// 从地址管理获取校区楼栋数据
var campusBuildings = @json(\App\Models\Location::getCampusBuildings());
var campuses = @json(\App\Models\Campus::where('status', 'active')->orderBy('sort_order')->orderBy('name')->get());

// 工单分类数据
var categoryData = @json($categories);

$(document).ready(function() {
    // 校区变更时更新楼栋选项
    $('#campus_id').change(function() {
        var campusId = $(this).val();
        var buildingSelect = $('#building');
        
        buildingSelect.empty().append('<option value="">请选择楼栋</option>');
        
        // 将校区ID转换为数字类型，确保能正确匹配campusBuildings的键
        var campusIdNum = parseInt(campusId);
        
        if (campusId && campusBuildings[campusIdNum]) {
            $.each(campusBuildings[campusIdNum].buildings, function(index, building) {
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
            $('#appointment_time_start').closest('.col-md-6').hide();
            $('#need_visit').closest('.col-md-3').hide();
            // 显示电话协助解决方案输入框
            if (!$('#phone_solution_div').length) {
                var solutionDiv = $('<div id="phone_solution_div">');
                solutionDiv.html(
                    '<div class="mb-4">' +
                        '<label for="phone_solution" class="label">电话解决方案 <span class="text-red-500">*</span></label>' +
                        '<textarea class="input" id="phone_solution" name="phone_solution" rows="4" required' +
                                  'placeholder="请详细描述电话解决方案..."></textarea>' +
                        '<div class="text-xs text-ink-muted mt-1">选择电话协助完成后，工单将直接标记为已解决状态</div>' +
                    '</div>'
                );
                $(this).closest('.row').after(solutionDiv);
            }
        } else {
            $('#appointment_time_start').closest('.col-md-6').show();
            $('#need_visit').closest('.col-md-3').show();
            $('#phone_solution_div').remove();
        }
    });
    
    // 附件预览
    $('#attachments').change(async function() {
        var preview = $('#attachmentPreview');
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
        var fileDiv = $('<div class="attachment-item mb-3 p-3 border rounded" data-file-index="' + fileIndex + '">');
        var thumbnailDiv = $('<div class="attachment-thumbnail mr-3">');
        
        if (file.type.startsWith('image/')) {
            // 图片文件显示缩略图
            var reader = new FileReader();
            reader.onload = function(e) {
                var img = $('<img src="' + e.target.result + '" class="rounded-lg border border-border" alt="' + file.name + '" style="cursor: pointer;">');
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
            thumbnailDiv.html('<i class="' + iconClass + ' text-4xl"></i>');
        }
        
        // 文件信息和描述
        var fileInfoDiv = $('<div class="attachment-info flex-grow-1">');
        var fileSize = options.fileSize || (file.size / 1024 / 1024).toFixed(2) + ' MB';
        var fileSizeHtml = '<div class="attachment-size text-ink-muted small">' + fileSize;
        
        // 如果是压缩后的图片，显示压缩信息
        if (options.isCompressed) {
            fileSizeHtml += ' <span class="badge bg-green-100 text-green-700">已压缩</span>';
            fileSizeHtml += '<div class="text-ink-muted small">原始: ' + options.originalSize + ' → 压缩后: ' + options.compressedSize + '</div>';
            if (options.compressionRatio) {
                fileSizeHtml += '<div class="text-ink-muted small">压缩率: ' + options.compressionRatio + '</div>';
            }
        }
        
        fileSizeHtml += '</div>';
        
        fileInfoDiv.html(
            '<div class="attachment-name"><strong>' + (options.originalName || file.name) + '</strong></div>' +
            fileSizeHtml +
            '<div class="attachment-description mt-2">' +
                '<label class="label small" for="attachment-desc-' + fileIndex + '">附件描述（选填）</label>' +
                '<input type="text" class="input attachment-desc-input" ' +
                       'id="attachment-desc-' + fileIndex + '" ' +
                       'data-file-index="' + fileIndex + '" ' +
                       'placeholder="请输入附件描述，如不填写将显示文件名"' +
                       'maxlength="200" autocomplete="off">' +
            '</div>' +
            '<div class="attachment-actions mt-2">' +
                '<button type="button" class="btn btn-sm btn-outline-danger remove-attachment" data-file-index="' + fileIndex + '">' +
                '<svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18 6L6 18M6 6l12 12"/></svg> 移除' +
                '</button>' +
            '</div>'
        );
        
        fileDiv.append(thumbnailDiv);
        fileDiv.append(fileInfoDiv);
        previewContainer.append(fileDiv);
    }
    
    // 更新文件输入框
    function updateFileInput(files) {
        var fileInput = $('#attachments')[0];
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
                        
                        // 尝试不同的质量级别
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
    
    // 移除附件
    function removeAttachment(button) {
        var fileIndex = $(button).data('file-index');
        $(button).closest('.attachment-item').remove();
        
        // 更新文件输入框
        var fileInput = $('#attachments')[0];
        var dt = new DataTransfer();
        var files = Array.from(fileInput.files);
        
        // 移除对应索引的文件
        files = files.filter((file, index) => index != fileIndex);
        
        // 重新添加剩余的文件
        files.forEach(file => dt.items.add(file));
        fileInput.files = dt.files;
        
        // 重新触发change事件以更新预览
        $('#attachments').trigger('change');
    }
    
    // 使用事件委托处理移除附件
    $(document).on('click', '.remove-attachment', function() {
        removeAttachment(this);
    });
    
    // 表单提交前验证和收集附件描述
    $('#workorderForm').on('submit', function(e) {
        // 验证必填字段
        var building = $('#building').val();
        var categorySub = $('#category_sub').val();
        var source = $('#source').val();
        var otherSource = $('#other_source').val();
        
        // 验证楼栋
        if (!building || building === '') {
            e.preventDefault();
            alert('请选择楼栋');
            $('#building').focus();
            return false;
        }
        
        // 验证故障分类
        if (!categorySub || categorySub === '') {
            e.preventDefault();
            alert('请选择故障分类');
            $('#category_sub').focus();
            return false;
        }
        
        // 验证其他来源
        if (source === '其他来源' && (!otherSource || otherSource.trim() === '')) {
            e.preventDefault();
            alert('请填写其他来源说明');
            $('#other_source').focus();
            return false;
        }
        
        // 收集附件描述
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
    
    // 切换其他来源输入框显示/隐藏
    window.toggleCustomSource = function() {
        var sourceSelect = $('#source');
        var otherSourceRow = $('#other_source_row');
        var otherSourceInput = $('#other_source');
        
        if (sourceSelect.val() === '其他来源') {
            otherSourceRow.show();
            otherSourceInput.attr('required', 'required');
        } else {
            otherSourceRow.hide();
            otherSourceInput.removeAttr('required');
            otherSourceInput.val('');
        }
    }
    
    // 初始化其他来源显示状态
    if ($('#source').val() === '其他来源') {
        $('#other_source_row').show();
        $('#other_source').attr('required', 'required');
    }
});

// 使用模板创建工单
function useTemplate(templateId, templateName) {
    window.location.href = '{{ \App\Helpers\UrlHelper::relative_url('/workorders/create') }}?template=' + templateId;
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

