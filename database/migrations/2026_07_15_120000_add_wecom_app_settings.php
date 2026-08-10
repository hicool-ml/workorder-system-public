<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * 添加企业微信自建应用通知配置
 */
return new class extends Migration
{
    public function up(): void
    {
        $settings = [
            [
                'key'         => 'wecom_send_mode',
                'value'       => 'webhook',
                'type'        => 'string',
                'description' => '企业微信推送模式（webhook/自建应用）',
                'is_public'   => false,
            ],
            [
                'key'         => 'wecom_app_enabled',
                'value'       => '0',
                'type'        => 'boolean',
                'description' => '是否启用企业微信自建应用通知',
                'is_public'   => false,
            ],
            [
                'key'         => 'wecom_app_corpid',
                'value'       => '',
                'type'        => 'string',
                'description' => '企业微信企业 ID（CorpID）',
                'is_public'   => false,
            ],
            [
                'key'         => 'wecom_app_secret',
                'value'       => '',
                'type'        => 'string',
                'description' => '企业微信自建应用 Secret',
                'is_public'   => false,
            ],
            [
                'key'         => 'wecom_app_agentid',
                'value'       => '',
                'type'        => 'string',
                'description' => '企业微信自建应用 AgentID',
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
        DB::table('system_settings')->whereIn('key', [
            'wecom_send_mode',
            'wecom_app_enabled',
            'wecom_app_corpid',
            'wecom_app_secret',
            'wecom_app_agentid',
        ])->delete();
    }
};
