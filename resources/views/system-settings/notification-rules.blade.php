@extends('layouts.app')

@section('title', '通知规则配置')

@section('content')
<div class="flex justify-between items-center flex-wrap gap-2 pt-3 pb-2 mb-4 border-b border-border">
    <h1 class="text-xl font-semibold text-ink">通知规则</h1>
    <a href="{{ route('system-settings.index') }}" class="btn btn-secondary btn-sm">
        <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        返回系统设置
    </a>
</div>

<div class="card p-5 mb-5">
    <h3 class="font-semibold text-ink mb-1">通知规则配置</h3>
    <p class="text-sm mb-4" style="color: var(--c-ink-muted);">为每个工单事件单独设置通知通道。站内消息免费，短信按运营商收费。</p>

    <div class="overflow-x-auto">
        <table class="w-full text-sm" id="rules-table">
            <thead>
                <tr class="border-b-2 border-border">
                    <th class="text-left py-3 px-2 font-semibold text-ink">工单事件</th>
                   <th class="text-center py-3 px-4 font-semibold text-ink">站内通知</th>
                    <th class="text-center py-3 px-4 font-semibold text-ink">短信通知</th>
                    <th class="text-center py-3 px-4 font-semibold text-ink">企业微信</th>
                    <th class="text-center py-3 px-4 font-semibold text-ink">钉钉</th>
                    <th class="text-center py-3 px-4 font-semibold text-ink">飞书</th>
                </tr>
            </thead>
            <tbody id="rules-body">
                <tr><td colspan="6" class="text-center py-8" style="color: var(--c-ink-subtle);">加载中...</td></tr>
            </tbody>
        </table>
    </div>

    <div class="mt-5 flex justify-end">
        <button type="button" class="btn btn-primary" id="save-rules-btn" onclick="saveRules()">
            <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            保存规则
        </button>
    </div>
</div>

@if(auth()->user() && auth()->user()->isAdmin())
<div class="card p-5">
    <h3 class="font-semibold text-ink mb-1">短信测试</h3>
    <p class="text-sm mb-4" style="color: var(--c-ink-muted);">发送一条测试短信验证短信配置是否正确。</p>

    <div class="flex gap-2 items-end flex-wrap">
        <div class="flex-1 min-w-[200px]">
            <label class="label">手机号</label>
            <input type="tel" class="input" id="sms-test-phone" placeholder="请输入手机号" autocomplete="off">
        </div>
        <button type="button" class="btn btn-secondary" onclick="testSms()">
            <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            发送测试
        </button>
    </div>
    <div id="sms-test-result" class="mt-3"></div>
</div>
@endif
@endsection

@section('head')
<style>
.toggle-switch { position: relative; display: inline-block; width: 44px; height: 24px; }
.toggle-switch input { opacity: 0; width: 0; height: 0; }
.toggle-slider { position: absolute; cursor: pointer; inset: 0; background-color: #cbd5e1; border-radius: 24px; transition: 0.2s; }
.toggle-slider:before { content: ''; position: absolute; height: 18px; width: 18px; left: 3px; bottom: 3px; background-color: white; border-radius: 50%; transition: 0.2s; }
.toggle-switch input:checked + .toggle-slider { background-color: #2563eb; }
.toggle-switch input:checked + .toggle-slider:before { transform: translateX(20px); }
.sms-switch input:checked + .toggle-slider { background-color: #059669; }
.wecom-switch input:checked + .toggle-slider { background-color: #059669; }
.dingtalk-switch input:checked + .toggle-slider { background-color: #1677ff; }
.feishu-switch input:checked + .toggle-slider { background-color: #3370ff; }
</style>
@endsection

@section('scripts')
<script>
let currentRules = {};
let eventLabels = {};

async function loadRules() {
    try {
        const resp = await fetch('{!! route("api.notification-rules") !!}', {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await resp.json();
        currentRules = data.rules || {};
        eventLabels = data.events || {};
        renderRules();
    } catch (e) {
        document.getElementById('rules-body').innerHTML = '<tr><td colspan="6" class="text-center py-8 text-red-500">加载失败</td></tr>';
    }
}

function renderRules() {
    const tbody = document.getElementById('rules-body');
    tbody.innerHTML = '';

    Object.entries(eventLabels).forEach(function(entry) {
        const key = entry[0], label = entry[1];
        const inApp = (currentRules[key] || {}).in_app === true;
        const sms = (currentRules[key] || {}).sms === true;
        const wecom = (currentRules[key] || {}).wecom === true;
        const dingtalk = (currentRules[key] || {}).dingtalk === true;
        const feishu = (currentRules[key] || {}).feishu === true;

        const tr = document.createElement('tr');
        tr.className = 'border-b border-border';
        tr.innerHTML =
            '<td class="py-3 px-2 font-medium text-ink">' + label + '</td>' +
            '<td class="text-center py-3 px-4">' +
                '<label class="toggle-switch">' +
                    '<input type="checkbox" data-event="' + key + '" data-channel="in_app" ' + (inApp ? 'checked' : '') + ' onchange="updateRule(this)">' +
                    '<span class="toggle-slider"></span>' +
                '</label>' +
            '</td>' +
            '<td class="text-center py-3 px-4">' +
                '<label class="toggle-switch sms-switch">' +
                    '<input type="checkbox" data-event="' + key + '" data-channel="sms" ' + (sms ? 'checked' : '') + ' onchange="updateRule(this)">' +
                    '<span class="toggle-slider"></span>' +
                '</label>' +
            '</td>' +
            '<td class="text-center py-3 px-4">' +
                '<label class="toggle-switch wecom-switch">' +
                    '<input type="checkbox" data-event="' + key + '" data-channel="wecom" ' + (wecom ? 'checked' : '') + ' onchange="updateRule(this)">' +
                    '<span class="toggle-slider"></span>' +
                '</label>' +
            '</td>' +
            '<td class="text-center py-3 px-4">' +
                '<label class="toggle-switch dingtalk-switch">' +
                    '<input type="checkbox" data-event="' + key + '" data-channel="dingtalk" ' + (dingtalk ? 'checked' : '') + ' onchange="updateRule(this)">' +
                    '<span class="toggle-slider"></span>' +
                '</label>' +
            '</td>' +
            '<td class="text-center py-3 px-4">' +
                '<label class="toggle-switch feishu-switch">' +
                    '<input type="checkbox" data-event="' + key + '" data-channel="feishu" ' + (feishu ? 'checked' : '') + ' onchange="updateRule(this)">' +
                    '<span class="toggle-slider"></span>' +
                '</label>' +
            '</td>';
        tbody.appendChild(tr);
    });
}

function updateRule(el) {
    var event = el.dataset.event;
    var channel = el.dataset.channel;
    if (!currentRules[event]) currentRules[event] = {};
    currentRules[event][channel] = el.checked;
}

async function saveRules() {
    var btn = document.getElementById('save-rules-btn');
    btn.disabled = true;
    btn.textContent = '保存中...';

    try {
        var resp = await fetch('{!! route("api.notification-rules.update") !!}', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': '{!! csrf_token() !!}'
            },
            body: JSON.stringify({ rules: currentRules })
        });
        var data = await resp.json();
        if (data.success) {
            showToast('通知规则保存成功');
        } else {
            alert(data.message || '保存失败');
        }
    } catch (e) {
        alert('保存失败: ' + e.message);
    } finally {
        btn.disabled = false;
        btn.innerHTML = '保存规则';
    }
}

async function testSms() {
    var phone = document.getElementById('sms-test-phone').value.trim();
    if (!phone) { alert('请输入手机号'); return; }

    var resultDiv = document.getElementById('sms-test-result');
    resultDiv.innerHTML = '<p class="text-sm" style="color: var(--c-ink-muted);">发送中...</p>';

    try {
        var resp = await fetch('{!! route("api.sms.test") !!}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': '{!! csrf_token() !!}'
            },
            body: JSON.stringify({ phone: phone })
        });
        var data = await resp.json();
        if (data.success) {
            resultDiv.innerHTML = '<p class="text-sm text-green-600">测试短信发送成功</p>';
        } else {
            resultDiv.innerHTML = '<p class="text-sm text-red-600">' + (data.message || '发送失败') + '</p>';
        }
    } catch (e) {
        resultDiv.innerHTML = '<p class="text-sm text-red-600">请求失败: ' + e.message + '</p>';
    }
}

function showToast(msg) {
    var t = document.createElement('div');
    t.className = 'fixed top-4 left-1/2 -translate-x-1/2 z-[100] px-5 py-2.5 rounded-lg bg-green-600 text-white text-sm font-medium shadow-lg';
    t.textContent = msg;
    document.body.appendChild(t);
    setTimeout(function() { t.style.opacity = '0'; t.style.transition = 'opacity 0.3s'; }, 2000);
    setTimeout(function() { t.remove(); }, 2400);
}

loadRules();
</script>
@endsection
