<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 将 locations 表改造为自引用地址树。
 * parent_id 指向上级节点，level_id 引用 location_levels 的层级定义。
 * 旧字段（campus_id / building_type 等）保留以兼容历史数据，新逻辑走树结构。
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('locations', 'parent_id')) {
            Schema::table('locations', function (Blueprint $table) {
                $table->unsignedBigInteger('parent_id')->nullable()->after('id')->comment('上级地址节点');
                $table->foreign('parent_id')->references('id')->on('locations')->nullOnDelete();
                $table->index('parent_id');
            });
        }

        if (!Schema::hasColumn('locations', 'level_id')) {
            Schema::table('locations', function (Blueprint $table) {
                $table->unsignedBigInteger('level_id')->nullable()->after('parent_id')->comment('地址层级定义ID');
                $table->foreign('level_id')->references('id')->on('location_levels')->nullOnDelete();
                $table->index('level_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('locations', 'parent_id')) {
            Schema::table('locations', function (Blueprint $table) {
                $table->dropForeign(['parent_id']);
                $table->dropIndex(['parent_id']);
                $table->dropColumn('parent_id');
            });
        }

        if (Schema::hasColumn('locations', 'level_id')) {
            Schema::table('locations', function (Blueprint $table) {
                $table->dropForeign(['level_id']);
                $table->dropIndex(['level_id']);
                $table->dropColumn('level_id');
            });
        }
    }
};
