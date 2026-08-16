@extends('layouts.app')

@section('title', '企业微信通知')

@section('content')
<div class="flex justify-between items-center flex-wrap gap-2 pt-3 pb-2 mb-4 border-b border-border">
    <h1 class="text-xl font-semibold text-ink">企业微信通知</h1>
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
    <p class="text-xs mb-4" style="color: var(--c-ink-muted);">选择通知发送方式。「群机器人」简单易用但消息仅在企业微信App内可见；「自建应用」可将消息直接推送到个人微信的微信插件。</p>
    <div class="flex gap-2 flex-wrap">
        <button type="button" id="mode-webhook-btn" class="mode-btn btn {{ $wecomSettings['send_mode'] === 'webhook' ? 'btn-primary' : 'btn-secondary' }}" onclick="switchMode('webhook')">群机器人 Webhook</button>
        <button type="button" id="mode-app-btn" class="mode-btn btn {{ $wecomSettings['send_mode'] === 'app' ? 'btn-primary' : 'btn-secondary' }}" onclick="switchMode('app')">自建应用（推荐）</button>
    </div>
</div>

<form method="POST" action="{{ route('system-settings.update-wecom') }}">
    @csrf
    <input type="hidden" name="wecom_send_mode" id="wecom_send_mode_input" value="{{ $wecomSettings['send_mode'] }}">

    {{-- ========== 群机器人模式 ========== --}}
    <div id="webhook-section" class="{{ $wecomSettings['send_mode'] === 'app' ? 'hidden' : '' }}">
        <div class="card p-5 mb-4">
            <div class="flex items-center justify-between mb-1">
                <h3 class="font-semibold text-ink">企业微信群机器人</h3>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="wecom_webhook_enabled" value="1" class="w-4 h-4" {{ $wecomSettings['webhook_enabled'] ? 'checked' : '' }}>
                    <span class="text-sm" style="color: var(--c-ink-muted);">{{ $wecomSettings['webhook_enabled'] ? '已启用' : '未启用' }}</span>
                </label>
            </div>
            <p class="text-xs" style="color: var(--c-ink-muted);">启用后工单通知会按「通知规则」中配置的事件，通过 Webhook 推送到企业微信群。</p>
        </div>

        <div class="card p-5 mb-4">
            <h3 class="font-semibold text-ink mb-4">Webhook 配置</h3>
            <div>
                <label class="label">Webhook 地址</label>
                <input type="url" class="input" name="wecom_webhook_url" id="wecom_webhook_url" value="{{ $wecomSettings['webhook_url'] }}" placeholder="https://qyapi.weixin.qq.com/cgi-bin/webhook/send?key=...">
                <p class="text-xs mt-1" style="color: var(--c-ink-subtle);">在企业微信群聊中添加「群机器人」即可获取此地址。地址仅保存在服务器，不会对用户公开。</p>
            </div>
        </div>
    </div>

    {{-- ========== 自建应用模式 ========== --}}
    <div id="app-section" class="{{ $wecomSettings['send_mode'] === 'webhook' ? 'hidden' : '' }}">
        <div class="card p-5 mb-4">
            <div class="flex items-center justify-between mb-1">
                <h3 class="font-semibold text-ink">企业微信自建应用</h3>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="wecom_app_enabled" value="1" class="w-4 h-4" {{ $wecomSettings['app_enabled'] ? 'checked' : '' }}>
                    <span class="text-sm" style="color: var(--c-ink-muted);">{{ $wecomSettings['app_enabled'] ? '已启用' : '未启用' }}</span>
                </label>
            </div>
            <p class="text-xs" style="color: var(--c-ink-muted);">通过自建应用发送的消息可直接显示在个人微信的「微信插件」中，成员无需打开企业微信App即可查看完整内容。</p>
        </div>

        <div class="card p-5 mb-4">
            <h3 class="font-semibold text-ink mb-4">应用凭证配置</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label class="label">企业ID（CorpID）</label>
                    <input type="text" class="input" name="wecom_app_corpid" id="wecom_app_corpid" value="{{ $wecomSettings['app_corpid'] }}" placeholder="ww开头的CorpID">
                    <p class="text-xs mt-1" style="color: var(--c-ink-subtle);">在企业微信管理后台「我的企业」中查看。</p>
                </div>
                <div>
                    <label class="label">应用 Secret</label>
                    <input type="password" class="input" name="wecom_app_secret" id="wecom_app_secret" value="" placeholder="{{ $wecomSettings['app_secret'] ? '已设置，留空则不修改' : '自建应用的Secret' }}" autocomplete="new-password">
                    <p class="text-xs mt-1" style="color: var(--c-ink-subtle);">在应用详情页查看。</p>
                </div>
                <div>
                    <label class="label">应用 AgentID</label>
                    <input type="text" class="input" name="wecom_app_agentid" id="wecom_app_agentid" value="{{ $wecomSettings['app_agentid'] }}" placeholder="如 1000002">
                    <p class="text-xs mt-1" style="color: var(--c-ink-subtle);">应用详情页顶部的数字ID。</p>
                </div>
            </div>
        </div>

        <div class="card p-5 mb-4" style="background: rgba(59,130,246,0.05);">
            <h3 class="text-sm font-semibold text-ink mb-2">接入步骤</h3>
            <ol class="text-xs space-y-1.5" style="color: var(--c-ink-muted);">
                <li>1. 登录企业微信管理后台，进入「应用管理」→「自建」创建新应用</li>
                <li>2. 设置可见范围为目标部门或全员</li>
                <li>3. 复制 CorpID、Secret、AgentID 填入上方表单</li>
                <li>4. 确保成员已通过个人微信关注企业「微信插件」</li>
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

{{-- ========== SSL 证书管理 ========== --}}
<div class="card p-5 mt-4">
    <div class="flex items-center justify-between mb-1">
        <h3 class="font-semibold text-ink">SSL 证书管理</h3>
        <label class="flex items-center gap-2 cursor-pointer">
            <input type="checkbox" id="ssl_verify_toggle" class="w-4 h-4" {{ $wecomSettings['ssl_verify_enabled'] ? 'checked' : '' }} onchange="toggleSslVerify(this.checked)">
            <span class="text-sm" style="color: var(--c-ink-muted);">验证 HTTPS 证书</span>
        </label>
    </div>
    <p class="text-xs" style="color: var(--c-ink-muted);">上传 CA 证书后可正常验证 HTTPS 连接（推荐）。也可关闭验证用于测试，但存在安全风险。</p>

    {{-- 当前证书状态 --}}
    <div class="mt-3 flex items-center gap-2 flex-wrap">
        @if($wecomSettings['ssl_cacert_exists'])
            <span class="badge bg-green-100 text-green-700">CA 证书已配置</span>
            <code class="text-xs px-2 py-1 rounded bg-surface-muted" style="color: var(--c-ink-muted);">{{ $wecomSettings['ssl_cacert_path'] }}</code>
            <button type="button" class="btn btn-ghost btn-sm text-red-500" onclick="deleteCacert()">删除证书</button>
        @else
            <span class="badge bg-amber-100 text-amber-700">未配置自定义 CA 证书</span>
        @endif
    </div>

    {{-- 上传区域 --}}
    <div class="mt-3">
        <label class="label">上传 CA 证书（cacert.pem）</label>
        <div class="flex gap-2 items-center flex-wrap">
            <input type="file" id="cacert_file_input" accept=".pem,.crt,.cer,.txt" class="text-sm">
            <button type="button" class="btn btn-secondary btn-sm" onclick="uploadCacert()">
                <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4-4m0 0L8 12m4-4v12"/></svg>
                上传
            </button>
        </div>
        <div id="cacert-upload-result" class="mt-2"></div>
    </div>

    {{-- 下载指导 --}}
    <div class="mt-3 p-3 rounded-lg" style="background: rgba(59,130,246,0.05);">
        <p class="text-xs font-medium text-ink mb-1">如何获取 CA 证书？</p>
        <p class="text-xs" style="color: var(--c-ink-muted);">
            从 <a href="https://curl.se/ca/cacert.pem" target="_blank" class="text-blue-600 underline">https://curl.se/ca/cacert.pem</a> 下载最新的 CA 证书包（cacert.pem），然后点击上方「上传」按钮导入。上传后系统会自动启用 HTTPS 验证，无需修改 php.ini 或重启服务。
        </p>
    </div>

    {{-- 关闭验证的风险提醒 --}}
    <div id="ssl-warning" class="mt-2 {{ $wecomSettings['ssl_verify_enabled'] ? 'hidden' : '' }} p-3 rounded-lg" style="background: rgba(239,68,68,0.06); border: 1px solid rgba(239,68,68,0.2);">
        <p class="text-xs text-red-600">
            <strong>安全风险提醒</strong>：关闭 SSL 验证意味着系统不会校验 HTTPS 连接的真实性，可能遭受中间人攻击。此选项仅建议在本地测试环境中临时使用，生产环境务必保持开启或上传有效的 CA 证书。
        </p>
    </div>
</div>

@if(auth()->user() && auth()->user()->isAdmin())
<div class="card p-5 mt-4">
    <h3 class="font-semibold text-ink mb-1">发送测试</h3>
    <p class="text-sm mb-4" style="color: var(--c-ink-muted);">根据当前推送模式发送一条测试消息，验证配置是否正确。</p>
    <button type="button" class="btn btn-secondary" onclick="testWecom()">
        <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
        发送测试
    </button>
    <div id="wecom-test-result" class="mt-3"></div>
</div>
@endif

<div class="card p-5 mt-4" style="background: rgba(59,130,246,0.05);">
    <h3 class="text-sm font-semibold text-ink mb-1">提示</h3>
    <p class="text-xs" style="color: var(--c-ink-muted);">通知规则在<a href="{{ route('system-settings.notification-rules') }}" class="text-blue-600 underline">通知规则</a>页面按事件单独开启「企业微信」通道。自建应用模式下消息以纯文本发送以确保兼容个人微信。</p>
</div>
@endsection

@section('scripts')
<script>
function switchMode(mode) {
    document.getElementById('wecom_send_mode_input').value = mode;
    document.getElementById('webhook-section').classList.toggle('hidden', mode !== 'webhook');
    document.getElementById('app-section').classList.toggle('hidden', mode !== 'app');

    var wh = document.getElementById('mode-webhook-btn');
    var app = document.getElementById('mode-app-btn');
    wh.className = 'mode-btn btn ' + (mode === 'webhook' ? 'btn-primary' : 'btn-secondary');
    app.className = 'mode-btn btn ' + (mode === 'app' ? 'btn-primary' : 'btn-secondary');
}

function testWecom() {
    var mode = document.getElementById('wecom_send_mode_input').value;
    var payload = { wecom_send_mode: mode };
    if (mode === 'webhook') {
        payload.wecom_webhook_url = document.getElementById('wecom_webhook_url').value.trim();
    }
    var resultDiv = document.getElementById('wecom-test-result');
    resultDiv.innerHTML = '<p class="text-sm" style="color: var(--c-ink-muted);">发送中...</p>';

    fetch('{!! route("api.wecom.test") !!}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': '{!! csrf_token() !!}'
        },
        body: JSON.stringify(payload)
    }).then(function(r) { return r.json(); })
      .then(function(data) {
          if (data.success) {
              resultDiv.innerHTML = '<p class="text-sm text-green-600">测试消息发送成功</p>';
          } else {
              if (data.test_sent) {
                  resultDiv.innerHTML = '<div class="p-3 rounded-lg" style="background: rgba(245,158,11,0.08); border: 1px solid rgba(245,158,11,0.3);"><p class="text-sm text-amber-600">' + (data.message || '测试已发送') + '</p></div>';
              } else {
                  resultDiv.innerHTML = '<p class="text-sm text-red-600">' + (data.message || '发送失败') + '</p>';
              }
          }
      })
      .catch(function(e) {
          resultDiv.innerHTML = '<p class="text-sm text-red-600">请求失败: ' + e.message + '</p>';
      });
}

function toggleSslVerify(enabled) {
    fetch('{!! route("api.ssl-verify") !!}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': '{!! csrf_token() !!}'
        },
        body: JSON.stringify({ enabled: enabled })
    }).then(function(r) { return r.json(); })
      .then(function(data) {
          document.getElementById('ssl-warning').classList.toggle('hidden', enabled);
          showToast(data.message || '操作成功');
      })
      .catch(function(e) { showToast('操作失败: ' + e.message); });
}

function uploadCacert() {
    var input = document.getElementById('cacert_file_input');
    if (!input.files[0]) { alert('请先选择证书文件'); return; }
    var resultDiv = document.getElementById('cacert-upload-result');
    resultDiv.innerHTML = '<p class="text-sm" style="color: var(--c-ink-muted);">上传中...</p>';

    var formData = new FormData();
    formData.append('cacert_file', input.files[0]);
    formData.append('_token', '{!! csrf_token() !!}');

    fetch('{!! route("api.upload-cacert") !!}', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData
    }).then(function(r) { return r.json(); })
      .then(function(data) {
          if (data.success) {
              resultDiv.innerHTML = '<p class="text-sm text-green-600">' + data.message + '</p>';
              setTimeout(function() { location.reload(); }, 1200);
          } else {
              resultDiv.innerHTML = '<p class="text-sm text-red-600">' + (data.message || '上传失败') + '</p>';
          }
      })
      .catch(function(e) {
          resultDiv.innerHTML = '<p class="text-sm text-red-600">请求失败: ' + e.message + '</p>';
      });
}

function deleteCacert() {
    if (!confirm('确定要删除已上传的 CA 证书吗？')) return;
    fetch('{!! route("api.cacert.delete") !!}', {
        method: 'DELETE',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': '{!! csrf_token() !!}'
        }
    }).then(function(r) { return r.json(); })
      .then(function(data) {
          showToast(data.message || '操作成功');
          setTimeout(function() { location.reload(); }, 1200);
      })
      .catch(function(e) { showToast('操作失败: ' + e.message); });
}

function showToast(msg) {
    var t = document.createElement('div');
    t.className = 'fixed top-4 left-1/2 -translate-x-1/2 z-[100] px-5 py-2.5 rounded-lg bg-green-600 text-white text-sm font-medium shadow-lg';
    t.textContent = msg;
    document.body.appendChild(t);
    setTimeout(function() { t.style.opacity = '0'; t.style.transition = 'opacity 0.3s'; }, 2000);
    setTimeout(function() { t.remove(); }, 2400);
}

function testWecom_old() {
    var mode = document.getElementById('wecom_send_mode_input').value;
    var payload = { wecom_send_mode: mode };
    if (mode === 'webhook') {
        payload.wecom_webhook_url = document.getElementById('wecom_webhook_url').value.trim();
    }
    var resultDiv = document.getElementById('wecom-test-result');
    resultDiv.innerHTML = '<p class="text-sm" style="color: var(--c-ink-muted);">发送中...</p>';

    fetch('{!! route("api.wecom.test") !!}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': '{!! csrf_token() !!}'
        },
        body: JSON.stringify(payload)
    }).then(function(r) { return r.json(); })
      .then(function(data) {
          if (data.success) {
              resultDiv.innerHTML = '<p class="text-sm text-green-600">测试消息发送成功</p>';
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
