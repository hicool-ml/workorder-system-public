<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * 添加"需要用户确认完结"系统开关
 * 关闭时：工程师点击"已解决"后工单直接进入已完结状态
 * 开启时：工程师解决后工单等待用户确认才算完结
 */
return new class extends Migration
{
    public function up(): void
    {
        $exists = DB::table('system_settings')->where('key', 'require_user_completion_confirm')->exists();
        if (!$exists) {
            DB::table('system_settings')->insert([
                'key'         => 'require_user_completion_confirm',
                'value'       => '0',
                'type'        => 'boolean',
                'description' => '是否需要用户确认完结（关闭=工程师解决即完结，开启=需用户确认）',
                'is_public'   => false,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('system_settings')->where('key', 'require_user_completion_confirm')->delete();
    }
};