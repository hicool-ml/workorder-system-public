<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\SystemSetting;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 添加短信配置到系统设置表
        $smsSettings = [
            [
                'key' => 'sms_enabled',
                'value' => '0',
                'type' => 'boolean',
                'description' => '是否启用短信通知',
                'is_public' => false,
            ],
            [
                'key' => 'sms_provider',
                'value' => 'aliyun',
                'type' => 'string',
                'description' => '短信服务提供商 (aliyun/tencent/custom)',
                'is_public' => false,
            ],
            [
                'key' => 'sms_access_key',
                'value' => '',
                'type' => 'string',
                'description' => '短信服务Access Key ID',
                'is_public' => false,
            ],
            [
                'key' => 'sms_access_secret',
                'value' => '',
                'type' => 'string',
                'description' => '短信服务Access Key Secret',
                'is_public' => false,
            ],
            [
                'key' => 'sms_sign_name',
                'value' => '',
                'type' => 'string',
                'description' => '短信签名',
                'is_public' => false,
            ],
            [
                'key' => 'sms_template_codes',
                'value' => '{}',
                'type' => 'json',
                'description' => '短信模板代码 (JSON格式)',
                'is_public' => false,
            ],
            [
                'key' => 'sms_test_phone',
                'value' => '',
                'type' => 'string',
                'description' => '测试手机号',
                'is_public' => false,
            ],
            [
                'key' => 'sms_daily_limit',
                'value' => '100',
                'type' => 'integer',
                'description' => '每日短信发送限制',
                'is_public' => false,
            ],
            [
                'key' => 'sms_notification_types',
                'value' => '[]',
                'type' => 'json',
                'description' => '启用短信通知的类型 (JSON数组)',
                'is_public' => false,
            ],
        ];

        foreach ($smsSettings as $setting) {
            SystemSetting::firstOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 删除短信相关配置
        $smsKeys = [
            'sms_enabled',
            'sms_provider',
            'sms_access_key',
            'sms_access_secret',
            'sms_sign_name',
            'sms_template_codes',
            'sms_test_phone',
            'sms_daily_limit',
            'sms_notification_types',
        ];

        SystemSetting::whereIn('key', $smsKeys)->delete();
    }
};
