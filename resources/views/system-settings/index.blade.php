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
    </div>

    {{-- Version management --}}
    <div class="card overflow-hidden">
        <div class="px-5 py-4 border-b border-border flex items-center justify-between gap-3">
            <h3 class="text-sm font-semibold text-ink">版本管理</h3>
            <div class="flex items-center gap-2">
                <button type="button" onclick="openVersionModal()" class="btn btn-secondary btn-sm">
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
                        <p class="text-sm font-medium text-ink truncate">{{ $groupedSettings['system']->firstWhere('key', 'system_name')?->typed_value ?? '工单管理系统' }}</p>
                    </div>
                </div>
            </div>

            <div id="versionHistory" class="hidden mt-4 pt-4 border-t border-border">
                <h4 class="text-sm font-medium text-ink mb-3">版本历史</h4>
                <div id="versionHistoryList" class="space-y-2"></div>
            </div>
        </div>
    </div>

    {{-- 数据备份 --}}
    <div class="card overflow-hidden">
        <div class="px-5 py-4 border-b border-border flex items-center justify-between gap-3">
            <h3 class="text-sm font-semibold text-ink">数据备份与恢复</h3>
            <div class="flex items-center gap-2 flex-wrap">
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
            </div>
        </div>
        <div class="p-5">
            <div class="flex items-center gap-3 mb-3 p-3 rounded-lg bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-300 text-xs">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4 M12 17h.01 M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                <span>恢复会覆盖当前数据库。每次恢复前系统会自动备份当前状态，便于回滚。每日凌晨 2 点自动备份一次。</span>
            </div>
            <div id="backupList">
                <div class="text-center py-6 text-sm" style="color: var(--c-ink-muted);">点击"刷新"加载备份列表</div>
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

            {{-- 钉钉 --}}
            <a href="{{ route('system-settings.dingtalk') }}" class="block p-4 rounded-lg border border-border hover:border-brand-400 hover:shadow-sm transition-all">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-10 h-10 rounded-lg flex items-center justify-center bg-blue-50 dark:bg-blue-900/20">
                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 2a10 10 0 100 20 10 10 0 000-20zM8 14s1.5 2 4 2 4-2 4-2M9 9h.01M15 9h.01"/></svg>
                    </div>
                    <span class="text-sm font-semibold text-ink">钉钉</span>
                </div>
                <p class="text-xs" style="color: var(--c-ink-muted);">群机器人 / 工作通知
                    @if(filter_var(\App\Models\SystemSetting::get('dingtalk_webhook_enabled', '0'), FILTER_VALIDATE_BOOLEAN) || filter_var(\App\Models\SystemSetting::get('dingtalk_app_enabled', '0'), FILTER_VALIDATE_BOOLEAN))
                    <span class="text-green-600 font-medium ml-1">已启用</span>
                    @else
                    <span class="text-orange-500 ml-1">未启用</span>
                    @endif
                </p>
            </a>

            {{-- 飞书 --}}
            <a href="{{ route('system-settings.feishu') }}" class="block p-4 rounded-lg border border-border hover:border-brand-400 hover:shadow-sm transition-all">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-10 h-10 rounded-lg flex items-center justify-center bg-indigo-50 dark:bg-indigo-900/20">
                        <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 11.5a8.38 8.38 0 01-.9 3.8 8.5 8.5 0 01-7.6 4.7 8.38 8.38 0 01-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 01-.9-3.8 8.5 8.5 0 014.7-7.6 8.38 8.38 0 013.8-.9h.5a8.48 8.48 0 018 8v.5z"/></svg>
                    </div>
                    <span class="text-sm font-semibold text-ink">飞书</span>
                </div>
                <p class="text-xs" style="color: var(--c-ink-muted);">群机器人 / 自建应用
                    @if(filter_var(\App\Models\SystemSetting::get('feishu_webhook_enabled', '0'), FILTER_VALIDATE_BOOLEAN) || filter_var(\App\Models\SystemSetting::get('feishu_app_enabled', '0'), FILTER_VALIDATE_BOOLEAN))
                    <span class="text-green-600 font-medium ml-1">已启用</span>
                    @else
                    <span class="text-orange-500 ml-1">未启用</span>
                    @endif
                </p>
            </a>

        </div>
    </div>

        {{-- All settings table --}}
    @foreach($categorizedSettings as $label => $items)
    <div class="card overflow-hidden">
        <div class="px-5 py-4 border-b border-border">
            <h3 class="text-sm font-semibold text-ink">{{ $label }}</h3>
        </div>
        <div class="md:hidden divide-y divide-border">
            @foreach($items as $setting)
            <div class="p-4">
                <div class="flex items-center justify-between gap-2 mb-1">
                    <code class="text-sm text-ink">{{ $setting->key }}</code>
                    @if($setting->type === 'boolean')<span class="badge {{ $setting->typed_value ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">{{ $setting->typed_value ? '是' : '否' }}</span>@endif
                </div>
                <p class="text-xs mb-2" style="color: var(--c-ink-subtle);">{{ $setting->description ?? '-' }}</p>
                <div class="flex items-center gap-2">
                    <button type="button" class="btn btn-ghost btn-sm" data-edit-setting data-key="{{ $setting->key }}" data-type="{{ $setting->type }}" data-secret="{{ $setting->isSecretKey() ? '1' : '0' }}">编辑</button>
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
                @foreach($items as $setting)
                <tr class="border-b border-border">
                    <td class="px-5 py-3"><code class="text-ink">{{ $setting->key }}</code></td>
                    <td class="px-5 py-3 text-ink">@if($setting->type === 'boolean')<span class="badge {{ $setting->typed_value ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">{{ $setting->typed_value ? '是' : '否' }}</span>@elseif($setting->isSecretKey())<span class="badge {{ $setting->value ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">{{ $setting->value ? '已设置' : '未设置' }}</span>@else{{ Str::limit($setting->value, 50) }}@endif</td>
                    <td class="px-5 py-3"><span class="badge bg-blue-100 text-blue-700">{{ $setting->type }}</span></td>
                    <td class="px-5 py-3 text-ink">{{ $setting->description ?? '-' }}</td>
                    <td class="px-5 py-3">@if($setting->is_public)<span class="text-green-600">是</span>@else<span style="color: var(--c-ink-subtle);">否</span>@endif</td>
                    <td class="px-5 py-3">
                        <div class="flex items-center justify-end gap-1">
                            <button type="button" class="btn btn-ghost btn-icon btn-sm" title="编辑" data-edit-setting data-key="{{ $setting->key }}" data-type="{{ $setting->type }}" data-secret="{{ $setting->isSecretKey() ? '1' : '0' }}">
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
    @endforeach
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

{{-- Restore backup confirm modal --}}
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
       ['editSettingModal', 'versionUpdateModal', 'restoreBackupModal'].forEach(closeModal);
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

// 密钥类设置：不回显旧值，留空提交 = 保留原值（服务端 update() 跳过空值密钥）
document.addEventListener('click', function(e) {
    var btn = e.target.closest('[data-edit-setting]');
    if (!btn) return;
    var isSecret = btn.dataset.secret === '1';
    editSetting(btn.dataset.key, '', btn.dataset.type);
    if (isSecret) {
        document.getElementById('edit_value').placeholder = '已隐藏，留空则不修改';
    } else {
        document.getElementById('edit_value').placeholder = '';
    }
});

function initializeDefaults() {
    if (!confirm('确定要初始化默认设置吗？这可能会覆盖现有设置。')) return;
    fetch('{{ route("system-settings.initialize-defaults") }}', {
        method: 'POST',
        headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
    }).then(function(r) { return r.json(); })
      .then(function(data) { if (data.success) location.reload(); else alert('初始化失败：' + (data.message || '未知错误')); })
      .catch(function(err) { alert('初始化失败：' + (err.message || '网络错误')); });
}

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

// ========== 数据备份与恢复 ==========
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

// 监听输入框，匹配"确认恢复"才解锁按钮
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

// 进入页面时自动加载一次备份列表
document.addEventListener('DOMContentLoaded', loadBackups);

</script>
@endsection
