<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Department;
use App\Models\WorkorderType;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 引用已由 DepartmentSeeder 创建的部门（扁平结构，无 parent_id/level）
        $itDepartment = Department::where('code', 'IT')->first();
        $networkDepartment = Department::where('code', 'IT-NETWORK')->first()
            ?? Department::where('code', 'IT')->first();

        // 创建默认工单类型（firstOrCreate 保证幂等）
        $workorderTypes = [
            ['name' => '网络故障', 'code' => 'NETWORK_ISSUE', 'description' => '网络连接、网速慢、无法上网等问题', 'source' => 'web', 'subcategory' => '网络', 'default_priority' => 2, 'default_hours' => 24, 'status' => 'active', 'sort_order' => 1],
            ['name' => '设备故障', 'code' => 'DEVICE_ISSUE', 'description' => '电脑、打印机、投影仪等设备故障', 'source' => 'web', 'subcategory' => '设备', 'default_priority' => 2, 'default_hours' => 48, 'status' => 'active', 'sort_order' => 2],
            ['name' => '机房故障', 'code' => 'SERVER_ROOM', 'description' => '服务器机房相关故障', 'source' => 'web', 'subcategory' => '机房', 'default_priority' => 1, 'default_hours' => 4, 'status' => 'active', 'sort_order' => 3],
            ['name' => '多媒体教室故障', 'code' => 'CLASSROOM', 'description' => '多媒体教室设备故障', 'source' => 'web', 'subcategory' => '多媒体教室', 'default_priority' => 2, 'default_hours' => 24, 'status' => 'active', 'sort_order' => 4],
        ];

        foreach ($workorderTypes as $type) {
            WorkorderType::firstOrCreate(['code' => $type['code']], $type);
        }

        // 管理员
        User::firstOrCreate(['email' => 'admin@workorder.com'], [
            'username' => 'admin',
            'name' => '系统管理员',
            'email' => 'admin@workorder.com',
            'password' => Hash::make('admin123'),
            'phone' => '13800000000',
            'employee_id' => 'ADMIN001',
            'department_id' => $itDepartment?->id,
            'role' => 'admin',
            'status' => 'active',
            'location' => '行政楼',
            'remarks' => '系统默认管理员账户',
        ]);

        // 工程师
        User::firstOrCreate(['email' => 'engineer@workorder.com'], [
            'username' => 'engineer',
            'name' => '测试工程师',
            'email' => 'engineer@workorder.com',
            'password' => Hash::make('engineer123'),
            'phone' => '13800000001',
            'employee_id' => 'ENG001',
            'department_id' => $networkDepartment?->id,
            'role' => 'engineer',
            'status' => 'active',
            'location' => '行政楼',
            'remarks' => '系统测试工程师账户',
        ]);

        // 普通用户
        User::firstOrCreate(['email' => 'user@workorder.com'], [
            'username' => 'user',
            'name' => '测试用户',
            'email' => 'user@workorder.com',
            'password' => Hash::make('user123'),
            'phone' => '13800000002',
            'employee_id' => 'USER001',
            'department_id' => $itDepartment?->id,
            'role' => 'user',
            'status' => 'active',
            'location' => '教学楼',
            'remarks' => '系统测试普通用户账户',
        ]);

        $this->command->info('默认用户创建完成！');
        $this->command->info('管理员账户：admin@workorder.com / admin123');
        $this->command->info('工程师账户：engineer@workorder.com / engineer123');
        $this->command->info('普通用户账户：user@workorder.com / user123');
    }
}
