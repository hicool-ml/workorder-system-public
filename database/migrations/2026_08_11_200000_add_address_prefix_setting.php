<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * 新增系统设置项：address_prefix_location_id
 *
 * 含义：地址前缀截止节点 ID（locations 表的主键）。
 * 该节点之上的层级（如：省/市/区/街道/门牌）在工单填写、地址管理界面默认不展示，
 * 用户只与前缀节点之下的层级（如：校区/楼栋/房间）打交道。
 *
 * 默认值：取当前已初始化的基础地址最深层节点（Location::getDailyRoot() 等价查询）。
 * 管理员可在「系统设置 → 地址前缀」页面修改为任意节点。
 */
return new class extends Migration
{
    public function up(): void
    {
        // 计算默认前缀根：最深层基础地址层级（is_daily_use=false）下、按 level 倒序的第一个启用节点
        $deepestBaseLevel = DB::table('location_levels')
            ->where('is_active', true)
            ->where('is_daily_use', false)
            ->orderByDesc('level')
            ->first();

        $defaultPrefixId = null;
        if ($deepestBaseLevel) {
            $root = DB::table('locations')
                ->where('level_id', $deepestBaseLevel->id)
                ->where('status', 'active')
                ->first();
            $defaultPrefixId = $root?->id;
        }

        DB::table('system_settings')->updateOrInsert(
            ['key' => 'address_prefix_location_id'],
            [
                'value' => $defaultPrefixId ? (string) $defaultPrefixId : '',
                'type' => 'integer',
                'description' => '地址前缀截止节点 ID（该节点之上层级在工单/管理界面默认隐藏）',
                'is_public' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        DB::table('system_settings')->where('key', 'address_prefix_location_id')->delete();
    }
};
