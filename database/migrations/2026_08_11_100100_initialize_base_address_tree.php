<?php

use App\Models\Campus;
use App\Models\Location;
use App\Models\LocationLevel;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * 地址体系初始化（通用化方案）：
 *
 * 1. 将地址层级同步为 8 级标准方案，并标记是否日常使用：
 *    - 基础地址层（is_daily_use=false）：省→市→区县→街道→门牌/路段
 *    - 日常层（is_daily_use=true）：区域/园区→楼栋→房间/工位
 * 2. 写入基础地址：用通用占位（"省份"/"城市"/"区县"/"街道"/"门牌号"），
 *    部署后管理员应通过「基础地址」页面改为单位实际地址。
 * 3. 依据 campuses 表生成区域/园区节点
 * 4. 将既有扁平楼栋数据挂载到对应区域节点下（初始化存量数据）
 */
return new class extends Migration
{
    private const STANDARD_LEVELS = [
        ['name' => '省/自治区/直辖市', 'code' => 'province', 'level' => 1, 'is_daily_use' => false],
        ['name' => '市/地区/自治州', 'code' => 'city', 'level' => 2, 'is_daily_use' => false],
        ['name' => '区/县/县级市', 'code' => 'district', 'level' => 3, 'is_daily_use' => false],
        ['name' => '街道/乡镇', 'code' => 'street', 'level' => 4, 'is_daily_use' => false],
        ['name' => '门牌/路段', 'code' => 'road', 'level' => 5, 'is_daily_use' => false],
        ['name' => '区域/园区', 'code' => 'campus', 'level' => 6, 'is_daily_use' => true],
        ['name' => '楼栋/建筑', 'code' => 'building', 'level' => 7, 'is_daily_use' => true],
        ['name' => '房间/工位', 'code' => 'room', 'level' => 8, 'is_daily_use' => true],
    ];

    public function up(): void
    {
        // ---------- 1. 同步层级定义 ----------
        foreach (self::STANDARD_LEVELS as $lv) {
            DB::table('location_levels')->updateOrInsert(
                ['code' => $lv['code']],
                array_merge($lv, [
                    'description' => $lv['is_daily_use']
                        ? '日常层：工单/报表级联选择时展示'
                        : '基础地址：初始化后固定存在，日常选择省略',
                    'sort_order' => $lv['level'],
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }

        // 清理不再使用的旧层级（仅当无地址引用时删除，否则仅停用）
        $standardCodes = array_column(self::STANDARD_LEVELS, 'code');
        foreach (LocationLevel::whereNotIn('code', $standardCodes)->get() as $old) {
            if ($old->locations()->exists()) {
                $old->update(['is_active' => false]);
            } else {
                $old->delete();
            }
        }

        // ---------- 2. 写入基础地址（通用占位）----------
        $levelId = fn (string $code) => LocationLevel::where('code', $code)->value('id');

        // 仅当基础地址链尚未初始化时写入占位；已初始化则保留现有数据不动
        $existingRoad = Location::where('level_id', $levelId('road'))->where('status', 'active')->first();
        if (! $existingRoad) {
            $base = [
                ['name' => '省份',   'code' => null, 'lv' => 'province'],
                ['name' => '城市',   'code' => null, 'lv' => 'city'],
                ['name' => '区县',   'code' => null, 'lv' => 'district'],
                ['name' => '街道',   'code' => null, 'lv' => 'street'],
                ['name' => '门牌号', 'code' => null, 'lv' => 'road'],
            ];

            $parentId = null;
            $root = null;
            foreach ($base as $item) {
                $node = Location::create([
                    'name' => $item['name'],
                    'code' => $item['code'],
                    'level_id' => $levelId($item['lv']),
                    'parent_id' => $parentId,
                    'sort_order' => 1,
                    'status' => 'active',
                ]);
                $parentId = $node->id;
                if ($item['lv'] === 'road') {
                    $root = $node;
                }
            }
        } else {
            $root = $existingRoad;
        }

        // ---------- 3. 依据 campuses 生成区域/园区节点 ----------
        $campusLvId = $levelId('campus');
        foreach (Campus::orderBy('sort_order')->orderBy('id')->get() as $campus) {
            $node = Location::where('level_id', $campusLvId)->where('name', $campus->name)->first();
            if (! $node) {
                Location::create([
                    'name' => $campus->name,
                    'level_id' => $campusLvId,
                    'parent_id' => $root->id,
                    'campus_id' => $campus->id,
                    'sort_order' => $campus->sort_order ?? 0,
                    'status' => 'active',
                ]);
            } elseif (! $node->campus_id) {
                $node->update(['campus_id' => $campus->id]);
            }
        }

        // ---------- 4. 挂载既有扁平楼栋到区域节点 ----------
        $buildingLvId = $levelId('building');
        foreach (Location::whereNull('level_id')->get() as $loc) {
            $payload = ['level_id' => $buildingLvId];
            if ($loc->campus_id) {
                $campusNode = Location::where('level_id', $campusLvId)->where('campus_id', $loc->campus_id)->first();
                if ($campusNode && ! $loc->parent_id) {
                    $payload['parent_id'] = $campusNode->id;
                }
            }
            $loc->update($payload);
        }
    }

    public function down(): void
    {
        // 不自动回滚数据，仅提示
    }
};
