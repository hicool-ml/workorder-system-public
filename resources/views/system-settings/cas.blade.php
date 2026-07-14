@extends('layouts.app')

@section('title', '统一身份认证')

@section('content')
<div class="flex justify-between items-center flex-wrap gap-2 pt-3 pb-2 mb-4 border-b border-border">
    <h1 class="text-xl font-semibold text-ink">统一身份认证（CAS）</h1>
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

<form method="POST" action="{{ route('system-settings.update-cas') }}">
    @csrf

    <div class="card p-5 mb-4">
        <div class="flex items-center justify-between mb-1">
            <h3 class="font-semibold text-ink">认证开关</h3>
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" name="cas_enabled" value="1" class="w-4 h-4" {{ $casSettings['enabled'] ? 'checked' : '' }}>
                <span class="text-sm" style="color: var(--c-ink-muted);">{{ $casSettings['enabled'] ? '已启用' : '未启用' }}</span>
            </label>
        </div>
        <p class="text-xs" style="color: var(--c-ink-muted);">启用后登录页将出现「统一身份认证」入口，与本地账号共存。CAS 用户自动创建为报修人角色。</p>
    </div>

    <div class="card p-5 mb-4">
        <h3 class="font-semibold text-ink mb-4">服务地址</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="sm:col-span-2">
                <label class="label">CAS Base URL</label>
                <input type="text" class="input" name="cas_base_url" value="{{ $casSettings['base_url'] }}" placeholder="https://sourceid.ruishan.cc/linkid">
                <p class="text-xs mt-1" style="color: var(--c-ink-subtle);">LinkID 平台的 CAS 服务根地址</p>
            </div>
            <div class="sm:col-span-2">
                <label class="label">Service ID（可选）</label>
                <input type="text" class="input" name="cas_service_id" value="{{ $casSettings['service_id'] }}" placeholder="在 LinkID 注册后获得">
            </div>
        </div>
    </div>

    <div class="card p-5 mb-4">
        <h3 class="font-semibold text-ink mb-1">用户属性映射</h3>
        <p class="text-xs mb-4" style="color: var(--c-ink-muted);">LinkID 返回的用户属性字段名，根据实际情况调整。默认值适用于大多数 CAS 3.0 实现。</p>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="label">工号/学号 <span class="text-red-500">*</span></label>
                <input type="text" class="input" name="cas_attr_username" value="{{ $casSettings['attr_username'] }}" required>
            </div>
            <div>
                <label class="label">姓名 <span class="text-red-500">*</span></label>
                <input type="text" class="input" name="cas_attr_name" value="{{ $casSettings['attr_name'] }}" required>
            </div>
            <div>
                <label class="label">手机号</label>
                <input type="text" class="input" name="cas_attr_phone" value="{{ $casSettings['attr_phone'] }}">
            </div>
            <div>
                <label class="label">邮箱</label>
                <input type="text" class="input" name="cas_attr_email" value="{{ $casSettings['attr_email'] }}">
            </div>
            <div>
                <label class="label">部门</label>
                <input type="text" class="input" name="cas_attr_department" value="{{ $casSettings['attr_department'] }}">
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
    <h3 class="text-sm font-semibold text-ink mb-2">接入说明</h3>
    <ol class="text-xs space-y-1.5" style="color: var(--c-ink-muted);">
        <li>1. 在 LinkID 平台注册本系统为 Service Provider</li>
        <li>2. 将回调地址设置为：<code class="text-blue-600">{{ route('cas.callback') }}</code></li>
        <li>3. 填写上方的 Base URL 和 Service ID</li>
        <li>4. 勾选启用并保存</li>
        <li>5. 登录页将出现「统一身份认证」按钮，用户点击后跳转至学校统一认证页面</li>
    </ol>
</div>
@endsection