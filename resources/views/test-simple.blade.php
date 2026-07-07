<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>测试分类功能</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <div class="container mt-5">
        <h1>测试分类功能</h1>
        
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <label for="first_level_category_id" class="form-label">一级分类</label>
                <select class="form-select" id="first_level_category_id">
                    <option value="">请选择一级分类</option>
                    <option value="5">IT支持</option>
                    <option value="24">设施维护</option>
                    <option value="43">行政服务</option>
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
            url: '/test/api/subcategories',
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
</body>
</html>