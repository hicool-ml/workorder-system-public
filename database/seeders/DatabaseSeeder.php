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
            LocationSeeder::class,
            WorkorderCategorySimplifiedSeeder::class,
            // WorkorderTypeSeeder::class, // 暂时跳过，使用简化的工单分类
        ]);
    }
}
