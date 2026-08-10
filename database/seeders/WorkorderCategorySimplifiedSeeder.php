<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * 通用工单系统默认分类（一级 + 二级）。
 * 采用 IT 服务台常见分类，可按实际业务在管理后台调整。
 */
class WorkorderCategorySimplifiedSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('workorder_categories_simplified')->delete();

        // 一级分类
        $categories = [
            ['name' => '硬件支持', 'parent_id' => null, 'ticket_prefix' => 'H', 'default_hours' => 24, 'color' => '#dc3545', 'description' => '终端、外设及硬件设备故障', 'sort_order' => 1, 'status' => true],
            ['name' => '软件与应用', 'parent_id' => null, 'ticket_prefix' => 'S', 'default_hours' => 12, 'color' => '#0d6efd', 'description' => '操作系统与各类应用软件', 'sort_order' => 2, 'status' => true],
            ['name' => '网络与通信', 'parent_id' => null, 'ticket_prefix' => 'N', 'default_hours' => 8, 'color' => '#fd7e14', 'description' => '网络连接、VPN 及通信相关', 'sort_order' => 3, 'status' => true],
            ['name' => '办公环境', 'parent_id' => null, 'ticket_prefix' => 'E', 'default_hours' => 48, 'color' => '#198754', 'description' => '空调电力、门禁安防及办公设施', 'sort_order' => 4, 'status' => true],
            ['name' => '其他服务', 'parent_id' => null, 'ticket_prefix' => 'O', 'default_hours' => 24, 'color' => '#6c757d', 'description' => '账号权限、数据请求及综合咨询', 'sort_order' => 5, 'status' => true],
        ];

        foreach ($categories as $category) {
            DB::table('workorder_categories_simplified')->insert($category);
        }

        $id = fn ($name) => DB::table('workorder_categories_simplified')->where('name', $name)->value('id');
        $hw = $id('硬件支持');
        $sw = $id('软件与应用');
        $net = $id('网络与通信');
        $env = $id('办公环境');
        $other = $id('其他服务');

        // 二级分类
        $subcategories = [
            // 硬件支持
            ['name' => '电脑/终端故障', 'parent_id' => $hw, 'sort_order' => 1],
            ['name' => '打印/扫描外设', 'parent_id' => $hw, 'sort_order' => 2],
            ['name' => '网络设备故障', 'parent_id' => $hw, 'sort_order' => 3],
            ['name' => '显示器/投影', 'parent_id' => $hw, 'sort_order' => 4],
            // 软件与应用
            ['name' => '操作系统问题', 'parent_id' => $sw, 'sort_order' => 1],
            ['name' => '办公软件问题', 'parent_id' => $sw, 'sort_order' => 2],
            ['name' => '专业软件安装', 'parent_id' => $sw, 'sort_order' => 3],
            ['name' => '系统访问/登录', 'parent_id' => $sw, 'sort_order' => 4],
            // 网络与通信
            ['name' => '网络连接异常', 'parent_id' => $net, 'sort_order' => 1],
            ['name' => 'VPN/远程接入', 'parent_id' => $net, 'sort_order' => 2],
            ['name' => '电话/传真故障', 'parent_id' => $net, 'sort_order' => 3],
            ['name' => '无线网络问题', 'parent_id' => $net, 'sort_order' => 4],
            // 办公环境
            ['name' => '空调/电力问题', 'parent_id' => $env, 'sort_order' => 1],
            ['name' => '门禁/安防', 'parent_id' => $env, 'sort_order' => 2],
            ['name' => '办公家具/设施', 'parent_id' => $env, 'sort_order' => 3],
            // 其他服务
            ['name' => '账号/权限申请', 'parent_id' => $other, 'sort_order' => 1],
            ['name' => '数据/报表请求', 'parent_id' => $other, 'sort_order' => 2],
            ['name' => '咨询与建议', 'parent_id' => $other, 'sort_order' => 3],
        ];

        foreach ($subcategories as $sub) {
            DB::table('workorder_categories_simplified')->insert(array_merge($sub, [
                'ticket_prefix' => '',
                'default_hours' => 0,
                'color' => '',
                'description' => '',
                'status' => true,
            ]));
        }
    }
}
