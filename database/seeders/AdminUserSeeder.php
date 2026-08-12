<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Department;
use App\Models\WorkorderType;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * 出于安全考虑，默认账号的初始密码在每次运行 seeder 时随机生成，
     * 并通过 command line 输出一次；不会写入代码仓库。
     * 如需指定密码，可在 .env 设置：
     *   SEED_ADMIN_PASSWORD / SEED_ENGINEER_PASSWORD / SEED_MANAGER_PASSWORD / SEED_USER_PASSWORD
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

        $passwords = $this->resolvePasswords();

        // 管理员
        $this->createUser([
            'username' => 'admin',
            'name' => '系统管理员',
            'email' => 'admin@workorder.com',
            'password' => $passwords['admin'],
            'phone' => '13800000000',
            'employee_id' => 'ADMIN001',
            'department_id' => $itDepartment?->id,
            'role' => 'admin',
            'location' => '综合办公楼',
            'remarks' => '系统默认管理员账户',
        ]);

        // 工程师
        $this->createUser([
            'username' => 'engineer',
            'name' => '测试工程师',
            'email' => 'engineer@workorder.com',
            'password' => $passwords['engineer'],
            'phone' => '13800000001',
            'employee_id' => 'ENG001',
            'department_id' => $networkDepartment?->id,
            'role' => 'engineer',
            'location' => '综合办公楼',
            'remarks' => '系统测试工程师账户',
        ]);

        // 工单管理员
        $this->createUser([
            'username' => 'manager',
            'name' => '测试工单管理员',
            'email' => 'manager@workorder.com',
            'password' => $passwords['manager'],
            'phone' => '13800000003',
            'employee_id' => 'MGR001',
            'department_id' => $itDepartment?->id,
            'role' => 'workorder_manager',
            'location' => '综合办公楼',
            'remarks' => '系统测试工单管理员账户',
        ]);

        // 普通用户
        $this->createUser([
            'username' => 'user',
            'name' => '测试用户',
            'email' => 'user@workorder.com',
            'password' => $passwords['user'],
            'phone' => '13800000002',
            'employee_id' => 'USER001',
            'department_id' => $itDepartment?->id,
            'role' => 'user',
            'location' => '综合办公楼',
            'remarks' => '系统测试普通用户账户',
        ]);

        $this->command->info('默认用户创建/已存在！');
        $this->command->info('=== 初始账号（请妥善保存，仅此次显示） ===');
        $this->command->info("管理员：admin@workorder.com / {$passwords['admin']}");
        $this->command->info("工程师：engineer@workorder.com / {$passwords['engineer']}");
        $this->command->info("工单管理员：manager@workorder.com / {$passwords['manager']}");
        $this->command->info("普通用户：user@workorder.com / {$passwords['user']}");
        $this->command->info('首次登录后系统将强制修改密码。');
    }

    /**
     * 从 env 读取密码；未配置则随机生成 16 位字符串
     */
    private function resolvePasswords(): array
    {
        return [
            'admin'    => env('SEED_ADMIN_PASSWORD') ?: Str::random(16),
            'engineer' => env('SEED_ENGINEER_PASSWORD') ?: Str::random(16),
            'manager'  => env('SEED_MANAGER_PASSWORD') ?: Str::random(16),
            'user'     => env('SEED_USER_PASSWORD') ?: Str::random(16),
        ];
    }

    /**
     * 创建用户；若已存在则跳过（firstOrCreate），并打印一条提示
     */
    private function createUser(array $attrs): void
    {
        $exists = User::where('email', $attrs['email'])->exists();
        if ($exists) {
            $this->command->warn("用户 {$attrs['email']} 已存在，跳过创建（未覆盖密码）");
            return;
        }
        User::create(array_merge($attrs, [
            'status' => 'active',
            'password' => Hash::make($attrs['password']),
        ]));
    }
}