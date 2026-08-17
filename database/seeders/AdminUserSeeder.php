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
     * 全新部署只创建 admin 一个初始化账号；engineer / workorder_manager / user
     * 等角色由管理员登录后在「用户管理」中添加，不再由 seeder 预置。
     *
     * 出于安全考虑，初始密码每次 seeder 随机生成并通过命令行输出一次，
     * 不写入代码仓库；也可在 .env 用 SEED_ADMIN_PASSWORD 指定。
     */
    public function run(): void
    {
        // 引用已由 DepartmentSeeder 创建的部门
        $itDepartment = Department::where('code', 'IT')->first();

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

        // 只创建管理员账号
        $password = env('SEED_ADMIN_PASSWORD') ?: Str::random(16);
        $this->createAdmin($password, $itDepartment?->id);

        $this->command->info('=== 初始账号（请妥善保存，仅此次显示） ===');
        $this->command->info("用户名：admin / 密码：{$password}");
        $this->command->info('首次登录后系统将强制修改密码；其他角色请登录后通过「用户管理」添加。');
    }

    /**
     * 创建管理员；若已存在则跳过（不覆盖密码）
     */
    private function createAdmin(string $password, ?int $departmentId): void
    {
        if (User::where('username', 'admin')->exists()) {
            $this->command->warn('管理员 admin 已存在，跳过创建（未覆盖密码）');
            return;
        }

        User::create([
            'username' => 'admin',
            'name' => '系统管理员',
            'email' => 'admin@workorder.com',
            'password' => Hash::make($password),
            'phone' => '13800000000',
            'employee_id' => 'ADMIN001',
            'department_id' => $departmentId,
            'role' => 'admin',
            'status' => 'active',
            'location' => '综合办公楼',
            'remarks' => '系统默认管理员账户',
        ]);
    }
}
