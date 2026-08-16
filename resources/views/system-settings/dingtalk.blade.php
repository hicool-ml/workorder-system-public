@extends('layouts.app')

@section('title', '钉钉通知')

@section('content')
<div class="flex justify-between items-center flex-wrap gap-2 pt-3 pb-2 mb-4 border-b border-border">
    <h1 class="text-xl font-semibold text-ink">钉钉通知</h1>
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

@if(session('error'))
<div class="card p-3 mb-4 border-l-4" style="border-left-color: #ef4444; background: rgba(239,68,68,0.08);">
    <p class="text-sm text-red-700">{{ session('error') }}</p>
</div>
@endif

{{-- 推送模式切换 --}}
<div class="card p-5 mb-4">
    <h3 class="font-semibold text-ink mb-1">推送模式</h3>
    <p class="text-xs mb-4" style="color: var(--c-ink-muted);">「自定义机器人」在钉钉群内发消息（可 @ 群成员）；「工作通知」通过企业内部应用推送到个人钉钉。</p>
    <div class="flex gap-2 flex-wrap">
        <button type="button" id="mode-webhook-btn" class="mode-btn btn {{ $dingtalkSettings['send_mode'] === 'webhook' ? 'btn-primary' : 'btn-secondary' }}" onclick="switchMode('webhook')">自定义机器人 Webhook</button>
        <button type="button" id="mode-app-btn" class="mode-btn btn {{ $dingtalkSettings['send_mode'] === 'app' ? 'btn-primary' : 'btn-secondary' }}" onclick="switchMode('app')">工作通知（企业内部应用）</button>
    </div>
</div>

<form method="POST" action="{{ route('system-settings.update-dingtalk') }}">
    @csrf
    <input type="hidden" name="dingtalk_send_mode" id="send_mode_input" value="{{ $dingtalkSettings['send_mode'] }}">

    {{-- ========== 自定义机器人模式 ========== --}}
    <div id="webhook-section" class="{{ $dingtalkSettings['send_mode'] === 'app' ? 'hidden' : '' }}">
        <div class="card p-5 mb-4">
            <div class="flex items-center justify-between mb-1">
                <h3 class="font-semibold text-ink">钉钉自定义机器人</h3>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="dingtalk_webhook_enabled" value="1" class="w-4 h-4" {{ $dingtalkSettings['webhook_enabled'] ? 'checked' : '' }}>
                    <span class="text-sm" style="color: var(--c-ink-muted);">{{ $dingtalkSettings['webhook_enabled'] ? '已启用' : '未启用' }}</span>
                </label>
            </div>
            <p class="text-xs" style="color: var(--c-ink-muted);">启用后工单通知会按「通知规则」中配置的事件，推送到钉钉群。</p>
        </div>

        <div class="card p-5 mb-4">
            <h3 class="font-semibold text-ink mb-4">Webhook 配置</h3>
            <div class="space-y-4">
                <div>
                    <label class="label">Webhook 地址</label>
                    <input type="url" class="input" name="dingtalk_webhook_url" id="webhook_url" value="{{ $dingtalkSettings['webhook_url'] }}" placeholder="https://oapi.dingtalk.com/robot/send?access_token=...">
                    <p class="text-xs mt-1" style="color: var(--c-ink-subtle);">在钉钉群「群设置 → 智能群助手 → 添加机器人 → 自定义」获取。</p>
                </div>
                <div>
                    <label class="label">加签 secret（可选，推荐）</label>
                    <input type="text" class="input" name="dingtalk_webhook_secret" id="webhook_secret" value="{{ $dingtalkSettings['webhook_secret'] }}" placeholder="SEC开头的加签密钥">
                    <p class="text-xs mt-1" style="color: var(--c-ink-subtle);">安全设置选择「加签」时填入，防止地址被盗用。留空表示不加签。</p>
                </div>
            </div>
        </div>
    </div>

    {{-- ========== 工作通知模式 ========== --}}
    <div id="app-section" class="{{ $dingtalkSettings['send_mode'] === 'webhook' ? 'hidden' : '' }}">
        <div class="card p-5 mb-4">
            <div class="flex items-center justify-between mb-1">
                <h3 class="font-semibold text-ink">钉钉工作通知（企业内部应用）</h3>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="dingtalk_app_enabled" value="1" class="w-4 h-4" {{ $dingtalkSettings['app_enabled'] ? 'checked' : '' }}>
                    <span class="text-sm" style="color: var(--c-ink-muted);">{{ $dingtalkSettings['app_enabled'] ? '已启用' : '未启用' }}</span>
                </label>
            </div>
            <p class="text-xs" style="color: var(--c-ink-muted);">通过企业内部应用发送工作通知，消息直达个人钉钉。需要在钉钉开发者后台创建企业内部应用。</p>
        </div>

        <div class="card p-5 mb-4">
            <h3 class="font-semibold text-ink mb-4">应用凭证配置</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="label">AppKey</label>
                    <input type="text" class="input" name="dingtalk_app_key" id="app_key" value="{{ $dingtalkSettings['app_key'] }}" placeholder="应用的 AppKey">
                </div>
                <div>
                    <label class="label">AppSecret</label>
                    <input type="password" class="input" name="dingtalk_app_secret" id="app_secret" value="" placeholder="{{ $dingtalkSettings['app_secret'] ? '已设置，留空则不修改' : '应用的 AppSecret' }}" autocomplete="new-password">
                </div>
                <div class="sm:col-span-2">
                    <label class="label">AgentId</label>
                    <input type="text" class="input" name="dingtalk_app_agentid" id="app_agentid" value="{{ $dingtalkSettings['app_agentid'] }}" placeholder="如 123456789">
                    <p class="text-xs mt-1" style="color: var(--c-ink-subtle);">工作通知的应用 AgentId，在应用详情页查看。</p>
                </div>
            </div>
        </div>

        <div class="card p-5 mb-4" style="background: rgba(59,130,246,0.05);">
            <h3 class="text-sm font-semibold text-ink mb-2">接入步骤</h3>
            <ol class="text-xs space-y-1.5" style="color: var(--c-ink-muted);">
                <li>1. 登录钉钉开发者后台，创建「企业内部应用」</li>
                <li>2. 复制 AppKey、AppSecret、AgentId 填入上方表单</li>
                <li>3. 申请「通讯录管理」「企业内机器人」等权限（工作通知需开通消息通知权限）</li>
                <li>4. @ 提醒：在「用户管理」编辑用户时填写其钉钉 userid（分配/超时通知会 @ 对应工程师）</li>
            </ol>
        </div>
    </div>

    <div class="flex justify-end">
        <button type="submit" class="btn btn-primary">
            <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            保存配置
        </button>
    </div>
</form>

@if(auth()->user() && auth()->user()->isAdmin())
<div class="card p-5 mt-4">
    <h3 class="font-semibold text-ink mb-1">发送测试</h3>
    <p class="text-sm mb-4" style="color: var(--c-ink-muted);">根据当前推送模式发送一条测试消息，验证配置是否正确。</p>
    <button type="button" class="btn btn-secondary" onclick="testNotify()">
        <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
        发送测试
    </button>
    <div id="test-result" class="mt-3"></div>
</div>
@endif

<div class="card p-5 mt-4" style="background: rgba(59,130,246,0.05);">
    <h3 class="text-sm font-semibold text-ink mb-1">提示</h3>
    <p class="text-xs" style="color: var(--c-ink-muted);">通知规则在<a href="{{ route('system-settings.notification-rules') }}" class="text-blue-600 underline">通知规则</a>页面按事件单独开启「钉钉」通道。HTTPS 出站共用「企业微信通知」页的 SSL 证书设置。</p>
</div>
@endsection

@section('scripts')
<script>
function switchMode(mode) {
    document.getElementById('send_mode_input').value = mode;
    document.getElementById('webhook-section').classList.toggle('hidden', mode !== 'webhook');
    document.getElementById('app-section').classList.toggle('hidden', mode !== 'app');
    var wh = document.getElementById('mode-webhook-btn');
    var app = document.getElementById('mode-app-btn');
    wh.className = 'mode-btn btn ' + (mode === 'webhook' ? 'btn-primary' : 'btn-secondary');
    app.className = 'mode-btn btn ' + (mode === 'app' ? 'btn-primary' : 'btn-secondary');
}

function testNotify() {
    var resultDiv = document.getElementById('test-result');
    resultDiv.innerHTML = '<p class="text-sm" style="color: var(--c-ink-muted);">发送中...</p>';
    fetch('{!! route("api.dingtalk.test") !!}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': '{!! csrf_token() !!}'
        }
    }).then(function(r) { return r.json(); })
      .then(function(data) {
          if (data.success) {
              resultDiv.innerHTML = '<p class="text-sm text-green-600">测试消息发送成功</p>';
          } else if (data.test_sent) {
              resultDiv.innerHTML = '<div class="p-3 rounded-lg" style="background: rgba(245,158,11,0.08); border: 1px solid rgba(245,158,11,0.3);"><p class="text-sm text-amber-600">' + (data.message || '测试已发送') + '</p></div>';
          } else {
              resultDiv.innerHTML = '<p class="text-sm text-red-600">' + (data.message || '发送失败') + '</p>';
          }
      })
      .catch(function(e) {
          resultDiv.innerHTML = '<p class="text-sm text-red-600">请求失败: ' + e.message + '</p>';
      });
}
</script>
@endsection
