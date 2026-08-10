<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 地址层级定义表 —— 用户自主定义分级方案。
 *
 * 例如城市级：市→区→街道→社区→路→号
 * 例如园区级：园区→楼栋→楼层→房间
 * 每条记录定义一个层级，locations 表的节点通过 level_id 引用。
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('location_levels')) {
            Schema::create('location_levels', function (Blueprint $table) {
                $table->id();
                $table->string('name', 50)->comment('层级名称，如：市、区、街道、楼栋');
                $table->string('code', 30)->unique()->comment('层级代码，程序识别用');
                $table->unsignedSmallInteger('level')->comment('层级深度，1 为最顶层');
                $table->string('description')->nullable()->comment('层级描述');
                $table->integer('sort_order')->default(0)->comment('排序');
                $table->boolean('is_active')->default(true)->comment('是否启用');
                $table->timestamps();

                $table->index(['is_active', 'sort_order']);
                $table->index('level');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('location_levels');
    }
};
