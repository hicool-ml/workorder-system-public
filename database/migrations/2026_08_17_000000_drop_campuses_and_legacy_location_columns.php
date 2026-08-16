<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 地址通用化收尾：删除旧「校区（campuses）」双轨制与 locations 冗余列。
 *
 * 背景：早期地址仅三级（校区/楼栋/门牌），后引入物流地址规范，
 * 校区信息已并入 locations 树（经 location_levels 的日常层级表达）。
 * campuses 表、locations.campus_id / building_type / building_code 自此废弃。
 *
 * 注意：破坏性迁移，down() 不恢复数据（原始校区数据已并入 locations 树，无法反推）。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            if (Schema::hasColumn('locations', 'campus_id')) {
                // 先删指向 campuses 的外键（若存在），再删列
                try {
                    $table->dropForeign(['campus_id']);
                } catch (\Throwable $e) {
                    // 外键可能不存在（SQLite 或历史差异），忽略
                }
                $table->dropColumn('campus_id');
            }
            if (Schema::hasColumn('locations', 'building_type')) {
                $table->dropColumn('building_type');
            }
            if (Schema::hasColumn('locations', 'building_code')) {
                $table->dropColumn('building_code');
            }
        });

        Schema::dropIfExists('campuses');
    }

    public function down(): void
    {
        // 无操作：campuses 表与 locations 冗余列的历史数据无法从 locations 树可靠反推。
        // 如需回退，应从迁移前的数据库备份恢复。
    }
};
