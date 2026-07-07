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
            // 添加缺失的字段（如果不存在）
            if (!Schema::hasColumn('workorders', 'failure_description')) {
                $table->text('failure_description')->nullable()->after('description')->comment('具体故障现象');
            }
            
            if (!Schema::hasColumn('workorders', 'department_name')) {
                $table->string('department_name')->nullable()->after('department_id')->comment('部门名称');
            }
            
            if (!Schema::hasColumn('workorders', 'campus')) {
                $table->string('campus')->nullable()->after('location_detail')->comment('校区');
            }
            
            if (!Schema::hasColumn('workorders', 'building')) {
                $table->string('building')->nullable()->after('campus')->comment('楼栋');
            }
            
            if (!Schema::hasColumn('workorders', 'appointment_time')) {
                $table->datetime('appointment_time')->nullable()->after('building')->comment('预约时间');
            }
            
            if (!Schema::hasColumn('workorders', 'phone_assisted')) {
                $table->boolean('phone_assisted')->default(false)->after('is_emergency')->comment('电话协助完成');
            }
            
            if (!Schema::hasColumn('workorders', 'processing_duration')) {
                $table->integer('processing_duration')->nullable()->after('expected_complete_at')->comment('处理时长(分钟)');
            }
            
            // 更新外键约束，指向简化的分类表
            if (Schema::hasColumn('workorders', 'category_id')) {
                // 先删除旧的外键约束
                $table->dropForeign(['category_id']);
                // 添加新的外键约束
                $table->foreign('category_id')->references('id')->on('workorder_categories_simplified')->onDelete('set null');
            }
            
            // 添加索引（如果不存在）
            if (Schema::hasColumn('workorders', 'campus') && !Schema::hasIndex('workorders', 'workorders_campus_index')) {
                $table->index(['campus']);
            }
            
            if (Schema::hasColumn('workorders', 'building') && !Schema::hasIndex('workorders', 'workorders_building_index')) {
                $table->index(['building']);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('workorders', function (Blueprint $table) {
            // 删除字段
            $table->dropColumn([
                'failure_description',
                'department_name',
                'campus',
                'building',
                'appointment_time',
                'phone_assisted',
                'processing_duration'
            ]);
            
            // 删除索引
            if (Schema::hasIndex('workorders', 'workorders_campus_index')) {
                $table->dropIndex(['campus']);
            }
            
            if (Schema::hasIndex('workorders', 'workorders_building_index')) {
                $table->dropIndex(['building']);
            }
        });
    }
};