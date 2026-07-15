@extends('layouts.app')

@section('title', '个人资料')

@section('content')

<div class="mb-6">
    <h1 class="text-xl font-semibold text-ink">个人资料</h1>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    {{-- Main column --}}
    <div class="lg:col-span-2 space-y-4">

        {{-- Profile edit --}}
        <div class="card p-5">
            <h2 class="text-sm font-semibold text-ink mb-4">基本信息</h2>
            @if(auth()->user()->isCasUser())
            <div class="rounded-lg border border-amber-300 bg-amber-50 dark:bg-amber-950/40 dark:border-amber-800 p-4 mb-4">
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-amber-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z"/></svg>
                    <div class="text-sm">
                        <p class="font-medium text-amber-800 dark:text-amber-300">统一身份认证账号</p>
                        <p class="text-amber-700 dark:text-amber-400 mt-0.5">您的个人信息由学校统一身份认证系统管理，如需修改请联系学校信息中心。</p>
                    </div>
                </div>
            </div>
            @endif
            <form method="POST" action="{{ route('profile.update') }}">
                @csrf
                @method('PUT')
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="label" for="name">姓名 <span class="text-red-500">*</span></label>
                        <input type="text" class="input" id="name" name="name" value="{{ auth()->user()->name }}" required>
                    </div>
                    <div>
                        <label class="label" for="email">邮箱 <span class="text-red-500">*</span></label>
                        <input type="email" class="input" id="email" name="email" value="{{ auth()->user()->email }}" required>
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="label" for="phone">电话</label>
                        <input type="tel" class="input" id="phone" name="phone" value="{{ auth()->user()->phone }}">
                    </div>
                    <div>
                        <label class="label" for="employee_id">员工编号</label>
                        <input type="text" class="input" id="employee_id" name="employee_id" value="{{ auth()->user()->employee_id }}">
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="label" for="location">办公地点</label>
                        <input type="text" class="input" id="location" name="location" value="{{ auth()->user()->location }}">
                    </div>
                    <div>
                        <label class="label" for="department_id">所属部门</label>
                        <select class="input" id="department_id" name="department_id">
                            <option value="">请选择部门</option>
                            @foreach(App\Models\Department::where('status', 'active')->get() as $department)
                            <option value="{{ $department->id }}" {{ auth()->user()->department_id == $department->id ? 'selected' : '' }}>{{ $department->full_path ?? $department->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="mb-4">
                    <label class="label" for="remarks">备注</label>
                    <textarea class="input" id="remarks" name="remarks" rows="3">{{ auth()->user()->remarks }}</textarea>
                </div>
                <div class="flex justify-end">
                    @if(!auth()->user()->isCasUser())
                    <button type="submit" class="btn btn-primary">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        <span>保存</span>
                    </button>
                    @endif
                </div>
            </form>
        </div>

        {{-- Password change --}}
        <div class="card p-5">
            <h2 class="text-sm font-semibold text-ink mb-4">修改密码</h2>
            @if(auth()->user()->isCasUser())
            <div class="rounded-lg border border-amber-300 bg-amber-50 dark:bg-amber-950/40 dark:border-amber-800 p-4">
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-amber-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/></svg>
                    <div class="text-sm">
                        <p class="font-medium text-amber-800 dark:text-amber-300">密码由统一身份认证管理</p>
                        <p class="text-amber-700 dark:text-amber-400 mt-0.5">CAS 用户的密码通过学校统一身份认证系统修改，无法在此处更改。</p>
                    </div>
                </div>
            </div>
            @else
            <form method="POST" action="{{ route('profile.password') }}">
                @csrf
                @method('PUT')
                <div class="mb-4">
                    <label class="label" for="current_password">当前密码 <span class="text-red-500">*</span></label>
                    <input type="password" class="input" id="current_password" name="current_password" required>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="label" for="password">新密码 <span class="text-red-500">*</span></label>
                        <input type="password" class="input" id="password" name="password" required minlength="6">
                    </div>
                    <div>
                        <label class="label" for="password_confirmation">确认密码 <span class="text-red-500">*</span></label>
                        <input type="password" class="input" id="password_confirmation" name="password_confirmation" required>
                    </div>
                </div>
                <div class="flex justify-end">
                    <button type="submit" class="btn btn-secondary">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 2l-2 2m-7.6 7.6a5 5 0 1 1-7.07 7.07A5 5 0 0 1 11.4 11.6zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"/></svg>
                        <span>修改密码</span>
                    </button>
                </div>
            </form>
            @endif
        </div>
    </div>

    {{-- Sidebar --}}
    <div class="lg:col-span-1 space-y-4">
        <div class="card p-5">
            <h3 class="text-sm font-semibold text-ink mb-3">我的统计</h3>
            <div class="grid grid-cols-2 gap-3">
                <div class="text-center p-3 rounded-lg" style="background-color: var(--c-muted);">
                    <p class="text-xl font-semibold text-ink">{{ auth()->user()->createdWorkorders()->count() }}</p>
                    <p class="text-xs" style="color: var(--c-ink-subtle);">创建工单</p>
                </div>
                <div class="text-center p-3 rounded-lg" style="background-color: var(--c-muted);">
                    <p class="text-xl font-semibold text-ink">{{ auth()->user()->assignedWorkorders()->count() }}</p>
                    <p class="text-xs" style="color: var(--c-ink-subtle);">处理工单</p>
                </div>
                @if(auth()->user()->canHandleWorkorders())
                <div class="text-center p-3 rounded-lg" style="background-color: var(--c-muted);">
                    <p class="text-xl font-semibold text-ink">{{ auth()->user()->pending_workorders_count ?? 0 }}</p>
                    <p class="text-xs" style="color: var(--c-ink-subtle);">待处理</p>
                </div>
                <div class="text-center p-3 rounded-lg" style="background-color: var(--c-muted);">
                    <p class="text-xl font-semibold text-ink">{{ auth()->user()->today_workorders_count ?? 0 }}</p>
                    <p class="text-xs" style="color: var(--c-ink-subtle);">今日处理</p>
                </div>
                @endif
            </div>
        </div>

        <div class="card p-5">
            <h3 class="text-sm font-semibold text-ink mb-3">角色权限</h3>
            <div class="space-y-2 text-sm">
                <div class="flex items-center gap-2">
                    <span style="color: var(--c-ink-subtle);">角色</span>
                    <span class="badge bg-blue-100 text-blue-700">{{ auth()->user()->role_text }}</span>
                </div>
                <div class="flex items-center gap-2">
                    <span style="color: var(--c-ink-subtle);">状态</span>
                    <span class="badge {{ auth()->user()->status == 'active' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">{{ auth()->user()->status_text }}</span>
                </div>
            </div>
            <div class="mt-3 pt-3 border-t border-border space-y-1.5 text-sm">
                @if(auth()->user()->canHandleWorkorders())<p class="flex items-center gap-2"><svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg><span style="color: var(--c-ink-muted);">处理工单</span></p>@endif
                @if(auth()->user()->canAssignWorkorders())<p class="flex items-center gap-2"><svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg><span style="color: var(--c-ink-muted);">分配工单</span></p>@endif
                @if(auth()->user()->canManageWorkorderTypes())<p class="flex items-center gap-2"><svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg><span style="color: var(--c-ink-muted);">管理工单类型</span></p>@endif
                @if(auth()->user()->canManageDepartments())<p class="flex items-center gap-2"><svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg><span style="color: var(--c-ink-muted);">管理部门</span></p>@endif
                @if(auth()->user()->canViewReports())<p class="flex items-center gap-2"><svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg><span style="color: var(--c-ink-muted);">查看报表</span></p>@endif
            </div>
        </div>
    </div>
</div>
@endsection
