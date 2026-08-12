@extends('layouts.app')

@section('title', '新增用户')

@section('content')
<div class="flex items-center justify-between mb-6 gap-3 flex-wrap">
    <div>
        <h1 class="text-xl font-semibold text-ink">新增用户</h1>
        <p class="text-sm text-ink-muted mt-0.5">创建用户账号</p>
    </div>
    <a href="{{ route('users.index') }}" class="btn btn-secondary">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7 7-7M3 12h18"/></svg>
        <span>返回列表</span>
    </a>
</div>

<form method="POST" action="{{ route('users.store') }}" class="card p-5 space-y-4">
    @csrf
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div>
            <label class="label" for="name">姓名 <span class="text-red-500">*</span></label>
            <input type="text" class="input" id="name" name="name" value="{{ old('name') }}" required>
            @error('name')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="label" for="username">用户名 <span class="text-red-500">*</span></label>
            <input type="text" class="input" id="username" name="username" value="{{ old('username') }}" required>
            @error('username')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="label" for="email">邮箱 <span class="text-red-500">*</span></label>
            <input type="email" class="input" id="email" name="email" value="{{ old('email') }}" required>
            @error('email')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
        </div>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <label class="label" for="password">密码 <span class="text-red-500">*</span></label>
            <input type="password" class="input" id="password" name="password" required>
            @error('password')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="label" for="password_confirmation">确认密码 <span class="text-red-500">*</span></label>
            <input type="password" class="input" id="password_confirmation" name="password_confirmation" required>
            @error('password_confirmation')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
        </div>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <label class="label" for="role">角色 <span class="text-red-500">*</span></label>
            <select class="input" id="role" name="role" required>
                <option value="">请选择角色</option>
                <option value="admin" {{ old('role') == 'admin' ? 'selected' : '' }}>管理员</option>
                <option value="workorder_manager" {{ old('role') == 'workorder_manager' ? 'selected' : '' }}>工单管理员</option>
                <option value="engineer" {{ old('role') == 'engineer' ? 'selected' : '' }}>工程师</option>
                <option value="user" {{ old('role') == 'user' ? 'selected' : '' }}>普通用户</option>
            </select>
            @error('role')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="label" for="department_id">部门</label>
            <select class="input" id="department_id" name="department_id">
                <option value="">请选择部门</option>
                @foreach($departments as $department)
                <option value="{{ $department->id }}" {{ old('department_id') == $department->id ? 'selected' : '' }}>{{ $department->name }}</option>
                @endforeach
            </select>
            @error('department_id')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
        </div>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
         <div>
             <label class="label" for="phone">联系电话</label>
             <input type="text" class="input" id="phone" name="phone" value="{{ old('phone') }}">
             @error('phone')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
         </div>
         <div>
             <label class="label" for="employee_id">工号</label>
             <input type="text" class="input" id="employee_id" name="employee_id" value="{{ old('employee_id') }}">
             @error('employee_id')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
         </div>
         <div>
            <label class="label" for="wecom_userid">企业微信UserID</label>
            <input type="text" class="input" id="wecom_userid" name="wecom_userid" value="{{ old('wecom_userid') }}" maxlength="100" placeholder="可选，用于@提醒">
            @error('wecom_userid')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="label" for="dingtalk_userid">钉钉userid</label>
            <input type="text" class="input" id="dingtalk_userid" name="dingtalk_userid" value="{{ old('dingtalk_userid') }}" maxlength="100" placeholder="可选，用于钉钉@提醒">
            @error('dingtalk_userid')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="label" for="feishu_user_id">飞书user_id</label>
            <input type="text" class="input" id="feishu_user_id" name="feishu_user_id" value="{{ old('feishu_user_id') }}" maxlength="100" placeholder="可选，用于飞书@提醒">
            @error('feishu_user_id')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="label" for="wechat_openid">微信OpenID</label>
            <input type="text" class="input" id="wechat_openid" name="wechat_openid" value="{{ old('wechat_openid') }}" maxlength="128" placeholder="可选，微信登录绑定">
            @error('wechat_openid')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
        </div>
     </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <label class="label" for="status">状态 <span class="text-red-500">*</span></label>
            <select class="input" id="status" name="status" required>
                <option value="active" {{ old('status', 'active') == 'active' ? 'selected' : '' }}>启用</option>
                <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>禁用</option>
            </select>
            @error('status')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="label" for="account_type">账户类型 <span class="text-red-500">*</span></label>
            <select class="input" id="account_type" name="account_type" required>
                <option value="staff" {{ old('account_type', 'staff') == 'staff' ? 'selected' : '' }}>员工</option>
                <option value="student" {{ old('account_type') == 'student' ? 'selected' : '' }}>其他</option>
                <option value="external" {{ old('account_type') == 'external' ? 'selected' : '' }}>外部人员</option>
            </select>
            @error('account_type')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
        </div>
    </div>
    <div class="flex items-center justify-end gap-2 pt-2 border-t border-border">
        <a href="{{ route('users.index') }}" class="btn btn-secondary">取消</a>
        <button type="submit" class="btn btn-primary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            <span>保存</span>
        </button>
    </div>
</form>
@endsection
