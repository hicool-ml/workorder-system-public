<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * 默认地址层级定义 —— 8 级标准方案（参考 GB/T 2260 行政区划代码与物流结构化地址标准）。
 * - 基础地址层（is_daily_use=false）：省→市→区县→街道→门牌/路段，初始化后固定存在，日常选择省略
 * - 日常层（is_daily_use=true）：区域/园区→楼栋→房间/工位，工单/报表级联选择时展示
 * 用户可在管理后台自由增删改。
 */
class LocationLevelSeeder extends Seeder
{
    public function run(): void
    {
        $levels = [
            ['name' => '省/自治区/直辖市', 'code' => 'province', 'level' => 1, 'is_daily_use' => false, 'sort_order' => 1],
            ['name' => '市/地区/自治州', 'code' => 'city', 'level' => 2, 'is_daily_use' => false, 'sort_order' => 2],
            ['name' => '区/县/县级市', 'code' => 'district', 'level' => 3, 'is_daily_use' => false, 'sort_order' => 3],
            ['name' => '街道/乡镇', 'code' => 'street', 'level' => 4, 'is_daily_use' => false, 'sort_order' => 4],
            ['name' => '门牌/路段', 'code' => 'road', 'level' => 5, 'is_daily_use' => false, 'sort_order' => 5],
            ['name' => '区域/园区', 'code' => 'campus', 'level' => 6, 'is_daily_use' => true, 'sort_order' => 6],
            ['name' => '楼栋/建筑', 'code' => 'building', 'level' => 7, 'is_daily_use' => true, 'sort_order' => 7],
            ['name' => '房间/工位', 'code' => 'room', 'level' => 8, 'is_daily_use' => true, 'sort_order' => 8],
        ];

        foreach ($levels as $lv) {
            DB::table('location_levels')->updateOrInsert(
                ['code' => $lv['code']],
                array_merge($lv, [
                    'description' => $lv['is_daily_use']
                        ? '日常层：工单/报表级联选择时展示'
                        : '基础地址：初始化后固定存在，日常选择省略',
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }
}
