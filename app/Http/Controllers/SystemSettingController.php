<?php

namespace App\Http\Controllers;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SystemSettingController extends Controller
{
    /**
     * 防御层：确保只有管理员可操作（不依赖路由中间件，防止路由重组后越权）
     */
    private function guardAdmin(): void
    {
        if (!Auth::user() || !Auth::user()->isAdmin()) {
            abort(403, '仅管理员可访问系统设置');
        }
    }

    /**
     * 系统设置列表页面（旧版单页，保留兼容；新版拆分为 settings.* 子页）
     */
    public function index()
    {
        $this->guardAdmin();
        return view('system-settings.index', $this->settingsViewData());
    }

    /**
     * 新版"设置"拆分子页：系统设置 / 版本管理 / 备份恢复 / 消息设置 / 全部
     */
    public function page(string $section)
    {
        $this->guardAdmin();

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
        $this->guardAdmin();

        $request->validate([
            'settings' => 'required|array',
            'settings.*' => 'required|string',
        ]);

        DB::beginTransaction();
        try {
            foreach ($request->input('settings') as $key => $value) {
                // 密钥类设置留空提交 = 保留原值（页面上旧值已隐藏，无法回显）
                if ($value === '' && SystemSetting::isSecretKeyString($key)) {
                    $existing = SystemSetting::where('key', $key)->first();
                    if ($existing && $existing->value !== '') {
                        continue;
                    }
                }
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
        $this->guardAdmin();

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
        $this->guardAdmin();

        $request->validate([
            'key' => 'required|string|max:100|unique:system_settings,key',
            'value' => 'required|string',
            'type' => 'required|in:string,boolean,integer,float,json,array',
            'description' => 'nullable|string|max:255',
            'is_public' => 'boolean',
        ]);

        try {
            // 密钥类设置禁止标记为公开（防 publicSettings 端点未来启用时泄露）
            $attributes = $request->all();
            if (SystemSetting::isSecretKeyString($request->input('key'))) {
                $attributes['is_public'] = false;
            }
            SystemSetting::create($attributes);

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
        $this->guardAdmin();

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
        $this->guardAdmin();

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
}
