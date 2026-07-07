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
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // 地址名称，如"老校区1-7教"
            $table->string('campus', 20); // 校区：old_campus, new_campus, asean_campus
            $table->string('building_type', 30); // 建筑类型：teaching_building, dormitory, office_building, etc.
            $table->string('building_code')->nullable(); // 楼栋代码，如1-7, 8-14, A-J
            $table->text('description')->nullable(); // 描述
            $table->integer('sort_order')->default(0); // 排序
            $table->string('status', 20)->default('active'); // 状态
            $table->timestamps();
            
            $table->index(['campus', 'building_type']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};