<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 将 workorder_sources 表对齐到 WorkorderSource 模型 / 控制器 / 视图所期望的结构。
 *
 * 历史迁移（2025_12_17_093000）移除了 description，且建表迁移从未创建 is_active，
 * 但模型、控制器与 blade 视图均依赖 is_active(布尔) 与 description(字符串)，
 * 导致来源管理全部报错，并因 WorkorderController::store/update 调用
 * WorkorderSource::getActiveSourceCodes() 而连带阻断工单创建/编辑流程。
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('workorder_sources', 'description')) {
            Schema::table('workorder_sources', function (Blueprint $table) {
                $table->string('description', 200)->nullable()->after('name');
            });
        }

        if (!Schema::hasColumn('workorder_sources', 'is_active')) {
            Schema::table('workorder_sources', function (Blueprint $table) {
                $table->boolean('is_active')->default(true)->after('description');
            });

            // 由历史 status 列回填 is_active
            if (Schema::hasColumn('workorder_sources', 'status')) {
                DB::table('workorder_sources')
                    ->where('status', 'inactive')
                    ->update(['is_active' => false]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('workorder_sources', 'is_active')) {
            Schema::table('workorder_sources', function (Blueprint $table) {
                $table->dropColumn('is_active');
            });
        }

        if (Schema::hasColumn('workorder_sources', 'description')) {
            Schema::table('workorder_sources', function (Blueprint $table) {
                $table->dropColumn('description');
            });
        }
    }
};
