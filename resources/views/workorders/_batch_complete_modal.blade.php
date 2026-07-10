<!-- 批量完结模态框 -->
<div class="modal fade" id="batchCompleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-check-circle text-success"></i> 批量完结工单
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="关闭批量完结对话框"></button>
            </div>
            <form method="POST" action="{{ \App\Helpers\UrlHelper::relative_url('/workorders/batch/complete') }}" id="batchCompleteForm">
                @csrf
                <input type="hidden" name="workorder_ids" id="batchCompleteIds" autocomplete="off">
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>注意：</strong>只有状态为"已解决"的工单才能被完结。完结后的工单状态将变为"已完结"。
                    </div>
                    
                    <div class="mb-3">
                        <label for="batch_completion_note" class="form-label">完结备注</label>
                        <textarea class="form-control" id="batch_completion_note" name="completion_note" rows="4"
                                  placeholder="请输入批量完结的备注信息（可选）..." autocomplete="off"></textarea>
                        <div class="form-text">
                            可以记录完结的原因、总结或其他相关信息
                        </div>
                    </div>
                    
                    <!-- 常用完结备注模板 -->
                    <div class="mb-3">
                        <label class="form-label">常用完结备注模板</label>
                        <select class="form-select" id="completion_note_template">
                            <option value="">选择模板...</option>
                            <option value="用户确认问题已解决，工单完结">用户确认问题已解决，工单完结</option>
                            <option value="系统运行正常，问题已彻底解决">系统运行正常，问题已彻底解决</option>
                            <option value="设备更换完成，运行稳定">设备更换完成，运行稳定</option>
                            <option value="软件更新完成，功能正常">软件更新完成，功能正常</option>
                            <option value="网络配置完成，连接稳定">网络配置完成，连接稳定</option>
                            <option value="定期维护完成，系统运行良好">定期维护完成，系统运行良好</option>
                        </select>
                    </div>
                    
                    <!-- 待完结工单列表 -->
                    <div class="mb-3">
                        <label class="form-label">待完结工单列表</label>
                        <div id="batch_complete_workorders_list" class="border rounded p-2" style="max-height: 200px; overflow-y: auto;">
                            <!-- 工单列表将通过JavaScript动态生成 -->
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i> 取消
                    </button>
                    <button type="submit" class="btn btn-success" id="confirmCompleteBtn">
                        <i class="fas fa-check-circle"></i> 确认完结
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
// 批量完结表单提交事件
$(document).ready(function() {
    // 完结备注模板选择事件
    $('#completion_note_template').on('change', function() {
        var templateText = $(this).val();
        if (templateText) {
            $('#batch_completion_note').val(templateText);
        }
    });
    
    // 批量完结表单提交事件
    $('#batchCompleteForm').on('submit', function(e) {
        e.preventDefault();
        
        var form = $(this);
        var submitBtn = $('#confirmCompleteBtn');
        var originalText = submitBtn.html();
        
        // 禁用提交按钮，显示加载状态
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> 完结中...');
        
        $.ajax({
            url: form.attr('action'),
            method: 'POST',
            data: form.serialize(),
            success: function(response) {
                if (response.success) {
                    // 关闭模态框
                    $('#batchCompleteModal').modal('hide');
                    
                    // 显示成功消息
                    alert(response.message);
                    
                    // 刷新页面
                    setTimeout(function() {
                        window.location.reload();
                    }, 1500);
                } else {
                    alert(response.message);
                }
            },
            error: function(xhr) {
                var message = '批量完结失败，请稍后重试';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }
                alert(message);
            },
            complete: function() {
                // 恢复提交按钮状态
                submitBtn.prop('disabled', false).html(originalText);
            }
        });
    });
});
</script>
@endpush