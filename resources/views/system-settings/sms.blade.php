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
                <input type="text" class="input" name="sms_sign_name" value="{{ $smsSettings['sign_name'] }}" placeholder="如：工单系统">
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

    {{-- 报修人短信 --}}
    <div class="card p-5 mb-4 mt-4">
        <h3 class="font-semibold text-ink mb-1">报修人短信</h3>
        <p class="text-sm mb-4" style="color: var(--c-ink-muted);">工单生命周期内最多向报修人发送两条短信：受理通知、满意度调查。各自由独立开关控制，互不影响。</p>

        {{-- 受理通知开关 --}}
        <div class="flex items-center justify-between py-2 border-b border-border">
            <div>
                <span class="text-sm font-medium text-ink">受理通知</span>
                <p class="text-xs mt-0.5" style="color: var(--c-ink-subtle);">工单受理时（创建即分配或工程师接单）发送一次</p>
            </div>
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" name="creator_sms_enabled" value="1" class="w-4 h-4" {{ $smsSettings['creator_sms_enabled'] ? 'checked' : '' }}>
                <span class="text-sm" style="color: var(--c-ink-muted);">{{ $smsSettings['creator_sms_enabled'] ? '开启' : '关闭' }}</span>
            </label>
        </div>

        {{-- 满意度调查开关 --}}
        <div class="flex items-center justify-between py-2 border-b border-border">
            <div>
                <span class="text-sm font-medium text-ink">满意度调查</span>
                <p class="text-xs mt-0.5" style="color: var(--c-ink-subtle);">工单完结时发送一次，报修人回复 1=满意 / 0=不满意</p>
            </div>
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" name="creator_survey_enabled" value="1" class="w-4 h-4" {{ $smsSettings['creator_survey_enabled'] ? 'checked' : '' }}>
                <span class="text-sm" style="color: var(--c-ink-muted);">{{ $smsSettings['creator_survey_enabled'] ? '开启' : '关闭' }}</span>
            </label>
        </div>

        {{-- 模板编辑 --}}
        <h4 class="text-sm font-semibold text-ink mt-5 mb-2">短信模板</h4>
        <p class="text-xs mb-3" style="color: var(--c-ink-muted);">支持占位符：<code class="px-1 py-0.5 rounded bg-slate-200/70 text-slate-700 dark:bg-slate-700/60 dark:text-slate-100">{系统名称}</code> <code class="px-1 py-0.5 rounded bg-slate-200/70 text-slate-700 dark:bg-slate-700/60 dark:text-slate-100">{工程师电话}</code> <code class="px-1 py-0.5 rounded bg-slate-200/70 text-slate-700 dark:bg-slate-700/60 dark:text-slate-100">{预约时间}</code> <code class="px-1 py-0.5 rounded bg-slate-200/70 text-slate-700 dark:bg-slate-700/60 dark:text-slate-100">{工单编号}</code>，发送时自动替换。</p>

        {{-- 云厂商模板代码：阿里云/腾讯云必须配置，否则报修人短信无法发送 --}}
        <div id="tpl-code-fields" class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-3">
            <div>
                <label class="label">受理通知模板代码 <span class="text-red-500">*</span></label>
                <input type="text" class="input" name="sms_creator_acceptance_code" value="{{ $smsSettings['acceptance_code'] ?? '' }}" placeholder="阿里云 SMS_xxx… / 腾讯云数字ID">
                <p class="text-xs mt-1" style="color: var(--c-ink-subtle);">服务商控制台报备通过的模板 CODE/ID</p>
            </div>
            <div>
                <label class="label">满意度调查模板代码 <span class="text-red-500">*</span></label>
                <input type="text" class="input" name="sms_creator_survey_code" value="{{ $smsSettings['survey_code'] ?? '' }}" placeholder="阿里云 SMS_xxx… / 腾讯云数字ID">
                <p class="text-xs mt-1" style="color: var(--c-ink-subtle);">不开启满意度调查可留空</p>
            </div>
        </div>

        <div class="space-y-3">
            <div>
                <label class="label">受理通知（有预约时间）</label>
                <textarea name="tpl_acceptance_with_appt" class="input" rows="2">{{ $smsSettings['tpl_acceptance_with_appt'] }}</textarea>
            </div>
            <div>
                <label class="label">受理通知（无预约时间）</label>
                <textarea name="tpl_acceptance_no_appt" class="input" rows="2">{{ $smsSettings['tpl_acceptance_no_appt'] }}</textarea>
            </div>
            <div>
                <label class="label">满意度调查</label>
                <textarea name="tpl_survey" class="input" rows="2">{{ $smsSettings['tpl_survey'] }}</textarea>
            </div>
        </div>
    </div>

    {{-- 模板修改说明 --}}
    <div class="card p-4 mb-4" style="background: rgba(245,158,11,0.06); border-left: 4px solid #f59e0b;">
        <h4 class="text-sm font-semibold text-ink mb-1.5">模板修改说明</h4>
        <ul class="text-xs space-y-1" style="color: var(--c-ink-muted);">
            <li><b>自定义接口</b>：在此修改后保存即可立即生效，系统直接使用上面的文案发送。</li>
            <li><b>阿里云 / 腾讯云</b>：以上文案仅作参考。云服务商要求模板必须在 <b>服务商控制台预先报备审核</b>，实际发送用的是控制台里报备通过的模板。修改文案的步骤：</li>
            <li class="pl-4">1. 登录短信服务商控制台（阿里云：短信服务 → 国内消息 → 模板管理；腾讯云：短信 → 国内短信 → 正文模板）。</li>
            <li class="pl-4">2. 新建或修改模板，内容使用对应占位符（阿里云 <code class="px-1 py-0.5 rounded bg-slate-200/70 text-slate-700 dark:bg-slate-700/60 dark:text-slate-100">${name}</code>、腾讯云 <code class="px-1 py-0.5 rounded bg-slate-200/70 text-slate-700 dark:bg-slate-700/60 dark:text-slate-100">{1}</code>），提交审核（通常 2 小时内）。</li>
            <li class="pl-4">3. 审核通过后记下 <b>模板 CODE / ID</b>，填入上方「模板代码」输入框（阿里云模板变量需恰好声明 <code class="px-1 py-0.5 rounded bg-slate-200/70 text-slate-700 dark:bg-slate-700/60 dark:text-slate-100">${workorder_number}</code> 和 <code class="px-1 py-0.5 rounded bg-slate-200/70 text-slate-700 dark:bg-slate-700/60 dark:text-slate-100">${content}</code>；腾讯云按顺序 {1}=工单编号、{2}=正文）。</li>
            <li class="pl-4">4. 模板审核期间，短信可能发送失败；建议先用「自定义接口」或「测试短信」验证流程。</li>
            <li class="pt-2"><b>满意度回复回调</b>：开启满意度调查后，报修人回复短信需回写系统。请在服务商后台配置上行回调地址为 <code class="px-1 py-0.5 rounded bg-slate-200/70 text-slate-700 dark:bg-slate-700/60 dark:text-slate-100">{{ rtrim(config('app.url'), '/') }}/sms/reply</code>。系统自动适配各服务商字段（阿里云 <code class="px-1 py-0.5 rounded bg-slate-200/70 text-slate-700 dark:bg-slate-700/60 dark:text-slate-100">phone_number/content</code>、腾讯云 <code class="px-1 py-0.5 rounded bg-slate-200/70 text-slate-700 dark:bg-slate-700/60 dark:text-slate-100">PhoneNumber/ReplyContent</code>），自定义接口默认取 <code class="px-1 py-0.5 rounded bg-slate-200/70 text-slate-700 dark:bg-slate-700/60 dark:text-slate-100">phone/content</code>。</li>
        </ul>
    </div>

    {{-- 回调鉴权配置 --}}
    <div class="card p-5 mb-4">
        <h3 class="font-semibold text-ink mb-1">回复回调鉴权</h3>
        <p class="text-xs mb-4" style="color: var(--c-ink-muted);">满意度回复回调（/sms/reply）的鉴权方式。生产环境至少配置其一，否则回调返回 401。</p>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="label">回调密钥（推荐）</label>
                <input type="password" class="input" name="sms_reply_secret" value="{{ $smsSettings['reply_secret'] ?? '' }}" placeholder="留空 = 不修改" autocomplete="new-password">
                <p class="text-xs mt-1" style="color: var(--c-ink-subtle);">支持 token 直传 / md5 签名 / HMAC-SHA256+时间戳（推荐，服务商侧按 hmac=HMAC(phone|content|timestamp, secret) 计算）</p>
            </div>
            <div>
                <label class="label">回调 IP 白名单（可选，逗号分隔 CIDR）</label>
                <input type="text" class="input" name="sms_reply_ip_whitelist" value="{{ $smsSettings['reply_ip_whitelist'] ?? '' }}" placeholder="如 47.102.x.x, 8.209.x.x/24">
                <p class="text-xs mt-1" style="color: var(--c-ink-subtle);">仅配合 TRUSTED_PROXIES 生效；建议优先使用密钥</p>
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
