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
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('部门名称');
            $table->string('code')->unique()->comment('部门编码');
            $table->unsignedBigInteger('parent_id')->nullable()->comment('上级部门ID');
            $table->integer('level')->default(1)->comment('部门层级');
            $table->string('manager_name')->nullable()->comment('部门负责人');
            $table->string('manager_phone')->nullable()->comment('负责人电话');
            $table->string('location')->nullable()->comment('办公地点');
            $table->text('description')->nullable()->comment('部门描述');
            $table->enum('status', ['active', 'inactive'])->default('active')->comment('状态');
            $table->integer('sort_order')->default(0)->comment('排序');
            $table->timestamps();
            
            $table->foreign('parent_id')->references('id')->on('departments')->onDelete('cascade');
            $table->index(['parent_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('departments');
    }
};