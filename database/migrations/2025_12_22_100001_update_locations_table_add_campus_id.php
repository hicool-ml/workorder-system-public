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
        Schema::table('locations', function (Blueprint $table) {
            // 添加campus_id字段
            $table->unsignedBigInteger('campus_id')->nullable()->after('id')->comment('校区ID');
            
            // 添加索引
            $table->index('campus_id');
        });

        // 将现有的campus字段数据迁移到campus_id（通过校区代码匹配）
        DB::statement('UPDATE locations l JOIN campuses c ON l.campus = c.code SET l.campus_id = c.id WHERE l.campus IS NOT NULL AND l.campus != ""');
        
        // 添加外键约束
        Schema::table('locations', function (Blueprint $table) {
            $table->foreign('campus_id')->references('id')->on('campuses')->onDelete('cascade');
        });
        
        // 删除旧的campus字段
        Schema::table('locations', function (Blueprint $table) {
            $table->dropColumn('campus');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            // 添加回旧的campus字段
            $table->string('campus')->nullable()->comment('校区');
        });

        // 将campus_id数据迁移回campus字段
        DB::statement('UPDATE locations SET campus = campus_id WHERE campus_id IS NOT NULL');

        Schema::table('locations', function (Blueprint $table) {
            // 删除外键约束和索引
            $table->dropForeign(['campus_id']);
            $table->dropIndex(['campus_id']);
            
            // 删除campus_id字段
            $table->dropColumn('campus_id');
        });
    }
};