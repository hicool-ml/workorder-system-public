<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Location;

class LocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 幂等：每次 seed 清空后重新插入
        Location::query()->delete();

        $oldCampus = [
            ['name' => '1-7教学楼', 'campus' => 'old_campus', 'building_type' => 'teaching_building', 'building_code' => '1-7教学楼', 'sort_order' => 1],
            ['name' => '1教', 'campus' => 'old_campus', 'building_type' => 'teaching_building', 'building_code' => '1教', 'sort_order' => 2],
            ['name' => '2教', 'campus' => 'old_campus', 'building_type' => 'teaching_building', 'building_code' => '2教', 'sort_order' => 3],
            ['name' => '3教', 'campus' => 'old_campus', 'building_type' => 'teaching_building', 'building_code' => '3教', 'sort_order' => 4],
            ['name' => '4教', 'campus' => 'old_campus', 'building_type' => 'teaching_building', 'building_code' => '4教', 'sort_order' => 5],
            ['name' => '5教', 'campus' => 'old_campus', 'building_type' => 'teaching_building', 'building_code' => '5教', 'sort_order' => 6],
            ['name' => '6教', 'campus' => 'old_campus', 'building_type' => 'teaching_building', 'building_code' => '6教', 'sort_order' => 7],
            ['name' => '7教', 'campus' => 'old_campus', 'building_type' => 'teaching_building', 'building_code' => '7教', 'sort_order' => 8],
            ['name' => '1-10栋', 'campus' => 'old_campus', 'building_type' => 'dormitory', 'building_code' => '1-10栋', 'sort_order' => 20],
            ['name' => '1栋', 'campus' => 'old_campus', 'building_type' => 'dormitory', 'building_code' => '1栋', 'sort_order' => 21],
            ['name' => '2栋', 'campus' => 'old_campus', 'building_type' => 'dormitory', 'building_code' => '2栋', 'sort_order' => 22],
            ['name' => '3栋', 'campus' => 'old_campus', 'building_type' => 'dormitory', 'building_code' => '3栋', 'sort_order' => 23],
            ['name' => '4栋', 'campus' => 'old_campus', 'building_type' => 'dormitory', 'building_code' => '4栋', 'sort_order' => 24],
            ['name' => '5栋', 'campus' => 'old_campus', 'building_type' => 'dormitory', 'building_code' => '5栋', 'sort_order' => 25],
            ['name' => '6栋', 'campus' => 'old_campus', 'building_type' => 'dormitory', 'building_code' => '6栋', 'sort_order' => 26],
            ['name' => '7栋', 'campus' => 'old_campus', 'building_type' => 'dormitory', 'building_code' => '7栋', 'sort_order' => 27],
            ['name' => '8栋', 'campus' => 'old_campus', 'building_type' => 'dormitory', 'building_code' => '8栋', 'sort_order' => 28],
            ['name' => '9栋', 'campus' => 'old_campus', 'building_type' => 'dormitory', 'building_code' => '9栋', 'sort_order' => 29],
            ['name' => '10栋', 'campus' => 'old_campus', 'building_type' => 'dormitory', 'building_code' => '10栋', 'sort_order' => 30],
            ['name' => '行政楼', 'campus' => 'old_campus', 'building_type' => 'office_building', 'building_code' => '行政楼', 'sort_order' => 40],
            ['name' => '行政保障中心', 'campus' => 'old_campus', 'building_type' => 'office_building', 'building_code' => '行政保障中心', 'sort_order' => 41],
            ['name' => '校医院', 'campus' => 'old_campus', 'building_type' => 'other', 'building_code' => '校医院', 'sort_order' => 42],
            ['name' => '图书馆', 'campus' => 'old_campus', 'building_type' => 'library', 'building_code' => '图书馆', 'sort_order' => 43],
        ];

        $newCampus = [
            ['name' => '8-14教学楼', 'campus' => 'new_campus', 'building_type' => 'teaching_building', 'building_code' => '8-14教学楼', 'sort_order' => 1],
            ['name' => '8教', 'campus' => 'new_campus', 'building_type' => 'teaching_building', 'building_code' => '8教', 'sort_order' => 2],
            ['name' => '9教', 'campus' => 'new_campus', 'building_type' => 'teaching_building', 'building_code' => '9教', 'sort_order' => 3],
            ['name' => '10教', 'campus' => 'new_campus', 'building_type' => 'teaching_building', 'building_code' => '10教', 'sort_order' => 4],
            ['name' => '11教', 'campus' => 'new_campus', 'building_type' => 'teaching_building', 'building_code' => '11教', 'sort_order' => 5],
            ['name' => '12教', 'campus' => 'new_campus', 'building_type' => 'teaching_building', 'building_code' => '12教', 'sort_order' => 6],
            ['name' => '13教', 'campus' => 'new_campus', 'building_type' => 'teaching_building', 'building_code' => '13教', 'sort_order' => 7],
            ['name' => '14教', 'campus' => 'new_campus', 'building_type' => 'teaching_building', 'building_code' => '14教', 'sort_order' => 8],
            ['name' => '11-18栋', 'campus' => 'new_campus', 'building_type' => 'dormitory', 'building_code' => '11-18栋', 'sort_order' => 20],
            ['name' => '11栋', 'campus' => 'new_campus', 'building_type' => 'dormitory', 'building_code' => '11栋', 'sort_order' => 21],
            ['name' => '12栋', 'campus' => 'new_campus', 'building_type' => 'dormitory', 'building_code' => '12栋', 'sort_order' => 22],
            ['name' => '13栋', 'campus' => 'new_campus', 'building_type' => 'dormitory', 'building_code' => '13栋', 'sort_order' => 23],
            ['name' => '14栋', 'campus' => 'new_campus', 'building_type' => 'dormitory', 'building_code' => '14栋', 'sort_order' => 24],
            ['name' => '15栋', 'campus' => 'new_campus', 'building_type' => 'dormitory', 'building_code' => '15栋', 'sort_order' => 25],
            ['name' => '16栋', 'campus' => 'new_campus', 'building_type' => 'dormitory', 'building_code' => '16栋', 'sort_order' => 26],
            ['name' => '17栋', 'campus' => 'new_campus', 'building_type' => 'dormitory', 'building_code' => '17栋', 'sort_order' => 27],
            ['name' => '18栋', 'campus' => 'new_campus', 'building_type' => 'dormitory', 'building_code' => '18栋', 'sort_order' => 28],
        ];

        $aseanCampus = [
            ['name' => 'A-J教学楼', 'campus' => 'asean_campus', 'building_type' => 'teaching_building', 'building_code' => 'A-J教学楼', 'sort_order' => 1],
            ['name' => 'A教', 'campus' => 'asean_campus', 'building_type' => 'teaching_building', 'building_code' => 'A教', 'sort_order' => 2],
            ['name' => 'B教', 'campus' => 'asean_campus', 'building_type' => 'teaching_building', 'building_code' => 'B教', 'sort_order' => 3],
            ['name' => 'C教', 'campus' => 'asean_campus', 'building_type' => 'teaching_building', 'building_code' => 'C教', 'sort_order' => 4],
            ['name' => 'D教', 'campus' => 'asean_campus', 'building_type' => 'teaching_building', 'building_code' => 'D教', 'sort_order' => 5],
            ['name' => 'E教', 'campus' => 'asean_campus', 'building_type' => 'teaching_building', 'building_code' => 'E教', 'sort_order' => 6],
            ['name' => 'F教', 'campus' => 'asean_campus', 'building_type' => 'teaching_building', 'building_code' => 'F教', 'sort_order' => 7],
            ['name' => 'G教', 'campus' => 'asean_campus', 'building_type' => 'teaching_building', 'building_code' => 'G教', 'sort_order' => 8],
            ['name' => 'H教', 'campus' => 'asean_campus', 'building_type' => 'teaching_building', 'building_code' => 'H教', 'sort_order' => 9],
            ['name' => 'I教', 'campus' => 'asean_campus', 'building_type' => 'teaching_building', 'building_code' => 'I教', 'sort_order' => 10],
            ['name' => 'J教', 'campus' => 'asean_campus', 'building_type' => 'teaching_building', 'building_code' => 'J教', 'sort_order' => 11],
            ['name' => '19-20栋', 'campus' => 'asean_campus', 'building_type' => 'dormitory', 'building_code' => '19-20栋', 'sort_order' => 20],
            ['name' => '19栋', 'campus' => 'asean_campus', 'building_type' => 'dormitory', 'building_code' => '19栋', 'sort_order' => 21],
            ['name' => '20栋', 'campus' => 'asean_campus', 'building_type' => 'dormitory', 'building_code' => '20栋', 'sort_order' => 22],
        ];

        $allLocations = array_merge($oldCampus, $newCampus, $aseanCampus);

        foreach ($allLocations as &$location) {
            $location['status'] = 'active';
        }

        Location::insert($allLocations);
    }
}
