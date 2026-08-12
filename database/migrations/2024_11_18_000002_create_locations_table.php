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
            $table->string('name'); // 地址名称（通用，如"A 楼"、"101 室"）
            $table->string('campus', 20); // 早期字段：旧部署可能保留区域代码，新部署建议通过 campuses 表关联
            $table->string('building_type', 30); // 建筑类型分类（可选）
            $table->string('building_code')->nullable(); // 楼栋编码（可选，便于报表引用）
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