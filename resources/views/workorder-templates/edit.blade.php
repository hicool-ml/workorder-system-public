@extends('layouts.app')

@section('title', '编辑工单模板 - ' . $workorderTemplate->name)

@section('content')
<div class="flex items-center justify-between mb-6 gap-3 flex-wrap">
    <div>
        <h1 class="text-xl font-semibold text-ink">编辑工单模板</h1>
        <p class="text-sm text-ink-muted mt-0.5">{{ $workorderTemplate->name }}</p>
    </div>
    <a href="{{ route('workorder-templates.index') }}" class="btn btn-secondary">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7 7-7M3 12h18"/></svg>
        <span>返回列表</span>
    </a>
</div>

<form method="POST" action="{{ route('workorder-templates.update', $workorderTemplate->id) }}" class="card p-5 space-y-5">
    @csrf
    @method('PUT')
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <label class="label" for="name">模板名称 <span class="text-red-500">*</span></label>
            <input type="text" class="input" id="name" name="name" value="{{ old('name', $workorderTemplate->name) }}" required>
            @error('name')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="label" for="priority">优先级</label>
            <select class="input" id="priority" name="priority">
                <option value="">请选择</option>
                @foreach(\App\Models\WorkorderTemplate::getPriorityOptions() as $value => $label)
                <option value="{{ $value }}" {{ old('priority', $workorderTemplate->priority) == $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            @error('priority')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
        </div>
    </div>

    <div>
        <label class="label" for="description">工单描述 <span class="text-red-500">*</span></label>
        <textarea class="input" id="description" name="description" rows="4" required>{{ old('description', $workorderTemplate->description) }}</textarea>
        @error('description')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <label class="label" for="category_main">主分类 <span class="text-red-500">*</span></label>
            <select class="input" id="category_main" name="category_main" required>
                <option value="">请选择主分类</option>
                @foreach($categoryOptions['main'] as $category)
                <option value="{{ $category->id }}" {{ old('category_main', $workorderTemplate->category?->parent_id) == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                @endforeach
            </select>
            @error('category_main')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="label" for="category_sub">子分类 <span class="text-red-500">*</span></label>
            <select class="input" id="category_sub" name="category_sub" required>
                <option value="">请先选择主分类</option>
                @if(old('category_main', $workorderTemplate->category?->parent_id))
                @foreach($categoryOptions['sub'][old('category_main', $workorderTemplate->category?->parent_id)] ?? [] as $category)
                <option value="{{ $category->id }}" {{ old('category_sub', $workorderTemplate->category_id) == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                @endforeach
                @endif
            </select>
            @error('category_sub')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
        </div>
    </div>

    <div class="pt-4 border-t border-border">
        <h3 class="text-sm font-semibold text-ink mb-3">联系信息</h3>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <label class="label" for="contact_name">联系人姓名</label>
                <input type="text" class="input" id="contact_name" name="contact_name" value="{{ old('contact_name', $workorderTemplate->contact_name) }}">
            </div>
            <div>
                <label class="label" for="contact_phone">联系电话</label>
                <input type="text" class="input" id="contact_phone" name="contact_phone" value="{{ old('contact_phone', $workorderTemplate->contact_phone) }}">
            </div>
            <div>
                <label class="label" for="contact_email">邮箱</label>
                <input type="email" class="input" id="contact_email" name="contact_email" value="{{ old('contact_email', $workorderTemplate->contact_email) }}">
            </div>
        </div>
    </div>

    <div class="pt-4 border-t border-border">
        <h3 class="text-sm font-semibold text-ink mb-3">位置信息</h3>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <label class="label" for="campus_id">区域</label>
                <select class="input" id="campus_id" name="campus_id">
                    <option value="">请选择</option>
                    @foreach(\App\Models\WorkorderTemplate::getCampusOptions() as $value => $label)
                    <option value="{{ $value }}" {{ old('campus_id', $workorderTemplate->campus_id) == $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label" for="building">楼栋</label>
                <input type="text" class="input" id="building" name="building" value="{{ old('building', $workorderTemplate->building) }}">
            </div>
            <div>
                <label class="label" for="time_limit_hours">处理时限（小时）</label>
                <input type="number" class="input" id="time_limit_hours" name="time_limit_hours" value="{{ old('time_limit_hours', $workorderTemplate->time_limit_hours) }}" min="1" max="168">
            </div>
        </div>
        <div class="mt-4">
            <label class="label" for="location_detail">位置详情</label>
            <textarea class="input" id="location_detail" name="location_detail" rows="2">{{ old('location_detail', $workorderTemplate->location_detail) }}</textarea>
        </div>
    </div>

    <div class="pt-4 border-t border-border">
        <h3 class="text-sm font-semibold text-ink mb-3">其他信息</h3>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <label class="label" for="source">来源</label>
                <select class="input" id="source" name="source">
                    <option value="">请选择</option>
                    @foreach(\App\Models\WorkorderTemplate::getSourceOptions() as $value => $label)
                    <option value="{{ $value }}" {{ old('source', $workorderTemplate->source) == $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label" for="department_name">部门名称</label>
                <input type="text" class="input" id="department_name" name="department_name" value="{{ old('department_name', $workorderTemplate->department_name) }}">
            </div>
            <div>
                <label class="label" for="other_reason">其他原因</label>
                <input type="text" class="input" id="other_reason" name="other_reason" value="{{ old('other_reason', $workorderTemplate->other_reason) }}">
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-5 mt-4">
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" id="need_visit" name="need_visit" value="1" class="rounded w-4 h-4" {{ old('need_visit', $workorderTemplate->need_visit) ? 'checked' : '' }}>
                <span class="text-sm" style="color: var(--c-ink-muted);">需要回访</span>
            </label>
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" id="is_emergency" name="is_emergency" value="1" class="rounded w-4 h-4" {{ old('is_emergency', $workorderTemplate->is_emergency) ? 'checked' : '' }}>
                <span class="text-sm" style="color: var(--c-ink-muted);">紧急工单</span>
            </label>
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" id="phone_assisted" name="phone_assisted" value="1" class="rounded w-4 h-4" {{ old('phone_assisted', $workorderTemplate->phone_assisted) ? 'checked' : '' }}>
                <span class="text-sm" style="color: var(--c-ink-muted);">电话协助</span>
            </label>
        </div>
    </div>

    <div class="flex items-center justify-end gap-2 pt-2 border-t border-border">
        <a href="{{ route('workorder-templates.index') }}" class="btn btn-secondary">取消</a>
        <button type="submit" class="btn btn-primary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            <span>更新模板</span>
        </button>
    </div>
</form>
@endsection

@section('scripts')
<script>
var subCategories = @json($categoryOptions['sub']);
var currentSubId = '{{ $workorderTemplate->category_id }}';
var currentMainId = '{{ $workorderTemplate->category?->parent_id }}';

document.getElementById('category_main').addEventListener('change', function() {
    var mainId = this.value;
    var subSel = document.getElementById('category_sub');
    subSel.innerHTML = '<option value="">请选择子分类</option>';
    if (mainId && subCategories[mainId]) {
        subCategories[mainId].forEach(function(cat) {
            var opt = document.createElement('option');
            opt.value = cat.id;
            opt.textContent = cat.name;
            subSel.appendChild(opt);
        });
    }
});

// Initialize: trigger cascade then restore selected sub-category
if (currentMainId) {
    var mainSel = document.getElementById('category_main');
    mainSel.value = currentMainId;
    mainSel.dispatchEvent(new Event('change'));
    setTimeout(function() {
        document.getElementById('category_sub').value = currentSubId;
    }, 0);
}
</script>
@endsection
