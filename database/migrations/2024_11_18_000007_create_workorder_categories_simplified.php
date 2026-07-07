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
        Schema::create('workorder_categories_simplified', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('分类名称');
            $table->string('parent_id')->nullable()->comment('父分类ID');
            $table->string('ticket_prefix')->default('WO')->comment('工单编号前缀');
            $table->integer('default_hours')->default(24)->comment('默认处理时限（小时）');
            $table->string('color')->default('#6c757d')->comment('显示颜色');
            $table->text('description')->nullable()->comment('分类描述');
            $table->integer('sort_order')->default(0)->comment('排序顺序');
            $table->boolean('status')->default(true)->comment('状态：1-启用，0-禁用');
            $table->timestamps();
            
            // 添加索引
            $table->index('parent_id');
            $table->index('status');
            $table->index('sort_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workorder_categories_simplified');
    }
};