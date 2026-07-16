<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * 添加企业微信 Webhook 通知配置
 */
return new class extends Migration
{
    public function up(): void
    {
        $settings = [
            [
                'key'         => 'wecom_webhook_enabled',
                'value'       => '0',
                'type'        => 'boolean',
                'description' => '是否启用企业微信群机器人通知',
                'is_public'   => false,
            ],
            [
                'key'         => 'wecom_webhook_url',
                'value'       => '',
                'type'        => 'string',
                'description' => '企业微信群机器人 Webhook 地址',
                'is_public'   => false,
            ],
        ];

        foreach ($settings as $s) {
            if (!DB::table('system_settings')->where('key', $s['key'])->exists()) {
                DB::table('system_settings')->insert(array_merge($s, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }
        }
    }

    public function down(): void
    {
        DB::table('system_settings')->whereIn('key', ['wecom_webhook_enabled', 'wecom_webhook_url'])->delete();
    }
};