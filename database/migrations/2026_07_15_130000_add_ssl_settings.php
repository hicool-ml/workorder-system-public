<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * SSL 安全与 HTTPS 证书验证配置
 */
return new class extends Migration
{
    public function up(): void
    {
        $settings = [
            [
                'key'         => 'ssl_verify_enabled',
                'value'       => '1',
                'type'        => 'boolean',
                'description' => '是否启用 HTTPS SSL 证书验证',
                'is_public'   => false,
            ],
            [
                'key'         => 'ssl_cacert_path',
                'value'       => '',
                'type'        => 'string',
                'description' => '自定义 CA 证书路径（留空使用系统默认证书库）',
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
            'ssl_verify_enabled',
            'ssl_cacert_path',
        ])->delete();
    }
};
