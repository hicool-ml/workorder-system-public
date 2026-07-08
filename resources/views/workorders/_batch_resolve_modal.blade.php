{{-- Batch resolve modal --}}
<div id="batchResolveModal" data-modal class="hidden fixed inset-0 z-50 items-center justify-center p-4 bg-black/40">
    <div class="card w-full max-w-md shadow-xl max-h-[90vh] flex flex-col">
        <div class="flex items-center justify-between px-5 py-3 border-b border-border shrink-0">
            <h3 class="font-medium">批量解决工单</h3>
            <button type="button" data-modal-close="batchResolveModal" class="p-1 rounded-lg hover:bg-surface-muted">
                <svg class="w-5 h-5 text-ink-muted" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18 6 6 18M6 6l12 12"/></svg>
            </button>
        </div>
        <form method="POST" action="{{ route('workorders.batch.resolve') }}" id="batchResolveForm">
            @csrf
            <input type="hidden" name="workorder_ids" id="batchResolveIds" autocomplete="off">
            <div class="p-5 space-y-4 overflow-y-auto">
                <div>
                    <label class="label" for="batch_solution">解决方案 <span class="text-red-500">*</span></label>
                    <textarea class="input" id="batch_solution" name="solution" rows="4" required placeholder="请详细描述解决方案..." autocomplete="off"></textarea>
                </div>
                <div>
                    <label class="label">备件耗材使用情况</label>
                    <label class="flex items-center gap-2 text-sm text-ink-muted mb-2 cursor-pointer">
                        <input type="checkbox" id="batch_no_materials" name="no_materials" value="1" class="rounded border-border-strong" autocomplete="off">
                        无备件耗材使用
                    </label>
                    <div id="batch_materials_usage_div">
                        <textarea class="input" id="batch_materials_usage" name="materials_usage" rows="3" placeholder="请详细描述使用的备件和耗材情况（适用于所有选中的工单）..." autocomplete="off"></textarea>
                        <p class="text-xs text-ink-subtle mt-1">批量解决时，此备件耗材使用情况将应用于所有选中的工单</p>
                    </div>
                </div>
            </div>
            <div class="flex items-center justify-end gap-2 px-5 py-3 border-t border-border shrink-0">
                <button type="button" data-modal-close="batchResolveModal" class="btn btn-secondary">取消</button>
                <button type="submit" class="btn btn-primary">确认解决</button>
            </div>
        </form>
    </div>
</div>