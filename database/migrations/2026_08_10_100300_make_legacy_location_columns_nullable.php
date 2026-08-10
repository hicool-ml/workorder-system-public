<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 将 locations 表的旧字段（building_type 等）改为 nullable，
 * 使新的树结构数据（parent_id / level_id）可以不填这些旧列。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            if (Schema::hasColumn('locations', 'building_type')) {
                $table->string('building_type', 30)->nullable()->change();
            }
            if (Schema::hasColumn('locations', 'name')) {
                $table->string('name')->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        // 不回滚：旧字段保持 nullable 更安全
    }
};
