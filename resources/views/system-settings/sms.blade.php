@extends('layouts.app')

@section('title', '短信配置')

@section('content')
<div class="flex justify-between items-center flex-wrap gap-2 pt-3 pb-2 mb-4 border-b border-border">
    <h1 class="text-xl font-semibold text-ink">短信配置</h1>
    <a href="{{ route('system-settings.index') }}" class="btn btn-secondary btn-sm">
        <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        返回系统设置
    </a>
</div>

@if(session('success'))
<div class="card p-3 mb-4 border-l-4" style="border-left-color: #10b981; background: rgba(16,185,129,0.08);">
    <p class="text-sm text-green-700">{{ session('success') }}</p>
</div>
@endif

<form method="POST" action="{{ route('system-settings.update-sms') }}">
    @csrf

    {{-- 启用开关 --}}
    <div class="card p-5 mb-4">
        <div class="flex items-center justify-between mb-1">
            <h3 class="font-semibold text-ink">短信服务</h3>
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" name="sms_enabled" value="1" class="w-4 h-4" {{ $smsSettings['enabled'] ? 'checked' : '' }}>
                <span class="text-sm" style="color: var(--c-ink-muted);">{{ $smsSettings['enabled'] ? '已启用' : '未启用' }}</span>
            </label>
        </div>
        <p class="text-xs" style="color: var(--c-ink-muted);">启用后工单通知会按「通知规则」中配置的事件自动发送短信。短信费用按运营商标准收取。</p>
    </div>

    {{-- 服务商配置 --}}
    <div class="card p-5 mb-4">
        <h3 class="font-semibold text-ink mb-4">服务商配置</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="label">短信服务商</label>
                <select name="sms_provider" class="input" onchange="toggleProviderFields(this.value)">
                    <option value="aliyun" {{ $smsSettings['provider'] === 'aliyun' ? 'selected' : '' }}>阿里云短信</option>
                    <option value="tencent" {{ $smsSettings['provider'] === 'tencent' ? 'selected' : '' }}>腾讯云短信</option>
                    <option value="custom" {{ $smsSettings['provider'] === 'custom' ? 'selected' : '' }}>自定义接口</option>
                </select>
            </div>
            <div>
                <label class="label">短信签名</label>
                <input type="text" class="input" name="sms_sign_name" value="{{ $smsSettings['sign_name'] }}" placeholder="如：校园网工单">
            </div>
        </div>

        {{-- 阿里云/腾讯云 共用密钥 --}}
        <div id="cloud-fields" class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
            <div>
                <label class="label" id="key-label">AccessKey ID / SecretId</label>
                <input type="text" class="input" name="sms_access_key" value="{{ $smsSettings['access_key'] }}" placeholder="API密钥ID">
            </div>
            <div>
                <label class="label" id="secret-label">AccessKey Secret / SecretKey</label>
                <input type="text" class="input" name="sms_access_secret" value="{{ $smsSettings['access_secret'] }}" placeholder="API密钥">
            </div>
            <div id="sdk-app-id-field" class="hidden">
                <label class="label">SDK AppID（腾讯云）</label>
                <input type="text" class="input" name="sms_sdk_app_id" value="{{ $smsSettings['sdk_app_id'] ?? '' }}" placeholder="腾讯云短信应用ID">
            </div>
        </div>

        {{-- 自定义接口 --}}
        <div id="custom-fields" class="hidden grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
            <div class="sm:col-span-2">
                <label class="label">接口 URL</label>
                <input type="text" class="input" name="sms_api_url" value="{{ $smsSettings['api_url'] ?? '' }}" placeholder="https://...">
            </div>
            <div>
                <label class="label">请求方式</label>
                <select name="sms_method" class="input">
                    <option value="POST" {{ ($smsSettings['method'] ?? 'POST') === 'POST' ? 'selected' : '' }}>POST</option>
                    <option value="GET" {{ ($smsSettings['method'] ?? '') === 'GET' ? 'selected' : '' }}>GET</option>
                </select>
            </div>
            <div>
                <label class="label">API Key（可选）</label>
                <input type="text" class="input" name="sms_api_key" value="{{ $smsSettings['api_key'] ?? '' }}" placeholder="Bearer Token">
            </div>
        </div>
    </div>

    <div class="flex justify-end">
        <button type="submit" class="btn btn-primary">
            <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            保存配置
        </button>
    </div>
</form>

<div class="card p-5 mt-4" style="background: rgba(59,130,246,0.05);">
    <h3 class="text-sm font-semibold text-ink mb-1">提示</h3>
    <p class="text-xs" style="color: var(--c-ink-muted);">短信通知规则在<a href="{{ route('system-settings.notification-rules') }}" class="text-blue-600 underline">通知规则</a>页面按事件单独开启。此处只配置服务商连接参数。</p>
</div>
@endsection

@section('scripts')
<script>
function toggleProviderFields(val) {
    var cloud = document.getElementById('cloud-fields');
    var custom = document.getElementById('custom-fields');
    var sdkField = document.getElementById('sdk-app-id-field');
    var keyLabel = document.getElementById('key-label');
    var secretLabel = document.getElementById('secret-label');

    if (val === 'custom') {
        cloud.classList.add('hidden');
        custom.classList.remove('hidden');
    } else {
        cloud.classList.remove('hidden');
        custom.classList.add('hidden');
        if (val === 'tencent') {
            sdkField.classList.remove('hidden');
            keyLabel.textContent = 'SecretId';
            secretLabel.textContent = 'SecretKey';
        } else {
            sdkField.classList.add('hidden');
            keyLabel.textContent = 'AccessKey ID';
            secretLabel.textContent = 'AccessKey Secret';
        }
    }
}
toggleProviderFields('{{ $smsSettings['provider'] }}');
</script>
@endsection