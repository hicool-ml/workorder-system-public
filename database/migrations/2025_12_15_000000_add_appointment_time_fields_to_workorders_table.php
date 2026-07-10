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
            // 添加预约开始时间和结束时间字段
            $table->dateTime('appointment_time_start')->nullable()->after('location_detail');
            $table->dateTime('appointment_time_end')->nullable()->after('appointment_time_start');
            
            // 为现有数据创建索引
            $table->index('appointment_time_start');
            $table->index('appointment_time_end');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('workorders', function (Blueprint $table) {
            $table->dropIndex(['appointment_time_start']);
            $table->dropIndex(['appointment_time_end']);
            $table->dropColumn(['appointment_time_start', 'appointment_time_end']);
        });
    }
};