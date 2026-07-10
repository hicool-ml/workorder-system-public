@extends('layouts.app')

@section('title', '登录')

@section('content')
<div class="text-center mb-6">
    <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-brand-600 text-white mb-3">
        <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
    </div>
    <h1 class="text-xl font-semibold text-ink">{{ \App\Helpers\SystemHelper::getSystemName() }}</h1>
    <p class="text-sm text-ink-muted mt-0.5">系统登录</p>
</div>

<div class="card p-6">
    <form method="POST" action="{{ route('login') }}">
        @csrf
        <div class="mb-4">
            <label class="label" for="login">用户名</label>
            <input type="text" class="input" id="login" name="login" value="{{ old('login') }}" required autocomplete="username" autofocus placeholder="请输入用户名">
        </div>
        <div class="mb-4">
            <label class="label" for="password">密码</label>
            <input type="password" class="input" id="password" name="password" required autocomplete="current-password" placeholder="请输入密码">
        </div>
        <label class="flex items-center gap-2 mb-5 cursor-pointer">
            <input type="checkbox" id="remember" name="remember" class="rounded border-border-strong w-4 h-4">
            <span class="text-sm" style="color: var(--c-ink-muted);">记住我</span>
        </label>
        <button type="submit" class="btn btn-primary w-full">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4 M10 17l5-5-5-5 M15 12H3"/></svg>
            <span>登录</span>
        </button>
    </form>

    @if(\App\Models\SystemSetting::isRegistrationEnabled())
    <div class="text-center mt-4 pt-4 border-t border-border">
        <p class="text-sm text-ink-muted">还没有账户？</p>
        <a href="{{ route('register') }}" class="btn btn-secondary btn-sm mt-2">立即注册</a>
    </div>
    @endif
</div>

<p class="text-center text-xs mt-4" style="color: var(--c-ink-subtle);">{{ \App\Helpers\SystemHelper::getSystemCopyright() }}</p>
@endsection
