@extends('layouts.app')

@section('title', '绑定微信')

@section('content')
<div class="text-center mb-6">
    <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-green-600 text-white mb-3">
        <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.86 9.86 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
    </div>
    <h1 class="text-xl font-semibold text-ink">绑定微信账号</h1>
    <p class="text-sm text-ink-muted mt-0.5">首次使用微信登录，请绑定您的系统账号</p>
</div>

<div class="card p-6">
    @if($headimgurl)
    <div class="flex items-center gap-3 mb-5 p-3 rounded-lg" style="background: rgba(16,185,129,0.08);">
        <img src="{{ $headimgurl }}" alt="微信头像" class="w-12 h-12 rounded-full">
        <div>
            <p class="text-sm font-medium text-ink">微信昵称：{{ $nickname ?? '未知' }}</p>
            <p class="text-xs" style="color: var(--c-ink-muted);">绑定后，此微信即可免密登录本系统</p>
        </div>
    </div>
    @else
    <div class="mb-5 p-3 rounded-lg" style="background: rgba(16,185,129,0.08);">
        <p class="text-xs" style="color: var(--c-ink-muted);">已通过微信静默授权获取身份标识，请输入系统账号完成绑定。绑定后，此微信即可免密登录本系统。</p>
    </div>
    @endif

    <form method="POST" action="{{ route('wechat.bind') }}">
        @csrf
        <div class="mb-4">
            <label class="label" for="login">用户名 / 邮箱</label>
            <input type="text" class="input" id="login" name="login" value="{{ old('login') }}" required autocomplete="username" autofocus placeholder="请输入系统用户名或邮箱">
            @error('login')
            <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
            @enderror
        </div>
        <div class="mb-4">
            <label class="label" for="password">密码</label>
            <input type="password" class="input" id="password" name="password" required autocomplete="current-password" placeholder="请输入密码">
        </div>
        <button type="submit" class="btn btn-primary w-full">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span>绑定并登录</span>
        </button>
    </form>

    <div class="text-center mt-4 pt-4 border-t border-border">
        <p class="text-sm text-ink-muted">不想绑定？</p>
        <a href="{{ route('login') }}" class="btn btn-secondary btn-sm mt-2">返回登录</a>
    </div>
</div>

<p class="text-center text-xs mt-4" style="color: var(--c-ink-subtle);">绑定成功后，下次使用微信打开本系统将自动登录</p>
@endsection
