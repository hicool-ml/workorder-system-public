<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * SSL ??? HTTPS ????
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
                'description' => '???? HTTPS SSL ????',
                'is_public'   => false,
            ],
            [
                'key'         => 'ssl_cacert_path',
                'value'       => '',
                'type'        => 'string',
                'description' => '??? CA ??????????????????????',
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
