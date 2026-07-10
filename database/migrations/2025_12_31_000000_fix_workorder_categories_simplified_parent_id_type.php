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
        Schema::table('workorder_categories_simplified', function (Blueprint $table) {
            // 修改 parent_id 字段类型为 unsignedBigInteger
            $table->unsignedBigInteger('parent_id')->nullable()->change();
            
            // 添加外键约束
            $table->foreign('parent_id')
                ->references('id')
                ->on('workorder_categories_simplified')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('workorder_categories_simplified', function (Blueprint $table) {
            // 删除外键约束
            $table->dropForeign(['parent_id']);
            
            // 恢复为 string 类型
            $table->string('parent_id')->nullable()->change();
        });
    }
};
