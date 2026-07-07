<!-- 批量解决模态框 -->
<div class="modal fade" id="batchResolveModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">批量解决工单</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="关闭批量解决对话框"></button>
            </div>
            <form method="POST" action="{{ route('workorders.batch.resolve') }}" id="batchResolveForm">
                @csrf
                <div class="modal-body">
                    <input type="hidden" name="workorder_ids" id="batchResolveIds" autocomplete="off">
                    <div class="mb-3">
                        <label for="batch_solution" class="form-label">解决方案 <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="batch_solution" name="solution" rows="5" required
                                  placeholder="请详细描述解决方案..." autocomplete="off"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="batch_materials_usage" class="form-label">备件耗材使用情况 <span class="text-danger">*</span></label>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" id="batch_no_materials" name="no_materials" value="1" autocomplete="off">
                            <label class="form-check-label" for="batch_no_materials">
                                无备件耗材使用
                            </label>
                        </div>
                        <div id="batch_materials_usage_div">
                            <label for="batch_materials_usage" class="form-label">请填写备件耗材使用情况</label>
                            <textarea class="form-control" id="batch_materials_usage" name="materials_usage" rows="4"
                                      placeholder="请详细描述使用的备件和耗材情况（适用于所有选中的工单）..." autocomplete="off"></textarea>
                            <div class="form-text">
                                请记录使用的备件名称、规格、数量等信息
                                <br><small class="text-info">批量解决时，此备件耗材使用情况将应用于所有选中的工单</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                    <button type="submit" class="btn btn-primary">确认解决</button>
                </div>
            </form>
        </div>
    </div>
</div>