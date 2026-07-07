@extends('layouts.app')

@section('title', '编辑工单模板')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-file-alt"></i> 编辑工单模板
                    </h3>
                    
                    <div class="card-tools">
                        <a href="{{ route('workorder-templates.index') }}" class="btn btn-default btn-sm">
                            <i class="fas fa-arrow-left"></i> 返回
                        </a>
                    </div>
                </div>
                
                <form method="POST" action="{{ route('workorder-templates.update', $workorderTemplate->id) }}">
                    @csrf
                    @method('PUT')
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="name" class="form-label">模板名称 <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $workorderTemplate->name) }}" required>
                                    @error('name')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="priority" class="form-label">优先级</label>
                                    <select class="form-control" id="priority" name="priority">
                                        <option value="">请选择</option>
                                        @foreach(\App\Models\WorkorderTemplate::getPriorityOptions() as $value => $label)
                                            <option value="{{ $value }}" {{ old('priority', $workorderTemplate->priority) == $value ? 'selected' : '' }}>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('priority')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="description" class="form-label">工单描述 <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="description" name="description" rows="4" required>{{ old('description', $workorderTemplate->description) }}</textarea>
                            @error('description')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="category_main" class="form-label">主分类 <span class="text-danger">*</span></label>
                                    <select class="form-control" id="category_main" name="category_main" required>
                                        <option value="">请选择主分类</option>
                                        @foreach($categoryOptions['main'] as $category)
                                            <option value="{{ $category->id }}" 
                                                    {{ old('category_main', $workorderTemplate->category?->parent_id) == $category->id ? 'selected' : '' }}>
                                                {{ $category->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('category_main')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="category_sub" class="form-label">子分类 <span class="text-danger">*</span></label>
                                    <select class="form-control" id="category_sub" name="category_sub" required>
                                        <option value="">请先选择主分类</option>
                                        @if(old('category_main', $workorderTemplate->category?->parent_id))
                                            @foreach($categoryOptions['sub'][old('category_main', $workorderTemplate->category?->parent_id)] ?? [] as $category)
                                                <option value="{{ $category->id }}" 
                                                        {{ old('category_sub', $workorderTemplate->category_id) == $category->id ? 'selected' : '' }}>
                                                    {{ $category->name }}
                                                </option>
                                            @endforeach
                                        @endif
                                    </select>
                                    @error('category_sub')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="contact_name" class="form-label">联系人姓名</label>
                                    <input type="text" class="form-control" id="contact_name" name="contact_name" value="{{ old('contact_name', $workorderTemplate->contact_name) }}">
                                    @error('contact_name')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="contact_phone" class="form-label">联系电话</label>
                                    <input type="text" class="form-control" id="contact_phone" name="contact_phone" value="{{ old('contact_phone', $workorderTemplate->contact_phone) }}">
                                    @error('contact_phone')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="contact_email" class="form-label">邮箱</label>
                                    <input type="email" class="form-control" id="contact_email" name="contact_email" value="{{ old('contact_email', $workorderTemplate->contact_email) }}">
                                    @error('contact_email')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="campus" class="form-label">校区</label>
                                    <select class="form-control" id="campus" name="campus">
                                        <option value="">请选择</option>
                                        @foreach(\App\Models\WorkorderTemplate::getCampusOptions() as $value => $label)
                                            <option value="{{ $value }}" {{ old('campus', $workorderTemplate->campus) == $value ? 'selected' : '' }}>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('campus')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="building" class="form-label">楼栋</label>
                                    <input type="text" class="form-control" id="building" name="building" value="{{ old('building', $workorderTemplate->building) }}">
                                    @error('building')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="time_limit_hours" class="form-label">处理时限（小时）</label>
                                    <input type="number" class="form-control" id="time_limit_hours" name="time_limit_hours" 
                                           value="{{ old('time_limit_hours', $workorderTemplate->time_limit_hours) }}" min="1" max="168">
                                    @error('time_limit_hours')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="location_detail" class="form-label">位置详情</label>
                            <textarea class="form-control" id="location_detail" name="location_detail" rows="2">{{ old('location_detail', $workorderTemplate->location_detail) }}</textarea>
                            @error('location_detail')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="source" class="form-label">来源</label>
                                    <select class="form-control" id="source" name="source">
                                        <option value="">请选择</option>
                                        @foreach(\App\Models\WorkorderTemplate::getSourceOptions() as $value => $label)
                                            <option value="{{ $value }}" {{ old('source', $workorderTemplate->source) == $value ? 'selected' : '' }}>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('source')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="department_name" class="form-label">部门名称</label>
                                    <input type="text" class="form-control" id="department_name" name="department_name" value="{{ old('department_name', $workorderTemplate->department_name) }}">
                                    @error('department_name')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="other_reason" class="form-label">其他原因</label>
                                    <input type="text" class="form-control" id="other_reason" name="other_reason" value="{{ old('other_reason', $workorderTemplate->other_reason) }}">
                                    @error('other_reason')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="need_visit" name="need_visit" 
                                           value="1" {{ old('need_visit', $workorderTemplate->need_visit) ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="need_visit">需要回访</label>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="is_emergency" name="is_emergency" 
                                           value="1" {{ old('is_emergency', $workorderTemplate->is_emergency) ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="is_emergency">紧急工单</label>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="phone_assisted" name="phone_assisted" 
                                           value="1" {{ old('phone_assisted', $workorderTemplate->phone_assisted) ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="phone_assisted">电话协助</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> 更新模板
                        </button>
                        <a href="{{ route('workorder-templates.index') }}" class="btn btn-default">
                            <i class="fas fa-times"></i> 取消
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// 主分类改变时更新子分类选项
$('#category_main').change(function() {
    const mainCategoryId = $(this).val();
    const $subCategorySelect = $('#category_sub');
    
    $subCategorySelect.html('<option value="">请选择子分类</option>');
    
    if (mainCategoryId) {
        // 获取子分类数据
        const subCategories = @json($categoryOptions['sub']);
        
        if (subCategories[mainCategoryId]) {
            subCategories[mainCategoryId].forEach(function(category) {
                $subCategorySelect.append(`<option value="${category.id}">${category.name}</option>`);
            });
        }
    }
});

// 初始化时设置当前选中的子分类
$(document).ready(function() {
    const currentSubCategoryId = '{{ $workorderTemplate->category_id }}';
    const currentMainCategoryId = '{{ $workorderTemplate->category?->parent_id }}';
    
    if (currentMainCategoryId) {
        $('#category_main').val(currentMainCategoryId).trigger('change');
        setTimeout(function() {
            $('#category_sub').val(currentSubCategoryId);
        }, 100);
    }
});
</script>
@endpush