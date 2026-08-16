@extends('layouts.app')

@section('title', '微信登录')

@section('content')
<div class="flex justify-between items-center flex-wrap gap-2 pt-3 pb-2 mb-4 border-b border-border">
    <h1 class="text-xl font-semibold text-ink">微信公众号登录（OAuth2）</h1>
    <div class="flex items-center gap-2">
        <a href="{{ route('system-settings.cas') }}" class="btn btn-secondary btn-sm">CAS 认证</a>
        <a href="{{ route('system-settings.oidc') }}" class="btn btn-secondary btn-sm">OIDC 认证</a>
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

<form method="POST" action="{{ route('system-settings.update-wechat-oauth') }}">
    @csrf

    {{-- 认证开关 --}}
    <div class="card p-5 mb-4">
        <div class="flex items-center justify-between mb-1">
            <h3 class="font-semibold text-ink">认证开关</h3>
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" name="wechat_oauth_enabled" value="1" class="w-4 h-4" {{ $wechatOauthSettings['enabled'] ? 'checked' : '' }}>
                <span class="text-sm" style="color: var(--c-ink-muted);">{{ $wechatOauthSettings['enabled'] ? '已启用' : '未启用' }}</span>
            </label>
        </div>
        <p class="text-xs" style="color: var(--c-ink-muted);">启用后登录页将出现「微信登录」入口。用户首次使用需绑定系统账号，之后即可通过微信免密登录。前置条件：公众号为【已认证】服务号/订阅号，且在公众号后台配置了「网页授权域名」。</p>
    </div>

    {{-- 客户端凭据 --}}
    <div class="card p-5 mb-4">
        <h3 class="font-semibold text-ink mb-4">公众号凭据</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="label">AppID <span class="text-red-500">*</span></label>
                <input type="text" class="input" name="wechat_oauth_appid" value="{{ $wechatOauthSettings['appid'] }}" placeholder="wx1234567890abcdef">
                <p class="text-xs mt-1" style="color: var(--c-ink-subtle);">公众号「设置与开发」→「基本配置」中获取</p>
            </div>
            <div>
                <label class="label">AppSecret</label>
                <input type="text" class="input" name="wechat_oauth_secret" value="{{ $wechatOauthSettings['secret'] }}" placeholder="公众号 AppSecret">
            </div>
            <div class="sm:col-span-2">
                <label class="label">网页授权 Scope</label>
                <select class="input" name="wechat_oauth_scope">
                    <option value="snsapi_base" {{ $wechatOauthSettings['scope'] === 'snsapi_base' ? 'selected' : '' }}>snsapi_base —— 静默授权，仅获取 openid（推荐）</option>
                    <option value="snsapi_userinfo" {{ $wechatOauthSettings['scope'] === 'snsapi_userinfo' ? 'selected' : '' }}>snsapi_userinfo —— 需用户点击授权，额外获取昵称/头像</option>
                </select>
                <p class="text-xs mt-1" style="color: var(--c-ink-subtle);">登录只依赖 openid，snsapi_base 已足够；snsapi_userinfo 用于绑定页展示微信昵称头像，但需要用户手动授权</p>
            </div>
            <div class="sm:col-span-2">
                <label class="label">授权回调地址</label>
                <input type="text" class="input bg-neutral-100 dark:bg-neutral-800" readonly value="{{ \App\Helpers\SystemHelper::absoluteUrl('/wechat/callback') }}">
                <p class="text-xs mt-1" style="color: var(--c-ink-subtle);">需在公众号后台「设置与开发」→「公众号设置」→「功能设置」→「网页授权域名」中配置为本站域名</p>
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
<div class="card p-5 mt-4" style="background: rgba(16,185,129,0.05);">
    <h3 class="text-sm font-semibold text-ink mb-2">接入说明</h3>
    <ol class="text-xs space-y-1.5" style="color: var(--c-ink-muted);">
        <li>1. 准备一个【已认证】的微信公众号（服务号/订阅号均可），在公众号后台获取 AppID / AppSecret</li>
        <li>2. 在公众号后台「设置与开发 → 公众号设置 → 功能设置 → 网页授权域名」中，添加本系统域名（仅域名，不含路径，无需加 http）</li>
        <li>3. 将回调地址设置为：<code class="text-blue-600">{{ \App\Helpers\SystemHelper::absoluteUrl('/wechat/callback') }}</code></li>
        <li>4. 在本页填写 AppID / AppSecret，选择授权 Scope，勾选启用并保存</li>
        <li>5. 登录页出现「微信登录」按钮，用户点击后跳转微信授权</li>
        <li>6. 首次登录的用户会进入绑定页，输入系统用户名/密码完成一次性绑定，之后即可免密登录</li>
    </ol>
    <div class="mt-3 pt-3 border-t border-border">
        <p class="text-xs font-semibold text-ink mb-1">说明</p>
        <ul class="text-xs space-y-1" style="color: var(--c-ink-muted);">
            <li>- openid 是匿名标识，不含姓名/工号，无法自动建号，因此必须绑定一次系统账号</li>
            <li>- 已绑定的微信再次登录直接免密进入系统，不再弹出绑定页</li>
            <li>- 换绑：请在用户管理页清除该用户的「微信OpenID」后重新绑定</li>
            <li>- 微信登录与本地/CAS/OIDC 账号共存，同一个系统账号只允许绑定一个微信</li>
        </ul>
    </div>
</div>
@endsection
