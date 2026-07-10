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
        Schema::table('departments', function (Blueprint $table) {
            // 删除上级部门相关字段
            $table->dropForeign(['parent_id']);
            $table->dropColumn('parent_id');
            $table->dropColumn('level');
            $table->dropIndex(['parent_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->unsignedBigInteger('parent_id')->nullable()->comment('上级部门ID');
            $table->integer('level')->default(1)->comment('部门层级');
            
            $table->foreign('parent_id')->references('id')->on('departments')->onDelete('cascade');
            $table->index(['parent_id', 'status']);
        });
    }
};