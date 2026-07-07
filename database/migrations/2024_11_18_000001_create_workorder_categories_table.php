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
        Schema::create('workorder_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('分类名称');
            $table->string('code')->unique()->comment('分类编码');
            $table->text('description')->nullable()->comment('分类描述');
            $table->unsignedBigInteger('parent_id')->nullable()->comment('父分类ID');
            $table->integer('level')->default(1)->comment('层级：1-一级，2-二级，3-三级');
            $table->integer('sort_order')->default(0)->comment('排序');
            $table->enum('status', ['active', 'inactive'])->default('active')->comment('状态');
            $table->timestamps();
            
            $table->foreign('parent_id')->references('id')->on('workorder_categories')->onDelete('cascade');
            $table->index(['parent_id', 'level', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workorder_categories');
    }
};