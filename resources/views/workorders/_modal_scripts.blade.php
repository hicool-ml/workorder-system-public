c<script>
// 工单列表页面所有模态框的JavaScript逻辑
$(document).ready(function() {
    // 分配工单模态框
    $('#assignModal').on('show.bs.modal', function (e) {
        var button = $(e.relatedTarget);
        var workorderId = button.data('workorder-id');
        $('#assignWorkorderId').val(workorderId);
        var action = $('#assignForm').attr('action').replace(':workorder_id', workorderId);
        $('#assignForm').attr('action', action);
    });
    
    // 批量分配表单提交处理
    $('#batchAssignForm').on('submit', function(e) {
        e.preventDefault();
        
        // 获取表单数据
        var workorderIds = $('#batchAssignIds').val();
        var formData = {
            workorder_ids: workorderIds,
            assignee_id: $('#batch_modal_assignee_id').val(),
            note: $('#batch_assign_note').val(),
            _token: '{{ csrf_token() }}'
        };
        
        console.log('批量分配表单数据:', formData);
        console.log('workorder_ids值:', workorderIds);
        console.log('workorder_ids类型:', typeof workorderIds);
        console.log('workorder_ids长度:', workorderIds ? workorderIds.length : 0);
        
        // 验证数据
        if (!formData.workorder_ids || formData.workorder_ids.split(',').filter(id => id.trim()).length === 0) {
            console.error('工单ID验证失败:', formData.workorder_ids);
            alert('请先选择要分配的工单');
            return;
        }
        
        if (!formData.assignee_id) {
            console.error('处理人ID验证失败:', formData.assignee_id);
            alert('请选择处理人');
            return;
        }
        
        console.log('发送AJAX请求到:', '{{ \App\Helpers\UrlHelper::relative_url("/workorders/batch/assign") }}');
        
        // 发送AJAX请求
        $.ajax({
            url: '{{ \App\Helpers\UrlHelper::relative_url("/workorders/batch/assign") }}',
            method: 'POST',
            data: formData,
            beforeSend: function(xhr) {
                console.log('AJAX请求发送前，请求头:', xhr);
            },
            success: function(response) {
                console.log('批量分配成功响应:', response);
                if (response.success) {
                    alert('批量分配成功');
                    window.location.href = '{{ \App\Helpers\UrlHelper::relative_url('/workorders') }}';
                } else {
                    console.error('批量分配失败响应:', response);
                    alert('批量分配失败：' + response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('批量分配AJAX错误:', {
                    status: xhr.status,
                    statusText: xhr.statusText,
                    responseText: xhr.responseText,
                    error: error
                });
                
                if (xhr.status === 404) {
                    alert('批量分配路由未找到 (404)，请检查路由配置');
                } else if (xhr.status === 403) {
                    alert('批量分配权限不足 (403)，请检查用户权限');
                } else if (xhr.status === 302) {
                    alert('批量分配请求被重定向 (302)，可能是会话过期，请重新登录');
                } else {
                    alert('批量分配失败，HTTP ' + xhr.status + ': ' + xhr.statusText);
                }
            }
        });
    });
    
    // 批量分配按钮点击
    $('#batchAssignBtn').click(function() {
        var canAssign = true;
        var invalidWorkorders = [];
        
        // 使用去重的工单ID进行检查，避免重复验证
        var uniqueWorkorderIds = new Set();
        $('.workorder-checkbox:checked').each(function() {
            uniqueWorkorderIds.add($(this).val());
        });
        
        // 检查选中的工单是否可以分配
        uniqueWorkorderIds.forEach(function(workorderId) {
            // 只检查第一个匹配的工单，避免重复
            var checkbox = $('.workorder-checkbox[value="' + workorderId + '"]:first');
            var row = checkbox.closest('tr, .workorder-card');
            var status = row.data('status');
            var assignee = row.data('assignee');
            // 只有待处理且未分配的工单才能分配
            if (status !== 'pending' || assignee) {
                canAssign = false;
                // 获取工单号的方式根据桌面端和移动端不同
                var ticketNo = '';
                if (row.hasClass('workorder-card')) {
                    // 移动端卡片视图
                    ticketNo = row.find('.card-title a').text().trim();
                } else {
                    // 桌面端表格视图 - 现在是第4列（描述列）
                    ticketNo = row.find('td:eq(4)').text().trim();
                }
                invalidWorkorders.push(ticketNo + ' (状态: ' + status + ', 分配人: ' + (assignee || '无') + ')');
            }
        });
        
        if (!canAssign) {
            alert('以下工单不能分配（非待处理状态或已分配）：\n' + invalidWorkorders.join('\n'));
            return;
        }
        
        // 确保有选中的工单
        if (selectedWorkorders.length === 0) {
            alert('请先选择要分配的工单');
            return;
        }
        
        console.log('设置批量分配ID:', selectedWorkorders.join(','));
        $('#batchAssignIds').val(selectedWorkorders.join(','));
        $('#batchAssignModal').modal('show');
    });
    
    // 批量开始按钮点击
    $('#batchStartBtn').click(function() {
        var canStart = true;
        var invalidWorkorders = [];
        
        // 使用去重的工单ID进行检查，避免重复验证
        var uniqueWorkorderIds = new Set();
        $('.workorder-checkbox:checked').each(function() {
            uniqueWorkorderIds.add($(this).val());
        });
        
        // 检查选中的工单是否可以开始处理
        uniqueWorkorderIds.forEach(function(workorderId) {
            // 只检查第一个匹配的工单，避免重复
            var checkbox = $('.workorder-checkbox[value="' + workorderId + '"]:first');
            var row = checkbox.closest('tr, .workorder-card');
            var status = row.data('status');
            var assignee = row.data('assignee');
            // 只有已分配且分配给当前用户的工单才能开始处理（管理员除外）
            if (status !== 'assigned' || (assignee != '{{ auth()->id() }}' && {{ auth()->check() && auth()->user()->isAdmin() ? 'false' : 'true' }})) {
                canStart = false;
                // 获取工单号的方式根据桌面端和移动端不同
                var ticketNo = '';
                if (row.hasClass('workorder-card')) {
                    // 移动端卡片视图
                    ticketNo = row.find('.card-title a').text().trim();
                } else {
                    // 桌面端表格视图 - 现在是第4列（描述列）
                    ticketNo = row.find('td:eq(4)').text().trim();
                }
                invalidWorkorders.push(ticketNo + ' (状态: ' + status + ', 分配人: ' + (assignee || '无') + ')');
            }
        });
        
        if (!canStart) {
            alert('以下工单不能开始处理（未分配或非您的工单）：\n' + invalidWorkorders.join('\n'));
            return;
        }
        
        if (confirm('确认开始处理选中的 ' + selectedWorkorders.length + ' 个工单吗？')) {
            batchOperation('start');
        }
    });
    
    // 批量解决按钮点击
    $('#batchResolveBtn').click(function() {
        var canResolve = true;
        var invalidWorkorders = [];
        
        // 使用去重的工单ID进行检查，避免重复验证
        var uniqueWorkorderIds = new Set();
        $('.workorder-checkbox:checked').each(function() {
            uniqueWorkorderIds.add($(this).val());
        });
        
        // 检查选中的工单是否可以解决
        uniqueWorkorderIds.forEach(function(workorderId) {
            // 只检查第一个匹配的工单，避免重复
            var checkbox = $('.workorder-checkbox[value="' + workorderId + '"]:first');
            var row = checkbox.closest('tr, .workorder-card');
            var status = row.data('status');
            var assignee = row.data('assignee');
            // 只有处理中且分配给当前用户的工单才能解决（管理员除外）
            if (status !== 'processing' || (assignee != '{{ auth()->id() }}' && {{ auth()->check() && auth()->user()->isAdmin() ? 'false' : 'true' }})) {
                canResolve = false;
                // 获取工单号的方式根据桌面端和移动端不同
                var ticketNo = '';
                if (row.hasClass('workorder-card')) {
                    // 移动端卡片视图
                    ticketNo = row.find('.card-title a').text().trim();
                } else {
                    // 桌面端表格视图 - 现在是第4列（描述列）
                    ticketNo = row.find('td:eq(4)').text().trim();
                }
                invalidWorkorders.push(ticketNo + ' (状态: ' + status + ', 分配人: ' + (assignee || '无') + ')');
            }
        });
        
        if (!canResolve) {
            alert('以下工单不能解决（非处理中状态或非您的工单）：\n' + invalidWorkorders.join('\n'));
            return;
        }
        
        $('#batchResolveIds').val(selectedWorkorders.join(','));
        
        // 生成单独设置工单列表
        generateIndividualWorkorderList();
        
        $('#batchResolveModal').modal('show');
    });
    
    // 批量完结按钮点击
    $('#batchCompleteBtn').click(function() {
        var canComplete = true;
        var invalidWorkorders = [];
        
        // 使用去重的工单ID进行检查，避免重复验证
        var uniqueWorkorderIds = new Set();
        $('.workorder-checkbox:checked').each(function() {
            uniqueWorkorderIds.add($(this).val());
        });
        
        // 检查选中的工单是否可以完结
        uniqueWorkorderIds.forEach(function(workorderId) {
            // 只检查第一个匹配的工单，避免重复
            var checkbox = $('.workorder-checkbox[value="' + workorderId + '"]:first');
            var row = checkbox.closest('tr, .workorder-card');
            var status = row.data('status');
            var assignee = row.data('assignee');
            // 只有已解决且分配给当前用户的工单才能完结（管理员除外）
            if (status !== 'resolved' || (assignee != '{{ auth()->id() }}' && {{ auth()->check() && auth()->user()->isAdmin() ? 'false' : 'true' }})) {
                canComplete = false;
                // 获取工单号的方式根据桌面端和移动端不同
                var ticketNo = '';
                if (row.hasClass('workorder-card')) {
                    // 移动端卡片视图
                    ticketNo = row.find('.card-title a').text().trim();
                } else {
                    // 桌面端表格视图 - 现在是第4列（描述列）
                    ticketNo = row.find('td:eq(4)').text().trim();
                }
                invalidWorkorders.push(ticketNo + ' (状态: ' + status + ', 分配人: ' + (assignee || '无') + ')');
            }
        });
        
        if (!canComplete) {
            alert('以下工单不能完结（非已解决状态或非您的工单）：\n' + invalidWorkorders.join('\n'));
            return;
        }
        
        // 确保有选中的工单
        if (selectedWorkorders.length === 0) {
            alert('请先选择要完结的工单');
            return;
        }
        
        console.log('设置批量完结ID:', selectedWorkorders.join(','));
        $('#batchCompleteIds').val(selectedWorkorders.join(','));
        
        // 显示待完结工单列表
        displayWorkordersForCompletion(selectedWorkorders);
        
        // 显示模态框
        $('#batchCompleteModal').modal('show');
    });
    
    // 批量关闭按钮点击
    $('#batchCloseBtn').click(function() {
        var canClose = true;
        var invalidWorkorders = [];
        
        // 使用去重的工单ID进行检查，避免重复验证
        var uniqueWorkorderIds = new Set();
        $('.workorder-checkbox:checked').each(function() {
            uniqueWorkorderIds.add($(this).val());
        });
        
        // 检查选中的工单是否可以关闭
        uniqueWorkorderIds.forEach(function(workorderId) {
            // 只检查第一个匹配的工单，避免重复
            var checkbox = $('.workorder-checkbox[value="' + workorderId + '"]:first');
            var row = checkbox.closest('tr, .workorder-card');
            var status = row.data('status');
            // 只有已解决的工单才能关闭
            if (status !== 'resolved') {
                canClose = false;
                // 获取工单号的方式根据桌面端和移动端不同
                var ticketNo = '';
                if (row.hasClass('workorder-card')) {
                    // 移动端卡片视图
                    ticketNo = row.find('.card-title a').text().trim();
                } else {
                    // 桌面端表格视图 - 现在是第4列（描述列）
                    ticketNo = row.find('td:eq(4)').text().trim();
                }
                invalidWorkorders.push(ticketNo);
            }
        });
        
        if (!canClose) {
            alert('以下工单不能关闭（非已解决状态）：\n' + invalidWorkorders.join('\n'));
            return;
        }
        
        if (confirm('确认关闭选中的 ' + selectedWorkorders.length + ' 个工单吗？此操作不可撤销！')) {
            batchOperation('close');
        }
    });
    
    // 清除选择按钮点击
    $('#clearSelectionBtn').click(function() {
        $('.workorder-checkbox').prop('checked', false);
        $('#selectAll').prop('checked', false);
        updateSelectedWorkorders();
        updateBatchOperationsUI();
    });
    
    // 批量解决表单验证
    $('#batchResolveForm').on('submit', function(e) {
        e.preventDefault();
        
        // 获取解决方案类型
        const solutionType = $('input[name="solution_type"]:checked').val();
        const workorderIds = $('#batchResolveIds').val();
        
        // 如果是单独设置模式且工单列表未生成，先生成列表
        if (solutionType === 'individual' && $('#individual_workorders_list').children().length === 0) {
            generateIndividualWorkorderList();
            alert('正在生成工单列表，请重新提交');
            return false;
        }
        
        // 移除之前的验证错误标记
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').remove();
        
        let formData = {
            workorder_ids: workorderIds,
            solution_type: solutionType,
            _token: '{{ csrf_token() }}'
        };
        
        if (solutionType === 'common') {
            // 通用解决方案模式
            const solution = $('#batch_solution').val().trim();
            const noMaterials = $('#batch_no_materials').is(':checked');
            const materialsUsage = $('#batch_materials_usage').val().trim();
            
            console.log('通用解决方案模式数据:', {
                workorder_ids: workorderIds,
                solution: solution,
                no_materials: noMaterials,
                materials_usage: materialsUsage
            });
            
            // 验证解决方案
            if (!solution) {
                console.error('解决方案验证失败');
                alert('请填写通用解决方案');
                return false;
            }
            
            // 验证备品耗材使用情况
            if (!noMaterials && !materialsUsage) {
                console.error('备品耗材验证失败');
                alert('请填写备品耗材使用情况或勾选"无备件耗材使用"');
                return false;
            }
            
            formData.solution = solution;
            formData.no_materials = noMaterials;
            formData.materials_usage = materialsUsage;
            
        } else {
            // 单独设置模式
            const solutions = {};
            const noMaterialsArray = {};
            const materialsUsageArray = {};
            let hasError = false;
            let errorMessage = '';
            
            // 收集每个工单的解决方案和耗材使用情况
            selectedWorkorders.forEach(function(workorderId) {
                const solutionElement = $('textarea[name="individual_solution_' + workorderId + '"]');
                const solution = solutionElement.length ? solutionElement.val().trim() : '';
                const noMaterialsElement = $('input[name="individual_no_materials_' + workorderId + '"]');
                const noMaterials = noMaterialsElement.length ? noMaterialsElement.is(':checked') : false;
                const materialsUsageElement = $('textarea[name="individual_materials_usage_' + workorderId + '"]');
                const materialsUsage = materialsUsageElement.length ? materialsUsageElement.val().trim() : '';
                
                // 检查元素是否可见和可交互
                if (solutionElement.length && !solutionElement.is(':visible')) {
                    console.warn('工单 ' + workorderId + ' 的解决方案字段不可见');
                }
                if (solutionElement.length && solutionElement.prop('disabled')) {
                    console.warn('工单 ' + workorderId + ' 的解决方案字段被禁用');
                }
                
                solutions[workorderId] = solution;
                noMaterialsArray[workorderId] = noMaterials;
                materialsUsageArray[workorderId] = materialsUsage;
                
                // 验证必填字段
                if (!solution) {
                    hasError = true;
                    errorMessage += `工单 ${workorderId} 的解决方案不能为空\n`;
                    
                    // 标记字段为无效
                    if (solutionElement.length) {
                        solutionElement.addClass('is-invalid');
                        solutionElement.after('<div class="invalid-feedback">解决方案不能为空</div>');
                    }
                    
                    // 尝试展开对应的手风琴项以便用户填写
                    const accordionItem = solutionElement.closest('.accordion-item');
                    if (accordionItem.length) {
                        const collapse = accordionItem.find('.accordion-collapse');
                        const button = accordionItem.find('.accordion-button');
                        if (collapse.length && button.length) {
                            collapse.addClass('show');
                            button.removeClass('collapsed');
                            button.attr('aria-expanded', 'true');
                        }
                    }
                }
                
                if (!noMaterials && !materialsUsage) {
                    hasError = true;
                    errorMessage += `工单 ${workorderId} 的备件耗材使用情况不能为空\n`;
                    
                    // 标记字段为无效
                    if (materialsUsageElement.length) {
                        materialsUsageElement.addClass('is-invalid');
                        materialsUsageElement.after('<div class="invalid-feedback">备件耗材使用情况不能为空</div>');
                    }
                    
                    // 尝试展开对应的手风琴项以便用户填写
                    const accordionItem = materialsUsageElement.closest('.accordion-item');
                    if (accordionItem.length) {
                        const collapse = accordionItem.find('.accordion-collapse');
                        const button = accordionItem.find('.accordion-button');
                        if (collapse.length && button.length) {
                            collapse.addClass('show');
                            button.removeClass('collapsed');
                            button.attr('aria-expanded', 'true');
                        }
                    }
                }
            });
            
            if (hasError) {
                alert(errorMessage);
                // 滚动到第一个错误的手风琴项
                const firstErrorItem = $('.accordion-item').has('.is-invalid').first();
                if (firstErrorItem.length) {
                    // 展开手风琴项
                    const collapse = firstErrorItem.find('.accordion-collapse');
                    const button = firstErrorItem.find('.accordion-button');
                    if (collapse.length && button.length) {
                        collapse.addClass('show');
                        button.removeClass('collapsed');
                        button.attr('aria-expanded', 'true');
                    }
                    
                    // 滚动到错误项
                    firstErrorItem[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
                    
                    // 尝试聚焦到第一个无效字段
                    setTimeout(function() {
                        const firstInvalidField = firstErrorItem.find('.is-invalid').first();
                        if (firstInvalidField.length && firstInvalidField.is(':visible')) {
                            firstInvalidField.focus();
                        }
                    }, 500);
                }
                return false;
            }
            
            formData.solutions = solutions;
            formData.no_materials_array = noMaterialsArray;
            formData.materials_usage_array = materialsUsageArray;
            
            console.log('单独设置模式数据:', {
                workorder_ids: workorderIds,
                solutions: solutions,
                no_materials_array: noMaterialsArray,
                materials_usage_array: materialsUsageArray
            });
        }
        
        console.log('发送批量解决AJAX请求:', formData);
        console.log('请求URL:', '{{ \App\Helpers\UrlHelper::relative_url("/workorders/batch/resolve") }}');
        
        $.ajax({
            url: '{{ \App\Helpers\UrlHelper::relative_url("/workorders/batch/resolve") }}',
            method: 'POST',
            data: formData,
            success: function(response) {
                console.log('批量解决成功响应:', response);
                if (response.success) {
                    alert('批量解决成功');
                    window.location.href = '{{ \App\Helpers\UrlHelper::relative_url('/workorders') }}';
                } else {
                    console.error('批量解决失败响应:', response);
                    alert('批量解决失败：' + response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('批量解决AJAX错误:', {
                    status: xhr.status,
                    statusText: xhr.statusText,
                    responseText: xhr.responseText,
                    error: error
                });
                
                if (xhr.status === 404) {
                    alert('批量解决路由未找到 (404)，请检查路由配置');
                } else if (xhr.status === 403) {
                    alert('批量解决权限不足 (403)，请检查用户权限');
                } else if (xhr.status === 302) {
                    alert('批量解决请求被重定向 (302)，可能是会话过期，请重新登录');
                } else {
                    alert('批量解决失败，HTTP ' + xhr.status + ': ' + xhr.statusText);
                }
            }
        });
    });
    
    // 批量完结表单提交处理
    $('#batchCompleteForm').on('submit', function(e) {
        e.preventDefault();
        
        // 获取表单数据
        var workorderIds = $('#batchCompleteIds').val();
        var formData = {
            workorder_ids: workorderIds,
            completion_note: $('#batch_completion_note').val().trim(),
            _token: '{{ csrf_token() }}'
        };
        
        console.log('批量完结表单数据:', formData);
        console.log('workorder_ids值:', workorderIds);
        console.log('workorder_ids类型:', typeof workorderIds);
        console.log('workorder_ids长度:', workorderIds ? workorderIds.length : 0);
        
        // 验证数据
        if (!formData.workorder_ids || formData.workorder_ids.split(',').filter(id => id.trim()).length === 0) {
            console.error('工单ID验证失败:', formData.workorder_ids);
            alert('请先选择要完结的工单');
            return;
        }
        
        if (!formData.completion_note) {
            console.error('完结说明验证失败:', formData.completion_note);
            alert('请填写完结说明');
            return;
        }
        
        console.log('发送AJAX请求到:', '{{ \App\Helpers\UrlHelper::relative_url("/workorders/batch/complete") }}');
        
        // 发送AJAX请求
        $.ajax({
            url: '{{ \App\Helpers\UrlHelper::relative_url("/workorders/batch/complete") }}',
            method: 'POST',
            data: formData,
            beforeSend: function(xhr) {
                console.log('AJAX请求发送前，请求头:', xhr);
            },
            success: function(response) {
                console.log('批量完结成功响应:', response);
                if (response.success) {
                    alert('批量完结成功');
                    window.location.href = '{{ \App\Helpers\UrlHelper::relative_url('/workorders') }}';
                } else {
                    console.error('批量完结失败响应:', response);
                    alert('批量完结失败：' + response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('批量完结AJAX错误:', {
                    status: xhr.status,
                    statusText: xhr.statusText,
                    responseText: xhr.responseText,
                    error: error
                });
                
                if (xhr.status === 404) {
                    alert('批量完结路由未找到 (404)，请检查路由配置');
                } else if (xhr.status === 403) {
                    alert('批量完结权限不足 (403)，请检查用户权限');
                } else if (xhr.status === 302) {
                    alert('批量完结请求被重定向 (302)，可能是会话过期，请重新登录');
                } else {
                    alert('批量完结失败，HTTP ' + xhr.status + ': ' + xhr.statusText);
                }
            }
        });
    });
    
    // 解决方案类型切换
    $('input[name="solution_type"]').on('change', function() {
        const solutionType = $(this).val();
        
        if (solutionType === 'common') {
            $('#common_solution_area').show();
            $('#individual_solution_area').hide();
            // 通用解决方案时，恢复必填验证
            $('#batch_solution').attr('required', 'required');
            if (!$('#batch_no_materials').is(':checked')) {
                $('#batch_materials_usage').attr('required', 'required');
            }
        } else {
            $('#common_solution_area').hide();
            $('#individual_solution_area').show();
            // 单独设置时，移除通用字段的必填验证
            $('#batch_solution').removeAttr('required');
            $('#batch_materials_usage').removeAttr('required');
            
            // 确保单独设置工单列表已生成
            if ($('#individual_workorders_list').children().length === 0) {
                generateIndividualWorkorderList();
            }
        }
    });
    
    // 处理"无备件耗材使用"复选框
    $('#batch_no_materials').on('change', function() {
        const isChecked = $(this).is(':checked');
        const materialsDiv = $('#batch_materials_usage_div');
        
        if (isChecked) {
            materialsDiv.hide();
            $('#batch_materials_usage').removeAttr('required');
        } else {
            materialsDiv.show();
            // 只有在通用解决方案模式下才需要必填验证
            if ($('input[name="solution_type"]:checked').val() === 'common') {
                $('#batch_materials_usage').attr('required', 'required');
            }
        }
    });
    
    // 快速填充按钮点击
    $('#quickFillBtn').click(function() {
        const solution = $('#quick_fill_solution').val().trim();
        const noMaterials = $('#quick_fill_no_materials').is(':checked');
        const materialsUsage = $('#quick_fill_materials').val().trim();
        
        if (!solution && !noMaterials && !materialsUsage) {
            alert('请填写要快速填充的内容');
            return;
        }
        
        // 填充所有工单的解决方案和耗材使用情况
        $('.individual-solution').val(solution);
        $('.individual-no-materials').prop('checked', noMaterials);
        $('.individual-materials-usage').val(materialsUsage);
        
        // 触发变化事件以显示/隐藏耗材输入框
        $('.individual-no-materials').trigger('change');
        
        alert('快速填充完成');
    });
    
    // 解决方案模板选择
    $('#solution_template').on('change', function() {
        const template = $(this).val();
        const templates = {
            'hardware_replacement': '更换了故障硬件设备，经测试功能正常',
            'software_fix': '修复了软件配置问题，系统运行恢复正常',
            'network_config': '重新配置了网络设置，连接已恢复正常',
            'system_restart': '重启了相关系统服务，功能已恢复正常',
            'cable_replacement': '更换了损坏的连接线缆，设备通信正常',
            'cleaning_maintenance': '对设备进行了清洁和维护，运行状态良好'
        };
        
        if (template && templates[template]) {
            const currentType = $('input[name="solution_type"]:checked').val();
            
            if (currentType === 'common') {
                $('#batch_solution').val(templates[template]);
            } else {
                $('#quick_fill_solution').val(templates[template]);
            }
        }
    });
    
    // 生成单独设置工单列表
    window.generateIndividualWorkorderList = function() {
        const container = $('#individual_workorders_list');
        container.empty();
        
        // 确保 selectedWorkorders 是数组
        if (!Array.isArray(selectedWorkorders)) {
            console.error('selectedWorkorders 不是数组:', selectedWorkorders);
            container.html('<p class="text-muted">工单选择数据错误，请刷新页面重试</p>');
            return;
        }
        
        if (selectedWorkorders.length === 0) {
            container.html('<p class="text-muted">请先选择要解决的工单</p>');
            return;
        }
        
        let html = '<div class="accordion" id="workorderAccordion">';
        
        selectedWorkorders.forEach(function(workorderId, index) {
            const checkbox = $('.workorder-checkbox[value="' + workorderId + '"]:first');
            const row = checkbox.closest('tr, .workorder-card');
            let ticketNo = '';
            let description = '';
            
            if (row.length === 0) {
                console.warn('找不到工单ID为 ' + workorderId + ' 的行元素');
                ticketNo = '工单 #' + workorderId;
                description = '无法获取描述';
            } else if (row.hasClass('workorder-card')) {
                // 移动端卡片视图
                ticketNo = row.find('.card-title a').text().trim();
                description = row.find('.workorder-description').text().trim();
            } else {
                // 桌面端表格视图
                ticketNo = row.find('td:eq(4)').text().trim();
                description = row.find('.workorder-description').text().trim();
            }
            
            html += `
                <div class="accordion-item">
                    <h2 class="accordion-header" id="heading${index}">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse${index}" aria-expanded="false" aria-controls="collapse${index}">
                            <strong>${ticketNo}</strong>
                            <small class="text-muted ms-2">${description.substring(0, 50)}${description.length > 50 ? '...' : ''}</small>
                        </button>
                    </h2>
                    <div id="collapse${index}" class="accordion-collapse collapse" aria-labelledby="heading${index}" data-bs-parent="#workorderAccordion">
                        <div class="accordion-body">
                            <div class="mb-3">
                                <label class="form-label">解决方案 <span class="text-danger">*</span></label>
                                <textarea class="form-control individual-solution" name="individual_solution_${workorderId}" rows="3" required
                                          placeholder="请详细描述此工单的解决方案..." autocomplete="off"></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">备件耗材使用情况 <span class="text-danger">*</span></label>
                                <div class="form-check mb-2">
                                    <input class="form-check-input individual-no-materials" type="checkbox" name="individual_no_materials_${workorderId}" value="1" autocomplete="off">
                                    <label class="form-check-label">无备件耗材使用</label>
                                </div>
                                <div class="individual-materials-div">
                                    <textarea class="form-control individual-materials-usage" name="individual_materials_usage_${workorderId}" rows="2"
                                              placeholder="请详细描述此工单的备件耗材使用情况..." autocomplete="off"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });
        
        html += '</div>';
        container.html(html);
        
        // 为单独设置的耗材使用情况添加事件监听
        $('.individual-no-materials').on('change', function() {
            const isChecked = $(this).is(':checked');
            const materialsDiv = $(this).closest('.mb-3').find('.individual-materials-div');
            
            if (isChecked) {
                materialsDiv.hide();
            } else {
                materialsDiv.show();
            }
        });
    }
    
    // 监听批量解决备件耗材输入框变化，如果有内容则禁用"无备件耗材使用"复选框
    $('#batch_materials_usage').on('input change', function() {
        var materialsTextarea = $(this);
        var noMaterialsCheckbox = $('#batch_no_materials');
        var materialsValue = materialsTextarea.val().trim();
        
        if (materialsValue) {
            // 如果输入框有内容，禁用复选框
            noMaterialsCheckbox.prop('disabled', true);
            noMaterialsCheckbox.prop('checked', false);
            $('#batch_materials_usage_div').show();
        } else {
            // 如果输入框为空，启用复选框
            noMaterialsCheckbox.prop('disabled', false);
        }
    });
    
    // 执行批量操作
    function batchOperation(action) {
        var data = {
            workorder_ids: selectedWorkorders.join(','),
            _token: '{{ csrf_token() }}'
        };
        
        var url = '';
        var successMessage = '';
        
        switch(action) {
            case 'start':
                url = '{{ \App\Helpers\UrlHelper::relative_url("/workorders/batch/start") }}';
                successMessage = '批量开始处理成功';
                break;
            case 'close':
                url = '{{ \App\Helpers\UrlHelper::relative_url("/workorders/batch/close") }}';
                successMessage = '批量关闭成功';
                break;
        }
        
        console.log('执行批量操作:', action);
        console.log('请求URL:', url);
        console.log('请求数据:', data);
        console.log('选中的工单ID:', selectedWorkorders);
        
        $.ajax({
            url: url,
            method: 'POST',
            data: data,
            success: function(response) {
                console.log('批量操作成功响应:', response);
                if (response.success) {
                    alert(successMessage);
                    // 使用相对路径重新加载页面，避免协议问题
                    window.location.href = '{{ \App\Helpers\UrlHelper::relative_url('/workorders') }}';
                } else {
                    console.error('批量操作失败响应:', response);
                    alert('操作失败：' + response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('批量操作AJAX错误:', {
                    action: action,
                    status: xhr.status,
                    statusText: xhr.statusText,
                    responseText: xhr.responseText,
                    error: error
                });
                
                if (xhr.status === 404) {
                    alert('批量' + action + '路由未找到 (404)，请检查路由配置');
                } else if (xhr.status === 403) {
                    alert('批量' + action + '权限不足 (403)，请检查用户权限');
                } else if (xhr.status === 302) {
                    alert('批量' + action + '请求被重定向 (302)，可能是会话过期，请重新登录');
                } else {
                    alert('批量' + action + '失败，HTTP ' + xhr.status + ': ' + xhr.statusText);
                }
            }
        });
    }
    
    // 显示待完结工单列表
    function displayWorkordersForCompletion(workorderIds) {
        var listContainer = $('#batch_complete_workorders_list');
        listContainer.empty();
        
        if (workorderIds.length === 0) {
            listContainer.html('<p class="text-muted mb-0">没有选中的工单</p>');
            return;
        }
        
        var listHtml = '<div class="list-group list-group-flush">';
        
        workorderIds.forEach(function(workorderId) {
            var checkbox = $('.workorder-checkbox[value="' + workorderId + '"]:first');
            var row = checkbox.closest('tr, .workorder-card');
            var ticketNo = '';
            var description = '';
            var status = '';
            
            if (row.hasClass('workorder-card')) {
                // 移动端卡片视图
                ticketNo = row.find('.card-title a').text().trim();
                description = row.find('.workorder-description').text().trim();
                status = row.find('.status-badge').text().trim();
            } else {
                // 桌面端表格视图
                ticketNo = row.find('td:eq(4)').text().trim();
                description = row.find('.workorder-description').text().trim();
                status = row.find('.status-badge').text().trim();
            }
            
            // 检查工单状态是否可以完结
            var canComplete = status === '已解决';
            var statusClass = canComplete ? 'text-success' : 'text-warning';
            var statusIcon = canComplete ? 'fa-check-circle' : 'fa-exclamation-triangle';
            
            listHtml += '<div class="list-group-item d-flex justify-content-between align-items-center">';
            listHtml += '<div>';
            listHtml += '<strong>' + ticketNo + '</strong>';
            listHtml += '<div class="text-muted small">' + description.substring(0, 50) + (description.length > 50 ? '...' : '') + '</div>';
            listHtml += '</div>';
            listHtml += '<span class="' + statusClass + '">';
            listHtml += '<i class="fas ' + statusIcon + '"></i> ' + status;
            listHtml += '</span>';
            listHtml += '</div>';
        });
        
        listHtml += '</div>';
        listContainer.html(listHtml);
    }
    
    // 更新选中的工单列表
    function updateSelectedWorkorders() {
        // 使用Set来去重，确保每个工单ID只被计算一次
        const uniqueIds = new Set();
        $('.workorder-checkbox:checked').each(function() {
            uniqueIds.add($(this).val());
        });
        selectedWorkorders = Array.from(uniqueIds);
        $('#selectedCount').text(selectedWorkorders.length);
        console.log('更新选中工单数量: ' + selectedWorkorders.length);
        console.log('选中工单ID:', selectedWorkorders);
    }
    
    // 更新批量操作UI
    function updateBatchOperationsUI() {
        if (selectedWorkorders.length > 0) {
            $('#batchOperations').slideDown();
            console.log('显示批量操作栏');
        } else {
            $('#batchOperations').slideUp();
            console.log('隐藏批量操作栏');
        }
    }
});
</script>