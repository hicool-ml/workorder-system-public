@extends('layouts.app')

@section('title', 'OIDC 统一身份认证')

@section('content')
<div class="flex justify-between items-center flex-wrap gap-2 pt-3 pb-2 mb-4 border-b border-border">
    <h1 class="text-xl font-semibold text-ink">OIDC / OAuth2 统一身份认证</h1>
    <div class="flex items-center gap-2">
        <a href="{{ route('system-settings.cas') }}" class="btn btn-secondary btn-sm">CAS 认证</a>
        <a href="{{ route('system-settings.index') }}" class="btn btn-secondary btn-sm">
            <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            返回系统设置
        </a>
    </div>
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

<form method="POST" action="{{ route('system-settings.update-oidc') }}">
    @csrf

    {{-- 认证开关 --}}
    <div class="card p-5 mb-4">
        <div class="flex items-center justify-between mb-1">
            <h3 class="font-semibold text-ink">认证开关</h3>
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" name="oidc_enabled" value="1" class="w-4 h-4" {{ $oidcSettings['enabled'] ? 'checked' : '' }}>
                <span class="text-sm" style="color: var(--c-ink-muted);">{{ $oidcSettings['enabled'] ? '已启用' : '未启用' }}</span>
            </label>
        </div>
        <p class="text-xs" style="color: var(--c-ink-muted);">启用后登录页将出现「统一身份认证」入口，支持泛微令信通、派拉、宁盾、阿里云 IDaaS、TOPIAM 等所有兼容 OIDC/OAuth2 标准的 IAM 平台通过配置接入，无需逐个对接。</p>
    </div>

    {{-- Issuer / Discovery --}}
    <div class="card p-5 mb-4">
        <h3 class="font-semibold text-ink mb-1">OIDC Discovery（推荐）</h3>
        <p class="text-xs mb-4" style="color: var(--c-ink-muted);">填写 Issuer URL（如 <code class="text-blue-600">https://iam.example.com</code>），系统将自动从 <code>.well-known/openid-configuration</code> 发现授权地址、Token 地址、UserInfo 地址等全部端点，无需手动填写。如果您的 IAM 平台不支持 Discovery，请留空并在下方手动填写各端点地址。</p>
        <div>
            <label class="label">Issuer URL</label>
            <input type="text" class="input" name="oidc_issuer" value="{{ $oidcSettings['issuer'] }}" placeholder="https://iam.example.com">
        </div>
    </div>

    {{-- 客户端凭据 --}}
    <div class="card p-5 mb-4">
        <h3 class="font-semibold text-ink mb-4">客户端凭据</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="label">Client ID <span class="text-red-500">*</span></label>
                <input type="text" class="input" name="oidc_client_id" value="{{ $oidcSettings['client_id'] }}" placeholder="在 IAM 平台注册后获取">
            </div>
            <div>
                <label class="label">Client Secret</label>
                <input type="text" class="input" name="oidc_client_secret" value="{{ $oidcSettings['client_secret'] }}" placeholder="机密客户端填写，公开客户端留空（使用 PKCE）">
            </div>
            <div class="sm:col-span-2">
                <label class="label">Scope</label>
                <input type="text" class="input" name="oidc_scope" value="{{ $oidcSettings['scope'] }}" placeholder="openid profile email">
                <p class="text-xs mt-1" style="color: var(--c-ink-subtle);">通常使用默认值即可，部分平台可能需要追加 phone、department 等 scope</p>
            </div>
        </div>
    </div>

    {{-- 手动端点配置 --}}
    <div class="card p-5 mb-4">
        <h3 class="font-semibold text-ink mb-1">手动端点配置（可选）</h3>
        <p class="text-xs mb-4" style="color: var(--c-ink-muted);">如果上方已填写 Issuer 且 IAM 平台支持 Discovery，以下字段可留空，系统会自动获取。仅当 IAM 平台不支持 Discovery 时才需要手动填写。</p>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="label">Authorization Endpoint</label>
                <input type="text" class="input" name="oidc_authorize_endpoint" value="{{ $oidcSettings['authorize_endpoint'] }}" placeholder="https://iam.example.com/oauth2/authorize">
            </div>
            <div>
                <label class="label">Token Endpoint</label>
                <input type="text" class="input" name="oidc_token_endpoint" value="{{ $oidcSettings['token_endpoint'] }}" placeholder="https://iam.example.com/oauth2/token">
            </div>
            <div>
                <label class="label">UserInfo Endpoint</label>
                <input type="text" class="input" name="oidc_userinfo_endpoint" value="{{ $oidcSettings['userinfo_endpoint'] }}" placeholder="https://iam.example.com/oauth2/userinfo">
            </div>
            <div>
                <label class="label">End Session Endpoint</label>
                <input type="text" class="input" name="oidc_end_session_endpoint" value="{{ $oidcSettings['end_session_endpoint'] }}" placeholder="https://iam.example.com/oauth2/logout">
                <p class="text-xs mt-1" style="color: var(--c-ink-subtle);">用于单点登出，留空则仅登出本系统</p>
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

{{-- 接入说明 --}}
<div class="card p-5 mt-4" style="background: rgba(59,130,246,0.05);">
    <h3 class="text-sm font-semibold text-ink mb-2">接入说明</h3>
    <ol class="text-xs space-y-1.5" style="color: var(--c-ink-muted);">
        <li>1. 在 IAM 平台（如泛微令信通、派拉、阿里云 IDaaS、TOPIAM 等）注册本系统为 OAuth2 Client</li>
        <li>2. 将回调地址设置为：<code class="text-blue-600">{{ route('oidc.callback') }}</code></li>
        <li>3. 获取 Issuer URL / Client ID / Client Secret（在 IAM 平台的客户端管理页面获取）</li>
        <li>4. 如果 IAM 平台支持 Discovery，仅需填写 Issuer + Client ID + Client Secret；否则需手动填写各端点地址</li>
        <li>5. 勾选启用并保存</li>
        <li>6. 登录页将出现「统一身份认证」按钮，用户点击后跳转至 IAM 平台完成认证</li>
    </ol>
    <div class="mt-3 pt-3 border-t border-border">
        <p class="text-xs font-semibold text-ink mb-1">协议特性</p>
        <ul class="text-xs space-y-1" style="color: var(--c-ink-muted);">
            <li>- Authorization Code Flow + PKCE (S256)，符合 OAuth2.1 安全要求</li>
            <li>- 支持 OIDC Discovery 自动发现端点</li>
            <li>- 支持 Confidential Client（带 Secret）和 Public Client（仅 PKCE）</li>
            <li>- 自动映射 sub / preferred_username / name / email / phone_number 等标准声明</li>
            <li>- OIDC 用户自动创建为报修人角色，与本地账号共存</li>
        </ul>
    </div>
</div>
@endsection