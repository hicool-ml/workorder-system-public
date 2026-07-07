<!-- 解决工单模态框 -->
<div class="modal fade" id="resolveModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">解决工单</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="关闭解决工单对话框"></button>
            </div>
            <form method="POST" action="{{ route('workorders.resolve', ':workorder_id') }}" id="resolveForm">
                @csrf
                <div class="modal-body">
                    <input type="hidden" name="workorder_id" id="resolveWorkorderId" autocomplete="off">
                    <div class="mb-3">
                        <label for="solution" class="form-label">解决方案 <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="solution" name="solution" rows="5" required
                                  placeholder="请详细描述解决方案..." autocomplete="off"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="resolve_materials_usage" class="form-label">备件耗材使用情况 <span class="text-danger">*</span></label>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" id="no_materials" name="no_materials" value="1" autocomplete="off">
                            <label class="form-check-label" for="no_materials">
                                无备件耗材使用
                            </label>
                        </div>
                        <div id="materials_usage_div">
                            <label for="resolve_materials_usage" class="form-label">请填写备件耗材使用情况</label>
                            <textarea class="form-control" id="resolve_materials_usage" name="materials_usage" rows="4"
                                      placeholder="请详细描述使用的备件和耗材情况..." autocomplete="off"></textarea>
                            <div class="form-text">
                                请记录使用的备件名称、规格、数量等信息
                                <br><small class="text-info d-none" id="materials_usage_prefilled_hint">已预填充之前填写的备件耗材使用情况，可在此基础上补充或修改</small>
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