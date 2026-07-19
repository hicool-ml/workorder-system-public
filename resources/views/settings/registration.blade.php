@extends('layouts.app')
@section('title', '注册设置')
@section('content')
<div class="flex items-center justify-between mb-6 gap-3 flex-wrap">
    <h1 class="text-xl font-semibold text-ink">注册设置</h1>
    <button type="button" onclick="initializeDefaults()" class="btn btn-secondary">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 1 1-3-6.7L21 8 M21 3v5h-5"/></svg>
        <span>初始化默认设置</span>
    </button>
</div>

<div class="space-y-6">
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
</script>
@endsection
