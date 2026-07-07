<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkorderType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'icon',
        'color',
        'parent_id',
        'level',
        'source',
        'subcategory',
        'default_priority',
        'default_hours',
        'status',
        'sort_order',
        'source_options',
        'default_ticket_prefix',
        'allow_user_select',
        'allowed_roles',
    ];

    protected $casts = [
        'default_priority' => 'integer',
        'default_hours' => 'integer',
        'sort_order' => 'integer',
        'level' => 'integer',
        'status' => 'string',
        'source_options' => 'array',
        'allowed_roles' => 'array',
        'allow_user_select' => 'boolean',
    ];

    /**
     * 获取是否启用属性（兼容视图中的is_active字段）
     */
    public function getIsActiveAttribute(): bool
    {
        return $this->status === 'active';
    }

    /**
     * 获取SLA时限属性（兼容视图中的sla_hours字段）
     */
    public function getSlaHoursAttribute(): int
    {
        return $this->default_hours;
    }

    /**
     * 获取该类型的工单
     */
    public function workorders(): HasMany
    {
        return $this->hasMany(Workorder::class, 'type_id');
    }

    /**
     * 获取优先级文本
     */
    public function getPriorityTextAttribute(): string
    {
        $priorities = [
            1 => '高',
            2 => '中',
            3 => '低',
        ];
        
        return $priorities[$this->default_priority] ?? '未知';
    }

    /**
     * 获取来源文本
     */
    public function getSourceTextAttribute(): string
    {
        $sources = [
            'phone' => '电话',
            'web' => '网络',
            'email' => '邮件',
            'scene' => '现场',
            'other' => '其他',
        ];
        
        return $sources[$this->source] ?? $this->source;
    }

    /**
     * 获取状态文本
     */
    public function getStatusTextAttribute(): string
    {
        $statuses = [
            'active' => '启用',
            'inactive' => '禁用',
        ];
        
        return $statuses[$this->status] ?? $this->status;
    }

    /**
     * 获取完整的类型名称（包含来源和子类别）
     */
    public function getFullNameAttribute(): string
    {
        $parts = [$this->name];
        
        if ($this->source) {
            $parts[] = $this->source_text;
        }
        
        if ($this->subcategory) {
            $parts[] = $this->subcategory;
        }
        
        return implode(' - ', $parts);
    }

    /**
     * 获取预计完成时间（基于默认小时数）
     */
    public function getExpectedCompleteTime(\Carbon\Carbon $startTime = null): \Carbon\Carbon
    {
        $startTime = $startTime ?? now();
        return $startTime->addHours($this->default_hours);
    }

    /**
     * 检查是否为紧急类型
     */
    public function isUrgent(): bool
    {
        return $this->default_priority === 1;
    }

    /**
     * 获取所有可用的来源选项
     */
    public static function getSourceOptions(): array
    {
        return [
            'phone' => '电话',
            'web' => '网络',
            'email' => '邮件',
            'scene' => '现场',
            'other' => '其他',
        ];
    }

    /**
     * 获取所有优先级选项
     */
    public static function getPriorityOptions(): array
    {
        return [
            1 => '高',
            2 => '中',
            3 => '低',
        ];
    }

    /**
     * 获取父级分类
     */
    public function parent()
    {
        return $this->belongsTo(WorkorderType::class, 'parent_id');
    }

    /**
     * 获取子分类
     */
    public function children()
    {
        return $this->hasMany(WorkorderType::class, 'parent_id');
    }

    /**
     * 获取所有子孙分类（递归）
     */
    public function descendants()
    {
        return $this->children()->with('descendants');
    }

    /**
     * 获取所有祖先分类（递归）
     */
    public function ancestors()
    {
        return $this->parent()->with('ancestors');
    }

    /**
     * 获取根分类（顶级分类）
     */
    public function root()
    {
        if ($this->parent_id === null) {
            return $this;
        }

        return $this->parent ? $this->parent->root() : null;
    }

    /**
     * 检查是否为根分类
     */
    public function isRoot(): bool
    {
        return $this->parent_id === null;
    }

    /**
     * 检查是否为叶子分类（没有子分类）
     */
    public function isLeaf(): bool
    {
        return $this->children()->count() === 0;
    }

    /**
     * 获取完整路径
     */
    public function getFullPathAttribute(): string
    {
        if ($this->isRoot()) {
            return $this->name;
        }

        $path = [];
        $current = $this;

        while ($current) {
            array_unshift($path, $current->name);
            $current = $current->parent;
        }

        return implode(' > ', $path);
    }

    /**
     * 获取同级分类
     */
    public function siblings()
    {
        return $this->parent
            ? $this->parent->children()->where('id', '!=', $this->id)
            : self::whereNull('parent_id')->where('id', '!=', $this->id);
    }

    /**
     * 获取指定层级的分类
     */
    public static function getLevel(int $level)
    {
        return self::where('level', $level)->where('status', 'active')->get();
    }

    /**
     * 获取分类树
     */
    public static function getTree($parentId = null)
    {
        $categories = self::where('parent_id', $parentId)
            ->where('status', 'active')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $tree = [];
        foreach ($categories as $category) {
            $category->children = self::getTree($category->id);
            $tree[] = $category;
        }

        return $tree;
    }

    /**
     * 获取扁平化的分类树（用于下拉菜单）
     */
    public static function getFlattenedTree($parentId = null, $prefix = '')
    {
        $categories = self::where('parent_id', $parentId)
            ->where('status', 'active')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $result = [];
        foreach ($categories as $category) {
            $result[$category->id] = $prefix . $category->name;
            $result = array_merge($result, self::getFlattenedTree($category->id, $prefix . '　　'));
        }

        return $result;
    }

    /**
     * 获取工单编号前缀
     */
    public function getTicketPrefixAttribute(): string
    {
        return $this->default_ticket_prefix ?? 'WO';
    }

    /**
     * 检查指定角色是否可以创建此类型工单
     */
    public function canBeCreatedByRole(string $role): bool
    {
        // 如果没有设置允许的角色，则所有角色都可以创建
        if (!$this->allowed_roles || empty($this->allowed_roles)) {
            return true;
        }
        
        return in_array($role, $this->allowed_roles);
    }

    /**
     * 获取可用的工单来源选项
     */
    public function getAvailableSources(): array
    {
        // 如果设置了来源选项，使用自定义的
        if ($this->source_options && !empty($this->source_options)) {
            return $this->source_options;
        }
        
        // 否则使用默认的来源选项
        return self::getSourceOptions();
    }

    /**
     * 获取所有可用的角色选项
     */
    public static function getRoleOptions(): array
    {
        return [
            'admin' => '管理员',
            'engineer' => '工程师',
            'user' => '普通用户',
        ];
    }

    /**
     * 获取所有可用的工单编号前缀选项
     */
    public static function getPrefixOptions(): array
    {
        return [
            'N' => 'N-网络故障',
            'M' => 'M-多媒体教室',
            'Z' => 'Z-专项',
            'WO' => 'WO-通用工单',
        ];
    }
}