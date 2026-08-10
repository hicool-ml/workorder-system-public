@extends('layouts.app')
@section('title', '版本管理')
@section('content')
<div class="mb-6">
    <h1 class="text-xl font-semibold text-ink">版本管理</h1>
</div>

<div class="space-y-6">
    <x-settings._card title="版本管理">
        <x-slot name="actions">
            <button type="button" onclick="openModal('versionUpdateModal')" class="btn btn-secondary btn-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/></svg>
                <span>更新版本</span>
            </button>
            <button type="button" onclick="loadVersionHistory()" class="btn btn-secondary btn-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3v5h5 M3.05 13A9 9 0 1 0 6 5.3L3 8 M12 7v5l4 2"/></svg>
                <span>版本历史</span>
            </button>
        </x-slot>
        <div class="p-5">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg flex items-center justify-center" style="background-color: var(--c-brand-light);">
                        <svg class="w-5 h-5" style="color: var(--c-brand);" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20.59 13.41 13.42 20.58a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z M7 7h.01"/></svg>
                    </div>
                    <div>
                        <p class="text-xs" style="color: var(--c-ink-muted);">当前版本</p>
                        <span class="badge bg-blue-100 text-blue-700">{{ $groupedSettings['version']->firstWhere('key', 'system_version')?->typed_value ?? '2.0.0' }}</span>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg flex items-center justify-center" style="background-color: var(--c-brand-light);">
                        <svg class="w-5 h-5" style="color: var(--c-brand);" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><path stroke-linecap="round" stroke-linejoin="round" d="M16 2v4M8 2v4M3 10h18"/></svg>
                    </div>
                    <div>
                        <p class="text-xs" style="color: var(--c-ink-muted);">发布日期</p>
                        <p class="text-sm font-medium text-ink">{{ $groupedSettings['version']->firstWhere('key', 'system_release_date')?->typed_value ?? date('Y-m-d') }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg flex items-center justify-center" style="background-color: var(--c-brand-light);">
                        <svg class="w-5 h-5" style="color: var(--c-brand);" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 21h18 M5 21V7l8-4v18 M19 21V11l-6-4"/></svg>
                    </div>
                    <div>
                        <p class="text-xs" style="color: var(--c-ink-muted);">系统名称</p>
                        <p class="text-sm font-medium text-ink truncate">{{ $groupedSettings['system']->firstWhere('key', 'system_name')?->typed_value ?? '工单管理系统' }}</p>
                    </div>
                </div>
            </div>
            <div id="versionHistory" class="hidden mt-4 pt-4 border-t border-border">
                <h4 class="text-sm font-medium text-ink mb-3">版本历史</h4>
                <div id="versionHistoryList" class="space-y-2"></div>
            </div>
        </div>
    </x-settings._card>
</div>

{{-- 更新版本弹窗 --}}
<div id="versionUpdateModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" data-modal onclick="if(event.target===this)closeModal('versionUpdateModal')">
    <div class="card max-w-md w-full max-h-[90vh] overflow-y-auto">
        <div class="px-5 py-4 border-b border-border flex items-center justify-between">
            <h3 class="text-sm font-semibold text-ink">更新系统版本</h3>
            <button type="button" onclick="closeModal('versionUpdateModal')" class="btn btn-ghost btn-icon btn-sm">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18 6 6 18M6 6l12 12"/></svg>
            </button>
        </div>
        <form id="versionUpdateForm" class="p-5 space-y-4">
            @csrf
            <div>
                <label class="label" for="new_version">新版本号</label>
                <input type="text" class="input" id="new_version" name="version" required placeholder="例如：2.1.0" value="{{ $groupedSettings['version']->firstWhere('key', 'system_version')?->typed_value ?? '2.0.0' }}">
            </div>
            <div>
                <label class="label" for="new_release_date">发布日期</label>
                <input type="date" class="input" id="new_release_date" name="release_date" required value="{{ date('Y-m-d') }}">
            </div>
            <div>
                <label class="label" for="release_notes">发布说明</label>
                <textarea class="input" id="release_notes" name="release_notes" rows="4" placeholder="请输入此版本的更新内容和改进..."></textarea>
            </div>
            <div class="flex items-center justify-end gap-2">
                <button type="button" onclick="closeModal('versionUpdateModal')" class="btn btn-secondary">取消</button>
                <button type="submit" class="btn btn-primary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    <span>更新版本</span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
@section('scripts')
<script>
function openModal(id) {
    var el = document.getElementById(id);
    el.classList.remove('hidden');
    el.classList.add('flex');
    document.body.classList.add('overflow-hidden');
}
function closeModal(id) {
    var el = document.getElementById(id);
    el.classList.add('hidden');
    el.classList.remove('flex');
    document.body.classList.remove('overflow-hidden');
}
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeModal('versionUpdateModal');
});
function loadVersionHistory() {
    var historyDiv = document.getElementById('versionHistory');
    var historyList = document.getElementById('versionHistoryList');
    if (!historyDiv.classList.contains('hidden')) { historyDiv.classList.add('hidden'); return; }
    historyList.innerHTML = '<div class="text-center py-4 text-sm" style="color: var(--c-ink-muted);">加载中...</div>';
    historyDiv.classList.remove('hidden');
    fetch('{{ route("system-settings.version-history") }}', { headers: { 'Accept': 'application/json' } })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data && data.length > 0) {
                var html = '';
                data.forEach(function(item) {
                    html += '<div class="p-3 rounded-lg border border-border">' +
                        '<div class="flex items-center justify-between mb-1">' +
                            '<span class="text-sm font-medium text-ink">版本 ' + item.version + '</span>' +
                            '<span class="text-xs" style="color: var(--c-ink-subtle);">' + item.created_at + '</span>' +
                        '</div>' +
                        '<p class="text-xs" style="color: var(--c-ink-muted);">' + (item.notes || '') + '</p>' +
                    '</div>';
                });
                historyList.innerHTML = html;
            } else {
                historyList.innerHTML = '<div class="text-center py-4 text-sm" style="color: var(--c-ink-muted);">暂无版本历史记录</div>';
            }
        })
        .catch(function() { historyList.innerHTML = '<div class="text-sm text-red-500">加载版本历史失败</div>'; });
}
document.getElementById('versionUpdateForm').addEventListener('submit', function(e) {
    e.preventDefault();
    var formData = new FormData(this);
    var data = {};
    formData.forEach(function(v, k) { data[k] = v; });
    fetch('{{ route("system-settings.update-version") }}', {
        method: 'POST',
        headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
        body: JSON.stringify(data)
    }).then(function(r) { return r.json(); })
      .then(function(data) {
          if (data.success) { closeModal('versionUpdateModal'); alert('版本更新成功！'); location.reload(); }
          else alert('版本更新失败：' + (data.message || '未知错误'));
      })
      .catch(function(err) { alert('版本更新失败：' + (err.message || '网络错误')); });
});
</script>
@endsection
