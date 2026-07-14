<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 报表查询索引优化
 *
 * 为高频统计报表查询补充复合索引，覆盖以下典型查询模式：
 *  - WHERE status IN (...) AND created_at BETWEEN
 *  - WHERE category_id IN (...) AND created_at BETWEEN
 *  - WHERE campus_id = ? AND created_at BETWEEN
 *  - WHERE is_emergency = ? AND created_at BETWEEN
 *  - WHERE expected_complete_at < ? AND created_at BETWEEN
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workorders', function (Blueprint $table) {
            // 状态 + 时间范围：报表中最频繁的筛选维度
            $table->index(['status', 'created_at'], 'workorders_status_created_at_index');

            // 校区 + 时间范围：校区统计
            $table->index(['campus_id', 'created_at'], 'workorders_campus_created_at_index');

            // 二级分类 + 时间范围：分类占比树图/柱状图
            $table->index(['category_id', 'created_at'], 'workorders_category_created_at_index');

            // 紧急工单筛选
            $table->index(['is_emergency', 'created_at'], 'workorders_emergency_created_at_index');

            // 超时工单筛选
            $table->index(['expected_complete_at', 'status'], 'workorders_expected_status_index');
        });
    }

    public function down(): void
    {
        Schema::table('workorders', function (Blueprint $table) {
            $table->dropIndex('workorders_status_created_at_index');
            $table->dropIndex('workorders_campus_created_at_index');
            $table->dropIndex('workorders_category_created_at_index');
            $table->dropIndex('workorders_emergency_created_at_index');
            $table->dropIndex('workorders_expected_status_index');
        });
    }
};