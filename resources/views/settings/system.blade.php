@extends('layouts.app')
@section('title', '系统设置')
@section('content')
<div class="flex items-center justify-between mb-6 gap-3 flex-wrap">
    <h1 class="text-xl font-semibold text-ink">系统设置</h1>
    <button type="button" onclick="initializeDefaults()" class="btn btn-secondary">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 1 1-3-6.7L21 8 M21 3v5h-5"/></svg>
        <span>初始化默认设置</span>
    </button>
</div>

<div class="space-y-6">
    <x-settings._card title="基础信息">
        <form method="POST" action="{{ route('system-settings.update') }}" class="p-5">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="label" for="system_name">系统名称</label>
                    <input type="text" class="input" id="system_name" name="settings[system_name]" value="{{ $groupedSettings['system']->firstWhere('key', 'system_name')?->typed_value ?? '工单管理系统' }}">
                </div>
                <div>
                    <label class="label" for="system_url">系统访问地址</label>
                    <input type="url" class="input" id="system_url" name="settings[system_url]" value="{{ $groupedSettings['system']->firstWhere('key', 'system_url')?->typed_value ?? '' }}" placeholder="http://192.168.1.100:8099">
                    <p class="text-xs mt-1" style="color: var(--c-ink-subtle);">企业微信通知中的工单链接会使用此地址，需填实际可访问的 IP/域名</p>
                </div>
            </div>
            <div class="mt-4">
                <button type="submit" class="btn btn-primary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    <span>保存设置</span>
                </button>
            </div>
        </form>
    </x-settings._card>

    <x-settings._card title="注册设置">
        <x-slot name="actions">
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" id="registration_enabled" class="rounded w-4 h-4" @if($groupedSettings['registration']->firstWhere('key', 'registration_enabled')?->typed_value) checked @endif onchange="toggleRegistration(this.checked)">
                <span class="text-sm" style="color: var(--c-ink-muted);">开放注册</span>
            </label>
        </x-slot>
        <form method="POST" action="{{ route('system-settings.update') }}" class="p-5">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="label" for="default_user_role">默认用户角色</label>
                    <select class="input" id="default_user_role" name="settings[default_user_role]">
                        <option value="user" @if($groupedSettings['user']->firstWhere('key', 'default_user_role')?->typed_value === 'user') selected @endif>普通用户</option>
                        <option value="engineer" @if($groupedSettings['user']->firstWhere('key', 'default_user_role')?->typed_value === 'engineer') selected @endif>工程师</option>
                        <option value="workorder_manager" @if($groupedSettings['user']->firstWhere('key', 'default_user_role')?->typed_value === 'workorder_manager') selected @endif>工单管理员</option>
                    </select>
                    <p class="text-xs mt-1" style="color: var(--c-ink-subtle);">新注册用户的默认角色</p>
                </div>
                <div>
                    <label class="flex items-center gap-2 cursor-pointer mt-6">
                        <input type="checkbox" id="require_email_verification" name="settings[require_email_verification]" value="1" class="rounded w-4 h-4" @if($groupedSettings['registration']->firstWhere('key', 'require_email_verification')?->typed_value) checked @endif>
                        <span class="text-sm" style="color: var(--c-ink-muted);">需要邮箱验证</span>
                    </label>
                </div>
            </div>
            <div class="mt-4">
                <button type="submit" class="btn btn-primary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    <span>保存设置</span>
                </button>
            </div>
        </form>
    </x-settings._card>

    <x-settings._card title="会话有效期">
        <form method="POST" action="{{ route('system-settings.update') }}" class="p-5">
            @csrf
            <div class="max-w-xs">
                <label class="label" for="session_lifetime">登录会话有效期（分钟）</label>
                <input type="number" class="input" id="session_lifetime" name="settings[session_lifetime]" value="{{ \App\Models\SystemSetting::get('session_lifetime', 120) }}" min="5" max="43200">
                <p class="text-xs mt-1" style="color: var(--c-ink-subtle);">超过该空闲时间未操作需重新登录，默认 120 分钟。微信内置浏览器可能清理 Cookie 导致掉线，如需长期保持登录可调大（如 43200 = 30 天）。</p>
            </div>
            <div class="mt-4">
                <button type="submit" class="btn btn-primary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    <span>保存设置</span>
                </button>
            </div>
        </form>
    </x-settings._card>

    <x-settings._card title="地址前缀">
        <form method="POST" action="{{ route('system-settings.update') }}" class="p-5">
            @csrf
            @php
                $prefixId = \App\Models\SystemSetting::getAddressPrefixId();
                $prefixNode = $prefixId ? \App\Models\Location::find($prefixId) : null;
                $prefixOptions = \App\Models\Location::getSelectOptions();
            @endphp
            <div class="max-w-xl">
                <label class="label" for="settings_address_prefix_location_id">地址前缀截止节点</label>
                <select class="input" id="settings_address_prefix_location_id" name="settings[address_prefix_location_id]">
                    <option value="0">-- 不截断（显示完整地址树） --</option>
                    @foreach($prefixOptions as $id => $label)
                        <option value="{{ $id }}" {{ (string) $prefixId === (string) $id ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                <p class="text-xs mt-1" style="color: var(--c-ink-subtle);">
                    工单填写、工单列表、地址管理界面默认只展示该节点<strong>之下</strong>的层级。
                    例：选"XX省 / XX市 / XX区 / XX路 / XX号"后，日常只与"区域/楼栋/房间"打交道。
                </p>
                @if($prefixNode)
                <p class="text-xs mt-2 text-green-700">当前前缀：{{ $prefixNode->full_address_delimited }}</p>
                @endif
            </div>
            <div class="mt-4">
                <button type="submit" class="btn btn-primary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    <span>保存设置</span>
                </button>
            </div>
        </form>
    </x-settings._card>

    <x-settings._card title="版本管理">
        <x-slot name="actions">
            <button type="button" onclick="openVersionModal()" class="btn btn-secondary btn-sm">
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
                <label class="label" for="release_notes">发布说明 <span class="text-red-500">*</span></label>
                <textarea class="input" id="release_notes" name="release_notes" rows="4" required placeholder="请输入此版本的更新内容和改进..."></textarea>
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
function toggleRegistration(enabled) {
    fetch('{{ route("system-settings.toggle-registration") }}', {
        method: 'POST',
        headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
        body: JSON.stringify({ enabled: enabled })
    }).then(function(r) { return r.json(); })
      .then(function(data) {
          if (!data.success) {
              alert('更新失败：' + (data.message || '未知错误'));
              document.getElementById('registration_enabled').checked = !enabled;
          }
      }).catch(function(err) {
          alert('网络错误：' + err.message);
          document.getElementById('registration_enabled').checked = !enabled;
      });
}
function initializeDefaults() {
    if (!confirm('确定要初始化所有缺失的默认设置吗？已有设置不会被覆盖。')) return;
    fetch('{{ route("system-settings.initialize-defaults") }}', { method: 'POST', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content } })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) { alert(data.message || '已初始化'); location.reload(); }
            else alert('初始化失败：' + (data.message || '未知错误'));
        }).catch(function(err) { alert('初始化失败：' + (err.message || '网络错误')); });
}
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
var CURRENT_VERSION = '{{ $groupedSettings['version']->firstWhere('key', 'system_version')?->typed_value ?? '2.0.0' }}';

function nextVersion(v) {
    v = String(v || '2.0.0').trim();
    var m = v.match(/^(\d+)\.(\d+)\.(\d+)/);
    if (!m) return v;
    return m[1] + '.' + m[2] + '.' + (parseInt(m[3], 10) + 1);
}

function todayStr() {
    var d = new Date();
    var mm = ('0' + (d.getMonth() + 1)).slice(-2);
    var dd = ('0' + d.getDate()).slice(-2);
    return d.getFullYear() + '-' + mm + '-' + dd;
}

function openVersionModal() {
    document.getElementById('new_version').value = nextVersion(CURRENT_VERSION);
    document.getElementById('new_release_date').value = todayStr();
    openModal('versionUpdateModal');
}

function deleteVersionHistory(version) {
    if (!confirm('确定删除版本 ' + version + ' 的发布记录吗？')) return;
    fetch('{{ route("system-settings.version-history.delete") }}', {
        method: 'DELETE',
        headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
        body: JSON.stringify({ version: version })
    }).then(function(r) { return r.json(); })
      .then(function(data) {
          if (data.success) { loadVersionHistory(); }
          else alert('删除失败：' + (data.message || '未知错误'));
      })
      .catch(function(err) { alert('删除失败：' + (err.message || '网络错误')); });
}

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
                            '<div class="flex items-center gap-2">' +
                                '<span class="text-xs" style="color: var(--c-ink-subtle);">' + item.created_at + '</span>' +
                                '<button type="button" onclick="deleteVersionHistory(\'' + item.version + '\')" class="btn btn-ghost btn-icon btn-sm text-red-500" title="删除记录">' +
                                    '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18 M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>' +
                                '</button>' +
                            '</div>' +
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
