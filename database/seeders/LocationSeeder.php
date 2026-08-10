<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Location;
use Illuminate\Support\Facades\DB;

/**
 * 通用工单系统默认地址树（城市级示例）。
 * 层级定义来自 LocationLevelSeeder，地址按树结构录入。
 */
class LocationSeeder extends Seeder
{
    public function run(): void
    {
        Location::query()->delete();

        $levelId = fn ($code) => DB::table('location_levels')->where('code', $code)->value('id');

        $cityLv     = $levelId('city');
        $districtLv = $levelId('district');
        $streetLv   = $levelId('street');
        $roadLv     = $levelId('road');
        $buildingLv = $levelId('building');

        // L1: 市
        $city = Location::create([
            'name' => '成都市', 'level_id' => $cityLv, 'parent_id' => null,
            'sort_order' => 1, 'status' => 'active',
        ]);

        // L2: 区
        $district = Location::create([
            'name' => '高新区', 'level_id' => $districtLv, 'parent_id' => $city->id,
            'sort_order' => 1, 'status' => 'active',
        ]);

        // L3: 街道
        $street = Location::create([
            'name' => '桂溪街道', 'level_id' => $streetLv, 'parent_id' => $district->id,
            'sort_order' => 1, 'status' => 'active',
        ]);

        // L4: 路
        $road1 = Location::create([
            'name' => '天府大道', 'level_id' => $roadLv, 'parent_id' => $street->id,
            'sort_order' => 1, 'status' => 'active',
        ]);
        $road2 = Location::create([
            'name' => '世纪城路', 'level_id' => $roadLv, 'parent_id' => $street->id,
            'sort_order' => 2, 'status' => 'active',
        ]);

        // L5: 楼栋
        Location::create([
            'name' => '1号办公楼', 'level_id' => $buildingLv, 'parent_id' => $road1->id,
            'sort_order' => 1, 'status' => 'active',
        ]);
        Location::create([
            'name' => '2号研发楼', 'level_id' => $buildingLv, 'parent_id' => $road1->id,
            'sort_order' => 2, 'status' => 'active',
        ]);
        Location::create([
            'name' => '世纪城会展中心', 'level_id' => $buildingLv, 'parent_id' => $road2->id,
            'sort_order' => 1, 'status' => 'active',
        ]);
    }
}