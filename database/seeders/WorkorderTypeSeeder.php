<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\WorkorderType;

class WorkorderTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 创建一级分类
        $categories = [
            [
                'name' => 'IT支持',
                'code' => 'IT',
                'description' => '信息技术相关问题',
                'icon' => 'fas fa-laptop',
                'color' => '#007bff',
                'default_hours' => 24,
                'default_priority' => 2,
                'status' => 'active',
                'sort_order' => 1,
                'children' => [
                    [
                        'name' => '硬件问题',
                        'code' => 'IT-HW',
                        'description' => '计算机硬件相关问题',
                        'children' => [
                            ['name' => '电脑无法开机', 'code' => 'IT-HW-BOOT', 'description' => '电脑启动失败或黑屏'],
                            ['name' => '显示器故障', 'code' => 'IT-HW-DISP', 'description' => '显示器显示异常或无法显示'],
                            ['name' => '键盘鼠标问题', 'code' => 'IT-HW-KBM', 'description' => '键盘鼠标失灵或响应异常'],
                            ['name' => '打印机问题', 'code' => 'IT-HW-PRN', 'description' => '打印机无法正常工作'],
                            ['name' => '网络设备故障', 'code' => 'IT-HW-NET', 'description' => '路由器、交换机等网络设备问题'],
                        ]
                    ],
                    [
                        'name' => '软件问题',
                        'code' => 'IT-SW',
                        'description' => '操作系统和应用软件问题',
                        'children' => [
                            ['name' => 'Windows系统问题', 'code' => 'IT-SW-WIN', 'description' => 'Windows操作系统故障'],
                            ['name' => 'Office办公软件', 'code' => 'IT-SW-OFFICE', 'description' => 'Word、Excel、PowerPoint等办公软件问题'],
                            ['name' => '专业软件问题', 'code' => 'IT-SW-PRO', 'description' => '专业领域软件使用问题'],
                            ['name' => '软件安装卸载', 'code' => 'IT-SW-INSTALL', 'description' => '软件安装或卸载失败'],
                            ['name' => '系统更新问题', 'code' => 'IT-SW-UPDATE', 'description' => '系统更新失败或异常'],
                        ]
                    ],
                    [
                        'name' => '网络问题',
                        'code' => 'IT-NET',
                        'description' => '网络连接和访问问题',
                        'children' => [
                            ['name' => '无法连接互联网', 'code' => 'IT-NET-EXT', 'description' => '无法访问外部网络'],
                            ['name' => '内网访问问题', 'code' => 'IT-NET-INT', 'description' => '无法访问内部网络资源'],
                            ['name' => '网络速度慢', 'code' => 'IT-NET-SLOW', 'description' => '网络连接速度异常缓慢'],
                            ['name' => 'WiFi连接问题', 'code' => 'IT-NET-WIFI', 'description' => '无线网络连接异常'],
                            ['name' => 'VPN连接问题', 'code' => 'IT-NET-VPN', 'description' => 'VPN连接失败或异常'],
                        ]
                    ]
                ]
            ],
            [
                'name' => '设施维护',
                'code' => 'FAC',
                'description' => '办公设施和环境问题',
                'icon' => 'fas fa-building',
                'color' => '#28a745',
                'default_hours' => 48,
                'default_priority' => 2,
                'status' => 'active',
                'sort_order' => 2,
                'children' => [
                    [
                        'name' => '办公设备',
                        'code' => 'FAC-OFFICE',
                        'description' => '办公设备相关问题',
                        'children' => [
                            ['name' => '空调故障', 'code' => 'FAC-OFFICE-AC', 'description' => '空调制冷制热异常'],
                            ['name' => '照明问题', 'code' => 'FAC-OFFICE-LIGHT', 'description' => '灯具损坏或照明不足'],
                            ['name' => '门锁问题', 'code' => 'FAC-OFFICE-LOCK', 'description' => '门锁损坏或无法正常使用'],
                            ['name' => '桌椅损坏', 'code' => 'FAC-OFFICE-DESK', 'description' => '办公桌椅损坏'],
                            ['name' => '电话故障', 'code' => 'FAC-OFFICE-PHONE', 'description' => '办公电话无法正常使用'],
                        ]
                    ],
                    [
                        'name' => '环境卫生',
                        'code' => 'FAC-ENV',
                        'description' => '办公环境清洁问题',
                        'children' => [
                            ['name' => '清洁问题', 'code' => 'FAC-ENV-CLEAN', 'description' => '办公区域清洁问题'],
                            ['name' => '垃圾处理', 'code' => 'FAC-ENV-TRASH', 'description' => '垃圾清理不及时'],
                            ['name' => '卫生间问题', 'code' => 'FAC-ENV-TOILET', 'description' => '卫生间设施或清洁问题'],
                            ['name' => '饮水机问题', 'code' => 'FAC-ENV-WATER', 'description' => '饮水机故障或水质问题'],
                            ['name' => '通风问题', 'code' => 'FAC-ENV-AIR', 'description' => '办公室通风不良'],
                        ]
                    ],
                    [
                        'name' => '安全设施',
                        'code' => 'FAC-SEC',
                        'description' => '安全相关设施问题',
                        'children' => [
                            ['name' => '消防设施', 'code' => 'FAC-SEC-FIRE', 'description' => '灭火器、消防栓等消防设施问题'],
                            ['name' => '监控设备', 'code' => 'FAC-SEC-CAM', 'description' => '监控摄像头故障'],
                            ['name' => '门禁系统', 'code' => 'FAC-SEC-ACCESS', 'description' => '门禁卡或门禁系统问题'],
                            ['name' => '报警系统', 'code' => 'FAC-SEC-ALARM', 'description' => '安全报警系统故障'],
                            ['name' => '应急照明', 'code' => 'FAC-SEC-LIGHT', 'description' => '应急照明设备问题'],
                        ]
                    ]
                ]
            ],
            [
                'name' => '行政服务',
                'code' => 'ADMIN',
                'description' => '行政事务和服务问题',
                'icon' => 'fas fa-users',
                'color' => '#ffc107',
                'default_hours' => 72,
                'default_priority' => 3,
                'status' => 'active',
                'sort_order' => 3,
                'children' => [
                    [
                        'name' => '会议室管理',
                        'code' => 'ADMIN-MEETING',
                        'description' => '会议室预订和使用问题',
                        'children' => [
                            ['name' => '会议室预订', 'code' => 'ADMIN-MEETING-BOOK', 'description' => '会议室预订系统问题'],
                            ['name' => '设备使用', 'code' => 'ADMIN-MEETING-EQUIP', 'description' => '会议室设备使用问题'],
                            ['name' => '环境问题', 'code' => 'ADMIN-MEETING-ENV', 'description' => '会议室环境问题'],
                            ['name' => '时间冲突', 'code' => 'ADMIN-MEETING-TIME', 'description' => '会议室使用时间冲突'],
                            ['name' => '权限问题', 'code' => 'ADMIN-MEETING-AUTH', 'description' => '会议室使用权限问题'],
                        ]
                    ],
                    [
                        'name' => '办公用品',
                        'code' => 'ADMIN-SUPPLY',
                        'description' => '办公用品申领和管理',
                        'children' => [
                            ['name' => '用品申领', 'code' => 'ADMIN-SUPPLY-REQ', 'description' => '办公用品申领流程问题'],
                            ['name' => '库存不足', 'code' => 'ADMIN-SUPPLY-STOCK', 'description' => '办公用品库存不足'],
                            ['name' => '质量问题', 'code' => 'ADMIN-SUPPLY-QUAL', 'description' => '办公用品质量问题'],
                            ['name' => '配送问题', 'code' => 'ADMIN-SUPPLY-DELIVERY', 'description' => '办公用品配送延迟'],
                            ['name' => '特殊需求', 'code' => 'ADMIN-SUPPLY-SPECIAL', 'description' => '特殊办公用品需求'],
                        ]
                    ],
                    [
                        'name' => '车辆服务',
                        'code' => 'ADMIN-VEHICLE',
                        'description' => '公司车辆使用和管理',
                        'children' => [
                            ['name' => '车辆预订', 'code' => 'ADMIN-VEHICLE-BOOK', 'description' => '公司用车预订问题'],
                            ['name' => '司机安排', 'code' => 'ADMIN-VEHICLE-DRIVER', 'description' => '司机安排问题'],
                            ['name' => '车辆故障', 'code' => 'ADMIN-VEHICLE-FAULT', 'description' => '公司车辆故障'],
                            ['name' => '费用报销', 'code' => 'ADMIN-VEHICLE-EXPENSE', 'description' => '车辆费用报销问题'],
                            ['name' => '违章处理', 'code' => 'ADMIN-VEHICLE-VIOLATION', 'description' => '车辆违章处理'],
                        ]
                    ]
                ]
            ]
        ];

        foreach ($categories as $category) {
            $this->createCategoryWithChildren($category);
        }
    }

    /**
     * 创建分类及其子分类
     */
    private function createCategoryWithChildren(array $categoryData, ?WorkorderType $parent = null): WorkorderType
    {
        $category = WorkorderType::create([
            'name' => $categoryData['name'],
            'code' => $categoryData['code'],
            'description' => $categoryData['description'],
            'icon' => $categoryData['icon'] ?? null,
            'color' => $categoryData['color'] ?? null,
            'default_hours' => $categoryData['default_hours'] ?? 24,
            'default_priority' => $categoryData['default_priority'] ?? 2,
            'status' => $categoryData['status'] ?? 'active',
            'sort_order' => $categoryData['sort_order'] ?? 0,
            'parent_id' => $parent?->id,
            'level' => $parent ? $parent->level + 1 : 1,
        ]);

        // 创建子分类
        if (isset($categoryData['children'])) {
            foreach ($categoryData['children'] as $childData) {
                if (isset($childData['children'])) {
                    // 如果有子分类，递归创建
                    $this->createCategoryWithChildren($childData, $category);
                } else {
                    // 直接创建二级或三级分类
                    WorkorderType::create([
                        'name' => $childData['name'],
                        'code' => $childData['code'],
                        'description' => $childData['description'],
                        'default_hours' => $categoryData['default_hours'] ?? 24,
                        'default_priority' => $categoryData['default_priority'] ?? 2,
                        'status' => 'active',
                        'sort_order' => 0,
                        'parent_id' => $category->id,
                        'level' => $parent ? $parent->level + 2 : 2,
                    ]);
                }
            }
        }

        return $category;
    }
}