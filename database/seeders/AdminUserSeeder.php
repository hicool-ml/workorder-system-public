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
            ['name' => '硬件支持', 'code' => 'HARDWARE', 'description' => '电脑、打印机及终端设备故障', 'source' => 'web', 'subcategory' => '硬件', 'default_priority' => 2, 'default_hours' => 24, 'status' => 'active', 'sort_order' => 1],
            ['name' => '软件支持', 'code' => 'SOFTWARE', 'description' => '操作系统与应用软件问题', 'source' => 'web', 'subcategory' => '软件', 'default_priority' => 2, 'default_hours' => 12, 'status' => 'active', 'sort_order' => 2],
            ['name' => '网络与通信', 'code' => 'NETWORK', 'description' => '网络连接、VPN及通信故障', 'source' => 'web', 'subcategory' => '网络', 'default_priority' => 2, 'default_hours' => 8, 'status' => 'active', 'sort_order' => 3],
            ['name' => '综合服务', 'code' => 'GENERAL', 'description' => '账号权限、咨询及其他综合事务', 'source' => 'web', 'subcategory' => '综合', 'default_priority' => 3, 'default_hours' => 48, 'status' => 'active', 'sort_order' => 4],
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
            'location' => '综合办公楼',
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
            'location' => '综合办公楼',
            'remarks' => '系统测试工程师账户',
        ]);

        // 工单管理员
        User::firstOrCreate(['email' => 'manager@workorder.com'], [
            'username' => 'manager',
            'name' => '测试工单管理员',
            'email' => 'manager@workorder.com',
            'password' => Hash::make('manager123'),
            'phone' => '13800000003',
            'employee_id' => 'MGR001',
            'department_id' => $itDepartment?->id,
            'role' => 'workorder_manager',
            'status' => 'active',
            'location' => '综合办公楼',
            'remarks' => '系统测试工单管理员账户',
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
            'location' => '综合办公楼',
            'remarks' => '系统测试普通用户账户',
        ]);

        $this->command->info('默认用户创建完成！');
        $this->command->info('管理员账户：admin@workorder.com / admin123');
        $this->command->info('工程师账户：engineer@workorder.com / engineer123');
        $this->command->info('普通用户账户：user@workorder.com / user123');
    }
}