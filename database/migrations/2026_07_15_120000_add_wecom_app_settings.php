<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * ????????????????
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
                'description' => '?????????webhook??????? app??????',
                'is_public'   => false,
            ],
            [
                'key'         => 'wecom_app_enabled',
                'value'       => '0',
                'type'        => 'boolean',
                'description' => '??????????????',
                'is_public'   => false,
            ],
            [
                'key'         => 'wecom_app_corpid',
                'value'       => '',
                'type'        => 'string',
                'description' => '??????ID?CorpID?',
                'is_public'   => false,
            ],
            [
                'key'         => 'wecom_app_secret',
                'value'       => '',
                'type'        => 'string',
                'description' => '????????Secret',
                'is_public'   => false,
            ],
            [
                'key'         => 'wecom_app_agentid',
                'value'       => '',
                'type'        => 'string',
                'description' => '????????AgentID',
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
