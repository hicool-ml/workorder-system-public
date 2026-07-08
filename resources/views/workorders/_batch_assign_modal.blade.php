{{-- Batch assign modal --}}
<div id="batchAssignModal" data-modal class="hidden fixed inset-0 z-50 items-center justify-center p-4 bg-black/40">
    <div class="card w-full max-w-md shadow-xl">
        <div class="flex items-center justify-between px-5 py-3 border-b border-border">
            <h3 class="font-medium">批量分配工单</h3>
            <button type="button" data-modal-close="batchAssignModal" class="p-1 rounded-lg hover:bg-surface-muted">
                <svg class="w-5 h-5 text-ink-muted" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18 6 6 18M6 6l12 12"/></svg>
            </button>
        </div>
        <form id="batchAssignForm">
            @csrf
            <div class="p-5 space-y-4">
                <div>
                    <label class="label" for="batch_assignee_id">分配给 <span class="text-red-500">*</span></label>
                    <select class="input" id="batch_assignee_id" name="assignee_id" required autocomplete="off">
                        <option value="">请选择工程师</option>
                        @foreach(\App\Models\User::getAssignableEngineers() as $engineer)
                            <option value="{{ $engineer->id }}">{{ $engineer->name }} ({{ $engineer->employee_id ?? '-' }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="label" for="batch_assign_note">备注</label>
                    <textarea class="input" id="batch_assign_note" name="note" rows="3" placeholder="可选：填写分配说明..." autocomplete="off"></textarea>
                </div>
            </div>
            <div class="flex items-center justify-end gap-2 px-5 py-3 border-t border-border">
                <button type="button" data-modal-close="batchAssignModal" class="btn btn-secondary">取消</button>
                <button type="submit" class="btn btn-primary">确认批量分配</button>
            </div>
        </form>
    </div>
</div>