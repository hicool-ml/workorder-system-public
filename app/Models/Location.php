<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Location extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'parent_id',
        'level_id',
        // 以下为旧字段，保留兼容
        'campus_id',
        'building_type',
        'building_code',
        'description',
        'sort_order',
        'status',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'campus_id' => 'integer',
        'parent_id' => 'integer',
        'level_id' => 'integer',
    ];

    // 兼容旧视图的状态选项
    const STATUSES = [
        'active' => '启用',
        'inactive' => '禁用',
    ];

    // 旧字段兼容：建筑类型选项（仅用于已有数据展示，新数据走树结构）
    const BUILDING_TYPES = [
        'office_building' => '办公楼',
        'meeting_room' => '会议室',
        'data_center' => '机房/数据中心',
        'common_area' => '公共区域',
        'parking' => '停车场',
        'warehouse' => '仓库',
        'other' => '其他',
    ];

    // ===== 树关系 =====

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Location::class, 'parent_id')
            ->orderBy('sort_order')->orderBy('name');
    }

    public function level(): BelongsTo
    {
        return $this->belongsTo(LocationLevel::class, 'level_id');
    }

    /** 旧关系兼容 */
    public function campus()
    {
        return $this->belongsTo(Campus::class);
    }

    // ===== 祖先链 =====

    /**
     * 获取从根到当前节点的完整祖先链（含自身）
     * 逐层查询 parent_id（带循环引用保护），适合少量节点调用。
     * 批量场景应配合 getTree() 已预加载的 children 关系使用。
     */
    public function getAncestors(): Collection
    {
        $chain = collect([$this]);
        $seen = [$this->id => true];
        $pid = $this->parent_id;
        while ($pid && ! isset($seen[$pid])) {
            $parent = static::find($pid);
            if (! $parent) {
                break;
            }
            $seen[$pid] = true;
            $chain->prepend($parent);
            $pid = $parent->parent_id;
        }

        return $chain;
    }

    /**
     * 完整地址：祖先链各节点 name 拼接（根→叶）
     */
    public function getFullAddressAttribute(): string
    {
        return $this->getAncestors()->pluck('name')->implode('');
    }

    /**
     * 带分隔符的完整地址
     */
    public function getFullAddressDelimitedAttribute(): string
    {
        return $this->getAncestors()->pluck('name')->implode(' / ');
    }

    // ===== 地址初始化状态 =====

    /**
     * 是否完成基础地址初始化。
     * 判定：最深层基础地址层级存在启用节点，且其祖先链覆盖全部基础层级。
     */
    public static function isBaseAddressInitialized(): bool
    {
        $baseLevels = LocationLevel::baseLevels();
        if ($baseLevels->isEmpty()) {
            return false;
        }

        $deepest = $baseLevels->last();
        $root = static::where('level_id', $deepest->id)->where('status', 'active')->first();
        if (! $root) {
            return false;
        }

        $covered = $root->getAncestors()->pluck('level_id');

        return $baseLevels->pluck('id')->diff($covered)->isEmpty();
    }

    /**
     * 日常地址的根节点：最深层基础地址节点（其子节点即校区/园区等日常层）。
     */
    public static function getDailyRoot(): ?Location
    {
        if (! static::isBaseAddressInitialized()) {
            return null;
        }

        $deepest = LocationLevel::baseLevels()->last();

        return static::where('level_id', $deepest->id)->where('status', 'active')->first();
    }

    /**
     * 地址前缀根节点（来自系统设置 address_prefix_location_id）。
     * 前缀根之上的层级（如省/市/区）在工单/地址管理界面默认不展示。
     * 未设置时回退到 getDailyRoot()（保持向下兼容）。
     */
    public static function getPrefixRoot(): ?Location
    {
        $id = SystemSetting::getAddressPrefixId();
        if (! $id) {
            return static::getDailyRoot();
        }

        return static::find($id);
    }

    /**
     * 前缀根的完整地址（带分隔符）。在工单/地址管理页面顶部作为只读上下文展示。
     */
    public static function getPrefixLabel(): ?string
    {
        $root = static::getPrefixRoot();
        if (! $root) {
            return null;
        }

        return $root->full_address_delimited;
    }

    /**
     * 构建日常地址树（仅含「校区/园区 → 楼栋 → 房间」等日常层级，不含基础地址链）
     * 返回：前缀根节点的子节点集合（嵌套 children）
     */
    public static function getDailyTree(): Collection
    {
        $root = static::getPrefixRoot();
        if (! $root) {
            return collect();
        }

        $depth = LocationLevel::dailyLevels()->count();
        $with = 'children';
        for ($i = 1; $i < $depth; $i++) {
            $with .= '.children';
        }
        $root->load($with);

        return $root->children;
    }

    /**
     * 前缀根下指定 level code 的直接子节点（用于工单表单的"校区下拉"）
     */
    public static function getPrefixChildrenByLevelCode(string $levelCode): Collection
    {
        $root = static::getPrefixRoot();
        if (! $root) {
            return collect();
        }

        $level = LocationLevel::where('code', $levelCode)->first();
        if (! $level) {
            return collect();
        }

        // 前缀根本身就是该层级：返回它本身（包一层 collect 便于统一处理）
        if ($root->level_id === $level->id) {
            return collect([$root]);
        }

        // 否则取前缀根下所有该层级的子孙节点（递归到任意深度）
        return static::where('level_id', $level->id)
            ->where('status', 'active')
            ->where(function ($q) use ($root) {
                // 必须是前缀根的子孙：通过 parent_id 链判断
                $q->where('parent_id', $root->id)
                  ->orWhereIn('parent_id', static::getDescendantIds($root->id));
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * 获取某节点的所有子孙 id（用于前缀根下任意深度查询，递归实现）。
     * 给定前缀根规模通常 < 几千节点，性能可接受。
     */
    public static function getDescendantIds(int $rootId): array
    {
        $ids = [];
        $queue = [$rootId];
        while (! empty($queue)) {
            $batch = array_splice($queue, 0, 100);
            $children = static::whereIn('parent_id', $batch)->pluck('id')->all();
            if (empty($children)) {
                continue;
            }
            foreach ($children as $id) {
                $ids[] = $id;
                $queue[] = $id;
            }
        }

        return $ids;
    }

    /**
     * 工单表单两段式选择：返回 [校区Id => 校区名] 选项
     * 数据源 = 前缀根下所有 level=6（campus）的节点
     */
    public static function getCampusOptionsForWorkorder(): array
    {
        return static::getPrefixChildrenByLevelCode('campus')
            ->pluck('name', 'id')
            ->toArray();
    }

    /**
     * 工单表单两段式选择：返回某个校区下所有 level=7（building）节点
     * 用于前端 JS 通过 campusId 联动 building 下拉
     */
    public static function getBuildingsUnderCampus(int $campusLocationId): array
    {
        $buildingLevel = LocationLevel::where('code', 'building')->first();
        if (! $buildingLevel) {
            return [];
        }

        return static::where('parent_id', $campusLocationId)
            ->where('level_id', $buildingLevel->id)
            ->where('status', 'active')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($item) => ['id' => $item->id, 'name' => $item->name])
            ->toArray();
    }

    /**
     * 一次性返回所有校区 → 楼栋 的映射，供前端 JS 使用
     * 返回格式：[campusId => ['name' => 校区名, 'buildings' => [['id'=>..,'name'=>..],...]]]
     */
    public static function getCampusBuildingTree(): array
    {
        $campuses = static::getPrefixChildrenByLevelCode('campus');
        $buildingLevelId = LocationLevel::where('code', 'building')->value('id');
        if (! $buildingLevelId) {
            return [];
        }

        $campusIds = $campuses->pluck('id')->all();
        $buildings = static::whereIn('parent_id', $campusIds)
            ->where('level_id', $buildingLevelId)
            ->where('status', 'active')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'parent_id']);

        $result = [];
        foreach ($campuses as $campus) {
            $result[$campus->id] = [
                'name' => $campus->name,
                'buildings' => $buildings->where('parent_id', $campus->id)
                    ->map(fn ($b) => ['id' => $b->id, 'name' => $b->name])
                    ->values()
                    ->toArray(),
            ];
        }

        return $result;
    }

    // ===== 树构建 =====

    /**
     * 构建整棵地址树（带层级名称）
     */
    public static function getTree(): Collection
    {
        $nodes = static::with('level')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return static::buildTree($nodes);
    }

    /**
     * 将扁平集合组织成嵌套树
     */
    protected static function buildTree(Collection $nodes, $parentId = null): Collection
    {
        return $nodes->where('parent_id', $parentId)->map(function ($node) use ($nodes) {
            $children = static::buildTree($nodes, $node->id);
            $node->setRelation('children', $children);

            return $node;
        })->values();
    }

    // ===== 递归下拉选项 =====

    /**
     * 获取带缩进的下拉选项 [id => '成都市 / 锦江区']
     */
    public static function getSelectOptions(): array
    {
        $tree = static::getTree();
        $options = [];
        static::flattenOptions($tree, '', $options);

        return $options;
    }

    protected static function flattenOptions(Collection $tree, string $prefix, array &$options): void
    {
        foreach ($tree as $node) {
            $label = $prefix ? "{$prefix} / {$node->name}" : $node->name;
            $options[$node->id] = $label;
            if ($node->relationLoaded('children') && $node->children->isNotEmpty()) {
                static::flattenOptions($node->children, $label, $options);
            }
        }
    }

    /**
     * 获取某个父节点的直接子节点（用于级联选择）
     */
    public static function getChildrenOf($parentId): Collection
    {
        return static::where('parent_id', $parentId)
            ->orWhere(function ($q) use ($parentId) {
                if (! $parentId) {
                    $q->whereNull('parent_id');
                }
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    // ===== 兼容旧接口 =====

    public function getCampusTextAttribute()
    {
        if ($this->level) {
            return $this->level->name;
        }

        return $this->campus ? $this->campus->name : '未设置';
    }

    public function getBuildingTypeTextAttribute()
    {
        return self::BUILDING_TYPES[$this->building_type] ?? $this->building_type ?? '';
    }

    public function getStatusTextAttribute()
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getFullNameAttribute()
    {
        // 新树结构优先
        if ($this->parent_id !== null || $this->level_id !== null) {
            return $this->full_address_delimited;
        }
        // 回退到旧逻辑
        $campus = $this->campus ? $this->campus->name : '未设置';

        return "{$campus} - {$this->name}";
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByCampus($query, $campusId)
    {
        return $query->where('campus_id', $campusId);
    }

    public function scopeByBuildingType($query, $type)
    {
        return $query->where('building_type', $type);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public static function getCampusOptions(): array
    {
        return Campus::where('status', 'active')
            ->orderBy('sort_order')->orderBy('name')
            ->pluck('name', 'id')->toArray();
    }

    public static function getCampusBuildings(): array
    {
        $locations = self::with('campus')->active()
            ->orderBy('campus_id')->orderBy('sort_order')->get();

        $result = [];
        foreach ($locations as $location) {
            $campusId = $location->campus_id ?? 0;
            $campusName = $location->campus ? $location->campus->name : '未设置';
            if (! isset($result[$campusId])) {
                $result[$campusId] = ['name' => $campusName, 'buildings' => []];
            }
            $result[$campusId]['buildings'][] = [
                'id' => $location->id,
                'name' => $location->name,
                'address' => $location->address ?? '',
            ];
        }

        return $result;
    }
}
