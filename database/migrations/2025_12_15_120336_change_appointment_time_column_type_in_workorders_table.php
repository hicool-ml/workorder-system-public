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
            // 将 appointment_time 字段从 datetime 改为 string，用于存储格式化的预约时间描述
            $table->string('appointment_time', 200)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('workorders', function (Blueprint $table) {
            // 恢复为 datetime 类型
            $table->dateTime('appointment_time')->nullable()->change();
        });
    }
};
