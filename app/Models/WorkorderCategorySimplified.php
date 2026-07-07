<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkorderCategorySimplified extends Model
{
    use HasFactory;

    protected $table = 'workorder_categories_simplified';

    protected $fillable = [
        'name',
        'parent_id',
        'ticket_prefix',
        'default_hours',
        'color',
        'description',
        'sort_order',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
        'sort_order' => 'integer',
        'default_hours' => 'integer',
        'created_at' => 'datetime',
    ];

    /**
     * 获取父分类
     */
    public function parent()
    {
        return $this->belongsTo(WorkorderCategorySimplified::class, 'parent_id');
    }

    /**
     * 获取子分类
     */
    public function children()
    {
        return $this->hasMany(WorkorderCategorySimplified::class, 'parent_id')
            ->orderBy('sort_order', 'asc')
            ->orderBy('name', 'asc');
    }

    /**
     * 获取关联的工单
     */
    public function workorders()
    {
        return $this->hasMany(Workorder::class, 'category_id');
    }

    /**
     * 获取顶级分类
     */
    public static function getTopLevelCategories()
    {
        return self::whereNull('parent_id')
            ->where('status', true)
            ->orderBy('sort_order', 'asc')
            ->orderBy('name', 'asc')
            ->get();
    }

    /**
     * 获取子分类
     */
    public static function getSubCategories($parentId)
    {
        return self::where('parent_id', $parentId)
            ->where('status', true)
            ->orderBy('sort_order', 'asc')
            ->orderBy('name', 'asc')
            ->get();
    }


    /**
     * 获取分类路径
     */
    public function getPath()
    {
        $path = [];
        $current = $this;
        
        while ($current) {
            array_unshift($path, $current->name);
            $current = $current->parent;
        }
        
        return implode(' > ', $path);
    }

    /**
     * 获取工单编号前缀
     */
    public function getTicketPrefix()
    {
        if ($this->ticket_prefix) {
            return $this->ticket_prefix;
        }
        
        if ($this->parent) {
            return $this->parent->getTicketPrefix();
        }
        
        return 'WO';
    }

    /**
     * 获取默认处理时限
     */
    public function getDefaultHours()
    {
        if ($this->default_hours > 0) {
            return $this->default_hours;
        }
        
        if ($this->parent) {
            return $this->parent->getDefaultHours();
        }
        
        return 24;
    }

    /**
     * 获取显示颜色
     */
    public function getColor()
    {
        if ($this->color) {
            return $this->color;
        }
        
        if ($this->parent) {
            return $this->parent->getColor();
        }
        
        return '#6c757d';
    }

    /**
     * 检查是否为叶子节点（没有子分类）
     */
    public function isLeaf()
    {
        return $this->children()->count() === 0;
    }

    /**
     * 检查是否为根节点（没有父分类）
     */
    public function isRoot()
    {
        return is_null($this->parent_id);
    }

    /**
     * 获取层级文本
     */
    public function getLevelTextAttribute(): string
    {
        if ($this->isRoot()) {
            return '一级';
        } elseif ($this->isLeaf()) {
            return '二级';
        } else {
            return '二级';
        }
    }

    /**
     * 获取状态文本
     */
    public function getStatusTextAttribute(): string
    {
        return $this->status ? '启用' : '禁用';
    }

    /**
     * 获取是否启用属性（兼容视图中的is_active字段）
     */
    public function getIsActiveAttribute(): bool
    {
        return $this->status;
    }

    /**
     * 获取层级
     */
    public function getLevelAttribute(): int
    {
        if ($this->isRoot()) {
            return 1;
        } elseif ($this->isLeaf()) {
            return 2;
        } else {
            return 2;
        }
    }

    /**
     * 获取编码
     */
    public function getCodeAttribute(): string
    {
        return $this->ticket_prefix ?: 'WO';
    }

    /**
     * 获取所有子孙分类ID
     */
    public function getAllChildrenIds(): array
    {
        $ids = [];
        $children = $this->children;
        
        foreach ($children as $child) {
            $ids[] = $child->id;
            $ids = array_merge($ids, $child->getAllChildrenIds());
        }
        
        return $ids;
    }
}