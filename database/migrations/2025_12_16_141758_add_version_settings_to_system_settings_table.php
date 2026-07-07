<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 插入版本相关的系统设置
        DB::table('system_settings')->insertOrIgnore([
            [
                'key' => 'system_version',
                'value' => '2.0.0',
                'type' => 'string',
                'description' => '系统版本号',
                'is_public' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'system_release_date',
                'value' => '2025-12-16',
                'type' => 'string',
                'description' => '系统发布日期',
                'is_public' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'version_notes_2_0_0',
                'value' => '优化登录页面显示，避免重复系统名称；将第二个系统名称改为"系统登录"；提升用户体验和界面一致性；添加标准版本管理功能到系统设置模块',
                'type' => 'text',
                'description' => '版本 2.0.0 发布说明',
                'is_public' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 删除版本相关的系统设置
        DB::table('system_settings')
            ->whereIn('key', [
                'system_version',
                'system_release_date',
                'version_notes_2_0_0'
            ])
            ->delete();
    }
};
