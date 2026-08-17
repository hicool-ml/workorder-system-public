{{-- 批量彻底删除工单确认框（仅管理员） --}}
<div id="batchForceDeleteModal" data-modal class="hidden fixed inset-0 z-50 items-center justify-center p-4 bg-black/40">
    <div class="card w-full max-w-md shadow-xl">
        <div class="flex items-center justify-between px-5 py-3 border-b border-border">
            <h3 class="font-medium text-red-600">批量彻底删除工单</h3>
            <button type="button" data-modal-close="batchForceDeleteModal" class="p-1 rounded-lg hover:bg-surface-muted">
                <svg class="w-5 h-5 text-ink-muted" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18 6 6 18M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="p-5 space-y-4">
            <div class="text-sm text-ink-muted">
                即将彻底删除选中的 <strong id="forceDeleteCount" class="text-red-600">0</strong> 个工单。
                此操作会同时删除附件及所有关联记录，<span class="text-red-600 font-medium">不可恢复</span>。
            </div>
            <div>
                <label class="label" for="force_delete_confirm_input">
                    请输入待删除的工单数量 <strong id="forceDeleteCountHint" class="text-red-600">0</strong> 以确认
                </label>
                <input type="text" inputmode="numeric" class="input" id="force_delete_confirm_input" placeholder="输入数量确认" autocomplete="off">
                <p id="forceDeleteConfirmError" class="hidden text-xs text-red-600 mt-1">输入的数量不正确</p>
            </div>
        </div>
        <div class="flex items-center justify-end gap-2 px-5 py-3 border-t border-border">
            <button type="button" data-modal-close="batchForceDeleteModal" class="btn btn-secondary">取消</button>
            <button type="button" class="btn btn-danger" id="confirmBatchForceDeleteBtn">确认彻底删除</button>
        </div>
    </div>
</div>
