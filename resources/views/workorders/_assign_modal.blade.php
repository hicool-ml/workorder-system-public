<!-- 分配工单模态框 -->
<div class="modal fade" id="assignModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">分配工单</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="关闭"></button>
            </div>
            <form method="POST" action="{{ route('workorders.assign', ':workorder_id') }}" id="assignForm">
                @csrf
                <div class="modal-body">
                    <input type="hidden" name="workorder_id" id="assignWorkorderId" autocomplete="off">
                    <div class="mb-3">
                        <label for="assignee_id" class="form-label">分配给 <span class="text-danger">*</span></label>
                        <select class="form-select" id="assignee_id" name="assignee_id" required autocomplete="off">
                            <option value="">请选择工程师</option>
                            @foreach (\App\Models\User::getAssignableEngineers() as $engineer)
                                <option value="{{ $engineer->id }}">{{ $engineer->name }} ({{ $engineer->employee_id ?? '-' }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="assign_note" class="form-label">备注</label>
                        <textarea class="form-control" id="assign_note" name="note" rows="3"
                                  placeholder="可选：填写分配说明..." autocomplete="off"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                    <button type="submit" class="btn btn-primary">确认分配</button>
                </div>
            </form>
        </div>
    </div>
</div>
