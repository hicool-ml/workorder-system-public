<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>注册 - {{ config('app.name', '校园网工单系统') }}</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        body {
            background-color: #f8f9fa;
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .register-container {
            max-width: 500px;
            margin: 0 auto;
            width: 100%;
            padding: 0 15px;
        }
        .register-card {
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            border: 1px solid rgba(0, 0, 0, 0.125);
            border-radius: 0.5rem;
            background: white;
        }
        .register-header {
            background-color: #343a40;
            color: white;
            text-align: center;
            padding: 1.5rem;
            border-radius: 0.5rem 0.5rem 0 0;
        }
        .register-body {
            padding: 2rem;
        }
        .register-footer {
            text-align: center;
            padding: 1rem;
            background-color: #f8f9fa;
            border-radius: 0 0 0.5rem 0.5rem;
        }
        .brand-logo {
            font-size: 1.5rem;
            font-weight: 600;
        }
        .form-floating {
            margin-bottom: 1rem;
        }
        .password-strength {
            height: 5px;
            border-radius: 3px;
            margin-top: 0.5rem;
            transition: all 0.3s ease;
        }
        .strength-weak {
            background-color: #dc3545;
            width: 33%;
        }
        .strength-medium {
            background-color: #ffc107;
            width: 66%;
        }
        .strength-strong {
            background-color: #28a745;
            width: 100%;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-card">
            <div class="register-header">
                <i class="fas fa-user-plus fa-2x mb-2"></i>
                <div class="brand-logo">用户注册</div>
                <small class="d-block mt-1">{{ config('app.name', '校园网工单系统') }}</small>
            </div>
            
            <div class="register-body">
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
                    </div>
                @endif
                
                <form method="POST" action="{{ route('register') }}">
                    @csrf
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" id="name" name="name"
                                       value="{{ old('name') }}" required autocomplete="name" autofocus
                                       placeholder="请输入姓名">
                                <label for="name">姓名 <span class="text-danger">*</span></label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" id="username" name="username"
                                       value="{{ old('username') }}" required autocomplete="username"
                                       placeholder="请输入用户名">
                                <label for="username">用户名 <span class="text-danger">*</span></label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-floating mb-3">
                        <input type="email" class="form-control" id="email" name="email"
                               value="{{ old('email') }}" required autocomplete="email"
                               placeholder="请输入邮箱">
                        <label for="email">邮箱 <span class="text-danger">*</span></label>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-floating mb-3">
                                <input type="password" class="form-control" id="password" name="password" 
                                       required autocomplete="new-password" oninput="checkPasswordStrength(this.value)">
                                <label for="password">密码 <span class="text-danger">*</span></label>
                                <div class="password-strength" id="passwordStrength"></div>
                                <div class="form-text">密码至少需要6个字符</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating mb-3">
                                <input type="password" class="form-control" id="password_confirmation" 
                                       name="password_confirmation" required autocomplete="new-password">
                                <label for="password_confirmation">确认密码 <span class="text-danger">*</span></label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-floating mb-3">
                                <input type="tel" class="form-control" id="phone" name="phone"
                                       value="{{ old('phone') }}" autocomplete="tel"
                                       placeholder="请输入电话号码">
                                <label for="phone">电话号码</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" id="employee_id" name="employee_id"
                                       value="{{ old('employee_id') }}" autocomplete="employee_id"
                                       placeholder="请输入员工号">
                                <label for="employee_id">员工号</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-floating mb-3">
                        <select class="form-select" id="account_type" name="account_type" required>
                            <option value="">请选择账户类型</option>
                            <option value="staff" {{ old('account_type') == 'staff' ? 'selected' : '' }}>教职工</option>
                            <option value="student" {{ old('account_type') == 'student' ? 'selected' : '' }}>学生</option>
                            <option value="external" {{ old('account_type') == 'external' ? 'selected' : '' }}>外部用户</option>
                        </select>
                        <label for="account_type">账户类型 <span class="text-danger">*</span></label>
                    </div>
                    
                    <div class="form-floating mb-3">
                        <select class="form-select" id="department_id" name="department_id">
                            <option value="">请选择部门（可选）</option>
                            @if(isset($departments))
                                @foreach($departments as $department)
                                    <option value="{{ $department->id }}" 
                                            {{ old('department_id') == $department->id ? 'selected' : '' }}>
                                        {{ $department->name }}
                                    </option>
                                @endforeach
                            @endif
                        </select>
                        <label for="department_id">所属部门</label>
                    </div>
                    
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" id="location" name="location"
                               value="{{ old('location') }}" autocomplete="location"
                               placeholder="请输入位置信息">
                        <label for="location">位置信息</label>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-user-plus me-2"></i>注册账户
                        </button>
                        <a href="{{ route('login') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-sign-in-alt me-2"></i>返回登录
                        </a>
                    </div>
                </form>
            </div>
            
            <div class="register-footer">
                <small class="text-muted">
                    © {{ date('Y') }} {{ config('app.name', '校园网工单系统') }} v1.0.0
                </small>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function checkPasswordStrength(password) {
            const strengthBar = document.getElementById('passwordStrength');
            let strength = 0;
            
            if (password.length >= 6) strength++;
            if (password.length >= 10) strength++;
            if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^a-zA-Z0-9]/.test(password)) strength++;
            
            strengthBar.className = 'password-strength';
            
            if (password.length === 0) {
                strengthBar.style.width = '0';
            } else if (strength <= 2) {
                strengthBar.classList.add('strength-weak');
            } else if (strength <= 3) {
                strengthBar.classList.add('strength-medium');
            } else {
                strengthBar.classList.add('strength-strong');
            }
        }
        
        // 密码确认检查
        document.getElementById('password_confirmation').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmation = this.value;
            
            if (confirmation && password !== confirmation) {
                this.setCustomValidity('两次输入的密码不一致');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
</body>
</html>