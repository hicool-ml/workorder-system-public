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
        'description',
        'sort_order',
        'status',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'parent_id' => 'integer',
        'level_id' => 'integer',
    ];

    // 兼容旧视图的状态选项
    const STATUSES = [
        'active' => '启用',
        'inactive' => '禁用',
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

    // ===== 祖先链 =====

    /**
     * 请求级 id => Location 映射（一次全表载入，供祖先链/校区查找复用）。
     * 地址表行数有限（楼宇/房间级），整表内存映射是最简单可靠的 N+1 消除方式。
     */
    private static ?Collection $allNodesCache = null;
    private static ?Location $prefixRootCache = null;

    public static function allNodesCached(): Collection
    {
        return static::$allNodesCache ??= static::all()->keyBy('id');
    }

    /**
     * 获取从根到当前节点的完整祖先链（含自身）
     * 优先走请求级内存映射（零 SQL）；未预热时逐层查询 parent_id（带循环引用保护）。
     */
    public function getAncestors(): Collection
    {
        $chain = collect([$this]);
        $seen = [$this->id => true];
        $pid = $this->parent_id;

        if (static::$allNodesCache !== null) {
            $nodes = static::$allNodesCache;
            while ($pid && ! isset($seen[$pid]) && $nodes->has($pid)) {
                $parent = $nodes[$pid];
                $seen[$pid] = true;
                $chain->prepend($parent);
                $pid = $parent->parent_id;
            }

            return $chain;
        }

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

    // ===== 项目（多基础地址链）=====

    /**
     * 是否完成基础地址初始化。
     * 判定：最深层基础地址层级存在≥1个启用节点。
     */
    public static function isBaseAddressInitialized(): bool
    {
        $baseLevels = LocationLevel::baseLevels();
        if ($baseLevels->isEmpty()) {
            return false;
        }
        $deepest = $baseLevels->last();
        return static::where('level_id', $deepest->id)->where('status', 'active')->exists();
    }

    /**
     * 所有项目根节点（road 层级的 active 节点），每个代表一个物业/项目。
     */
    public static function getProjectRoots(): Collection
    {
        $deepest = LocationLevel::baseLevels()->last();
        if (! $deepest) {
            return collect();
        }
        return static::where('level_id', $deepest->id)
            ->where('status', 'active')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * 项目根数量
     */
    public static function getProjectCount(): int
    {
        $deepest = LocationLevel::baseLevels()->last();
        if (! $deepest) {
            return 0;
        }
        return static::where('level_id', $deepest->id)->where('status', 'active')->count();
    }

    /**
     * 获取项目标签列表 [id => '四川省 / 成都市 / ... / 成洛大道2025号']
     */
    public static function getProjectOptions(): array
    {
        return static::getProjectRoots()
            ->mapWithKeys(fn ($node) => [$node->id => $node->full_address_delimited])
            ->all();
    }

    /**
     * 日常地址的第一个项目根节点（兼容旧调用）。
     */
    public static function getDailyRoot(): ?Location
    {
        return static::getProjectRoots()->first();
    }

    /**
     * 地址前缀根节点（来自系统设置 address_prefix_location_id）。
     * 未设置时返回 null（不截断，展示所有项目）。请求级缓存。
     */
    public static function getPrefixRoot(): ?Location
    {
        $id = SystemSetting::getAddressPrefixId();
        if (! $id) {
            return null;
        }
        return static::$prefixRootCache ??= static::find($id);
    }

    /**
     * 前缀根的完整地址。在工单/地址管理页面顶部作为只读上下文展示。
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
     * 构建日常地址树。
     * - 有前缀根：只返回该前缀根的子树
     * - 无前缀根：合并所有项目的子树（多项目场景）
     */
    public static function getDailyTree(): Collection
    {
        $prefix = static::getPrefixRoot();
        if ($prefix) {
            $depth = LocationLevel::dailyLevels()->count();
            $with = 'children';
            for ($i = 1; $i < $depth; $i++) {
                $with .= '.children';
            }
            $prefix->load($with);
            return $prefix->children;
        }

        // 无前缀根：聚合所有项目根的子节点
        return static::getProjectTrees()->flatMap->children;
    }

    /**
     * 获取所有项目的完整子树（每个项目根含嵌套 children）。
     * 用于地址树页面按项目分组展示。
     */
    public static function getProjectTrees(): Collection
    {
        $roots = static::getProjectRoots();
        if ($roots->isEmpty()) {
            return collect();
        }
        $depth = LocationLevel::dailyLevels()->count();
        $with = 'children';
        for ($i = 1; $i < $depth; $i++) {
            $with .= '.children';
        }
        $roots->load($with);
        return $roots;
    }

    /**
     * 前缀根或所有项目根下指定层级（level_id）的节点（用于工单表单的"区域下拉"）。
     * - 有前缀根：只查该前缀根下的节点
     * - 无前缀根（多项目模式）：查所有项目根下的节点
     */
    public static function getPrefixChildrenByLevelId(int $levelId): Collection
    {
        $root = static::getPrefixRoot();
        if ($root) {
            // 有前缀根：只查该前缀根的子树
            if ($root->level_id === $levelId) {
                return collect([$root]);
            }
            return static::where('level_id', $levelId)
                ->where('status', 'active')
                ->where(function ($q) use ($root) {
                    $q->where('parent_id', $root->id)
                      ->orWhereIn('parent_id', static::getDescendantIds($root->id));
                })
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get();
        }

        // 无前缀根（多项目模式）：查所有项目根直接子节点中该层级的节点
        $projectRoots = static::getProjectRoots();
        if ($projectRoots->isEmpty()) {
            return collect();
        }
        $projectRootIds = $projectRoots->pluck('id')->all();
        // 日常层级节点直接或间接挂在项目根下；
        // 先收集项目根的所有子孙 id，再筛选特定层级
        $allDescendantIds = [];
        foreach ($projectRootIds as $rid) {
            $allDescendantIds = array_merge($allDescendantIds, static::getDescendantIds($rid));
        }
        // 该层级的节点：parent_id 在 [项目根 + 子孙] 中 AND level_id 匹配
        $allParentIds = array_merge($projectRootIds, $allDescendantIds);
        return static::where('level_id', $levelId)
            ->where('status', 'active')
            ->whereIn('parent_id', $allParentIds)
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
     * 工单表单两段式选择：返回「区域」选项（日常层级第一级，旧称「校区」）。
     * 数据源 = 前缀根下所有日常层级第一级的节点
     */
    public static function getCampusOptionsForWorkorder(): array
    {
        $regionLevelId = LocationLevel::dailyLevelAt(0)?->id;
        if (! $regionLevelId) {
            return [];
        }

        return static::getPrefixChildrenByLevelId($regionLevelId)
            ->pluck('name', 'id')
            ->toArray();
    }

    /**
     * 工单表单两段式选择：返回某个区域下所有「楼栋」（日常层级第二级）节点
     * 用于前端 JS 通过 regionId 联动 building 下拉
     */
    public static function getBuildingsUnderCampus(int $campusLocationId): array
    {
        $buildingLevelId = LocationLevel::dailyLevelAt(1)?->id;
        if (! $buildingLevelId) {
            return [];
        }

        return static::where('parent_id', $campusLocationId)
            ->where('level_id', $buildingLevelId)
            ->where('status', 'active')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($item) => ['id' => $item->id, 'name' => $item->name])
            ->toArray();
    }

    /**
     * 一次性返回所有 区域 → 楼栋 的映射，供前端 JS 使用
     * 返回格式：[regionId => ['name' => 区域名, 'buildings' => [['id'=>..,'name'=>..],...]]]
     */
    public static function getCampusBuildingTree(): array
    {
        $regionLevelId = LocationLevel::dailyLevelAt(0)?->id;
        $buildingLevelId = LocationLevel::dailyLevelAt(1)?->id;
        if (! $regionLevelId || ! $buildingLevelId) {
            return [];
        }

        $regions = static::getPrefixChildrenByLevelId($regionLevelId);
        $regionIds = $regions->pluck('id')->all();
        $buildings = static::whereIn('parent_id', $regionIds)
            ->where('level_id', $buildingLevelId)
            ->where('status', 'active')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'parent_id']);

        $result = [];
        foreach ($regions as $region) {
            $result[$region->id] = [
                'name' => $region->name,
                'buildings' => $buildings->where('parent_id', $region->id)
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

    public function getStatusTextAttribute()
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }
}
