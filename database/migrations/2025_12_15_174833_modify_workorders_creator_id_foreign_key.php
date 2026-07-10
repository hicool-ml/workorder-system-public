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
            // 删除现有的外键约束
            $table->dropForeign('workorders_creator_id_foreign');
            
            // 重新创建外键约束，使用 SET NULL
            $table->foreign('creator_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null')
                  ->name('workorders_creator_id_foreign');
        });
        
        // 同时修改 creator_id 字段允许为 NULL
        Schema::table('workorders', function (Blueprint $table) {
            $table->unsignedBigInteger('creator_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('workorders', function (Blueprint $table) {
            // 删除现有的外键约束
            $table->dropForeign('workorders_creator_id_foreign');
            
            // 恢复原来的约束
            $table->foreign('creator_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('restrict')
                  ->name('workorders_creator_id_foreign');
        });
        
        // 恢复 creator_id 字段不允许为 NULL
        Schema::table('workorders', function (Blueprint $table) {
            $table->unsignedBigInteger('creator_id')->nullable(false)->change();
        });
    }
};
