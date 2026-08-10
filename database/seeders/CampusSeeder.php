<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * 通用工单系统默认园区/分部数据。
 */
class CampusSeeder extends Seeder
{
    public function run(): void
    {
        $campuses = [
            ['name' => '总部园区', 'code' => 'hq', 'description' => '总部办公园区', 'sort_order' => 1],
            ['name' => '分部', 'code' => 'branch', 'description' => '分支机构 / 异地办公点', 'sort_order' => 2],
        ];

        foreach ($campuses as $c) {
            DB::table('campuses')->updateOrInsert(
                ['code' => $c['code']],
                array_merge($c, ['status' => 'active', 'created_at' => now(), 'updated_at' => now()])
            );
        }
    }
}
