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
        while ($pid && !isset($seen[$pid])) {
            $parent = static::find($pid);
            if (!$parent) {
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
                if (!$parentId) {
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
            if (!isset($result[$campusId])) {
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
