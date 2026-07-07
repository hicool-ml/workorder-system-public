@extends('layouts.app')

@section('title', '测试分类功能')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">测试分类功能</h1>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">分类测试</h5>
            </div>
            <div class="card-body">
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label for="first_level_category_id" class="form-label">一级分类</label>
                        <select class="form-select" id="first_level_category_id">
                            <option value="">请选择一级分类</option>
                            @foreach($firstLevelCategories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="second_level_category_id" class="form-label">二级分类</label>
                        <select class="form-select" id="second_level_category_id" disabled>
                            <option value="">请选择二级分类</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="third_level_category_id" class="form-label">三级分类</label>
                        <select class="form-select" id="third_level_category_id" disabled>
                            <option value="">请选择三级分类</option>
                        </select>
                    </div>
                </div>
                
                <div class="alert alert-info">
                    <h6>测试结果：</h6>
                    <div id="test-results"></div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // 一级分类变更时加载二级分类
    $('#first_level_category_id').change(function() {
        var parentId = $(this).val();
        loadSubCategories('second_level_category_id', parentId);
        $('#third_level_category_id').empty().append('<option value="">请选择三级分类</option>').prop('disabled', true);
        
        $('#test-results').html('选择了一级分类: ' + $(this).find('option:selected').text() + ' (ID: ' + parentId + ')');
    });
    
    // 二级分类变更时加载三级分类
    $('#second_level_category_id').change(function() {
        var parentId = $(this).val();
        loadSubCategories('third_level_category_id', parentId);
        
        $('#test-results').append('<br>选择了二级分类: ' + $(this).find('option:selected').text() + ' (ID: ' + parentId + ')');
    });
    
    // 三级分类变更时显示结果
    $('#third_level_category_id').change(function() {
        $('#test-results').append('<br>选择了三级分类: ' + $(this).find('option:selected').text() + ' (ID: ' + $(this).val() + ')');
    });
});

function loadSubCategories(targetSelectId, parentId) {
    var select = $('#' + targetSelectId);
    var defaultText = targetSelectId === 'second_level_category_id' ? '请选择二级分类' : '请选择三级分类';
    
    select.empty().append('<option value="">' + defaultText + '</option>');
    select.prop('disabled', true);
    
    if (!parentId) {
        return;
    }
    
    console.log('Loading subcategories for parent_id:', parentId);
    
    $.ajax({
        url: '{{ route("test.api.subcategories") }}',
        method: 'GET',
        data: { parent_id: parentId },
        success: function(data) {
            console.log('Subcategories loaded:', data);
            select.prop('disabled', false);
            
            $.each(data, function(index, category) {
                select.append('<option value="' + category.id + '">' + category.name + '</option>');
            });
        },
        error: function(xhr, status, error) {
            console.error('加载子分类失败:', error);
            console.error('Response:', xhr.responseText);
            $('#test-results').html('<div class="text-danger">加载子分类失败: ' + error + '</div>');
        }
    });
}
</script>
@endsection