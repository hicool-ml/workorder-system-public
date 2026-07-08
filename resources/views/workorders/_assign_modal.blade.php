{{-- Assign modal --}}
<div id="assignModal" data-modal class="hidden fixed inset-0 z-50 items-center justify-center p-4 bg-black/40">
    <div class="card w-full max-w-md shadow-xl">
        <div class="flex items-center justify-between px-5 py-3 border-b border-border">
            <h3 class="font-medium">分配工单</h3>
            <button type="button" data-modal-close="assignModal" class="p-1 rounded-lg hover:bg-surface-muted">
                <svg class="w-5 h-5 text-ink-muted" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18 6 6 18M6 6l12 12"/></svg>
            </button>
        </div>
        <form method="POST" action="{{ route('workorders.assign', 0) }}" id="assignForm">
            @csrf
            <div class="p-5 space-y-4">
                <input type="hidden" name="workorder_id" id="assignWorkorderId" autocomplete="off">
                <div>
                    <label class="label" for="assignee_id">分配给 <span class="text-red-500">*</span></label>
                    <select class="input" id="assignee_id" name="assignee_id" required autocomplete="off">
                        <option value="">请选择工程师</option>
                        @foreach(\App\Models\User::getAssignableEngineers() as $engineer)
                            <option value="{{ $engineer->id }}">{{ $engineer->name }} ({{ $engineer->employee_id ?? '-' }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="label" for="assign_note">备注</label>
                    <textarea class="input" id="assign_note" name="note" rows="3" placeholder="可选：填写分配说明..." autocomplete="off"></textarea>
                </div>
            </div>
            <div class="flex items-center justify-end gap-2 px-5 py-3 border-t border-border">
                <button type="button" data-modal-close="assignModal" class="btn btn-secondary">取消</button>
                <button type="submit" class="btn btn-primary">确认分配</button>
            </div>
        </form>
    </div>
</div>