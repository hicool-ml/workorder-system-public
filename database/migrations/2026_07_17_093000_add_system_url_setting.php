<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * 初始化系统访问地址设置项 system_url。
 *
 * 设置页表单引用了该 key，但此前缺少种子/迁移初始化，
 * 导致 SystemSettingController::update() 中 `if ($setting)` 被跳过，
 * 用户保存后值被静默丢弃。此处补建记录，使保存与读取都能正常工作。
 */
return new class extends Migration
{
    public function up(): void
    {
        // 仅在不存在时插入，避免覆盖已有值
        $exists = DB::table('system_settings')->where('key', 'system_url')->exists();
        if (!$exists) {
            DB::table('system_settings')->insert([
                'key'         => 'system_url',
                'value'       => '',
                'type'        => 'string',
                'description' => '系统访问地址，企业微信通知中的工单链接会使用此地址',
                'is_public'   => false,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('system_settings')->where('key', 'system_url')->delete();
    }
};
