@extends('layouts.app')

@section('title', '修改密码')

@section('content')
<div class="min-h-screen flex items-center justify-center px-4 py-10">
    <div class="w-full max-w-md">
        <div class="text-center mb-6">
            <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-amber-500 text-white mb-3">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/>
                </svg>
            </div>
            <h1 class="text-xl font-semibold text-ink">修改默认密码</h1>
            <p class="text-sm text-ink-muted mt-1">为了账户安全，首次登录请先修改密码</p>
        </div>

        <div class="card p-6">
            <form method="POST" action="{{ route('password.update') }}">
                @csrf
                @method('PUT')

                <div class="mb-4">
                    <label class="label" for="current_password">当前密码</label>
                    <input type="password" class="input" id="current_password" name="current_password" required autocomplete="current-password" placeholder="请输入当前密码">
                    @error('current_password') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="mb-4">
                    <label class="label" for="password">新密码</label>
                    <input type="password" class="input" id="password" name="password" required autocomplete="new-password" minlength="6" placeholder="至少 6 位">
                    @error('password') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="mb-5">
                    <label class="label" for="password_confirmation">确认新密码</label>
                    <input type="password" class="input" id="password_confirmation" name="password_confirmation" required autocomplete="new-password" placeholder="再次输入新密码">
                </div>

                <button type="submit" class="btn-primary w-full justify-center">
                    确认修改
                </button>
            </form>
        </div>

        <div class="text-center mt-4">
            <a href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form-pc').submit();" class="text-sm text-ink-muted hover:text-ink">退出登录</a>
            <form id="logout-form-pc" action="{{ route('logout') }}" method="POST" class="hidden">@csrf</form>
        </div>
    </div>
</div>
@endsection