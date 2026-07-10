<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 更新工单来源数据，使其更有意义
        DB::table('workorder_sources')->updateOrInsert(
            ['code' => 'phone'],
            [
                'name' => '电话报修',
                'description' => '用户通过电话直接报修',
                'is_active' => true,
                'sort_order' => 1,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        DB::table('workorder_sources')->updateOrInsert(
            ['code' => 'web'],
            [
                'name' => '在线平台',
                'description' => '通过网站或APP在线提交报修',
                'is_active' => true,
                'sort_order' => 2,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        DB::table('workorder_sources')->updateOrInsert(
            ['code' => 'email'],
            [
                'name' => '邮件申请',
                'description' => '通过发送邮件申请维修服务',
                'is_active' => true,
                'sort_order' => 3,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        DB::table('workorder_sources')->updateOrInsert(
            ['code' => 'scene'],
            [
                'name' => '现场报修',
                'description' => '工作人员现场发现并记录的问题',
                'is_active' => true,
                'sort_order' => 4,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        // 删除无意义的旧来源
        DB::table('workorder_sources')->whereIn('code', ['wechat', 'custom'])->delete();

        // 添加新的有意义的来源
        DB::table('workorder_sources')->updateOrInsert(
            ['code' => 'inspection'],
            [
                'name' => '巡检发现',
                'description' => '定期巡检过程中发现的设备问题',
                'is_active' => true,
                'sort_order' => 5,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        DB::table('workorder_sources')->updateOrInsert(
            ['code' => 'system'],
            [
                'name' => '系统预警',
                'description' => '监控系统自动发出的预警信息',
                'is_active' => true,
                'sort_order' => 6,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        DB::table('workorder_sources')->updateOrInsert(
            ['code' => 'other'],
            [
                'name' => '其他来源',
                'description' => '除上述分类外的其他报修方式',
                'is_active' => true,
                'sort_order' => 7,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        // 更新工单表中的来源代码，将wechat和custom替换为other
        DB::table('workorders')
            ->whereIn('source', ['wechat', 'custom'])
            ->update(['source' => 'other']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 恢复旧的工单来源数据
        DB::table('workorder_sources')->updateOrInsert(
            ['code' => 'wechat'],
            [
                'name' => '微信',
                'description' => '通过微信报修',
                'is_active' => true,
                'sort_order' => 5,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        DB::table('workorder_sources')->updateOrInsert(
            ['code' => 'custom'],
            [
                'name' => '自定义',
                'description' => '用户自定义来源',
                'is_active' => true,
                'sort_order' => 7,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        // 删除新增的来源
        DB::table('workorder_sources')->whereIn('code', ['inspection', 'system'])->delete();

        // 恢复原始的名称和描述
        DB::table('workorder_sources')->where('code', 'phone')->update([
            'name' => '电话',
            'description' => '通过电话报修',
        ]);

        DB::table('workorder_sources')->where('code', 'web')->update([
            'name' => '网站',
            'description' => '通过网站报修',
        ]);

        DB::table('workorder_sources')->where('code', 'email')->update([
            'name' => '邮件',
            'description' => '通过邮件报修',
        ]);

        DB::table('workorder_sources')->where('code', 'scene')->update([
            'name' => '现场',
            'description' => '现场报修',
        ]);

        DB::table('workorder_sources')->where('code', 'other')->update([
            'name' => '其他',
            'description' => '其他方式报修',
        ]);
    }
};