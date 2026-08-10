<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function sourceData(): array
    {
        return [
            ['code' => 'phone', 'name' => '电话报修', 'description' => '用户通过电话直接报修', 'sort_order' => 1],
            ['code' => 'web', 'name' => '在线平台', 'description' => '通过网站或APP在线提交报修', 'sort_order' => 2],
            ['code' => 'email', 'name' => '邮件申请', 'description' => '通过发送邮件申请维修服务', 'sort_order' => 3],
            ['code' => 'scene', 'name' => '现场报修', 'description' => '工作人员现场发现并记录的问题', 'sort_order' => 4],
            ['code' => 'inspection', 'name' => '巡检发现', 'description' => '定期巡检过程中发现的设备问题', 'sort_order' => 5],
            ['code' => 'system', 'name' => '系统预警', 'description' => '监控系统自动发出的预警信息', 'sort_order' => 6],
            ['code' => 'other', 'name' => '其他来源', 'description' => '除上述分类外的其他报修方式', 'sort_order' => 7],
        ];
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $hasIsActive = Schema::hasColumn('workorder_sources', 'is_active');

        foreach ($this->sourceData() as $source) {
            $data = [
                'name' => $source['name'],
                'description' => $source['description'],
                'sort_order' => $source['sort_order'],
                'updated_at' => now(),
                'created_at' => now(),
            ];
            // 兼容两种表结构：is_active (boolean) 或 status (string)
            if ($hasIsActive) {
                $data['is_active'] = true;
            }
            DB::table('workorder_sources')->updateOrInsert(['code' => $source['code']], $data);
        }

        // 删除无意义的旧来源
        DB::table('workorder_sources')->whereIn('code', ['wechat', 'custom'])->delete();

        // 更新工单表中的来源代码
        DB::table('workorders')
            ->whereIn('source', ['wechat', 'custom'])
            ->update(['source' => 'other']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('workorder_sources')->whereIn('code', ['inspection', 'system'])->delete();
    }
};