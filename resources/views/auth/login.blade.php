<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ \App\Helpers\SystemHelper::getSystemNameTitle('登录') }}</title>
    
    <!-- Bootstrap CSS -->
    <link href="{{ \App\Helpers\UrlHelper::relative_asset('assets/libs/bootstrap/5.3.0/css/bootstrap.min.css') }}" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="{{ \App\Helpers\UrlHelper::relative_asset('assets/libs/font-awesome/6.4.0/css/all.min.css') }}" rel="stylesheet">
    
    <style>
        body {
            background-color: #f8f9fa;
            height: 100vh;
            display: flex;
            align-items: center;
        }
        .login-container {
            max-width: 400px;
            margin: 0 auto;
        }
        .login-card {
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            border: 1px solid rgba(0, 0, 0, 0.125);
            border-radius: 0.5rem;
        }
        .login-header {
            background-color: #343a40;
            color: white;
            text-align: center;
            padding: 1.5rem;
            border-radius: 0.5rem 0.5rem 0 0;
        }
        .login-body {
            padding: 2rem;
        }
        .login-footer {
            text-align: center;
            padding: 1rem;
            background-color: #f8f9fa;
            border-radius: 0 0 0.5rem 0.5rem;
        }
        .brand-logo {
            font-size: 1.5rem;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <i class="fas fa-tools fa-2x mb-2"></i>
                <div class="brand-logo">{{ \App\Helpers\SystemHelper::getSystemName() }}</div>
                <small class="d-block mt-1">系统登录</small>
            </div>
            
            <div class="login-body">
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                
                <form method="POST" action="{{ route('login') }}">
                    @csrf
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">用户名/邮箱</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-user"></i>
                            </span>
                            <input type="text" class="form-control" id="name" name="name"
                                   value="{{ old('name') }}" required autocomplete="username" autofocus
                                   placeholder="请输入用户名或邮箱">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">密码</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-lock"></i>
                            </span>
                            <input type="password" class="form-control" id="password" name="password" 
                                   required autocomplete="current-password">
                        </div>
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="remember" name="remember" autocomplete="off">
                        <label class="form-check-label" for="remember">
                            记住我
                        </label>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-sign-in-alt me-2"></i>登录
                        </button>
                    </div>
                </form>
                
                @if(\App\Models\SystemSetting::isRegistrationEnabled())
                <div class="text-center mt-3">
                    <p class="mb-0">还没有账户？</p>
                    <a href="{{ route('register') }}" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-user-plus me-1"></i>立即注册
                    </a>
                </div>
                @endif
            </div>
            
            <div class="login-footer">
                <small class="text-muted">
                    {{ \App\Helpers\SystemHelper::getSystemCopyright() }}
                </small>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="{{ \App\Helpers\UrlHelper::relative_asset('assets/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js') }}"></script>
    
    <!-- 可访问性修复 - 为所有 select 元素添加 title 属性 -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // 为所有没有 title 属性的 select 元素添加 title
            const selectElements = document.querySelectorAll('select:not([title])');
            selectElements.forEach(function(select) {
                // 获取关联的 label 文本作为 title
                const label = document.querySelector(`label[for="${select.id}"]`);
                if (label) {
                    select.setAttribute('title', label.textContent.trim());
                } else if (select.name) {
                    // 如果没有关联的 label，使用 name 属性生成一个描述性的 title
                    const nameMap = {
                        'category_id': '选择分类',
                        'status': '选择状态',
                        'priority': '选择优先级',
                        'department_id': '选择部门',
                        'assignee_id': '选择处理人',
                        'campus': '选择校区',
                        'building': '选择楼栋',
                        'source': '选择来源',
                        'role': '选择角色',
                        'level': '选择层级',
                        'format': '选择格式',
                        'type': '选择类型',
                        'is_read': '选择读取状态',
                        'target_type': '选择发布范围',
                        'account_type': '选择账户类型',
                        'building_type': '选择建筑类型',
                        'parent_id': '选择父分类',
                        'is_active': '选择状态',
                        'default_user_role': '选择默认用户角色',
                        'phone_assisted': '选择处理方式',
                        'is_emergency': '选择特殊标记',
                        'visit_method': '选择回访方式',
                        'collaborator_id': '选择协作者',
                        'satisfaction_score': '选择满意度评分',
                        'service_quality_score': '选择服务质量评分',
                        'professional_score': '选择专业水平评分',
                        'overall_score': '选择总体满意度评分'
                    };
                    
                    const title = nameMap[select.name] || `选择${select.name}`;
                    select.setAttribute('title', title);
                }
            });
        });
    </script>
</body>
</html>