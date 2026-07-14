<?php

namespace App\Http\Controllers;

use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SystemSettingController extends Controller
{
    /**
     * 系统设置列表页面
     */
    public function index()
    {
        $settings = SystemSetting::orderBy('key')->get();
        
        // 按类别分组设置
        $groupedSettings = [
            'registration' => $settings->filter(fn($s) => str_contains($s->key, 'registration')),
            'user' => $settings->filter(fn($s) => str_contains($s->key, 'user')),
            'system' => $settings->filter(fn($s) => str_contains($s->key, 'system')),
            'version' => $settings->filter(fn($s) => in_array($s->key, ['system_version', 'system_release_date'])),
            'other' => $settings->filter(fn($s) => !in_array($s->key, [
                'registration_enabled', 'default_user_role', 'require_email_verification', 'system_name', 'system_version', 'system_release_date'
            ])),
        ];
        
        return view('system-settings.index', compact('groupedSettings', 'settings'));
    }

    /**
     * 更新系统设置
     */
    public function update(Request $request)
    {
        $request->validate([
            'settings' => 'required|array',
            'settings.*' => 'required|string',
        ]);

        DB::beginTransaction();
        try {
            foreach ($request->input('settings') as $key => $value) {
                $setting = SystemSetting::where('key', $key)->first();
                if ($setting) {
                    // 根据类型转换值
                    $convertedValue = match($setting->type) {
                        'boolean' => $value === '1' || $value === 'true',
                        'integer' => (int) $value,
                        'float' => (float) $value,
                        'json', 'array' => is_string($value) ? json_decode($value, true) : $value,
                        default => $value,
                    };
                    
                    $setting->setTypedValueAttribute($convertedValue);
                    $setting->save();
                }
            }
            
            DB::commit();
            
            return back()->with('success', '系统设置更新成功');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', '系统设置更新失败：' . $e->getMessage());
        }
    }

    /**
     * 切换注册开关
     */
    public function toggleRegistration(Request $request)
    {
        $enabled = $request->boolean('enabled', false);
        
        try {
            SystemSetting::toggleRegistration($enabled);
            
            $message = $enabled ? '开放注册已启用' : '开放注册已禁用';
            
            // 如果是AJAX请求，返回JSON响应
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message
                ]);
            }
            
            return back()->with('success', $message);
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            if (empty($errorMessage)) {
                $errorMessage = '系统设置更新失败';
            }
            
            // 如果是AJAX请求，返回JSON响应
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage
                ]);
            }
            
            return back()->with('error', '操作失败：' . $errorMessage);
        }
    }

    /**
     * 创建新设置
     */
    public function store(Request $request)
    {
        $request->validate([
            'key' => 'required|string|max:100|unique:system_settings,key',
            'value' => 'required|string',
            'type' => 'required|in:string,boolean,integer,float,json,array',
            'description' => 'nullable|string|max:255',
            'is_public' => 'boolean',
        ]);

        try {
            SystemSetting::create($request->all());
            
            return back()->with('success', '设置创建成功');
        } catch (\Exception $e) {
            return back()->with('error', '设置创建失败：' . $e->getMessage());
        }
    }

    /**
     * 删除设置
     */
    public function destroy(SystemSetting $systemSetting)
    {
        try {
            $systemSetting->delete();
            
            return back()->with('success', '设置删除成功');
        } catch (\Exception $e) {
            return back()->with('error', '设置删除失败：' . $e->getMessage());
        }
    }

    /**
     * 获取公开设置（API）
     */
    public function publicSettings()
    {
        $settings = SystemSetting::where('is_public', true)->get()->mapWithKeys(function ($setting) {
            return [$setting->key => $setting->typed_value];
        });
        
        return response()->json($settings);
    }

    /**
     * 初始化默认设置
     */
    public function initializeDefaults(Request $request)
    {
        try {
            SystemSetting::initializeDefaults();
            
            $message = '默认设置初始化成功';
            
            // 如果是AJAX请求，返回JSON响应
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message
                ]);
            }
            
            return back()->with('success', $message);
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            if (empty($errorMessage)) {
                $errorMessage = '默认设置初始化失败';
            }
            
            // 如果是AJAX请求，返回JSON响应
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage
                ]);
            }
            
            return back()->with('error', '默认设置初始化失败：' . $errorMessage);
        }
    }

    /**
     * 更新系统版本
     */
    public function updateVersion(Request $request)
    {
        $request->validate([
            'version' => 'required|string|max:20',
            'release_date' => 'required|date',
            'release_notes' => 'nullable|string|max:1000',
        ]);

        DB::beginTransaction();
        try {
            // 更新版本号
            SystemSetting::set(
                'system_version',
                $request->input('version'),
                'string',
                '系统版本号',
                true
            );

            // 更新发布日期
            SystemSetting::set(
                'system_release_date',
                $request->input('release_date'),
                'string',
                '系统发布日期',
                true
            );

            // 如果有发布说明，保存到版本历史
            if ($request->filled('release_notes')) {
                SystemSetting::set(
                    'version_notes_' . str_replace('.', '_', $request->input('version')),
                    $request->input('release_notes'),
                    'text',
                    "版本 {$request->input('version')} 发布说明",
                    false
                );
            }

            DB::commit();
            
            $message = '系统版本更新成功';
            
            // 如果是AJAX请求，返回JSON响应
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'version' => $request->input('version'),
                    'release_date' => $request->input('release_date')
                ]);
            }
            
            return back()->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();
            $errorMessage = $e->getMessage();
            if (empty($errorMessage)) {
                $errorMessage = '版本更新失败';
            }
            
            // 如果是AJAX请求，返回JSON响应
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage
                ]);
            }
            
            return back()->with('error', '版本更新失败：' . $errorMessage);
        }
    }

    /**
     * 获取版本历史
     */
    public function getVersionHistory()
    {
        $versionSettings = SystemSetting::where('key', 'like', 'version_notes_%')
            ->orderBy('key', 'desc')
            ->get();

        $versionHistory = [];
        foreach ($versionSettings as $setting) {
            $version = str_replace(['version_notes_', '_'], ['.', '.'], $setting->key);
            $versionHistory[] = [
                'version' => $version,
                'notes' => $setting->value,
                'created_at' => $setting->created_at->format('Y-m-d H:i:s')
            ];
        }

       return response()->json($versionHistory);
    }

    /**
     * 通知规则配置页面
     */
    public function notificationRules()
    {
        return view('system-settings.notification-rules');
    }


    /**
     * 获取通知规则
     */
    public function getNotificationRules()
    {
        return response()->json([
            'success' => true,
            'rules' => \App\Services\Notification\NotificationDispatcher::getRules(),
            'events' => \App\Services\Notification\NotificationDispatcher::getEventLabels(),
        ]);
    }

    /**
     * 更新通知规则
     */
    public function updateNotificationRules(Request $request)
    {
        if (!Auth::user() || !Auth::user()->isAdmin()) {
            return response()->json(['success' => false, 'message' => '无权操作'], 403);
        }

        $rules = $request->input('rules', []);
        \App\Services\Notification\NotificationDispatcher::updateRules($rules);

        return response()->json(['success' => true, 'message' => '通知规则已更新']);
    }

    /**
     * 测试短信发送
     */
    public function testSms(Request $request)
    {
        if (!Auth::user() || !Auth::user()->isAdmin()) {
            return response()->json(['success' => false, 'message' => '无权操作'], 403);
        }

        $request->validate(['phone' => 'required|string']);

        $sms = app(\App\Services\Sms\SmsManager::class);
        $result = $sms->send($request->input('phone'), 'SMS_TEST', [
            'content' => '【测试】这是一条来自工单系统的测试短信',
        ]);

        return response()->json($result);
    }
    /**
     * 短信配置页面
     */
    public function sms()
    {
        if (!Auth::user() || !Auth::user()->isAdmin()) {
            return redirect()->route('system-settings.index')->with('error', '无权操作');
        }

        $smsSettings = [
            'enabled'    => (bool) SystemSetting::get('sms_enabled', false),
            'provider'   => SystemSetting::get('sms_provider', 'aliyun'),
            'sign_name'  => SystemSetting::get('sms_sign_name', ''),
            'access_key' => SystemSetting::get('sms_access_key', ''),
            'access_secret' => SystemSetting::get('sms_access_secret', ''),
            'sdk_app_id' => SystemSetting::get('sms_sdk_app_id', ''),
            'api_url'    => SystemSetting::get('sms_api_url', ''),
            'method'     => SystemSetting::get('sms_method', 'POST'),
            'api_key'    => SystemSetting::get('sms_api_key', ''),
        ];

        return view('system-settings.sms', compact('smsSettings'));
    }

    /**
     * 更新短信配置
     */
    public function updateSms(Request $request)
    {
        if (!Auth::user() || !Auth::user()->isAdmin()) {
            return redirect()->route('system-settings.index')->with('error', '无权操作');
        }

        $request->validate([
            'sms_provider'   => 'required|in:aliyun,tencent,custom',
            'sms_sign_name'  => 'nullable|string|max:100',
            'sms_access_key' => 'nullable|string|max:200',
            'sms_access_secret' => 'nullable|string|max:200',
            'sms_sdk_app_id' => 'nullable|string|max:100',
            'sms_api_url'    => 'nullable|string|max:500',
            'sms_method'     => 'nullable|in:GET,POST',
            'sms_api_key'    => 'nullable|string|max:200',
        ]);

        $fields = [
            'sms_provider'   => $request->input('sms_provider'),
            'sms_sign_name'  => $request->input('sms_sign_name'),
            'sms_access_key' => $request->input('sms_access_key'),
            'sms_access_secret' => $request->input('sms_access_secret'),
            'sms_sdk_app_id' => $request->input('sms_sdk_app_id'),
            'sms_api_url'    => $request->input('sms_api_url'),
            'sms_method'     => $request->input('sms_method', 'POST'),
            'sms_api_key'    => $request->input('sms_api_key'),
        ];

        foreach ($fields as $key => $value) {
            SystemSetting::set($key, $value, 'string');
        }

        return back()->with('success', '短信配置已保存');
    }

    /**
     * CAS / 统一身份认证配置页面
     */
    public function cas()
    {
        if (!Auth::user() || !Auth::user()->isAdmin()) {
            return redirect()->route('system-settings.index')->with('error', '无权操作');
        }

        $casSettings = [
            'enabled'    => (bool) SystemSetting::get('cas_enabled', false),
            'base_url'   => SystemSetting::get('cas_base_url', config('services.cas.base_url')),
            'service_id' => SystemSetting::get('cas_service_id', ''),
            'attr_username' => SystemSetting::get('cas_attr_username', 'uid'),
            'attr_name'  => SystemSetting::get('cas_attr_name', 'cn'),
            'attr_phone' => SystemSetting::get('cas_attr_phone', 'mobile'),
            'attr_email' => SystemSetting::get('cas_attr_email', 'mail'),
            'attr_department' => SystemSetting::get('cas_attr_department', 'department'),
        ];

        return view('system-settings.cas', compact('casSettings'));
    }

    /**
     * 更新 CAS 配置
     */
    public function updateCas(Request $request)
    {
        if (!Auth::user() || !Auth::user()->isAdmin()) {
            return redirect()->route('system-settings.index')->with('error', '无权操作');
        }

        $request->validate([
            'cas_base_url' => 'nullable|string|max:500',
            'cas_service_id' => 'nullable|string|max:200',
            'cas_attr_username' => 'required|string|max:50',
            'cas_attr_name'  => 'required|string|max:50',
            'cas_attr_phone' => 'nullable|string|max:50',
            'cas_attr_email' => 'nullable|string|max:50',
            'cas_attr_department' => 'nullable|string|max:50',
        ]);

        $fields = [
            'cas_base_url'   => $request->input('cas_base_url'),
            'cas_service_id' => $request->input('cas_service_id'),
            'cas_attr_username' => $request->input('cas_attr_username'),
            'cas_attr_name'  => $request->input('cas_attr_name'),
            'cas_attr_phone' => $request->input('cas_attr_phone'),
            'cas_attr_email' => $request->input('cas_attr_email'),
            'cas_attr_department' => $request->input('cas_attr_department'),
        ];

        foreach ($fields as $key => $value) {
            SystemSetting::set($key, $value, 'string');
        }

        // 启用/禁用
        $enabled = $request->boolean('cas_enabled');
        SystemSetting::set('cas_enabled', $enabled, 'boolean', '是否启用CAS统一身份认证', false);

        return back()->with('success', 'CAS认证配置已保存' . ($enabled ? '（已启用）' : '（未启用）'));
    }
}
