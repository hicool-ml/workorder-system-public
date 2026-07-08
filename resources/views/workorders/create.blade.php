@extends('layouts.app')

@section('title', '创建工单')

@section('content')

{{-- Template notice --}}
@if(session('from_template'))
<div class="flash-msg flex items-center gap-3 px-4 py-3 mb-4 rounded-xl bg-blue-50 border border-blue-200 text-blue-800 text-sm" role="alert">
    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 16v-4M12 8h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/></svg>
    <span>已从模板 "{{ session('template_name') }}" 预填充内容，请修改后提交。</span>
</div>
@endif

{{-- Page header --}}
<div class="flex items-center justify-between mb-6 gap-3 flex-wrap">
    <div>
        <h1 class="text-xl font-semibold text-ink">创建工单</h1>
        <p class="text-sm text-ink-muted mt-0.5">填写信息提交工单</p>
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ route('workorders.index') }}" class="btn btn-secondary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7 7-7M3 12h18"/></svg>
            <span>返回</span>
        </a>
        @if(auth()->user()->canManageWorkorderTypes())
        <div class="relative">
            <button type="button" id="templateDropdownBtn" class="btn btn-secondary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z M14 2v6h6"/></svg>
                <span>模板</span>
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div id="templateDropdownMenu" class="hidden absolute right-0 mt-2 w-56 card shadow-lg py-1 z-50 max-h-72 overflow-y-auto">
                <p class="px-3 py-1.5 text-xs text-ink-subtle">常用模板</p>
                @foreach(\App\Models\WorkorderTemplate::where('is_active', true)->orderBy('name')->limit(5)->get() as $template)
                <a href="{{ route('workorders.create') }}?template={{ $template->id }}" class="block px-3 py-2 text-sm hover:bg-surface-muted" style="color: var(--c-ink);">{{ $template->name }}</a>
                @endforeach
                <div class="border-t border-border my-1"></div>
                <a href="{{ route('workorder-templates.index') }}" class="block px-3 py-2 text-sm hover:bg-surface-muted" style="color: var(--c-ink);">管理模板</a>
            </div>
        </div>
        @endif
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    {{-- Main form --}}
    <div class="lg:col-span-2 space-y-4">
        <form method="POST" action="{{ route('workorders.store') }}" enctype="multipart/form-data" id="workorderForm">
            @csrf

            {{-- Contact info --}}
            <div class="card p-5">
                <h2 class="text-sm font-semibold text-ink mb-4 flex items-center gap-2">
                    <svg class="w-4 h-4 text-brand-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2 M12 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8z"/></svg>
                    报修人信息
                </h2>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="label" for="contact_name">报修人 <span class="text-red-500">*</span></label>
                        <input type="text" class="input" id="contact_name" name="contact_name" value="{{ old('contact_name') }}" required maxlength="100" placeholder="姓名">
                        @error('contact_name')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="label" for="contact_phone">联系电话 <span class="text-red-500">*</span></label>
                        <input type="tel" class="input" id="contact_phone" name="contact_phone" value="{{ old('contact_phone') }}" required maxlength="20" placeholder="手机号">
                        @error('contact_phone')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="label" for="contact_email">联系邮箱</label>
                        <input type="email" class="input" id="contact_email" name="contact_email" value="{{ old('contact_email') }}" maxlength="100" placeholder="选填">
                        @error('contact_email')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
                    <div>
                        <label class="label" for="source">工单来源 <span class="text-red-500">*</span></label>
                        <select class="input" id="source" name="source" required>
                            <option value="phone" {{ old('source', 'phone') == 'phone' ? 'selected' : '' }}>电话</option>
                            <option value="web" {{ old('source') == 'web' ? 'selected' : '' }}>网络</option>
                            <option value="scene" {{ old('source') == 'scene' ? 'selected' : '' }}>现场</option>
                            <option value="email" {{ old('source') == 'email' ? 'selected' : '' }}>邮件</option>
                            <option value="other" {{ old('source') == 'other' ? 'selected' : '' }}>其他</option>
                            <option value="custom" {{ old('source') == 'custom' ? 'selected' : '' }}>添加新渠道</option>
                        </select>
                        @error('source')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="label">优先级 <span class="text-red-500">*</span></label>
                        <div class="flex items-center gap-2 mt-1">
                            @foreach(['low' => '低', 'medium' => '中', 'high' => '高'] as $val => $label)
                            <label class="flex-1 cursor-pointer">
                                <input type="radio" name="priority" value="{{ $val }}" class="peer sr-only" {{ old('priority', 'medium') == $val ? 'checked' : '' }}>
                                <span class="block text-center py-2 px-3 rounded-lg border border-border-strong text-sm font-medium transition-colors peer-checked:bg-brand-600 peer-checked:text-white peer-checked:border-brand-600" style="color: var(--c-ink-muted);">{{ $label }}</span>
                            </label>
                            @endforeach
                        </div>
                        @error('priority')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div id="custom_source_row" class="hidden mt-4">
                    <label class="label" for="custom_source">新渠道名称 <span class="text-red-500">*</span></label>
                    <input type="text" class="input" id="custom_source" name="custom_source" value="{{ old('custom_source') }}" maxlength="50" placeholder="请输入新的报修渠道名称">
                    @error('custom_source')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                </div>
            </div>

            {{-- Location --}}
            <div class="card p-5">
                <h2 class="text-sm font-semibold text-ink mb-4 flex items-center gap-2">
                    <svg class="w-4 h-4 text-brand-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z M12 13a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/></svg>
                    地址信息
                </h2>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="label" for="campus">校区 <span class="text-red-500">*</span></label>
                        <select class="input" id="campus" name="campus" required>
                            <option value="">请选择校区</option>
                            <option value="old_campus" {{ old('campus') == 'old_campus' ? 'selected' : '' }}>老校区</option>
                            <option value="new_campus" {{ old('campus') == 'new_campus' ? 'selected' : '' }}>新校区</option>
                            <option value="asean_campus" {{ old('campus') == 'asean_campus' ? 'selected' : '' }}>东盟校区</option>
                        </select>
                        @error('campus')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="label" for="building">楼栋 <span class="text-red-500">*</span></label>
                        <select class="input" id="building" name="building" required>
                            <option value="">请先选择校区</option>
                        </select>
                        @error('building')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="label" for="location_detail">详细地址</label>
                        <input type="text" class="input" id="location_detail" name="location_detail" value="{{ old('location_detail') }}" maxlength="500" placeholder="如：301室">
                        @error('location_detail')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
                    <div>
                        <label class="label" for="department_name">所属部门</label>
                        <input type="text" class="input" id="department_name" name="department_name" value="{{ old('department_name') }}" maxlength="100" placeholder="选填">
                        @error('department_name')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div id="appointment_time_wrapper">
                        <label class="label" for="appointment_time">预约时间</label>
                        <input type="datetime-local" class="input" id="appointment_time" name="appointment_time" value="{{ old('appointment_time') }}">
                        @error('appointment_time')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>

            {{-- Workorder details --}}
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
                            <option value="{{ $category->id }}" data-prefix="{{ $category->ticket_prefix }}" data-hours="{{ $category->default_hours }}" {{ old('category_main') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                            @endforeach
                        </select>
                        @error('category_main')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="label" for="category_sub">故障分类 <span class="text-red-500">*</span></label>
                        <select class="input" id="category_sub" name="category_sub" required>
                            <option value="">请先选择工单大类</option>
                        </select>
                        @error('category_sub')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="mt-4">
                    <label class="label" for="description">问题描述 <span class="text-red-500">*</span></label>
                    <textarea class="input" id="description" name="description" rows="4" required placeholder="请详细描述问题现象、发生时间等">{{ old('description') }}</textarea>
                    @error('description')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
                    <div>
                        <label class="label" for="time_limit_hours">处理时限（小时）</label>
                        <input type="number" class="input" id="time_limit_hours" name="time_limit_hours" value="{{ old('time_limit_hours') }}" min="1" max="168" step="1" placeholder="默认根据工单类型设置">
                        @error('time_limit_hours')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                    @if(auth()->user()->canAssignWorkorders())
                    <div>
                        <label class="label" for="assignee_id">指派处理人</label>
                        <select class="input" id="assignee_id" name="assignee_id">
                            <option value="">不指派（工程师自行接单）</option>
                            @foreach(\App\Models\User::getAssignableEngineers() as $engineer)
                            <option value="{{ $engineer->id }}" {{ old('assignee_id') == $engineer->id ? 'selected' : '' }}>{{ $engineer->name }} - {{ $engineer->department?->name }}</option>
                            @endforeach
                            <option value="other" {{ old('assignee_id') == 'other' ? 'selected' : '' }}>其他部门</option>
                        </select>
                    </div>
                    @endif
                </div>

                @if(auth()->user()->canAssignWorkorders())
                <div id="other_reason_div" class="hidden mt-4">
                    <label class="label" for="other_reason">其他部门原因</label>
                    <textarea class="input" id="other_reason" name="other_reason" rows="2" placeholder="请说明选择其他部门的原因">{{ old('other_reason') }}</textarea>
                </div>
                @endif

                {{-- Toggles --}}
                <div class="flex flex-wrap items-center gap-4 mt-4">
                    <label id="need_visit_wrap" class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" id="need_visit" name="need_visit" value="1" class="rounded border-border-strong w-4 h-4" {{ old('need_visit') ? 'checked' : '' }}>
                        <span class="text-sm" style="color: var(--c-ink-muted);">需要回访</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" id="is_emergency" name="is_emergency" value="1" class="rounded border-border-strong w-4 h-4" {{ old('is_emergency') ? 'checked' : '' }}>
                        <span class="text-sm" style="color: var(--c-ink-muted);">紧急工单</span>
                    </label>
                    @if(auth()->user()->canUsePhoneAssist())
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" id="phone_assisted" name="phone_assisted" value="1" class="rounded border-border-strong w-4 h-4" {{ old('phone_assisted') ? 'checked' : '' }}>
                        <span class="text-sm" style="color: var(--c-ink-muted);">电话协助完成</span>
                    </label>
                    @endif
                </div>

                {{-- Phone solution (shown when phone_assisted checked) --}}
                <div id="phone_solution_div" class="hidden mt-4">
                    <label class="label" for="phone_solution">电话解决方案 <span class="text-red-500">*</span></label>
                    <textarea class="input" id="phone_solution" name="phone_solution" rows="3" placeholder="请描述电话解决方案，工单将直接标记为已解决"></textarea>
                </div>
            </div>

            {{-- Attachments --}}
            <div class="card p-5">
                <h2 class="text-sm font-semibold text-ink mb-4 flex items-center gap-2">
                    <svg class="w-4 h-4 text-brand-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>
                    附件上传
                </h2>
                <input type="file" class="input" id="attachments" name="attachments[]" multiple accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.txt">
                <p class="text-xs mt-1.5" style="color: var(--c-ink-subtle);">支持图片、文档，单个最大 10MB，最多 5 个文件。大图自动压缩。</p>
                <div id="attachmentPreview" class="mt-3 space-y-3"></div>
            </div>

            {{-- Remarks --}}
            <div class="card p-5">
                <h2 class="text-sm font-semibold text-ink mb-4 flex items-center gap-2">
                    <svg class="w-4 h-4 text-brand-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                    其他说明
                </h2>
                <textarea class="input" id="remarks" name="remarks" rows="3" placeholder="补充说明（选填）">{{ old('remarks') }}</textarea>
                @error('remarks')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
            </div>

            {{-- Submit --}}
            <div class="flex items-center justify-end gap-2">
                <a href="{{ route('workorders.index') }}" class="btn btn-secondary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18 6 6 18M6 6l12 12"/></svg>
                    <span>取消</span>
                </a>
                <button type="submit" class="btn btn-primary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    <span>创建工单</span>
                </button>
            </div>
        </form>
    </div>

    {{-- Sidebar --}}
    <div class="lg:col-span-1 space-y-4">
        <div class="card p-5">
            <h3 class="text-sm font-semibold text-ink mb-3">创建提示</h3>
            <ul class="space-y-2 text-sm" style="color: var(--c-ink-muted);">
                <li class="flex gap-2"><svg class="w-4 h-4 shrink-0 mt-0.5 text-brand-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>尽可能详细描述问题</li>
                <li class="flex gap-2"><svg class="w-4 h-4 shrink-0 mt-0.5 text-brand-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>提供准确联系方式和位置</li>
                <li class="flex gap-2"><svg class="w-4 h-4 shrink-0 mt-0.5 text-brand-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>上传相关截图或文件</li>
                <li class="flex gap-2"><svg class="w-4 h-4 shrink-0 mt-0.5 text-brand-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>紧急问题请标记紧急</li>
                <li class="flex gap-2"><svg class="w-4 h-4 shrink-0 mt-0.5 text-brand-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>可设置预约时间</li>
            </ul>
        </div>

        <div class="card p-5">
            <h3 class="text-sm font-semibold text-ink mb-3">优先级说明</h3>
            <div class="space-y-2.5 text-sm">
                <div class="flex items-center gap-2">
                    <span class="badge bg-red-100 text-red-700">高</span>
                    <span style="color: var(--c-ink-muted);">严重影响工作或学习</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="badge bg-amber-100 text-amber-700">中</span>
                    <span style="color: var(--c-ink-muted);">部分影响正常活动</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="badge bg-green-100 text-green-700">低</span>
                    <span style="color: var(--c-ink-muted);">轻微影响，可延后</span>
                </div>
            </div>
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
    // Campus -> Building cascade
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

    // Category main -> sub cascade + default hours
    var mainSel = document.getElementById('category_main');
    var subSel = document.getElementById('category_sub');
    var timeLimitInput = document.getElementById('time_limit_hours');
    mainSel.addEventListener('change', function() {
        var id = this.value;
        subSel.innerHTML = '<option value="">请选择故障分类</option>';
        if (id && categoryData.sub[id]) {
            categoryData.sub[id].forEach(function(c) {
                var opt = document.createElement('option');
                opt.value = c.id;
                opt.textContent = c.name;
                subSel.appendChild(opt);
            });
        }
        var mainCat = categoryData.main.find(function(c) { return c.id == id; });
        if (mainCat && !timeLimitInput.value) {
            timeLimitInput.value = mainCat.default_hours;
        }
    });

    // Source: toggle custom source
    var sourceSel = document.getElementById('source');
    var customRow = document.getElementById('custom_source_row');
    var customInput = document.getElementById('custom_source');
    function syncCustomSource() {
        var isCustom = sourceSel.value === 'custom';
        customRow.classList.toggle('hidden', !isCustom);
        if (isCustom) customInput.setAttribute('required', 'required');
        else { customInput.removeAttribute('required'); }
    }
    sourceSel.addEventListener('change', syncCustomSource);
    syncCustomSource();

    // Assignee: toggle other_reason
    var assigneeSel = document.getElementById('assignee_id');
    if (assigneeSel) {
        var otherDiv = document.getElementById('other_reason_div');
        var otherInput = document.getElementById('other_reason');
        function syncOtherReason() {
            var isOther = assigneeSel.value === 'other';
            if (otherDiv) otherDiv.classList.toggle('hidden', !isOther);
            if (isOther && otherInput) otherInput.setAttribute('required', 'required');
            else if (otherInput) otherInput.removeAttribute('required');
        }
        assigneeSel.addEventListener('change', syncOtherReason);
        syncOtherReason();
    }

    // Phone assist toggle
    var phoneCb = document.getElementById('phone_assisted');
    if (phoneCb) {
        phoneCb.addEventListener('change', function() {
            var checked = this.checked;
            document.getElementById('phone_solution_div').classList.toggle('hidden', !checked);
            document.getElementById('appointment_time_wrapper').classList.toggle('hidden', checked);
            var nvWrap = document.getElementById('need_visit_wrap');
            if (nvWrap) nvWrap.classList.toggle('hidden', checked);
            var psInput = document.getElementById('phone_solution');
            if (checked && psInput) psInput.setAttribute('required', 'required');
            else if (psInput) psInput.removeAttribute('required');
        });
    }

    // Attachment preview
    var fileInput = document.getElementById('attachments');
    var previewDiv = document.getElementById('attachmentPreview');
    fileInput.addEventListener('change', function() {
        previewDiv.innerHTML = '';
        var files = this.files;
        for (var i = 0; i < files.length; i++) {
            (function(file, idx) {
                var sizeMB = (file.size / 1024 / 1024).toFixed(2);
                var willCompress = file.type.startsWith('image/') && file.size > 2 * 1024 * 1024;
                var item = document.createElement('div');
                item.className = 'flex items-start gap-3 p-3 rounded-lg border border-border';
                var thumb = document.createElement('div');
                thumb.className = 'w-12 h-12 rounded-lg overflow-hidden shrink-0 flex items-center justify-center';
                thumb.style.backgroundColor = 'var(--c-muted)';
                if (file.type.startsWith('image/')) {
                    var reader = new FileReader();
                    reader.onload = function(e) {
                        var img = document.createElement('img');
                        img.src = e.target.result;
                        img.className = 'w-full h-full object-cover cursor-pointer';
                        img.onclick = function() { showImagePreview(e.target.result, file.name); };
                        thumb.appendChild(img);
                    };
                    reader.readAsDataURL(file);
                } else {
                    thumb.innerHTML = '<svg class="w-6 h-6 text-ink-subtle" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z M14 2v6h6"/></svg>';
                }
                var info = document.createElement('div');
                info.className = 'flex-1 min-w-0';
                info.innerHTML =
                    '<div class="flex items-center gap-2 flex-wrap">' +
                        '<span class="text-sm font-medium text-ink truncate">' + file.name + '</span>' +
                        (willCompress ? '<span class="badge bg-blue-100 text-blue-700">将压缩</span>' : '') +
                    '</div>' +
                    '<p class="text-xs text-ink-subtle mt-0.5">' + sizeMB + ' MB</p>' +
                    '<input type="text" class="input mt-2 attachment-desc-input" data-file-index="' + idx + '" placeholder="附件描述（选填）" maxlength="200">';
                var removeBtn = document.createElement('button');
                removeBtn.type = 'button';
                removeBtn.className = 'btn btn-ghost btn-icon btn-sm shrink-0';
                removeBtn.innerHTML = '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18 6 6 18M6 6l12 12"/></svg>';
                removeBtn.onclick = function() {
                    var dt = new DataTransfer();
                    var arr = Array.from(fileInput.files);
                    arr.splice(idx, 1);
                    arr.forEach(function(f) { dt.items.add(f); });
                    fileInput.files = dt.files;
                    fileInput.dispatchEvent(new Event('change'));
                };
                item.appendChild(thumb);
                item.appendChild(info);
                item.appendChild(removeBtn);
                previewDiv.appendChild(item);
            })(files[i], i);
        }
    });

    // Collect attachment descriptions before submit
    document.getElementById('workorderForm').addEventListener('submit', function() {
        var inputs = this.querySelectorAll('.attachment-desc-input');
        inputs.forEach(function(inp, i) {
            var hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = 'attachment_descriptions[' + i + ']';
            hidden.value = inp.value;
            this.appendChild(hidden);
        }.bind(this));
    });

    // Template dropdown
    var tplBtn = document.getElementById('templateDropdownBtn');
    if (tplBtn) {
        var tplMenu = document.getElementById('templateDropdownMenu');
        tplBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            tplMenu.classList.toggle('hidden');
        });
        document.addEventListener('click', function() { tplMenu.classList.add('hidden'); });
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
