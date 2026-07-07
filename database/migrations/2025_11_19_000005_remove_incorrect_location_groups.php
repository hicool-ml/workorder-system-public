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
        // 删除错误的汇总地址
        $incorrectLocations = [
            '1-7教',      // 老校区1-7教 (错误)
            '8-14教',     // 新校区8-14教 (错误)
            'A-J教',       // 东盟A-J教 (错误)
            '1-10栋',     // 老校区1-10栋 (错误)
            '11-18栋',     // 新校区11-18栋 (错误)
            '19-20栋',     // 东盟19-20栋 (错误)
        ];

        foreach ($incorrectLocations as $locationName) {
            DB::table('locations')
                ->where('name', $locationName)
                ->delete();
            
            echo "Deleted incorrect location: {$locationName}\n";
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 恢复被删除的汇总地址（如果需要的话）
        $locationsToRestore = [
            [
                'name' => '1-7教',
                'campus' => 'old_campus',
                'building_type' => 'teaching_building',
                'building_code' => '1-7教',
                'sort_order' => 1,
                'status' => 'active'
            ],
            [
                'name' => '8-14教',
                'campus' => 'new_campus',
                'building_type' => 'teaching_building',
                'building_code' => '8-14教',
                'sort_order' => 1,
                'status' => 'active'
            ],
            [
                'name' => 'A-J教',
                'campus' => 'asean_campus',
                'building_type' => 'teaching_building',
                'building_code' => 'A-J教',
                'sort_order' => 1,
                'status' => 'active'
            ],
            [
                'name' => '1-10栋',
                'campus' => 'old_campus',
                'building_type' => 'dormitory',
                'building_code' => '1-10栋',
                'sort_order' => 20,
                'status' => 'active'
            ],
            [
                'name' => '11-18栋',
                'campus' => 'new_campus',
                'building_type' => 'dormitory',
                'building_code' => '11-18栋',
                'sort_order' => 20,
                'status' => 'active'
            ],
            [
                'name' => '19-20栋',
                'campus' => 'asean_campus',
                'building_type' => 'dormitory',
                'building_code' => '19-20栋',
                'sort_order' => 20,
                'status' => 'active'
            ]
        ];

        foreach ($locationsToRestore as $location) {
            $location['created_at'] = now();
            $location['updated_at'] = now();
            
            DB::table('locations')->insert($location);
            echo "Restored location: {$location['name']}\n";
        }
    }
};