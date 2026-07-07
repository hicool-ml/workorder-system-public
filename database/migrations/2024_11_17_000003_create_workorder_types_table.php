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
        Schema::create('workorder_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('工单类型名称');
            $table->string('code')->unique()->comment('类型编码');
            $table->text('description')->nullable()->comment('类型描述');
            $table->string('icon')->nullable()->comment('图标类名');
            $table->string('color')->nullable()->comment('颜色代码');
            $table->unsignedBigInteger('parent_id')->nullable()->comment('父级分类ID');
            $table->integer('level')->default(1)->comment('分类层级');
            $table->string('source')->nullable()->comment('来源渠道：电话、网络、现场等');
            $table->string('subcategory')->nullable()->comment('子类别：机房、多媒体教室、专项等');
            $table->integer('default_priority')->default(2)->comment('默认优先级：1-高，2-中，3-低');
            $table->integer('default_hours')->default(24)->comment('默认处理时限（小时）');
            $table->enum('status', ['active', 'inactive'])->default('active')->comment('状态');
            $table->integer('sort_order')->default(0)->comment('排序');
            $table->timestamps();
            
            $table->foreign('parent_id')->references('id')->on('workorder_types')->onDelete('cascade');
            $table->index(['parent_id', 'status']);
            $table->index(['source', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workorder_types');
    }
};