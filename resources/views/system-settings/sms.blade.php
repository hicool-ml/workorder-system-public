@extends('layouts.app')

@section('title', '短信配置')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">短信配置</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="{{ route('system-settings.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> 返回系统设置
        </a>
    </div>
</div>

<div class="row">
    <!-- 短信开关 -->
    <div class="col-12 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-sms"></i> 短信服务
                </h5>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="sms_enabled"
                           name="sms_enabled"
                           @if($smsSettings['enabled'])
                           checked
                           @endif
                           onchange="toggleSms(this.checked)">
                    <label class="form-check-label" for="sms_enabled">
                        启用短信通知
                    </label>
                </div>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    启用短信通知后，系统将在工单状态变更时向相关人员发送短信。请确保正确配置短信服务提供商信息。
                </div>
            </div>
        </div>
    </div>

    <!-- 短信服务配置 -->
    <div class="col-12 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-cog"></i> 短信服务配置
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('system-settings.update-sms') }}" id="smsConfigForm">
                    @csrf
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="sms_provider" class="form-label">短信服务提供商</label>
                                <select class="form-select" id="sms_provider" name="sms_provider" required>
                                    <option value="aliyun" @if($smsSettings['provider'] === 'aliyun') selected @endif>阿里云短信</option>
                                    <option value="tencent" @if($smsSettings['provider'] === 'tencent') selected @endif>腾讯云短信</option>
                                    <option value="dingtalk" @if($smsSettings['provider'] === 'dingtalk') selected @endif>钉钉短信</option>
                                    <option value="custom" @if($smsSettings['provider'] === 'custom') selected @endif>自定义接口</option>
                                </select>
                                <div class="form-text">选择短信服务提供商（钉钉短信使用阿里云短信服务）</div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="sms_sign_name" class="form-label">短信签名</label>
                                <input type="text" class="form-control" id="sms_sign_name" autocomplete="off"
                                       name="sms_sign_name" autocomplete="off"
                                       value="{{ $smsSettings['sign_name'] }}" required>
                                <div class="form-text">在短信服务提供商处申请的短信签名</div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="sms_access_key" class="form-label">Access Key ID</label>
                                <input type="text" class="form-control" id="sms_access_key" autocomplete="off"
                                       name="sms_access_key" autocomplete="off"
                                       value="{{ $smsSettings['access_key'] }}" required>
                                <div class="form-text" id="accessKeyHelp">短信服务访问密钥ID</div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="sms_access_secret" class="form-label">Access Key Secret</label>
                                <input type="password" class="form-control" id="sms_access_secret" autocomplete="off"
                                       name="sms_access_secret" autocomplete="off"
                                       value="{{ $smsSettings['access_secret'] }}" required>
                                <div class="form-text" id="accessSecretHelp">短信服务访问密钥Secret</div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="sms_daily_limit" class="form-label">每日发送限制</label>
                        <input type="number" class="form-control" id="sms_daily_limit" autocomplete="off"
                               name="sms_daily_limit" autocomplete="off"
                               value="{{ $smsSettings['daily_limit'] }}" min="1" max="10000" required>
                        <div class="form-text">每日最多发送的短信数量（1-10000）</div>
                    </div>

                    <div class="alert alert-info" id="dingtalkInfo" style="display: none;">
                        <i class="fas fa-info-circle"></i>
                        <strong>钉钉短信说明：</strong>
                        <ul class="mb-0 mt-2">
                            <li>钉钉短信服务基于阿里云短信，需要在钉钉开放平台获取AccessKey和Secret</li>
                            <li>访问 <a href="https://open-dev.dingtalk.com/" target="_blank">钉钉开放平台</a> 创建应用并获取短信权限</li>
                            <li>在阿里云短信服务中申请短信签名和模板</li>
                            <li>需要安装阿里云SDK：<code>composer require alibabacloud/dysmsapi-20170525</code></li>
                        </ul>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> 保存配置
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- 短信模板配置 -->
    <div class="col-12 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-file-alt"></i> 短信模板配置
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('system-settings.update-sms') }}" id="smsTemplateForm">
                    @csrf
                    <input type="hidden" name="sms_provider" value="{{ $smsSettings['provider'] }}">
                    <input type="hidden" name="sms_access_key" value="{{ $smsSettings['access_key'] }}">
                    <input type="hidden" name="sms_access_secret" value="{{ $smsSettings['access_secret'] }}">
                    <input type="hidden" name="sms_sign_name" value="{{ $smsSettings['sign_name'] }}">
                    <input type="hidden" name="sms_daily_limit" value="{{ $smsSettings['daily_limit'] }}">
                    
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        请在短信服务提供商处申请对应的短信模板，并将模板代码填写到下方。模板变量请参考各服务提供商的文档。
                    </div>

                    <div class="alert alert-info" id="dingtalkTemplateInfo" style="display: none;">
                        <i class="fas fa-info-circle"></i>
                        <strong>钉钉短信模板变量说明：</strong>
                        <ul class="mb-0 mt-2">
                            <li>工单分配：title（工单标题）、ticket（工单编号）</li>
                            <li>工单完成：title（工单标题）、ticket（工单编号）</li>
                            <li>验证码：code（验证码）</li>
                        </ul>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="template_workorder_assigned" class="form-label">工单分配通知模板</label>
                                <input type="text" class="form-control" id="template_workorder_assigned" autocomplete="off"
                                       name="sms_template_codes[workorder_assigned]" autocomplete="off"
                                       value="{{ $smsSettings['template_codes']['workorder_assigned'] ?? '' }}"
                                       placeholder="例如：SMS_123456789">
                                <div class="form-text">工单分配给处理人时发送的短信模板代码</div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="template_workorder_completed" class="form-label">工单完成通知模板</label>
                                <input type="text" class="form-control" id="template_workorder_completed" autocomplete="off"
                                       name="sms_template_codes[workorder_completed]" autocomplete="off"
                                       value="{{ $smsSettings['template_codes']['workorder_completed'] ?? '' }}"
                                       placeholder="例如：SMS_123456789">
                                <div class="form-text">工单完成时发送给创建人的短信模板代码</div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="template_verification_code" class="form-label">验证码模板</label>
                                <input type="text" class="form-control" id="template_verification_code" autocomplete="off"
                                       name="sms_template_codes[verification_code]" autocomplete="off"
                                       value="{{ $smsSettings['template_codes']['verification_code'] ?? '' }}"
                                       placeholder="例如：SMS_123456789">
                                <div class="form-text">发送验证码时使用的短信模板代码</div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="template_workorder_notification" class="form-label">工单通知模板</label>
                                <input type="text" class="form-control" id="template_workorder_notification" autocomplete="off"
                                       name="sms_template_codes[workorder_notification]" autocomplete="off"
                                       value="{{ $smsSettings['template_codes']['workorder_notification'] ?? '' }}"
                                       placeholder="例如：SMS_123456789">
                                <div class="form-text">工单状态变更通知的短信模板代码</div>
                            </div>
                        </div>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> 保存模板配置
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- 通知类型配置 -->
    <div class="col-12 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-bell"></i> 通知类型配置
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('system-settings.update-sms') }}" id="smsNotificationForm">
                    @csrf
                    <input type="hidden" name="sms_provider" value="{{ $smsSettings['provider'] }}">
                    <input type="hidden" name="sms_access_key" value="{{ $smsSettings['access_key'] }}">
                    <input type="hidden" name="sms_access_secret" value="{{ $smsSettings['access_secret'] }}">
                    <input type="hidden" name="sms_sign_name" value="{{ $smsSettings['sign_name'] }}">
                    <input type="hidden" name="sms_daily_limit" value="{{ $smsSettings['daily_limit'] }}">
                    
                    <div class="mb-3">
                        <label class="form-label">启用短信通知的类型</label>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="notify_workorder" 
                                           name="sms_notification_types[]" value="workorder"
                                           @if(in_array('workorder', $smsSettings['notification_types'] ?? [])) checked @endif>
                                    <label class="form-check-label" for="notify_workorder">
                                        工单通知
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="notify_assignment" 
                                           name="sms_notification_types[]" value="assignment"
                                           @if(in_array('assignment', $smsSettings['notification_types'] ?? [])) checked @endif>
                                    <label class="form-check-label" for="notify_assignment">
                                        工单分配
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="notify_completion" 
                                           name="sms_notification_types[]" value="completion"
                                           @if(in_array('completion', $smsSettings['notification_types'] ?? [])) checked @endif>
                                    <label class="form-check-label" for="notify_completion">
                                        工单完成
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> 保存通知类型
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- 测试短信 -->
    <div class="col-12 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-paper-plane"></i> 测试短信发送
                </h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    在正式使用前，建议先发送测试短信验证配置是否正确。
                </div>

                <form id="testSmsForm">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="test_phone" class="form-label">测试手机号</label>
                                <input type="text" class="form-control" id="test_phone" autocomplete="off"
                                       name="phone" autocomplete="off"
                                       value="{{ $smsSettings['test_phone'] }}"
                                       placeholder="请输入手机号" required>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="test_template_type" class="form-label">模板类型</label>
                                <select class="form-select" id="test_template_type" name="template_type" required>
                                    <option value="workorder_assigned">工单分配通知</option>
                                    <option value="workorder_completed">工单完成通知</option>
                                    <option value="verification_code">验证码</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-success" id="sendTestSmsBtn">
                            <i class="fas fa-paper-plane"></i> 发送测试短信
                        </button>
                    </div>
                </form>

                <div id="testSmsResult" class="mt-3" style="display: none;"></div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function toggleSms(enabled) {
    axios.post('{{ route("system-settings.toggle-sms") }}', {
        enabled: enabled
    }, {
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        }
    })
    .then(response => {
        if (response.data.success) {
            location.reload();
        } else {
            alert('操作失败：' + (response.data.message || '未知错误'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('操作失败：' + (error.response?.data?.message || error.message || '网络错误'));
    });
}

// 处理短信配置表单提交
document.getElementById('smsConfigForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const data = Object.fromEntries(formData.entries());
    
    // 处理复选框
    data.sms_enabled = document.getElementById('sms_enabled').checked;
    
    // 根据提供商更新帮助文本
    updateProviderHelp();
    
    axios.post('{{ route("system-settings.update-sms") }}', data, {
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => {
        if (response.data.success) {
            alert('短信配置保存成功！');
            location.reload();
        } else {
            alert('保存失败：' + (response.data.message || '未知错误'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('保存失败：' + (error.response?.data?.message || error.message || '网络错误'));
    });
});

// 处理短信模板表单提交
document.getElementById('smsTemplateForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const data = Object.fromEntries(formData.entries());
    
    // 处理模板代码数组
    const templateCodes = {};
    for (const [key, value] of formData.entries()) {
        if (key.startsWith('sms_template_codes[')) {
            const templateType = key.match(/sms_template_codes\[(.*?)\]/)[1];
            templateCodes[templateType] = value;
        }
    }
    data.sms_template_codes = templateCodes;
    
    // 删除原始的模板代码字段
    delete data['sms_template_codes[workorder_assigned]'];
    delete data['sms_template_codes[workorder_completed]'];
    delete data['sms_template_codes[verification_code]'];
    delete data['sms_template_codes[workorder_notification]'];
    
    axios.post('{{ route("system-settings.update-sms") }}', data, {
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => {
        if (response.data.success) {
            alert('模板配置保存成功！');
            location.reload();
        } else {
            alert('保存失败：' + (response.data.message || '未知错误'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('保存失败：' + (error.response?.data?.message || error.message || '网络错误'));
    });
});

// 处理通知类型表单提交
document.getElementById('smsNotificationForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const data = Object.fromEntries(formData.entries());
    
    // 处理通知类型数组
    const notificationTypes = [];
    for (const [key, value] of formData.entries()) {
        if (key.startsWith('sms_notification_types[')) {
            notificationTypes.push(value);
        }
    }
    data.sms_notification_types = notificationTypes;
    
    // 删除原始的通知类型字段
    for (const key of Object.keys(data)) {
        if (key.startsWith('sms_notification_types[')) {
            delete data[key];
        }
    }
    
    axios.post('{{ route("system-settings.update-sms") }}', data, {
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => {
        if (response.data.success) {
            alert('通知类型保存成功！');
            location.reload();
        } else {
            alert('保存失败：' + (response.data.message || '未知错误'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('保存失败：' + (error.response?.data?.message || error.message || '网络错误'));
    });
});

// 处理测试短信发送
document.getElementById('testSmsForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const data = Object.fromEntries(formData.entries());
    const resultDiv = document.getElementById('testSmsResult');
    const sendBtn = document.getElementById('sendTestSmsBtn');
    
    // 显示加载状态
    sendBtn.disabled = true;
    sendBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> 发送中...';
    resultDiv.style.display = 'none';
    
    axios.post('{{ route("system-settings.test-sms") }}', data, {
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => {
        if (response.data.success) {
            resultDiv.innerHTML = `
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    ${response.data.message}
                </div>
            `;
        } else {
            resultDiv.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-times-circle"></i>
                    ${response.data.message}
                </div>
            `;
        }
        resultDiv.style.display = 'block';
    })
    .catch(error => {
        console.error('Error:', error);
        resultDiv.innerHTML = `
            <div class="alert alert-danger">
                <i class="fas fa-times-circle"></i>
                发送失败：${error.response?.data?.message || error.message || '网络错误'}
            </div>
        `;
        resultDiv.style.display = 'block';
    })
    .finally(() => {
        sendBtn.disabled = false;
        sendBtn.innerHTML = '<i class="fas fa-paper-plane"></i> 发送测试短信';
    });
});

// 监听提供商选择变化
document.getElementById('sms_provider').addEventListener('change', function() {
    updateProviderHelp();
});

// 根据提供商更新帮助文本
function updateProviderHelp() {
    const provider = document.getElementById('sms_provider').value;
    const accessKeyHelp = document.getElementById('accessKeyHelp');
    const accessSecretHelp = document.getElementById('accessSecretHelp');
    const dingtalkInfo = document.getElementById('dingtalkInfo');
    const dingtalkTemplateInfo = document.getElementById('dingtalkTemplateInfo');
    
    if (provider === 'dingtalk') {
        accessKeyHelp.textContent = '钉钉AccessKey ID（从钉钉开放平台获取）';
        accessSecretHelp.textContent = '钉钉AccessKey Secret（从钉钉开放平台获取）';
        dingtalkInfo.style.display = 'block';
        dingtalkTemplateInfo.style.display = 'block';
    } else if (provider === 'aliyun') {
        accessKeyHelp.textContent = '阿里云AccessKey ID';
        accessSecretHelp.textContent = '阿里云AccessKey Secret';
        dingtalkInfo.style.display = 'none';
        dingtalkTemplateInfo.style.display = 'none';
    } else if (provider === 'tencent') {
        accessKeyHelp.textContent = '腾讯云SecretId';
        accessSecretHelp.textContent = '腾讯云SecretKey';
        dingtalkInfo.style.display = 'none';
        dingtalkTemplateInfo.style.display = 'none';
    } else {
        accessKeyHelp.textContent = '短信服务访问密钥ID';
        accessSecretHelp.textContent = '短信服务访问密钥Secret';
        dingtalkInfo.style.display = 'none';
        dingtalkTemplateInfo.style.display = 'none';
    }
}

// 初始化帮助文本
updateProviderHelp();
</script>
@endsection
