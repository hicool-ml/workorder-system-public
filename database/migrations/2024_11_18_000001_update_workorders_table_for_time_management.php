<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('workorders', function (Blueprint $table) {
            // 添加实际处理时长字段（分钟）
            if (!Schema::hasColumn('workorders', 'processing_duration')) {
                $table->integer('processing_duration')->nullable()->comment('实际处理时长（分钟）');
            }
            
            // 修改created_at字段，允许管理员修改
            $table->datetime('created_at')->nullable()->change();
            
            // 添加索引（仅当字段存在时）
            if (Schema::hasColumn('workorders', 'appointment_time')) {
                $table->index(['appointment_time']);
            }
            if (Schema::hasColumn('workorders', 'ticket_prefix')) {
                $table->index(['ticket_prefix']);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('workorders', function (Blueprint $table) {
            // 删除索引（仅当索引存在时）
            if (Schema::hasIndex('workorders', 'workorders_appointment_time_index')) {
                $table->dropIndex(['appointment_time']);
            }
            if (Schema::hasIndex('workorders', 'workorders_ticket_prefix_index')) {
                $table->dropIndex(['ticket_prefix']);
            }
            
            if (Schema::hasColumn('workorders', 'processing_duration')) {
                $table->dropColumn('processing_duration');
            }
            
            // 恢复created_at字段为不可空
            $table->datetime('created_at')->nullable(false)->change();
        });
    }
};