<!-- 批量分配工单模态框 -->
<div class="modal fade" id="batchAssignModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">批量分配工单</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="关闭"></button>
            </div>
            <form id="batchAssignForm">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="batch_assignee_id" class="form-label">分配给 <span class="text-danger">*</span></label>
                        <select class="form-select" id="batch_assignee_id" name="assignee_id" required autocomplete="off">
                            <option value="">请选择工程师</option>
                            @foreach (\App\Models\User::getAssignableEngineers() as $engineer)
                                <option value="{{ $engineer->id }}">{{ $engineer->name }} ({{ $engineer->employee_id ?? '-' }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="batch_assign_note" class="form-label">备注</label>
                        <textarea class="form-control" id="batch_assign_note" name="note" rows="3"
                                  placeholder="可选：填写分配说明..." autocomplete="off"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                    <button type="submit" class="btn btn-success">确认批量分配</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
document.getElementById('batchAssignForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const ids = window.selectedWorkorders || [];
    if (ids.length === 0) { alert('请先选择工单'); return; }
    const formData = new FormData(this);
    ids.forEach(id => formData.append('workorder_ids[]', id));
    fetch('{{ route("workorders.batch.assign") }}', { method: 'POST', body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(r => r.json())
        .then(d => { alert(d.message || '操作完成'); if (d.success) location.reload(); })
        .catch(() => alert('请求失败，请重试'));
});
</script>
