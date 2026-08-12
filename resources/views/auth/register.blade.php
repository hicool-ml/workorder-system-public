@extends('layouts.app')

@section('title', '注册')

@section('head')
<style>
    /* Override guest max-width for register (more fields) */
    .min-h-screen > div { max-width: 36rem !important; }
</style>
@endsection

@section('content')
<div class="text-center mb-6">
    <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-brand-600 text-white mb-3">
        <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2 M9 7a4 4 0 1 0 0-8 4 4 0 0 0 0 8z M19 8v6 M22 11h-6"/></svg>
    </div>
    <h1 class="text-xl font-semibold text-ink">用户注册</h1>
</div>

<div class="card p-6">
    <form method="POST" action="{{ route('register') }}">
        @csrf
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
            <div>
                <label class="label" for="name">姓名 <span class="text-red-500">*</span></label>
                <input type="text" class="input" id="name" name="name" value="{{ old('name') }}" required autocomplete="name" autofocus placeholder="真实姓名">
            </div>
            <div>
                <label class="label" for="username">用户名 <span class="text-red-500">*</span></label>
                <input type="text" class="input" id="username" name="username" value="{{ old('username') }}" required autocomplete="username" placeholder="登录用户名">
            </div>
        </div>
        <div class="mb-4">
            <label class="label" for="email">邮箱 <span class="text-red-500">*</span></label>
            <input type="email" class="input" id="email" name="email" value="{{ old('email') }}" required autocomplete="email" placeholder="邮箱地址">
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
            <div>
                <label class="label" for="password">密码 <span class="text-red-500">*</span></label>
                <input type="password" class="input" id="password" name="password" required autocomplete="new-password" oninput="checkPasswordStrength(this.value)" placeholder="至少6位">
                <div class="mt-1.5 h-1 rounded-full overflow-hidden" style="background-color: var(--c-border);">
                    <div id="passwordStrength" class="h-full transition-all duration-300" style="width:0;"></div>
                </div>
            </div>
            <div>
                <label class="label" for="password_confirmation">确认密码 <span class="text-red-500">*</span></label>
                <input type="password" class="input" id="password_confirmation" name="password_confirmation" required autocomplete="new-password" placeholder="再次输入">
            </div>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
            <div>
                <label class="label" for="phone">电话号码</label>
                <input type="tel" class="input" id="phone" name="phone" value="{{ old('phone') }}" autocomplete="tel" placeholder="选填">
            </div>
            <div>
                <label class="label" for="employee_id">员工号</label>
                <input type="text" class="input" id="employee_id" name="employee_id" value="{{ old('employee_id') }}" autocomplete="employee_id" placeholder="选填">
            </div>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
            <div>
                <label class="label" for="account_type">账户类型 <span class="text-red-500">*</span></label>
                <select class="input" id="account_type" name="account_type" required>
                    <option value="">请选择</option>
                    <option value="staff" {{ old('account_type') == 'staff' ? 'selected' : '' }}>员工</option>
                    <option value="student" {{ old('account_type') == 'student' ? 'selected' : '' }}>其他</option>
                    <option value="external" {{ old('account_type') == 'external' ? 'selected' : '' }}>外部用户</option>
                </select>
            </div>
            <div>
                <label class="label" for="department_id">所属部门</label>
                <select class="input" id="department_id" name="department_id">
                    <option value="">选填</option>
                    @if(isset($departments))
                        @foreach($departments as $department)
                        <option value="{{ $department->id }}" {{ old('department_id') == $department->id ? 'selected' : '' }}>{{ $department->name }}</option>
                        @endforeach
                    @endif
                </select>
            </div>
        </div>
        <div class="mb-5">
            <label class="label" for="location">位置信息</label>
            <input type="text" class="input" id="location" name="location" value="{{ old('location') }}" autocomplete="location" placeholder="选填">
        </div>
        <button type="submit" class="btn btn-primary w-full mb-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2 M9 7a4 4 0 1 0 0-8 4 4 0 0 0 0 8z M19 8v6 M22 11h-6"/></svg>
            <span>注册账户</span>
        </button>
        <a href="{{ route('login') }}" class="btn btn-secondary w-full">返回登录</a>
    </form>
</div>
@endsection

@section('scripts')
<script>
function checkPasswordStrength(password) {
    var bar = document.getElementById('passwordStrength');
    var strength = 0;
    if (password.length >= 6) strength++;
    if (password.length >= 10) strength++;
    if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
    if (/[0-9]/.test(password)) strength++;
    if (/[^a-zA-Z0-9]/.test(password)) strength++;
    if (password.length === 0) { bar.style.width = '0'; bar.style.backgroundColor = 'transparent'; return; }
    if (strength <= 2) { bar.style.width = '33%'; bar.style.backgroundColor = '#ef4444'; }
    else if (strength <= 3) { bar.style.width = '66%'; bar.style.backgroundColor = '#f59e0b'; }
    else { bar.style.width = '100%'; bar.style.backgroundColor = '#22c55e'; }
}
document.getElementById('password_confirmation')?.addEventListener('input', function() {
    var pw = document.getElementById('password').value;
    this.setCustomValidity(this.value && pw !== this.value ? '两次输入的密码不一致' : '');
});
</script>
@endsection
