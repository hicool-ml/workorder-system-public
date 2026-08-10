@extends('layouts.app')

@section('title', '编辑用户 - ' . $user->name)

@section('content')
<div class="flex items-center justify-between mb-6 gap-3 flex-wrap">
    <div>
        <h1 class="text-xl font-semibold text-ink">编辑用户</h1>
        <p class="text-sm text-ink-muted mt-0.5">{{ $user->name }}</p>
    </div>
    <a href="{{ route('users.show', $user->id) }}" class="btn btn-secondary">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7 7-7M3 12h18"/></svg>
        <span>返回详情</span>
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2">
        <form method="POST" action="{{ route('users.update', $user->id) }}" class="card p-5 space-y-5">
            @csrf
            @method('PUT')

            <div>
                <h3 class="text-sm font-semibold text-ink mb-3">基本信息</h3>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="label" for="name">姓名 <span class="text-red-500">*</span></label>
                        <input type="text" class="input" id="name" name="name" value="{{ old('name', $user->name) }}" required maxlength="100">
                        @error('name')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="label" for="username">用户名 <span class="text-red-500">*</span></label>
                        <input type="text" class="input" id="username" name="username" value="{{ old('username', $user->username) }}" required maxlength="50">
                        @error('username')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="label" for="email">邮箱 <span class="text-red-500">*</span></label>
                        <input type="email" class="input" id="email" name="email" value="{{ old('email', $user->email) }}" maxlength="100" required>
                        @error('email')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>

            <div class="pt-4 border-t border-border">
                <h3 class="text-sm font-semibold text-ink mb-3">密码信息</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="label" for="password">新密码</label>
                        <input type="password" class="input" id="password" name="password" placeholder="留空则不修改">
                        @error('password')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="label" for="password_confirmation">确认密码</label>
                        <input type="password" class="input" id="password_confirmation" name="password_confirmation" placeholder="留空则不修改">
                        @error('password_confirmation')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>

            <div class="pt-4 border-t border-border">
                <h3 class="text-sm font-semibold text-ink mb-3">角色和部门</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="label" for="role">角色 <span class="text-red-500">*</span></label>
                        <select class="input" id="role" name="role" required>
                            <option value="">请选择角色</option>
                            @foreach($roles as $key => $value)
                            <option value="{{ $key }}" {{ old('role', $user->role) == $key ? 'selected' : '' }}>{{ $value }}</option>
                            @endforeach
                        </select>
                        @error('role')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="label" for="department_id">所属部门</label>
                        <select class="input" id="department_id" name="department_id">
                            <option value="">请选择部门</option>
                            @foreach($departments as $department)
                            <option value="{{ $department->id }}" {{ old('department_id', $user->department_id) == $department->id ? 'selected' : '' }}>{{ $department->full_path ?? $department->name }}</option>
                            @endforeach
                        </select>
                        @error('department_id')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>

            <div class="pt-4 border-t border-border">
                <h3 class="text-sm font-semibold text-ink mb-3">联系信息</h3>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="label" for="phone">联系电话</label>
                        <input type="text" class="input" id="phone" name="phone" value="{{ old('phone', $user->phone) }}" maxlength="20">
                        @error('phone')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="label" for="employee_id">工号</label>
                        <input type="text" class="input" id="employee_id" name="employee_id" value="{{ old('employee_id', $user->employee_id) }}" maxlength="50">
                        @error('employee_id')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="label" for="wecom_userid">企业微信UserID</label>
                       <input type="text" class="input" id="wecom_userid" name="wecom_userid" value="{{ old('wecom_userid', $user->wecom_userid) }}" maxlength="100" placeholder="可选，用于@提醒">
                       @error('wecom_userid')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                   </div>
                    <div>
                        <label class="label" for="dingtalk_userid">钉钉userid</label>
                        <input type="text" class="input" id="dingtalk_userid" name="dingtalk_userid" value="{{ old('dingtalk_userid', $user->dingtalk_userid) }}" maxlength="100" placeholder="可选，用于钉钉@提醒">
                        @error('dingtalk_userid')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="label" for="feishu_user_id">飞书user_id</label>
                        <input type="text" class="input" id="feishu_user_id" name="feishu_user_id" value="{{ old('feishu_user_id', $user->feishu_user_id) }}" maxlength="100" placeholder="可选，用于飞书@提醒">
                        @error('feishu_user_id')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="label" for="wechat_openid">微信OpenID</label>
                        <input type="text" class="input" id="wechat_openid" name="wechat_openid" value="{{ old('wechat_openid', $user->wechat_openid) }}" maxlength="128" placeholder="可选，微信登录绑定；清空后该微信需重新绑定">
                        @error('wechat_openid')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="label" for="location">办公地点</label>
                        <input type="text" class="input" id="location" name="location" value="{{ old('location', $user->location) }}" maxlength="255">
                        @error('location')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>

            <div class="pt-4 border-t border-border">
                <h3 class="text-sm font-semibold text-ink mb-3">其他信息</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="label" for="status">状态 <span class="text-red-500">*</span></label>
                        <select class="input" id="status" name="status" required>
                            @foreach($statuses as $key => $value)
                            <option value="{{ $key }}" {{ old('status', $user->status) == $key ? 'selected' : '' }}>{{ $value }}</option>
                            @endforeach
                        </select>
                        @error('status')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="label" for="account_type">账户类型 <span class="text-red-500">*</span></label>
                        <select class="input" id="account_type" name="account_type" required>
                            <option value="staff" {{ old('account_type', $user->account_type) == 'staff' ? 'selected' : '' }}>教职工</option>
                            <option value="student" {{ old('account_type', $user->account_type) == 'student' ? 'selected' : '' }}>学生</option>
                            <option value="external" {{ old('account_type', $user->account_type) == 'external' ? 'selected' : '' }}>外部人员</option>
                        </select>
                        @error('account_type')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>
                <div class="mt-4">
                    <label class="label" for="remarks">备注</label>
                    <textarea class="input" id="remarks" name="remarks" rows="3" placeholder="其他需要说明的信息">{{ old('remarks', $user->remarks) }}</textarea>
                    @error('remarks')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2 border-t border-border">
                <a href="{{ route('users.show', $user->id) }}" class="btn btn-secondary">取消</a>
                <button type="submit" class="btn btn-primary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    <span>保存更改</span>
                </button>
            </div>
        </form>
    </div>

    <div class="lg:col-span-1 space-y-4">
        <div class="card p-5">
            <h3 class="text-sm font-semibold text-ink mb-3">用户状态</h3>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between"><dt style="color: var(--c-ink-muted);">用户ID</dt><dd class="text-ink">{{ $user->id }}</dd></div>
                <div class="flex justify-between items-center"><dt style="color: var(--c-ink-muted);">当前角色</dt><dd><span class="badge bg-blue-100 text-blue-700">{{ $user->role_text }}</span></dd></div>
                <div class="flex justify-between items-center"><dt style="color: var(--c-ink-muted);">当前状态</dt><dd>@if($user->status == 'active')<span class="badge bg-green-100 text-green-700">{{ $user->status_text }}</span>@else<span class="badge bg-red-100 text-red-700">{{ $user->status_text }}</span>@endif</dd></div>
                <div class="flex justify-between"><dt style="color: var(--c-ink-muted);">注册时间</dt><dd class="text-ink">{{ $user->created_at->format('Y-m-d H:i') }}</dd></div>
            </dl>
        </div>
        <div class="card p-5">
            <h3 class="text-sm font-semibold text-ink mb-3">编辑提示</h3>
            <ul class="space-y-2 text-sm" style="color: var(--c-ink-muted);">
                <li>密码字段留空则不修改</li>
                <li>修改角色会影响用户权限</li>
                <li>禁用后用户无法登录</li>
                <li>管理员不能禁用自己</li>
            </ul>
        </div>
    </div>
</div>
@endsection
