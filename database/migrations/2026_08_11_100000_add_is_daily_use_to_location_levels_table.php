<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 地址层级增加「是否日常使用」标记。
 * true 表示日常层（校区/园区、楼栋、房间），工单/报表级联选择时展示；
 * false 表示基础地址层（省、市、区县、街道、门牌），初始化后固定存在，日常选择省略。
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('location_levels', 'is_daily_use')) {
            Schema::table('location_levels', function (Blueprint $table) {
                $table->boolean('is_daily_use')->default(false)->after('is_active')->comment('是否日常使用（工单级联展示）');
                $table->index('is_daily_use');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('location_levels', 'is_daily_use')) {
            Schema::table('location_levels', function (Blueprint $table) {
                $table->dropIndex(['is_daily_use']);
                $table->dropColumn('is_daily_use');
            });
        }
    }
};
