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
    {{-- Registration settings --}}
    <div class="card overflow-hidden">
        <div class="px-5 py-4 border-b border-border flex items-center justify-between gap-3">
            <h3 class="text-sm font-semibold text-ink">注册设置</h3>
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" id="registration_enabled" class="rounded w-4 h-4" @if($groupedSettings['registration']->firstWhere('key', 'registration_enabled')?->typed_value) checked @endif onchange="toggleRegistration(this.checked)">
                <span class="text-sm" style="color: var(--c-ink-muted);">开放注册</span>
            </label>
        </div>
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
    </div>

    {{-- System settings --}}
    <div class="card overflow-hidden">
        <div class="px-5 py-4 border-b border-border">
            <h3 class="text-sm font-semibold text-ink">系统设置</h3>
        </div>
        <form method="POST" action="{{ route('system-settings.update') }}" class="p-5">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="sm:col-span-1">
                    <label class="label" for="system_name">系统名称</label>
                    <input type="text" class="input" id="system_name" name="settings[system_name]" value="{{ $groupedSettings['system']->firstWhere('key', 'system_name')?->typed_value ?? '校园网工单系统' }}">
                </div>
                <div>
                    <label class="label" for="system_version">版本号</label>
                    <input type="text" class="input" id="system_version" name="settings[system_version]" value="{{ $groupedSettings['version']->firstWhere('key', 'system_version')?->typed_value ?? '2.0.0' }}">
                </div>
                <div>
                    <label class="label" for="system_release_date">发布日期</label>
                    <input type="date" class="input" id="system_release_date" name="settings[system_release_date]" value="{{ $groupedSettings['version']->firstWhere('key', 'system_release_date')?->typed_value ?? date('Y-m-d') }}">
                </div>
                <div class="sm:col-span-3">
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
    </div>

    {{-- Version management --}}
    <div class="card overflow-hidden">
        <div class="px-5 py-4 border-b border-border flex items-center justify-between gap-3">
            <h3 class="text-sm font-semibold text-ink">版本管理</h3>
            <div class="flex items-center gap-2">
                <button type="button" onclick="openModal('versionUpdateModal')" class="btn btn-secondary btn-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/></svg>
                    <span>更新版本</span>
                </button>
                <button type="button" onclick="loadVersionHistory()" class="btn btn-secondary btn-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3v5h5 M3.05 13A9 9 0 1 0 6 5.3L3 8 M12 7v5l4 2"/></svg>
                    <span>版本历史</span>
                </button>
            </div>
        </div>
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
                        <p class="text-sm font-medium text-ink truncate">{{ $groupedSettings['system']->firstWhere('key', 'system_name')?->typed_value ?? '校园网工单系统' }}</p>
                    </div>
                </div>
            </div>

            <div id="versionHistory" class="hidden mt-4 pt-4 border-t border-border">
                <h4 class="text-sm font-medium text-ink mb-3">版本历史</h4>
                <div id="versionHistoryList" class="space-y-2"></div>
            </div>
        </div>
    </div>

    {{-- 集成配置 --}}
    <div class="card overflow-hidden">
        <div class="px-5 py-4 border-b border-border">
            <h3 class="text-sm font-semibold text-ink">集成配置</h3>
        </div>
        <div class="p-5 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">

            {{-- 通知规则 --}}
            <a href="{{ route('system-settings.notification-rules') }}" class="block p-4 rounded-lg border border-border hover:border-brand-400 hover:shadow-sm transition-all">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-10 h-10 rounded-lg flex items-center justify-center bg-blue-50 dark:bg-blue-900/20">
                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.2V11a6 6 0 0 0-4-5.7V5a2 2 0 0 0-4 0v.3A6 6 0 0 0 6 11v3.2c0 .5-.2 1-.6 1.4L4 17h5m6 0v1a3 3 0 0 1-6 0v-1m6 0H9"/></svg>
                    </div>
                    <span class="text-sm font-semibold text-ink">通知规则</span>
                </div>
                <p class="text-xs" style="color: var(--c-ink-muted);">按工单事件配置站内通知和短信通知</p>
            </a>

            {{-- 短信配置 --}}
            <a href="{{ route('system-settings.sms') }}" class="block p-4 rounded-lg border border-border hover:border-brand-400 hover:shadow-sm transition-all">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-10 h-10 rounded-lg flex items-center justify-center bg-green-50 dark:bg-green-900/20">
                        <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    </div>
                    <span class="text-sm font-semibold text-ink">短信配置</span>
                </div>
                <p class="text-xs" style="color: var(--c-ink-muted);">短信服务商密钥和模板配置</p>
            </a>

            {{-- 企业微信群机器人 --}}
            <a href="{{ route('system-settings.wecom') }}" class="block p-4 rounded-lg border border-border hover:border-brand-400 hover:shadow-sm transition-all">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-10 h-10 rounded-lg flex items-center justify-center bg-emerald-50 dark:bg-emerald-900/20">
                        <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 11a4 4 0 1 0-8 0 4 4 0 0 0 8 0z M16 11a4 4 0 1 0-8 0 4 4 0 0 0 8 0z M16 19a4 4 0 1 0-8 0 4 4 0 0 0 8 0z M24 19a4 4 0 1 0-8 0 4 4 0 0 0 8 0z"/></svg>
                    </div>
                    <span class="text-sm font-semibold text-ink">企业微信</span>
                </div>
                <p class="text-xs" style="color: var(--c-ink-muted);">群机器人 Webhook 通知
                    @if(filter_var(\App\Models\SystemSetting::get('wecom_webhook_enabled', '0'), FILTER_VALIDATE_BOOLEAN))
                    <span class="text-green-600 font-medium ml-1">已启用</span>
                    @else
                    <span class="text-orange-500 ml-1">未启用</span>
                    @endif
                </p>
            </a>

        </div>
    </div>

        {{-- All settings table --}}
    <div class="card overflow-hidden">
        <div class="px-5 py-4 border-b border-border">
            <h3 class="text-sm font-semibold text-ink">所有设置</h3>
        </div>
        <div class="md:hidden divide-y divide-border">
            @foreach($settings as $setting)
            <div class="p-4">
                <div class="flex items-center justify-between gap-2 mb-1">
                    <code class="text-sm text-ink">{{ $setting->key }}</code>
                    @if($setting->type === 'boolean')<span class="badge {{ $setting->typed_value ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">{{ $setting->typed_value ? '是' : '否' }}</span>@endif
                </div>
                <p class="text-xs mb-2" style="color: var(--c-ink-subtle);">{{ $setting->description ?? '-' }}</p>
                <div class="flex items-center gap-2">
                    <button type="button" class="btn btn-ghost btn-sm" onclick="editSetting('{{ $setting->key }}', '{{ $setting->value }}', '{{ $setting->type }}')">编辑</button>
                    <form method="POST" action="{{ route('system-settings.destroy', $setting) }}" class="inline" onsubmit="return confirm('确定要删除这个设置吗？')">@csrf @method('DELETE')<button type="submit" class="btn btn-ghost btn-sm text-red-500">删除</button></form>
                </div>
            </div>
            @endforeach
        </div>
        <div class="hidden md:block overflow-x-auto">
            <table class="w-full text-sm">
                <thead><tr class="border-b border-border text-left">
                    <th class="px-5 py-3 font-medium" style="color: var(--c-ink-muted);">设置键</th>
                    <th class="px-5 py-3 font-medium" style="color: var(--c-ink-muted);">值</th>
                    <th class="px-5 py-3 font-medium" style="color: var(--c-ink-muted);">类型</th>
                    <th class="px-5 py-3 font-medium" style="color: var(--c-ink-muted);">描述</th>
                    <th class="px-5 py-3 font-medium" style="color: var(--c-ink-muted);">公开</th>
                    <th class="px-5 py-3 font-medium text-right" style="color: var(--c-ink-muted);">操作</th>
                </tr></thead>
                <tbody>
                @foreach($settings as $setting)
                <tr class="border-b border-border">
                    <td class="px-5 py-3"><code class="text-ink">{{ $setting->key }}</code></td>
                    <td class="px-5 py-3 text-ink">@if($setting->type === 'boolean')<span class="badge {{ $setting->typed_value ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">{{ $setting->typed_value ? '是' : '否' }}</span>@else{{ Str::limit($setting->value, 50) }}@endif</td>
                    <td class="px-5 py-3"><span class="badge bg-blue-100 text-blue-700">{{ $setting->type }}</span></td>
                    <td class="px-5 py-3 text-ink">{{ $setting->description ?? '-' }}</td>
                    <td class="px-5 py-3">@if($setting->is_public)<span class="text-green-600">是</span>@else<span style="color: var(--c-ink-subtle);">否</span>@endif</td>
                    <td class="px-5 py-3">
                        <div class="flex items-center justify-end gap-1">
                            <button type="button" class="btn btn-ghost btn-icon btn-sm" title="编辑" onclick="editSetting('{{ $setting->key }}', '{{ $setting->value }}', '{{ $setting->type }}')">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7 M18.5 2.5a2.1 2.1 0 0 1 3 3L12 15l-4 1 1-4z"/></svg>
                            </button>
                            <form method="POST" action="{{ route('system-settings.destroy', $setting) }}" class="inline" onsubmit="return confirm('确定要删除这个设置吗？')">@csrf @method('DELETE')<button type="submit" class="btn btn-ghost btn-icon btn-sm text-red-500" title="删除"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18 M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg></button></form>
                        </div>
                    </td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Edit setting modal --}}
<div id="editSettingModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" data-modal onclick="if(event.target===this)closeModal('editSettingModal')">
    <div class="card max-w-md w-full">
        <div class="px-5 py-4 border-b border-border flex items-center justify-between">
            <h3 class="text-sm font-semibold text-ink">编辑设置</h3>
            <button type="button" onclick="closeModal('editSettingModal')" class="btn btn-ghost btn-icon btn-sm">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18 6 6 18M6 6l12 12"/></svg>
            </button>
        </div>
        <form method="POST" action="{{ route('system-settings.update') }}" class="p-5 space-y-4">
            @csrf
            <div>
                <label class="label">设置键</label>
                <input type="text" class="input" id="edit_key" readonly>
            </div>
            <div>
                <label class="label">设置值</label>
                <input type="text" class="input" id="edit_value" name="settings[edit_key]">
                <p class="text-xs mt-1" style="color: var(--c-ink-subtle);">根据设置类型输入相应的值</p>
            </div>
            <div class="flex items-center justify-end gap-2">
                <button type="button" onclick="closeModal('editSettingModal')" class="btn btn-secondary">取消</button>
                <button type="submit" class="btn btn-primary">保存</button>
            </div>
        </form>
    </div>
</div>

{{-- Version update modal --}}
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
    if (e.key === 'Escape') {
        ['editSettingModal', 'versionUpdateModal'].forEach(closeModal);
    }
});

function toggleRegistration(enabled) {
    fetch('{{ route("system-settings.toggle-registration") }}', {
        method: 'POST',
        headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
        body: JSON.stringify({ enabled: enabled })
    }).then(function(r) { return r.json(); })
      .then(function(data) { if (data.success) location.reload(); else alert('操作失败：' + (data.message || '未知错误')); })
      .catch(function(err) { alert('操作失败：' + (err.message || '网络错误')); });
}

function editSetting(key, value, type) {
    document.getElementById('edit_key').value = key;
    var valueInput = document.getElementById('edit_value');
    valueInput.name = 'settings[' + key + ']';
    valueInput.value = value;
    openModal('editSettingModal');
}

function initializeDefaults() {
    if (!confirm('确定要初始化默认设置吗？这可能会覆盖现有设置。')) return;
    fetch('{{ route("system-settings.initialize-defaults") }}', {
        method: 'POST',
        headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
    }).then(function(r) { return r.json(); })
      .then(function(data) { if (data.success) location.reload(); else alert('初始化失败：' + (data.message || '未知错误')); })
      .catch(function(err) { alert('初始化失败：' + (err.message || '网络错误')); });
}

function loadVersionHistory() {
    var historyDiv = document.getElementById('versionHistory');
    var historyList = document.getElementById('versionHistoryList');
    if (!historyDiv.classList.contains('hidden')) {
        historyDiv.classList.add('hidden');
        return;
    }
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
