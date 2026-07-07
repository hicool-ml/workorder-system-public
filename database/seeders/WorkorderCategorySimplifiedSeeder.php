<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WorkorderCategorySimplifiedSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 清空现有数据
        DB::table('workorder_categories_simplified')->delete();
        
        // 一级分类（大类）
        $categories = [
            [
                'name' => '网络故障',
                'parent_id' => null,
                'ticket_prefix' => 'N',
                'default_hours' => 24,
                'color' => '#dc3545',
                'description' => '网络相关故障',
                'sort_order' => 1,
                'status' => true,
            ],
            [
                'name' => '多媒体教室',
                'parent_id' => null,
                'ticket_prefix' => 'M',
                'default_hours' => 48,
                'color' => '#fd7e14',
                'description' => '多媒体教室设备故障',
                'sort_order' => 2,
                'status' => true,
            ],
            [
                'name' => '专项',
                'parent_id' => null,
                'ticket_prefix' => 'Z',
                'default_hours' => 72,
                'color' => '#6610f2',
                'description' => '专项工作',
                'sort_order' => 3,
                'status' => true,
            ],
            [
                'name' => '设备故障',
                'parent_id' => null,
                'ticket_prefix' => 'S',
                'default_hours' => 36,
                'color' => '#198754',
                'description' => '设备相关故障',
                'sort_order' => 4,
                'status' => true,
            ],
            [
                'name' => '软件支持',
                'parent_id' => null,
                'ticket_prefix' => 'R',
                'default_hours' => 12,
                'color' => '#0dcaf0',
                'description' => '软件相关支持',
                'sort_order' => 5,
                'status' => true,
            ],
        ];
        
        // 插入一级分类
        foreach ($categories as $category) {
            DB::table('workorder_categories_simplified')->insert($category);
        }
        
        // 获取一级分类的ID
        $networkId = DB::table('workorder_categories_simplified')->where('name', '网络故障')->value('id');
        $multimediaId = DB::table('workorder_categories_simplified')->where('name', '多媒体教室')->value('id');
        $specialId = DB::table('workorder_categories_simplified')->where('name', '专项')->value('id');
        $equipmentId = DB::table('workorder_categories_simplified')->where('name', '设备故障')->value('id');
        $softwareId = DB::table('workorder_categories_simplified')->where('name', '软件支持')->value('id');
        
        // 二级分类（故障分类）
        $subcategories = [
            // 网络故障子分类
            ['name' => '拨号失败', 'parent_id' => $networkId, 'sort_order' => 1],
            ['name' => '网络速度慢', 'parent_id' => $networkId, 'sort_order' => 2],
            ['name' => '网络连接不稳定', 'parent_id' => $networkId, 'sort_order' => 3],
            ['name' => '无法访问特定网站', 'parent_id' => $networkId, 'sort_order' => 4],
            ['name' => 'VPN连接问题', 'parent_id' => $networkId, 'sort_order' => 5],
            
            // 多媒体教室子分类
            ['name' => '大屏显示不正常', 'parent_id' => $multimediaId, 'sort_order' => 1],
            ['name' => '投影仪故障', 'parent_id' => $multimediaId, 'sort_order' => 2],
            ['name' => '音响系统问题', 'parent_id' => $multimediaId, 'sort_order' => 3],
            ['name' => '电脑无法开机', 'parent_id' => $multimediaId, 'sort_order' => 4],
            ['name' => '触控失灵', 'parent_id' => $multimediaId, 'sort_order' => 5],
            
            // 专项子分类
            ['name' => '新建项目线路测试', 'parent_id' => $specialId, 'sort_order' => 1],
            ['name' => '设备安装调试', 'parent_id' => $specialId, 'sort_order' => 2],
            ['name' => '系统迁移', 'parent_id' => $specialId, 'sort_order' => 3],
            ['name' => '网络升级改造', 'parent_id' => $specialId, 'sort_order' => 4],
            ['name' => '机房建设', 'parent_id' => $specialId, 'sort_order' => 5],
            
            // 设备故障子分类
            ['name' => '打印机故障', 'parent_id' => $equipmentId, 'sort_order' => 1],
            ['name' => '复印机故障', 'parent_id' => $equipmentId, 'sort_order' => 2],
            ['name' => '扫描仪故障', 'parent_id' => $equipmentId, 'sort_order' => 3],
            ['name' => '电话故障', 'parent_id' => $equipmentId, 'sort_order' => 4],
            ['name' => '门禁系统故障', 'parent_id' => $equipmentId, 'sort_order' => 5],
            
            // 软件支持子分类
            ['name' => '操作系统问题', 'parent_id' => $softwareId, 'sort_order' => 1],
            ['name' => '办公软件问题', 'parent_id' => $softwareId, 'sort_order' => 2],
            ['name' => '专业软件安装', 'parent_id' => $softwareId, 'sort_order' => 3],
            ['name' => '病毒查杀', 'parent_id' => $softwareId, 'sort_order' => 4],
            ['name' => '数据恢复', 'parent_id' => $softwareId, 'sort_order' => 5],
        ];
        
        // 插入二级分类
        foreach ($subcategories as $subcategory) {
            $subcategory['ticket_prefix'] = ''; // 子分类不设置前缀，使用父分类的前缀
            $subcategory['default_hours'] = 0; // 子分类不设置时限，使用父分类的时限
            $subcategory['color'] = ''; // 子分类不设置颜色，使用父分类的颜色
            $subcategory['description'] = ''; // 子分类不设置描述
            $subcategory['status'] = true;
            DB::table('workorder_categories_simplified')->insert($subcategory);
        }
    }
}