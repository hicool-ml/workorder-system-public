<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Department;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 幂等：每次 seed 清空后重新插入（扁平结构，parent_id/level 已由迁移移除）
        Department::query()->delete();

        Department::insert([
            ['name' => '信息技术部', 'code' => 'IT', 'description' => '负责公司信息技术系统和网络管理', 'manager_name' => 'IT经理', 'manager_phone' => '', 'location' => '办公楼3楼', 'status' => 'active', 'sort_order' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => '系统运维组', 'code' => 'IT-SYSOPS', 'description' => '负责服务器和系统维护', 'manager_name' => '系统运维主管', 'manager_phone' => '', 'location' => '办公楼3楼', 'status' => 'active', 'sort_order' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['name' => '网络管理组', 'code' => 'IT-NETWORK', 'description' => '负责网络设备和连接管理', 'manager_name' => '网络管理主管', 'manager_phone' => '', 'location' => '办公楼3楼', 'status' => 'active', 'sort_order' => 3, 'created_at' => now(), 'updated_at' => now()],
            ['name' => '软件开发组', 'code' => 'IT-DEVELOP', 'description' => '负责应用软件开发和维护', 'manager_name' => '软件开发主管', 'manager_phone' => '', 'location' => '办公楼3楼', 'status' => 'active', 'sort_order' => 4, 'created_at' => now(), 'updated_at' => now()],
            ['name' => '行政部', 'code' => 'ADMIN', 'description' => '负责公司行政事务和后勤保障', 'manager_name' => '行政经理', 'manager_phone' => '', 'location' => '办公楼1楼', 'status' => 'active', 'sort_order' => 5, 'created_at' => now(), 'updated_at' => now()],
            ['name' => '后勤保障组', 'code' => 'ADMIN-FACILITY', 'description' => '负责办公环境和设施维护', 'manager_name' => '后勤主管', 'manager_phone' => '', 'location' => '办公楼1楼', 'status' => 'active', 'sort_order' => 6, 'created_at' => now(), 'updated_at' => now()],
            ['name' => '文档管理组', 'code' => 'ADMIN-DOCUMENT', 'description' => '负责文档和档案管理', 'manager_name' => '文档管理主管', 'manager_phone' => '', 'location' => '办公楼1楼', 'status' => 'active', 'sort_order' => 7, 'created_at' => now(), 'updated_at' => now()],
            ['name' => '接待服务组', 'code' => 'ADMIN-RECEPTION', 'description' => '负责前台接待和访客服务', 'manager_name' => '接待服务主管', 'manager_phone' => '', 'location' => '办公楼1楼', 'status' => 'active', 'sort_order' => 8, 'created_at' => now(), 'updated_at' => now()],
            ['name' => '人力资源部', 'code' => 'HR', 'description' => '负责人力资源管理和员工服务', 'manager_name' => '人力资源经理', 'manager_phone' => '', 'location' => '办公楼2楼', 'status' => 'active', 'sort_order' => 9, 'created_at' => now(), 'updated_at' => now()],
            ['name' => '招聘培训组', 'code' => 'HR-RECRUIT', 'description' => '负责员工招聘和培训', 'manager_name' => '招聘培训主管', 'manager_phone' => '', 'location' => '办公楼2楼', 'status' => 'active', 'sort_order' => 10, 'created_at' => now(), 'updated_at' => now()],
            ['name' => '薪酬福利组', 'code' => 'HR-COMPENSATION', 'description' => '负责薪酬和福利管理', 'manager_name' => '薪酬福利主管', 'manager_phone' => '', 'location' => '办公楼2楼', 'status' => 'active', 'sort_order' => 11, 'created_at' => now(), 'updated_at' => now()],
            ['name' => '员工关系组', 'code' => 'HR-RELATION', 'description' => '负责员工关系和企业文化', 'manager_name' => '员工关系主管', 'manager_phone' => '', 'location' => '办公楼2楼', 'status' => 'active', 'sort_order' => 12, 'created_at' => now(), 'updated_at' => now()],
            ['name' => '财务部', 'code' => 'FIN', 'description' => '负责财务管理和会计核算', 'manager_name' => '财务经理', 'manager_phone' => '', 'location' => '办公楼4楼', 'status' => 'active', 'sort_order' => 13, 'created_at' => now(), 'updated_at' => now()],
            ['name' => '会计核算组', 'code' => 'FIN-ACCOUNTING', 'description' => '负责日常会计核算', 'manager_name' => '会计核算主管', 'manager_phone' => '', 'location' => '办公楼4楼', 'status' => 'active', 'sort_order' => 14, 'created_at' => now(), 'updated_at' => now()],
            ['name' => '资金管理组', 'code' => 'FIN-TREASURY', 'description' => '负责资金管理和预算控制', 'manager_name' => '资金管理主管', 'manager_phone' => '', 'location' => '办公楼4楼', 'status' => 'active', 'sort_order' => 15, 'created_at' => now(), 'updated_at' => now()],
            ['name' => '税务管理组', 'code' => 'FIN-TAXATION', 'description' => '负责税务申报和筹划', 'manager_name' => '税务管理主管', 'manager_phone' => '', 'location' => '办公楼4楼', 'status' => 'active', 'sort_order' => 16, 'created_at' => now(), 'updated_at' => now()],
            ['name' => '市场部', 'code' => 'MKT', 'description' => '负责市场营销和客户关系', 'manager_name' => '市场经理', 'manager_phone' => '', 'location' => '办公楼5楼', 'status' => 'active', 'sort_order' => 17, 'created_at' => now(), 'updated_at' => now()],
            ['name' => '市场推广组', 'code' => 'MKT-PROMOTION', 'description' => '负责市场推广活动', 'manager_name' => '市场推广主管', 'manager_phone' => '', 'location' => '办公楼5楼', 'status' => 'active', 'sort_order' => 18, 'created_at' => now(), 'updated_at' => now()],
            ['name' => '客户服务组', 'code' => 'MKT-SERVICE', 'description' => '负责客户服务和支持', 'manager_name' => '客户服务主管', 'manager_phone' => '', 'location' => '办公楼5楼', 'status' => 'active', 'sort_order' => 19, 'created_at' => now(), 'updated_at' => now()],
            ['name' => '品牌管理组', 'code' => 'MKT-BRANDING', 'description' => '负责品牌建设和维护', 'manager_name' => '品牌管理主管', 'manager_phone' => '', 'location' => '办公楼5楼', 'status' => 'active', 'sort_order' => 20, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
