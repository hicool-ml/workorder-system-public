<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * 默认地址层级定义 —— 城市级示例（市→区→街道→社区→路→楼栋）。
 * 用户可在管理后台自由增删改，定义自己的分级方案。
 */
class LocationLevelSeeder extends Seeder
{
    public function run(): void
    {
        $levels = [
            ['name' => '市', 'code' => 'city', 'level' => 1, 'sort_order' => 1],
            ['name' => '区/县', 'code' => 'district', 'level' => 2, 'sort_order' => 2],
            ['name' => '街道/乡镇', 'code' => 'street', 'level' => 3, 'sort_order' => 3],
            ['name' => '社区/村', 'code' => 'community', 'level' => 4, 'sort_order' => 4],
            ['name' => '路/街', 'code' => 'road', 'level' => 5, 'sort_order' => 5],
            ['name' => '楼栋/门牌', 'code' => 'building', 'level' => 6, 'sort_order' => 6],
        ];

        foreach ($levels as $lv) {
            DB::table('location_levels')->updateOrInsert(
                ['code' => $lv['code']],
                array_merge($lv, [
                    'description' => '默认' . $lv['name'] . '层级',
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }
}
