/**
 * 工单解决模态框共享逻辑
 */

// 初始化解决工单模态框
window.initResolveModal = function(workorderId) {
    // 重置表单状态
    $('#no_materials').prop('checked', false);
    $('#materials_usage_div').show();
    $('#resolve_materials_usage').removeAttr('required').val('');
    
    // 获取工单数据并预填充备品耗材使用情况
    $.get('/workorders/' + workorderId + '/materials-usage', function(data) {
        if (data.materials_usage) {
            if (data.materials_usage === '无备件耗材使用') {
                $('#no_materials').prop('checked', true);
                $('#no_materials').prop('disabled', false);
                $('#materials_usage_div').hide();
                $('#resolve_materials_usage').removeAttr('required').val('');
            } else {
                $('#resolve_materials_usage').val(data.materials_usage);
                $('#no_materials').prop('checked', false);
                $('#no_materials').prop('disabled', true); // 有内容时禁用复选框
                $('#materials_usage_div').show();
                $('#resolve_materials_usage').removeAttr('required');
            }
        } else {
            // 如果没有备品耗材使用记录，保持默认状态
            $('#no_materials').prop('checked', false);
            $('#no_materials').prop('disabled', false);
            $('#materials_usage_div').show();
            $('#resolve_materials_usage').removeAttr('required').val('');
        }
    }).fail(function() {
        // 如果获取失败，保持默认状态
        $('#no_materials').prop('checked', false);
        $('#no_materials').prop('disabled', false);
        $('#materials_usage_div').show();
        $('#resolve_materials_usage').removeAttr('required').val('');
    });
};

// 初始化备件耗材处理逻辑
window.initMaterialsHandlers = function() {
    // 解决工单模态框中的备件耗材处理
    $('#no_materials').off('change').on('change', function() {
        var materialsDiv = $('#materials_usage_div');
        var materialsTextarea = $('#resolve_materials_usage');
        
        if ($(this).is(':checked')) {
            // 如果勾选了"无备件耗材使用"
            materialsDiv.hide();
            materialsTextarea.removeAttr('required').val('');
        } else {
            // 如果取消勾选"无备件耗材使用"
            materialsDiv.show();
            materialsTextarea.attr('required', 'required');
        }
    });
    
    // 监听备件耗材输入框变化，如果有内容则禁用"无备件耗材使用"复选框
    $('#resolve_materials_usage').off('input change').on('input change', function() {
        var materialsTextarea = $(this);
        var noMaterialsCheckbox = $('#no_materials');
        var materialsValue = materialsTextarea.val().trim();
        
        if (materialsValue) {
            // 如果输入框有内容，禁用复选框
            noMaterialsCheckbox.prop('disabled', true);
            noMaterialsCheckbox.prop('checked', false);
            $('#materials_usage_div').show();
        } else {
            // 如果输入框为空，启用复选框
            noMaterialsCheckbox.prop('disabled', false);
        }
    });
    
    // 解决工单表单提交验证
    $('#resolveForm').off('submit').on('submit', function(e) {
        var solution = $('#solution').val().trim();
        var noMaterials = $('#no_materials').is(':checked');
        var materialsUsage = $('#resolve_materials_usage').val().trim();
        
        // 验证解决方案
        if (!solution) {
            e.preventDefault();
            alert('请填写解决方案');
            return false;
        }
        
        // 验证备品耗材使用情况
        if (!noMaterials && !materialsUsage) {
            e.preventDefault();
            alert('请填写备品耗材使用情况，或勾选"无备件耗材使用"选项');
            return false;
        }
        
        // 如果勾选了"无备件耗材使用"，确保materials_usage有值
        if (noMaterials && !materialsUsage) {
            $('#resolve_materials_usage').val('无备件耗材使用');
        }
    });
};

// 页面加载完成后初始化
$(document).ready(function() {
    // 初始化备件耗材处理逻辑
    window.initMaterialsHandlers();
});