@extends('layouts.app')
@section('title', '备份 & 恢复')
@section('content')
<div class="mb-6">
    <h1 class="text-xl font-semibold text-ink">备份 & 恢复</h1>
</div>

<div class="space-y-6">
    <x-settings._card title="数据备份与恢复">
        <x-slot name="actions">
            <button type="button" id="btnCreateBackup" onclick="createBackup()" class="btn btn-primary btn-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/></svg>
                <span>立即备份</span>
            </button>
            <label class="btn btn-secondary btn-sm cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2 M12 3v13 M7 8l5-5 5 5"/></svg>
                <span>上传备份</span>
                <input type="file" name="backup_file" accept=".zip" class="hidden" onchange="uploadBackup(this)">
            </label>
            <button type="button" onclick="loadBackups()" class="btn btn-secondary btn-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12a9 9 0 1 0 3-6.7L3 8 M3 3v5h5"/></svg>
                <span>刷新</span>
            </button>
        </x-slot>
        <div class="p-5">
            <div class="flex items-center gap-3 mb-3 p-3 rounded-lg bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-300 text-xs">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4 M12 17h.01 M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                <span>恢复会覆盖当前数据库。每次恢复前系统会自动备份当前状态，便于回滚。每日凌晨 2 点自动备份一次。</span>
            </div>
            <div id="backupList">
                <div class="text-center py-6 text-sm" style="color: var(--c-ink-muted);">加载中...</div>
            </div>
        </div>
    </x-settings._card>
</div>

{{-- 恢复确认弹窗 --}}
<div id="restoreBackupModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" data-modal onclick="if(event.target===this)closeModal('restoreBackupModal')">
    <div class="card max-w-md w-full">
        <div class="px-5 py-4 border-b border-border flex items-center justify-between">
            <h3 class="text-sm font-semibold text-ink">确认恢复备份</h3>
            <button type="button" onclick="closeModal('restoreBackupModal')" class="btn btn-ghost btn-icon btn-sm">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18 6 6 18M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="p-5 space-y-3">
            <div class="p-3 rounded-lg bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-300 text-xs">
                恢复将用备份数据<strong>覆盖</strong>当前数据库与附件。操作前会自动备份当前状态以便回滚。
            </div>
            <div class="text-sm text-ink">即将恢复备份：<span id="restoreBackupName" class="font-mono"></span></div>
            <div>
                <label class="label">请输入 <span class="font-mono font-bold">确认恢复</span> 以继续</label>
                <input type="text" id="restoreConfirmInput" class="input" placeholder="确认恢复" autocomplete="off">
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="closeModal('restoreBackupModal')" class="btn btn-secondary">取消</button>
                <button type="button" id="btnConfirmRestore" onclick="executeRestore()" class="btn btn-danger" disabled>确认恢复</button>
            </div>
        </div>
    </div>
</div>
@endsection
@section('scripts')
<script>
// openModal/closeModal 由 layouts/app 全局提供
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeModal('restoreBackupModal');
});

var BACKUP_ROUTES = {
    index: '{{ route("system-settings.backups.index") }}',
    create: '{{ route("system-settings.backups.create") }}',
    upload: '{{ route("system-settings.backups.upload") }}',
    download: function(name) { return '{{ route("system-settings.backups.download", ["__NAME__"]) }}'.replace('__NAME__', name); },
    destroy: function(name) { return '{{ route("system-settings.backups.destroy", ["__NAME__"]) }}'.replace('__NAME__', name); },
    restore: function(name) { return '{{ route("system-settings.backups.restore", ["__NAME__"]) }}'.replace('__NAME__', name); }
};
var CSRF = document.querySelector('meta[name="csrf-token"]').content;
var pendingRestoreName = null;

function loadBackups() {
    var list = document.getElementById('backupList');
    list.innerHTML = '<div class="text-center py-4 text-sm" style="color: var(--c-ink-muted);">加载中...</div>';
    fetch(BACKUP_ROUTES.index, { headers: { 'Accept': 'application/json' } })
        .then(function(r) { return r.json(); })
        .then(function(res) {
            if (!res.success) { list.innerHTML = renderEmpty(res.message || '加载失败'); return; }
            renderBackupList(res.data || []);
        })
        .catch(function(err) { list.innerHTML = renderEmpty('加载失败：' + (err.message || '网络错误')); });
}
function renderEmpty(msg) {
    return '<div class="text-center py-6 text-sm text-red-500">' + escapeHtml(msg) + '</div>';
}
function renderBackupList(items) {
    if (!items.length) {
        return void (document.getElementById('backupList').innerHTML =
            '<div class="text-center py-6 text-sm" style="color: var(--c-ink-muted);">暂无备份。点击"立即备份"创建第一份。</div>');
    }
    var html = '<div class="overflow-x-auto"><table class="w-full text-sm"><thead><tr class="text-left border-b border-border" style="color: var(--c-ink-muted);">'
        + '<th class="py-2 px-2 font-medium">备份名称</th>'
        + '<th class="py-2 px-2 font-medium">创建时间</th>'
        + '<th class="py-2 px-2 font-medium">大小</th>'
        + '<th class="py-2 px-2 font-medium">内容</th>'
        + '<th class="py-2 px-2 font-medium text-right">操作</th>'
        + '</tr></thead><tbody>';
    items.forEach(function(it) {
        var badges = '';
        if (it.uploaded) badges += ' <span class="badge bg-purple-100 text-purple-700 text-xs">上传</span>';
        else badges += ' <span class="badge bg-blue-100 text-blue-700 text-xs">系统</span>';
        if (it.has_sql) badges += ' <span class="badge bg-green-100 text-green-700 text-xs">数据库</span>';
        if (it.has_attachments) badges += ' <span class="badge bg-amber-100 text-amber-700 text-xs">附件</span>';
        html += '<tr class="border-b border-border hover:bg-black/5 dark:hover:bg-white/5">'
            + '<td class="py-2 px-2 font-mono text-xs">' + escapeHtml(it.name) + '</td>'
            + '<td class="py-2 px-2 text-xs" style="color: var(--c-ink-muted);">' + (it.created_at || '-') + '</td>'
            + '<td class="py-2 px-2 text-xs">' + escapeHtml(it.size_human) + '</td>'
            + '<td class="py-2 px-2">' + badges + '</td>'
            + '<td class="py-2 px-2 text-right whitespace-nowrap">'
            +     '<a href="' + BACKUP_ROUTES.download(it.name) + '" class="btn btn-secondary btn-sm" title="下载">'
            +       '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2 M12 3v13 M7 8l5 5 5-5"/></svg></a>'
            +     ' <button type="button" onclick="confirmRestore(\'' + it.name + '\')" class="btn btn-secondary btn-sm" title="恢复" ' + (it.has_sql ? '' : 'disabled') + '>'
            +       '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12a9 9 0 1 0 3-6.7L3 8 M3 3v5h5"/></svg></button>'
            +     ' <button type="button" onclick="deleteBackup(\'' + it.name + '\')" class="btn btn-danger btn-sm" title="删除">'
            +       '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18 M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg></button>'
            + '</td></tr>';
    });
    html += '</tbody></table></div>';
    document.getElementById('backupList').innerHTML = html;
}
function createBackup() {
    var btn = document.getElementById('btnCreateBackup');
    btn.disabled = true;
    var orig = btn.innerHTML;
    btn.innerHTML = '<svg class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke-opacity=".25" stroke-width="3"/><path d="M12 2a10 10 0 0 1 10 10" stroke-linecap="round"/></svg><span>备份中...</span>';
    fetch(BACKUP_ROUTES.create, { method: 'POST', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF } })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            btn.disabled = false; btn.innerHTML = orig;
            if (data.success) { alert('备份已创建：' + (data.backup || '')); loadBackups(); }
            else alert('备份失败：' + (data.message || '未知错误'));
        })
        .catch(function(err) {
            btn.disabled = false; btn.innerHTML = orig;
            alert('备份失败：' + (err.message || '网络错误'));
        });
}
function uploadBackup(input) {
    if (!input.files || !input.files[0]) return;
    var file = input.files[0];
    var fd = new FormData();
    fd.append('file', file);
    fd.append('_token', CSRF);
    if (!confirm('确认上传备份文件：' + file.name + ' ？')) { input.value = ''; return; }
    var list = document.getElementById('backupList');
    list.innerHTML = '<div class="text-center py-4 text-sm" style="color: var(--c-ink-muted);">上传中...</div>';
    fetch(BACKUP_ROUTES.upload, { method: 'POST', headers: { 'Accept': 'application/json' }, body: fd })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            input.value = '';
            if (data.success) { alert('上传成功'); loadBackups(); }
            else { alert('上传失败：' + (data.message || '未知错误')); loadBackups(); }
        })
        .catch(function(err) {
            input.value = '';
            alert('上传失败：' + (err.message || '网络错误'));
            loadBackups();
        });
}
function deleteBackup(name) {
    if (!confirm('确认删除备份 ' + name + ' ？此操作不可撤销。')) return;
    fetch(BACKUP_ROUTES.destroy(name), { method: 'DELETE', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF } })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) { loadBackups(); }
            else alert('删除失败：' + (data.message || '未知错误'));
        })
        .catch(function(err) { alert('删除失败：' + (err.message || '网络错误')); });
}
function confirmRestore(name) {
    pendingRestoreName = name;
    document.getElementById('restoreBackupName').textContent = name;
    document.getElementById('restoreConfirmInput').value = '';
    document.getElementById('btnConfirmRestore').disabled = true;
    openModal('restoreBackupModal');
}
document.getElementById('restoreConfirmInput').addEventListener('input', function() {
    document.getElementById('btnConfirmRestore').disabled = this.value.trim() !== '确认恢复';
});
function executeRestore() {
    if (!pendingRestoreName) return;
    if (document.getElementById('restoreConfirmInput').value.trim() !== '确认恢复') {
        alert('请输入"确认恢复"以继续'); return;
    }
    var btn = document.getElementById('btnConfirmRestore');
    btn.disabled = true;
    var orig = btn.innerHTML;
    btn.innerHTML = '恢复中...';
    fetch(BACKUP_ROUTES.restore(pendingRestoreName), {
        method: 'POST',
        headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
        body: JSON.stringify({ confirm: true })
    }).then(function(r) { return r.json(); })
      .then(function(data) {
          btn.disabled = false; btn.innerHTML = orig;
          closeModal('restoreBackupModal');
          if (data.success) {
              alert(data.message + '\n\n建议刷新页面以加载恢复后的数据。');
              location.reload();
          } else {
              alert('恢复失败：' + (data.message || '未知错误'));
          }
      })
      .catch(function(err) {
          btn.disabled = false; btn.innerHTML = orig;
          alert('恢复失败：' + (err.message || '网络错误'));
      });
}
function escapeHtml(s) {
    return String(s).replace(/[&<>"']/g, function(c) {
        return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
}
document.addEventListener('DOMContentLoaded', loadBackups);
</script>
@endsection
