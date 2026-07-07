@extends('layouts.app')

@section('title', '编辑用户 - ' . $user->name)

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">编辑用户</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="{{ route('users.show', $user->id) }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> 返回详情
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">用户信息</h5>
            </div>
            <form method="POST" action="{{ route('users.update', $user->id) }}">
                @csrf
                @method('PUT')
                <div class="card-body">
                    <!-- 基本信息 -->
                    <h6 class="mb-3">基本信息</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label for="name" class="form-label">姓名 <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name"
                                   value="{{ old('name', $user->name) }}" required maxlength="100">
                            @error('name')
                                <div class="invalid-feedback d-block">
                                    <strong>{{ $message }}</strong>
                                </div>
                            @enderror
                        </div>
                        <div class="col-md-4">
                            <label for="username" class="form-label">用户名 <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="username" name="username"
                                   value="{{ old('username', $user->username) }}" required maxlength="50">
                            @error('username')
                                <div class="invalid-feedback d-block">
                                    <strong>{{ $message }}</strong>
                                </div>
                            @enderror
                        </div>
                        <div class="col-md-4">
                            <label for="email" class="form-label">邮箱 <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="email" name="email"
                                   value="{{ old('email', $user->email) }}" maxlength="100" required>
                            @error('email')
                                <div class="invalid-feedback d-block">
                                    <strong>{{ $message }}</strong>
                                </div>
                            @enderror
                        </div>
                    </div>

                    <!-- 密码信息 -->
                    <h6 class="mb-3">密码信息</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="password" class="form-label">新密码</label>
                            <input type="password" class="form-control" id="password" name="password" 
                                   placeholder="留空则不修改">
                            @error('password')
                                <div class="invalid-feedback d-block">
                                    <strong>{{ $message }}</strong>
                                </div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="password_confirmation" class="form-label">确认密码</label>
                            <input type="password" class="form-control" id="password_confirmation" 
                                   name="password_confirmation" placeholder="留空则不修改">
                            @error('password_confirmation')
                                <div class="invalid-feedback d-block">
                                    <strong>{{ $message }}</strong>
                                </div>
                            @enderror
                        </div>
                    </div>

                    <!-- 角色和部门 -->
                    <h6 class="mb-3">角色和部门</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="role" class="form-label">角色 <span class="text-danger">*</span></label>
                            <select class="form-select" id="role" name="role" required>
                                <option value="">请选择角色</option>
                                @foreach($roles as $key => $value)
                                <option value="{{ $key }}" {{ old('role', $user->role) == $key ? 'selected' : '' }}>
                                    {{ $value }}
                                </option>
                                @endforeach
                            </select>
                            @error('role')
                                <div class="invalid-feedback d-block">
                                    <strong>{{ $message }}</strong>
                                </div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="department_id" class="form-label">所属部门</label>
                            <select class="form-select" id="department_id" name="department_id">
                                <option value="">请选择部门</option>
                                @foreach($departments as $department)
                                <option value="{{ $department->id }}" 
                                        {{ old('department_id', $user->department_id) == $department->id ? 'selected' : '' }}>
                                    {{ $department->full_path ?? $department->name }}
                                </option>
                                @endforeach
                            </select>
                            @error('department_id')
                                <div class="invalid-feedback d-block">
                                    <strong>{{ $message }}</strong>
                                </div>
                            @enderror
                        </div>
                    </div>

                    <!-- 联系信息 -->
                    <h6 class="mb-3">联系信息</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="phone" class="form-label">联系电话</label>
                            <input type="text" class="form-control" id="phone" name="phone" 
                                   value="{{ old('phone', $user->phone) }}" maxlength="20">
                            @error('phone')
                                <div class="invalid-feedback d-block">
                                    <strong>{{ $message }}</strong>
                                </div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="employee_id" class="form-label">工号</label>
                            <input type="text" class="form-control" id="employee_id" name="employee_id" 
                                   value="{{ old('employee_id', $user->employee_id) }}" maxlength="50">
                            @error('employee_id')
                                <div class="invalid-feedback d-block">
                                    <strong>{{ $message }}</strong>
                                </div>
                            @enderror
                        </div>
                    </div>

                    <!-- 其他信息 -->
                    <h6 class="mb-3">其他信息</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="status" class="form-label">状态 <span class="text-danger">*</span></label>
                            <select class="form-select" id="status" name="status" required>
                                @foreach($statuses as $key => $value)
                                <option value="{{ $key }}" {{ old('status', $user->status) == $key ? 'selected' : '' }}>
                                    {{ $value }}
                                </option>
                                @endforeach
                            </select>
                            @error('status')
                                <div class="invalid-feedback d-block">
                                    <strong>{{ $message }}</strong>
                                </div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="account_type" class="form-label">账户类型 <span class="text-danger">*</span></label>
                            <select class="form-select" id="account_type" name="account_type" required>
                                <option value="">请选择账户类型</option>
                                <option value="staff" {{ old('account_type', $user->account_type) == 'staff' ? 'selected' : '' }}>教职工</option>
                                <option value="student" {{ old('account_type', $user->account_type) == 'student' ? 'selected' : '' }}>学生</option>
                                <option value="external" {{ old('account_type', $user->account_type) == 'external' ? 'selected' : '' }}>外部人员</option>
                            </select>
                            @error('account_type')
                                <div class="invalid-feedback d-block">
                                    <strong>{{ $message }}</strong>
                                </div>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="location" class="form-label">办公地点</label>
                            <input type="text" class="form-control" id="location" name="location" 
                                   value="{{ old('location', $user->location) }}" maxlength="255">
                            @error('location')
                                <div class="invalid-feedback d-block">
                                    <strong>{{ $message }}</strong>
                                </div>
                            @enderror
                        </div>
                    </div>

                    <!-- 备注 -->
                    <div class="mb-4">
                        <label for="remarks" class="form-label">备注</label>
                        <textarea class="form-control" id="remarks" name="remarks" rows="3"
                                  placeholder="其他需要说明的信息">{{ old('remarks', $user->remarks) }}</textarea>
                        @error('remarks')
                            <div class="invalid-feedback d-block">
                                <strong>{{ $message }}</strong>
                            </div>
                        @enderror
                    </div>

                    <!-- 提交按钮 -->
                    <div class="d-flex justify-content-end">
                        <a href="{{ route('users.show', $user->id) }}" class="btn btn-secondary me-2">
                            <i class="fas fa-times"></i> 取消
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> 保存更改
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <div class="col-md-4">
        <!-- 用户状态信息 -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="card-title mb-0">用户状态</h6>
            </div>
            <div class="card-body">
                <div class="mb-2">
                    <strong>用户ID：</strong>{{ $user->id }}
                </div>
                <div class="mb-2">
                    <strong>当前角色：</strong>
                    <span class="badge bg-{{ $user->role == 'admin' ? 'danger' : ($user->role == 'workorder_manager' ? 'primary' : ($user->role == 'engineer' ? 'warning' : 'info')) }} text-white">
                        {{ $user->role_text }}
                    </span>
                </div>
                <div class="mb-2">
                    <strong>当前状态：</strong>
                    <span class="badge bg-{{ $user->status == 'active' ? 'success' : 'secondary' }}">
                        {{ $user->status_text }}
                    </span>
                </div>
                <div class="mb-2">
                    <strong>注册时间：</strong>{{ $user->created_at->format('Y-m-d H:i:s') }}
                </div>
                <div class="mb-2">
                    <strong>最后更新：</strong>{{ $user->updated_at->format('Y-m-d H:i:s') }}
                </div>
            </div>
        </div>
        
        <!-- 编辑提示 -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="card-title mb-0">编辑提示</h6>
            </div>
            <div class="card-body">
                <ul class="mb-0">
                    <li>密码字段留空则不修改原密码</li>
                    <li>修改角色会影响用户的权限</li>
                    <li>禁用用户后，用户无法登录系统</li>
                    <li>管理员不能禁用自己的账户</li>
                    <li>所有修改都会记录在系统日志中</li>
                </ul>
            </div>
        </div>
        
        <!-- 角色权限说明 -->
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">角色权限说明</h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <strong>管理员</strong>
                    <p class="text-muted small mb-0">拥有所有权限，可管理部门、用户、工单类型等</p>
                </div>
                <div class="mb-3">
                    <strong>工单管理员</strong>
                    <p class="text-muted small mb-0">可管理所有工单，包括创建、分配、关闭、批量操作、导出等</p>
                </div>
                <div class="mb-3">
                    <strong>工程师</strong>
                    <p class="text-muted small mb-0">可处理工单、查看报表、管理附件等</p>
                </div>
                <div>
                    <strong>普通用户</strong>
                    <p class="text-muted small mb-0">只能创建和查看自己的工单</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection