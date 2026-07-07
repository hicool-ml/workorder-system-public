<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkorderCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'parent_id',
        'level',
        'sort_order',
        'status',
    ];

    protected $casts = [
        'parent_id' => 'integer',
        'level' => 'integer',
        'sort_order' => 'integer',
        'status' => 'string',
    ];

    /**
     * 获取是否启用属性（兼容视图中的is_active字段）
     */
    public function getIsActiveAttribute(): bool
    {
        return $this->status === 'active';
    }

    /**
     * 获取子分类
     */
    public function children(): HasMany
    {
        return $this->hasMany(WorkorderCategory::class, 'parent_id')
            ->orderBy('sort_order')
            ->orderBy('name');
    }

    /**
     * 获取父分类
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(WorkorderCategory::class, 'parent_id');
    }

    /**
     * 获取工单
     */
    public function workorders(): HasMany
    {
        return $this->hasMany(Workorder::class, 'category_id');
    }

    /**
     * 获取完整的分类路径
     */
    public function getFullPathAttribute(): string
    {
        $path = [$this->name];
        $parent = $this->parent;
        
        while ($parent) {
            array_unshift($path, $parent->name);
            $parent = $parent->parent;
        }
        
        return implode(' > ', $path);
    }

    /**
     * 检查是否为根分类
     */
    public function isRoot(): bool
    {
        return is_null($this->parent_id);
    }

    /**
     * 检查是否为叶子分类
     */
    public function isLeaf(): bool
    {
        return $this->children()->count() === 0;
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

    /**
     * 检查是否为指定分类的后代
     */
    public function isDescendantOf(WorkorderCategory $category): bool
    {
        if ($this->isRoot()) {
            return false;
        }
        
        $parent = $this->parent;
        
        while ($parent) {
            if ($parent->id === $category->id) {
                return true;
            }
            $parent = $parent->parent;
        }
        
        return false;
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
     * 获取层级文本
     */
    public function getLevelTextAttribute(): string
    {
        $levels = [
            1 => '一级分类',
            2 => '二级分类',
            3 => '三级分类',
        ];
        
        return $levels[$this->level] ?? '未知';
    }

    /**
     * 获取所有可用的状态选项
     */
    public static function getStatusOptions(): array
    {
        return [
            'active' => '启用',
            'inactive' => '禁用',
        ];
    }

    /**
     * 获取所有可用的层级选项
     */
    public static function getLevelOptions(): array
    {
        return [
            1 => '一级分类',
            2 => '二级分类',
            3 => '三级分类',
        ];
    }

    /**
     * 获取树形结构的分类
     */
    public static function getTree($parentId = null, $level = 1): array
    {
        $categories = static::where('parent_id', $parentId)
            ->where('status', 'active')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
            
        $tree = [];
        
        foreach ($categories as $category) {
            $node = [
                'id' => $category->id,
                'name' => $category->name,
                'code' => $category->code,
                'level' => $category->level,
                'full_path' => $category->full_path,
                'children' => static::getTree($category->id, $level + 1)
            ];
            $tree[] = $node;
        }
        
        return $tree;
    }

    /**
     * 获取用于下拉菜单的分类选项
     */
    public static function getSelectOptions($parentId = null, $prefix = ''): array
    {
        $options = [];
        $categories = static::where('parent_id', $parentId)
            ->where('status', 'active')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
            
        foreach ($categories as $category) {
            $options[$category->id] = $prefix . $category->name;
            
            if ($category->children()->count() > 0) {
                $options = array_merge(
                    $options, 
                    static::getSelectOptions($category->id, $prefix . '　　')
                );
            }
        }
        
        return $options;
    }

    /**
     * 获取用于三级联动的分类数据
     */
    public static function getCascadeData(): array
    {
        $level1 = static::where('level', 1)
            ->where('status', 'active')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
            
        $data = [];
        
        foreach ($level1 as $l1) {
            $level2 = static::where('parent_id', $l1->id)
                ->where('status', 'active')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get();
                
            $l2Data = [];
            foreach ($level2 as $l2) {
                $level3 = static::where('parent_id', $l2->id)
                    ->where('status', 'active')
                    ->orderBy('sort_order')
                    ->orderBy('name')
                    ->get();
                    
                $l3Data = [];
                foreach ($level3 as $l3) {
                    $l3Data[] = [
                        'id' => $l3->id,
                        'name' => $l3->name,
                        'code' => $l3->code,
                    ];
                }
                
                $l2Data[] = [
                    'id' => $l2->id,
                    'name' => $l2->name,
                    'code' => $l2->code,
                    'children' => $l3Data,
                ];
            }
            
            $data[] = [
                'id' => $l1->id,
                'name' => $l1->name,
                'code' => $l1->code,
                'children' => $l2Data,
            ];
        }
        
        return $data;
    }
}