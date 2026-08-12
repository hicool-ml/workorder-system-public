<?php

namespace App\Http\Controllers;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class SystemSettingController extends Controller
{
    /**
     * 系统设置列表页面
     */
        /**
     * 系统设置列表页面（旧版单页，保留兼容；新版拆分为 settings.* 子页）
     */
    public function index()
    {
        return view('system-settings.index', $this->settingsViewData());
    }

    /**
     * 新版"设置"拆分子页：注册设置 / 系统设置 / 版本管理 / 备份恢复 / 消息设置 / 详细设置
     */
    public function page(string $section)
    {
        $map = [
            'system'       => 'settings.system',
            'version'      => 'settings.version',
            'backup'       => 'settings.backup',
            'messaging'    => 'settings.messaging',
            'all'          => 'settings.all',
        ];
        abort_unless(isset($map[$section]), 404);
        return view($map[$section], $this->settingsViewData());
    }

    /**
     * 设置页面共享数据：所有设置项 + 按类别分组
     */
    private function settingsViewData(): array
    {
        $settings = SystemSetting::orderBy('key')->get();

        $groupedSettings = [
            'registration' => $settings->filter(fn($s) => str_contains($s->key, 'registration')),
            'user' => $settings->filter(fn($s) => str_contains($s->key, 'user')),
            'system' => $settings->filter(fn($s) => str_contains($s->key, 'system') || $s->key === 'session_lifetime'),
            'version' => $settings->filter(fn($s) => in_array($s->key, ['system_version', 'system_release_date'])),
            'other' => $settings->filter(fn($s) => !in_array($s->key, [
                'registration_enabled', 'default_user_role', 'require_email_verification', 'system_name', 'system_version', 'system_release_date'
            ])),
        ];

        // 详细设置：按类别分组并按定义顺序排序（未归类项追加到"其他配置"）
        $categoryKeys = [
            '基础设置' => [
                'system_name', 'system_url', 'session_lifetime',
                'registration_enabled', 'require_email_verification', 'default_user_role',
                'require_user_completion_confirm',
            ],
            '版本信息' => [
                'system_version', 'system_release_date',
            ],
            '短信通知' => [
                'sms_enabled', 'sms_provider', 'sms_method', 'sms_api_url',
                'sms_api_key', 'sms_access_key', 'sms_access_secret', 'sms_sdk_app_id',
                'sms_sign_name', 'sms_template_codes',
                'sms_creator_acceptance_tpl_no_appt', 'sms_creator_acceptance_tpl_with_appt', 'sms_creator_survey_tpl',
                'creator_sms_enabled', 'creator_survey_enabled',
                'sms_test_phone', 'sms_daily_limit', 'sms_notification_types',
            ],
            '企业微信' => [
                'wecom_send_mode',
                'wecom_webhook_enabled', 'wecom_webhook_url',
                'wecom_app_enabled', 'wecom_app_corpid', 'wecom_app_secret', 'wecom_app_agentid',
            ],
            'SSL 安全' => [
                'ssl_verify_enabled', 'ssl_cacert_path',
            ],
            '其他配置' => [
                'notification_rules', 'campus_mapping',
            ],
        ];

        $categorizedSettings = [];
        $mappedKeys = [];
        foreach ($categoryKeys as $label => $keys) {
            $items = [];
            foreach ($keys as $key) {
                $item = $settings->firstWhere('key', $key);
                if ($item) {
                    $items[] = $item;
                    $mappedKeys[] = $key;
                }
            }
            if (!empty($items)) {
                $categorizedSettings[$label] = collect($items);
            }
        }

        $leftover = $settings->filter(fn($s) => !in_array($s->key, $mappedKeys));
        if ($leftover->isNotEmpty()) {
            if (isset($categorizedSettings['其他配置'])) {
                $categorizedSettings['其他配置'] = $categorizedSettings['其他配置']->merge($leftover);
            } else {
                $categorizedSettings['其他配置'] = $leftover;
            }
        }

        return compact('groupedSettings', 'settings', 'categorizedSettings');
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
                // firstOrCreate：若该 key 尚未在表中初始化（如 system_url），则自动建立记录，
                // 否则表单提交会被静默丢弃，表现为"保存无效"。
                $setting = SystemSetting::firstOrCreate(
                    ['key' => $key],
                    [
                        'value' => '',
                        'type' => 'string',
                        'description' => '系统设置 - ' . $key,
                        'is_public' => false,
                    ]
                );
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
            'release_notes' => 'required|string|max:1000',
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

            // 保存发布说明到版本历史（必填）
            SystemSetting::set(
                'version_notes_' . str_replace('.', '_', $request->input('version')),
                $request->input('release_notes'),
                'text',
                "版本 {$request->input('version')} 发布说明",
                false
            );

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
            $version = \Illuminate\Support\Str::after($setting->key, 'version_notes_');
            $version = str_replace('_', '.', $version);
            $versionHistory[] = [
                'version' => $version,
                'notes' => $setting->value,
                'created_at' => $setting->created_at->format('Y-m-d H:i:s')
            ];
        }

       return response()->json($versionHistory);
    }

    /**
     * 删除单条版本历史记录
     */
    public function deleteVersionHistory(Request $request)
    {
        if (!Auth::user() || !Auth::user()->isAdmin()) {
            return response()->json(['success' => false, 'message' => '无权操作'], 403);
        }

        $request->validate(['version' => 'required|string|max:20']);

        $key = 'version_notes_' . str_replace('.', '_', $request->input('version'));
        SystemSetting::where('key', $key)->delete();

        return response()->json(['success' => true, 'message' => '版本记录已删除']);
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

            // 报修人短信开关
            'creator_sms_enabled'    => (bool) SystemSetting::get('creator_sms_enabled', false),
            'creator_survey_enabled' => (bool) SystemSetting::get('creator_survey_enabled', false),

            // 报修人短信模板
            'tpl_acceptance_with_appt' => SystemSetting::get('sms_creator_acceptance_tpl_with_appt',
                "【{系统名称}】您的报修已受理，工程师\"{工程师电话}\"预计{预约时间}上门为您服务。"),
            'tpl_acceptance_no_appt' => SystemSetting::get('sms_creator_acceptance_tpl_no_appt',
                "【{系统名称}】您的报修已受理，请保持电话畅通，便于工程师\"{工程师电话}\"能联系到您并为您服务。"),
            'tpl_survey' => SystemSetting::get('sms_creator_survey_tpl',
                "【{系统名称}】您的报修服务已完成，请对本次服务进行评价：满意回复 1，不满意回复 0。"),
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

        $fieldDescriptions = [
            'sms_provider'      => '短信服务提供商（aliyun/tencent/custom）',
            'sms_sign_name'     => '短信签名',
            'sms_access_key'    => '短信服务 Access Key ID',
            'sms_access_secret' => '短信服务 Access Key Secret',
            'sms_sdk_app_id'    => '短信服务 SDK AppID（腾讯云等使用）',
            'sms_api_url'       => '短信服务商自定义接口地址',
            'sms_method'        => '短信接口请求方式（GET/POST）',
            'sms_api_key'       => '短信服务商 API 密钥',
        ];

        foreach ($fields as $key => $value) {
            SystemSetting::set($key, $value, 'string', $fieldDescriptions[$key] ?? null);
        }

        // 报修人短信开关
        SystemSetting::set('creator_sms_enabled', $request->boolean('creator_sms_enabled') ? '1' : '0', 'boolean', '报修人受理短信开关', false);
        SystemSetting::set('creator_survey_enabled', $request->boolean('creator_survey_enabled') ? '1' : '0', 'boolean', '报修人满意度调查开关', false);

        // 报修人短信模板（支持 {系统名称} {工程师电话} {预约时间} {工单编号} 占位符）
        SystemSetting::set('sms_creator_acceptance_tpl_with_appt', $request->input('tpl_acceptance_with_appt', ''), 'text', '受理短信模板（有预约）', false);
        SystemSetting::set('sms_creator_acceptance_tpl_no_appt', $request->input('tpl_acceptance_no_appt', ''), 'text', '受理短信模板（无预约）', false);
        SystemSetting::set('sms_creator_survey_tpl', $request->input('tpl_survey', ''), 'text', '满意度调查短信模板', false);

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
            'base_url'   => SystemSetting::get('cas_base_url', ''),
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
    /**
     * OIDC / OAuth2 统一身份认证配置页面
     */
    public function oidc()
    {
        if (!Auth::user() || !Auth::user()->isAdmin()) {
            return redirect()->route('system-settings.index')->with('error', '无权操作');
        }

        $oidcSettings = [
            'enabled'             => (bool) SystemSetting::get('oidc_enabled', false),
            'issuer'              => SystemSetting::get('oidc_issuer', ''),
            'client_id'           => SystemSetting::get('oidc_client_id', ''),
            'client_secret'       => SystemSetting::get('oidc_client_secret', ''),
            'scope'               => SystemSetting::get('oidc_scope', 'openid profile email'),
            'authorize_endpoint'  => SystemSetting::get('oidc_authorize_endpoint', ''),
            'token_endpoint'      => SystemSetting::get('oidc_token_endpoint', ''),
            'userinfo_endpoint'   => SystemSetting::get('oidc_userinfo_endpoint', ''),
            'end_session_endpoint' => SystemSetting::get('oidc_end_session_endpoint', ''),
        ];

        return view('system-settings.oidc', compact('oidcSettings'));
    }

    /**
     * 更新 OIDC 配置
     */
    public function updateOidc(Request $request)
    {
        if (!Auth::user() || !Auth::user()->isAdmin()) {
            return redirect()->route('system-settings.index')->with('error', '无权操作');
        }

        $request->validate([
            'oidc_issuer'               => 'nullable|string|max:500',
            'oidc_client_id'            => 'nullable|string|max:200',
            'oidc_client_secret'        => 'nullable|string|max:500',
            'oidc_scope'                => 'nullable|string|max:200',
            'oidc_authorize_endpoint'   => 'nullable|string|max:500',
            'oidc_token_endpoint'       => 'nullable|string|max:500',
            'oidc_userinfo_endpoint'    => 'nullable|string|max:500',
            'oidc_end_session_endpoint' => 'nullable|string|max:500',
        ]);

        $fields = [
            'oidc_issuer'              => $request->input('oidc_issuer'),
            'oidc_client_id'           => $request->input('oidc_client_id'),
            'oidc_client_secret'       => $request->input('oidc_client_secret'),
            'oidc_scope'               => $request->input('oidc_scope') ?: 'openid profile email',
            'oidc_authorize_endpoint'  => $request->input('oidc_authorize_endpoint'),
            'oidc_token_endpoint'      => $request->input('oidc_token_endpoint'),
            'oidc_userinfo_endpoint'   => $request->input('oidc_userinfo_endpoint'),
            'oidc_end_session_endpoint' => $request->input('oidc_end_session_endpoint'),
        ];

        foreach ($fields as $key => $value) {
            SystemSetting::set($key, $value, 'string');
        }

        // 清除 Discovery 缓存，使配置变更后重新发现
        Cache::forget('oidc_discovery');

        // 启用/禁用
        $enabled = $request->boolean('oidc_enabled');
        SystemSetting::set('oidc_enabled', $enabled, 'boolean', '是否启用OIDC统一身份认证', false);

        return back()->with('success', 'OIDC认证配置已保存' . ($enabled ? '（已启用）' : '（未启用）'));
    }

    /**
     * 微信公众号 OAuth 登录配置页面
     */
    public function wechatOauth()
    {
        if (!Auth::user() || !Auth::user()->isAdmin()) {
            return redirect()->route('system-settings.index')->with('error', '无权操作');
        }

        $wechatOauthSettings = [
            'enabled' => (bool) SystemSetting::get('wechat_oauth_enabled', false),
            'appid'   => SystemSetting::get('wechat_oauth_appid', ''),
            'secret'  => SystemSetting::get('wechat_oauth_secret', ''),
            'scope'   => SystemSetting::get('wechat_oauth_scope', 'snsapi_base'),
        ];

        return view('system-settings.wechat-oauth', compact('wechatOauthSettings'));
    }

    /**
     * 更新微信公众号 OAuth 登录配置
     */
    public function updateWechatOauth(Request $request)
    {
        if (!Auth::user() || !Auth::user()->isAdmin()) {
            return redirect()->route('system-settings.index')->with('error', '无权操作');
        }

        $request->validate([
            'wechat_oauth_appid'  => 'nullable|string|max:128',
            'wechat_oauth_secret' => 'nullable|string|max:200',
            'wechat_oauth_scope'  => 'nullable|string|max:50|in:snsapi_base,snsapi_userinfo',
        ]);

        SystemSetting::set('wechat_oauth_appid', trim($request->input('wechat_oauth_appid', '')), 'string', '微信公众号 AppID', false);
        SystemSetting::set('wechat_oauth_secret', trim($request->input('wechat_oauth_secret', '')), 'string', '微信公众号 AppSecret', false);
        SystemSetting::set('wechat_oauth_scope', $request->input('wechat_oauth_scope') ?: 'snsapi_base', 'string', '微信网页授权 scope', false);

        // 启用/禁用
        $enabled = $request->boolean('wechat_oauth_enabled');
        SystemSetting::set('wechat_oauth_enabled', $enabled, 'boolean', '是否启用微信登录', false);

        return back()->with('success', '微信登录配置已保存' . ($enabled ? '（已启用）' : '（未启用）'));
    }

    /**
     * 企业微信通知配置页面
     */
    public function wecom()
    {
        if (!Auth::user() || !Auth::user()->isAdmin()) {
            return redirect()->route('system-settings.index')->with('error', '无权操作');
        }

        $wecomSettings = [
            'send_mode'       => SystemSetting::get('wecom_send_mode', 'webhook'),
            'webhook_enabled' => filter_var(SystemSetting::get('wecom_webhook_enabled', '0'), FILTER_VALIDATE_BOOLEAN),
            'webhook_url'     => SystemSetting::get('wecom_webhook_url', ''),
            'app_enabled'     => filter_var(SystemSetting::get('wecom_app_enabled', '0'), FILTER_VALIDATE_BOOLEAN),
            'app_corpid'      => SystemSetting::get('wecom_app_corpid', ''),
            'app_secret'      => SystemSetting::get('wecom_app_secret', ''),
            'app_agentid'     => SystemSetting::get('wecom_app_agentid', ''),
            'ssl_verify_enabled' => filter_var(SystemSetting::get('ssl_verify_enabled', '1'), FILTER_VALIDATE_BOOLEAN),
            'ssl_cacert_path'    => SystemSetting::get('ssl_cacert_path', ''),
            'ssl_cacert_exists'  => file_exists(SystemSetting::get('ssl_cacert_path', '') ?: ''),
        ];

        return view('system-settings.wecom', compact('wecomSettings'));
    }

    /**
     * 更新企业微信通知配置（群机器人 / 自建应用）
     */
    public function updateWecom(Request $request)
    {
        if (!Auth::user() || !Auth::user()->isAdmin()) {
            return redirect()->route('system-settings.index')->with('error', '无权操作');
        }

        $mode = $request->input('wecom_send_mode', 'webhook');
        if (!in_array($mode, ['webhook', 'app'])) {
            $mode = 'webhook';
        }
        SystemSetting::set('wecom_send_mode', $mode, 'string', '企业微信推送模式', false);

        if ($mode === 'webhook') {
            $request->validate([
                'wecom_webhook_url' => 'nullable|string|max:500',
            ]);
            $url = trim($request->input('wecom_webhook_url', ''));
            $whEnabled = $request->boolean('wecom_webhook_enabled');
            if ($whEnabled && empty($url)) {
                return back()->withInput()->with('error', '启用前请先填写企业微信 Webhook 地址');
            }
            SystemSetting::set('wecom_webhook_url', $url, 'string', '企业微信群机器人 Webhook 地址', false);
            SystemSetting::set('wecom_webhook_enabled', $whEnabled, 'boolean', '是否启用企业微信群机器人通知', false);
            return back()->with('success', '企业微信配置已保存' . ($whEnabled ? '（已启用）' : '（未启用）'));
        }

        // 自建应用模式
        $request->validate([
            'wecom_app_corpid'   => 'nullable|string|max:200',
            'wecom_app_secret'   => 'nullable|string|max:200',
            'wecom_app_agentid'  => 'nullable|string|max:50',
        ]);
        $appEnabled = $request->boolean('wecom_app_enabled');
        $corpid = trim($request->input('wecom_app_corpid', ''));
        if ($appEnabled && empty($corpid)) {
            return back()->withInput()->with('error', '启用前请先填写企业ID（CorpID）');
        }
        SystemSetting::set('wecom_app_corpid', $corpid, 'string', '企业微信企业ID', false);
        SystemSetting::set('wecom_app_secret', trim($request->input('wecom_app_secret', '')), 'string', '企业微信自建应用Secret', false);
        SystemSetting::set('wecom_app_agentid', trim($request->input('wecom_app_agentid', '')), 'string', '企业微信自建应用AgentID', false);
        SystemSetting::set('wecom_app_enabled', $appEnabled, 'boolean', '是否启用企业微信自建应用通知', false);
        Cache::forget('wecom_app_access_token');
        return back()->with('success', '企业微信配置已保存' . ($appEnabled ? '（已启用）' : '（未启用）'));
    }

    /**
     * 发送企业微信测试消息
     */
    public function testWecom(Request $request)
    {
        if (!Auth::user() || !Auth::user()->isAdmin()) {
            return response()->json(['success' => false, 'message' => '无权操作'], 403);
        }

        $wecom = app(\App\Services\Notification\WeComWebhookService::class);
        $systemName = SystemSetting::get('system_name', '工单系统');

        $content = "【{$systemName}】测试通知\n"
            . "这是一条来自工单系统的测试消息。\n"
            . "收到此消息说明企业微信通知配置成功。";

        $mode = $request->input('wecom_send_mode', $wecom->getSendMode());

        // 检查当前推送通道是否已启用（与工单通知的 isEnabled() 检查一致）
        $enabled = $wecom->isEnabled();

        // 统一用 text 类型发送（纯文本），与工单通知格式一致
        $result = $wecom->sendText($content);

        // 即使测试发送成功，如果通道未启用也必须明确告知用户
        if ($result['success'] && !$enabled) {
            return response()->json([
                'success'         => false,
                'message'         => '测试消息已发送成功，但当前推送通道未启用，工单通知不会发送。请在上方勾选「启用」后再保存。',
                'test_sent'       => true,
                'channel_enabled' => false,
            ]);
        }

        return response()->json($result);
    }

    /**
     * 钉钉通知配置页面
     */
    public function dingtalk()
    {
        if (!Auth::user() || !Auth::user()->isAdmin()) {
            return redirect()->route('system-settings.index')->with('error', '无权操作');
        }

        $dingtalkSettings = [
            'send_mode'         => SystemSetting::get('dingtalk_send_mode', 'webhook'),
            'webhook_enabled'   => filter_var(SystemSetting::get('dingtalk_webhook_enabled', '0'), FILTER_VALIDATE_BOOLEAN),
            'webhook_url'       => SystemSetting::get('dingtalk_webhook_url', ''),
            'webhook_secret'    => SystemSetting::get('dingtalk_webhook_secret', ''),
            'app_enabled'       => filter_var(SystemSetting::get('dingtalk_app_enabled', '0'), FILTER_VALIDATE_BOOLEAN),
            'app_key'           => SystemSetting::get('dingtalk_app_key', ''),
            'app_secret'        => SystemSetting::get('dingtalk_app_secret', ''),
            'app_agentid'       => SystemSetting::get('dingtalk_app_agentid', ''),
        ];

        return view('system-settings.dingtalk', compact('dingtalkSettings'));
    }

    /**
     * 更新钉钉通知配置
     */
    public function updateDingtalk(Request $request)
    {
        if (!Auth::user() || !Auth::user()->isAdmin()) {
            return redirect()->route('system-settings.index')->with('error', '无权操作');
        }

        $mode = $request->input('dingtalk_send_mode', 'webhook');
        if (!in_array($mode, ['webhook', 'app'])) {
            $mode = 'webhook';
        }
        SystemSetting::set('dingtalk_send_mode', $mode, 'string', '钉钉推送模式', false);

        if ($mode === 'webhook') {
            $request->validate(['dingtalk_webhook_url' => 'nullable|string|max:500']);
            $url = trim($request->input('dingtalk_webhook_url', ''));
            $whEnabled = $request->boolean('dingtalk_webhook_enabled');
            if ($whEnabled && empty($url)) {
                return back()->withInput()->with('error', '启用前请先填写钉钉 Webhook 地址');
            }
            SystemSetting::set('dingtalk_webhook_url', $url, 'string', '钉钉自定义机器人 Webhook 地址', false);
            SystemSetting::set('dingtalk_webhook_secret', trim($request->input('dingtalk_webhook_secret', '')), 'string', '钉钉机器人加签 secret', false);
            SystemSetting::set('dingtalk_webhook_enabled', $whEnabled, 'boolean', '是否启用钉钉机器人通知', false);
            return back()->with('success', '钉钉配置已保存' . ($whEnabled ? '（已启用）' : '（未启用）'));
        }

        // 企业内部应用模式
        $request->validate([
            'dingtalk_app_key'     => 'nullable|string|max:200',
            'dingtalk_app_secret'  => 'nullable|string|max:200',
            'dingtalk_app_agentid' => 'nullable|string|max:50',
        ]);
        $appEnabled = $request->boolean('dingtalk_app_enabled');
        $appKey = trim($request->input('dingtalk_app_key', ''));
        if ($appEnabled && empty($appKey)) {
            return back()->withInput()->with('error', '启用前请先填写钉钉 AppKey');
        }
        SystemSetting::set('dingtalk_app_key', $appKey, 'string', '钉钉应用 AppKey', false);
        SystemSetting::set('dingtalk_app_secret', trim($request->input('dingtalk_app_secret', '')), 'string', '钉钉应用 AppSecret', false);
        SystemSetting::set('dingtalk_app_agentid', trim($request->input('dingtalk_app_agentid', '')), 'string', '钉钉应用 AgentId', false);
        SystemSetting::set('dingtalk_app_enabled', $appEnabled, 'boolean', '是否启用钉钉工作通知', false);
        Cache::forget('dingtalk_app_access_token');
        return back()->with('success', '钉钉配置已保存' . ($appEnabled ? '（已启用）' : '（未启用）'));
    }

    /**
     * 发送钉钉测试消息
     */
    public function testDingtalk(Request $request)
    {
        if (!Auth::user() || !Auth::user()->isAdmin()) {
            return response()->json(['success' => false, 'message' => '无权操作'], 403);
        }

        $dingtalk = app(\App\Services\Notification\DingTalkService::class);
        $systemName = SystemSetting::get('system_name', '工单系统');
        $content = "【{$systemName}】测试通知\n这是一条来自工单系统的钉钉测试消息。\n收到此消息说明钉钉通知配置成功。";

        $enabled = $dingtalk->isEnabled();
        $result = $dingtalk->sendText($content);

        if ($result['success'] && !$enabled) {
            return response()->json([
                'success'         => false,
                'message'         => '测试消息已发送成功，但当前推送通道未启用，工单通知不会发送。请在上方勾选「启用」后再保存。',
                'test_sent'       => true,
                'channel_enabled' => false,
            ]);
        }

        return response()->json($result);
    }

    /**
     * 飞书通知配置页面
     */
    public function feishu()
    {
        if (!Auth::user() || !Auth::user()->isAdmin()) {
            return redirect()->route('system-settings.index')->with('error', '无权操作');
        }

        $feishuSettings = [
            'send_mode'         => SystemSetting::get('feishu_send_mode', 'webhook'),
            'webhook_enabled'   => filter_var(SystemSetting::get('feishu_webhook_enabled', '0'), FILTER_VALIDATE_BOOLEAN),
            'webhook_url'       => SystemSetting::get('feishu_webhook_url', ''),
            'webhook_secret'    => SystemSetting::get('feishu_webhook_secret', ''),
            'app_enabled'       => filter_var(SystemSetting::get('feishu_app_enabled', '0'), FILTER_VALIDATE_BOOLEAN),
            'app_id'            => SystemSetting::get('feishu_app_id', ''),
            'app_secret'        => SystemSetting::get('feishu_app_secret', ''),
        ];

        return view('system-settings.feishu', compact('feishuSettings'));
    }

    /**
     * 更新飞书通知配置
     */
    public function updateFeishu(Request $request)
    {
        if (!Auth::user() || !Auth::user()->isAdmin()) {
            return redirect()->route('system-settings.index')->with('error', '无权操作');
        }

        $mode = $request->input('feishu_send_mode', 'webhook');
        if (!in_array($mode, ['webhook', 'app'])) {
            $mode = 'webhook';
        }
        SystemSetting::set('feishu_send_mode', $mode, 'string', '飞书推送模式', false);

        if ($mode === 'webhook') {
            $request->validate(['feishu_webhook_url' => 'nullable|string|max:500']);
            $url = trim($request->input('feishu_webhook_url', ''));
            $whEnabled = $request->boolean('feishu_webhook_enabled');
            if ($whEnabled && empty($url)) {
                return back()->withInput()->with('error', '启用前请先填写飞书 Webhook 地址');
            }
            SystemSetting::set('feishu_webhook_url', $url, 'string', '飞书自定义机器人 Webhook 地址', false);
            SystemSetting::set('feishu_webhook_secret', trim($request->input('feishu_webhook_secret', '')), 'string', '飞书机器人加签 secret', false);
            SystemSetting::set('feishu_webhook_enabled', $whEnabled, 'boolean', '是否启用飞书机器人通知', false);
            return back()->with('success', '飞书配置已保存' . ($whEnabled ? '（已启用）' : '（未启用）'));
        }

        // 自建应用模式
        $request->validate([
            'feishu_app_id'     => 'nullable|string|max:200',
            'feishu_app_secret' => 'nullable|string|max:200',
        ]);
        $appEnabled = $request->boolean('feishu_app_enabled');
        $appId = trim($request->input('feishu_app_id', ''));
        if ($appEnabled && empty($appId)) {
            return back()->withInput()->with('error', '启用前请先填写飞书 App ID');
        }
        SystemSetting::set('feishu_app_id', $appId, 'string', '飞书自建应用 App ID', false);
        SystemSetting::set('feishu_app_secret', trim($request->input('feishu_app_secret', '')), 'string', '飞书自建应用 App Secret', false);
        SystemSetting::set('feishu_app_enabled', $appEnabled, 'boolean', '是否启用飞书自建应用通知', false);
        Cache::forget('feishu_tenant_access_token');
        return back()->with('success', '飞书配置已保存' . ($appEnabled ? '（已启用）' : '（未启用）'));
    }

    /**
     * 发送飞书测试消息
     */
    public function testFeishu(Request $request)
    {
        if (!Auth::user() || !Auth::user()->isAdmin()) {
            return response()->json(['success' => false, 'message' => '无权操作'], 403);
        }

        $feishu = app(\App\Services\Notification\FeishuService::class);
        $systemName = SystemSetting::get('system_name', '工单系统');
        $content = "【{$systemName}】测试通知\n这是一条来自工单系统的飞书测试消息。\n收到此消息说明飞书通知配置成功。";

        $enabled = $feishu->isEnabled();
        $result = $feishu->sendText($content);

        if ($result['success'] && !$enabled) {
            return response()->json([
                'success'         => false,
                'message'         => '测试消息已发送成功，但当前推送通道未启用，工单通知不会发送。请在上方勾选「启用」后再保存。',
                'test_sent'       => true,
                'channel_enabled' => false,
            ]);
        }

        return response()->json($result);
    }

    /**
     * 上传 CA 证书文件
     */
    public function uploadCacert(Request $request)
    {
        if (!Auth::user() || !Auth::user()->isAdmin()) {
            return response()->json(['success' => false, 'message' => '无权操作'], 403);
        }

        $request->validate([
            'cacert_file' => 'required|file|max:1024',
        ]);

        $file = $request->file('cacert_file');
        $content = file_get_contents($file->getRealPath());

        // 校验是否为有效的 PEM 格式 CA 证书
        if (strpos($content, 'BEGIN CERTIFICATE') === false) {
            return response()->json(['success' => false, 'message' => '文件不是有效的 PEM 格式 CA 证书（缺少 BEGIN CERTIFICATE 标记）']);
        }

        // 存储到 storage/app/cacert.pem
        $path = storage_path('app/cacert.pem');
        file_put_contents($path, $content);

        SystemSetting::set('ssl_cacert_path', $path, 'string', '自定义 CA 证书路径', false);
        // 上传证书后自动开启 SSL 验证
        SystemSetting::set('ssl_verify_enabled', true, 'boolean', '是否启用 HTTPS SSL 证书验证', false);

        return response()->json([
            'success' => true,
            'message' => 'CA 证书上传成功，已自动启用 SSL 验证',
            'path' => $path,
        ]);
    }

    /**
     * 切换 SSL 验证开关
     */
    public function toggleSslVerify(Request $request)
    {
        if (!Auth::user() || !Auth::user()->isAdmin()) {
            return response()->json(['success' => false, 'message' => '无权操作'], 403);
        }

        $enabled = $request->boolean('enabled');
        SystemSetting::set('ssl_verify_enabled', $enabled, 'boolean', '是否启用 HTTPS SSL 证书验证', false);

        return response()->json([
            'success' => true,
            'message' => $enabled ? 'SSL 验证已开启' : 'SSL 验证已关闭（仅限测试环境）',
            'enabled' => $enabled,
        ]);
    }

    /**
     * 删除已上传的 CA 证书
     */
    public function deleteCacert(Request $request)
    {
        if (!Auth::user() || !Auth::user()->isAdmin()) {
            return response()->json(['success' => false, 'message' => '无权操作'], 403);
        }

        $path = SystemSetting::get('ssl_cacert_path', '');
        if (!empty($path) && file_exists($path)) {
            @unlink($path);
        }
        SystemSetting::set('ssl_cacert_path', '', 'string', '自定义 CA 证书路径', false);

        return response()->json(['success' => true, 'message' => 'CA 证书已删除，将使用系统默认配置']);
    }
}
