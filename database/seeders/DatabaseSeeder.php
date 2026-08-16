<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
       $this->call([
           DepartmentSeeder::class,
           AdminUserSeeder::class,
            SystemSettingSeeder::class,
            LocationLevelSeeder::class,
            LocationSeeder::class,
            WorkorderCategorySimplifiedSeeder::class,
       ]);
    }
}
