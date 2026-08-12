<?php

namespace Database\Seeders;

use App\Models\Location;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * 通用工单系统默认地址树（示例）。
 *
 * 仅作为部署后的初始示例，部署方应在「地址管理 → 基础地址」中
 * 填写单位真实基础地址，并通过「批量导入」补充日常地址树。
 *
 * 示例数据采用通用占位（总部园区 / A 楼 / 101 室），不含任何特定单位信息。
 */
class LocationSeeder extends Seeder
{
    public function run(): void
    {
        Location::query()->delete();

        $levelId = fn ($code) => DB::table('location_levels')->where('code', $code)->value('id');

        // 基础地址链（占位）：部署方应在「基础地址」页面改为单位实际地址
        $baseChain = [
            ['name' => '省份',     'code' => null, 'lv' => 'province'],
            ['name' => '城市',     'code' => null, 'lv' => 'city'],
            ['name' => '区县',     'code' => null, 'lv' => 'district'],
            ['name' => '街道',     'code' => null, 'lv' => 'street'],
            ['name' => '门牌号',   'code' => null, 'lv' => 'road'],
        ];

        $parentId = null;
        $root = null;
        foreach ($baseChain as $item) {
            $node = Location::create([
                'name' => $item['name'],
                'code' => $item['code'],
                'level_id' => $levelId($item['lv']),
                'parent_id' => $parentId,
                'sort_order' => 1,
                'status' => 'active',
            ]);
            $parentId = $node->id;
            $root = $node;
        }

        // 日常层示例
        $campusLv = $levelId('campus');
        $buildingLv = $levelId('building');
        $roomLv = $levelId('room');

        $campus = Location::create([
            'name' => '总部园区', 'level_id' => $campusLv, 'parent_id' => $root->id,
            'sort_order' => 1, 'status' => 'active',
        ]);

        $b1 = Location::create([
            'name' => 'A 楼', 'level_id' => $buildingLv, 'parent_id' => $campus->id,
            'sort_order' => 1, 'status' => 'active',
        ]);
        $b2 = Location::create([
            'name' => 'B 楼', 'level_id' => $buildingLv, 'parent_id' => $campus->id,
            'sort_order' => 2, 'status' => 'active',
        ]);

        Location::create([
            'name' => '101 室', 'level_id' => $roomLv, 'parent_id' => $b1->id,
            'sort_order' => 1, 'status' => 'active',
        ]);
        Location::create([
            'name' => '102 室', 'level_id' => $roomLv, 'parent_id' => $b1->id,
            'sort_order' => 2, 'status' => 'active',
        ]);
        Location::create([
            'name' => '201 室', 'level_id' => $roomLv, 'parent_id' => $b2->id,
            'sort_order' => 1, 'status' => 'active',
        ]);
    }
}
