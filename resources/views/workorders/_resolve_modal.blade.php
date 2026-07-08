{{-- Resolve modal --}}
<div id="resolveModal" data-modal class="hidden fixed inset-0 z-50 items-center justify-center p-4 bg-black/40">
    <div class="card w-full max-w-md shadow-xl max-h-[90vh] flex flex-col">
        <div class="flex items-center justify-between px-5 py-3 border-b border-border shrink-0">
            <h3 class="font-medium">解决工单</h3>
            <button type="button" data-modal-close="resolveModal" class="p-1 rounded-lg hover:bg-surface-muted">
                <svg class="w-5 h-5 text-ink-muted" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18 6 6 18M6 6l12 12"/></svg>
            </button>
        </div>
        <form method="POST" action="{{ route('workorders.resolve', 0) }}" id="resolveForm" class="overflow-y-auto">
            @csrf
            <div class="p-5 space-y-4">
                <input type="hidden" name="workorder_id" id="resolveWorkorderId" autocomplete="off">
                <div>
                    <label class="label" for="solution">解决方案 <span class="text-red-500">*</span></label>
                    <textarea class="input" id="solution" name="solution" rows="4" required placeholder="请详细描述解决方案..." autocomplete="off"></textarea>
                </div>
                <div>
                    <label class="label">备件耗材使用情况</label>
                    <label class="flex items-center gap-2 text-sm text-ink-muted mb-2 cursor-pointer">
                        <input type="checkbox" id="no_materials" name="no_materials" value="1" class="rounded border-border-strong" autocomplete="off">
                        无备件耗材使用
                    </label>
                    <div id="materials_usage_div">
                        <textarea class="input" id="resolve_materials_usage" name="materials_usage" rows="3" placeholder="请记录使用的备件名称、规格、数量等信息..." autocomplete="off"></textarea>
                        <p class="text-xs text-ink-subtle mt-1">请记录使用的备件名称、规格、数量等信息</p>
                    </div>
                </div>
            </div>
            <div class="flex items-center justify-end gap-2 px-5 py-3 border-t border-border shrink-0 sticky bottom-0 bg-white rounded-b-xl">
                <button type="button" data-modal-close="resolveModal" class="btn btn-secondary">取消</button>
                <button type="submit" class="btn btn-primary">确认解决</button>
            </div>
        </form>
    </div>
</div>