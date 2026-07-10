<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkorderTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'category_id',
        'contact_name',
        'contact_phone',
        'contact_email',
        'campus',
        'campus_id',
        'building',
        'location_detail',
        'time_limit_hours',
        'priority',
        'source',
        'department_name',
        'need_visit',
        'is_emergency',
        'phone_assisted',
        'other_reason',
        'is_active',
        'creator_id',
    ];

    protected $casts = [
        'need_visit' => 'boolean',
        'is_emergency' => 'boolean',
        'phone_assisted' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * 获取分类
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(WorkorderCategorySimplified::class, 'category_id');
    }

    /**
     * 获取创建人
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    /**
     * 获取优先级选项
     */
    public static function getPriorityOptions(): array
    {
        return [
            'high' => '高',
            'medium' => '中',
            'low' => '低',
        ];
    }

    /**
     * 获取来源选项
     */
    public static function getSourceOptions(): array
    {
        return [
            'phone' => '电话',
            'web' => '网页',
            'email' => '邮件',
            'scene' => '现场',
            'other' => '其他',
        ];
    }

    /**
     * 获取校区选项
     */
    public static function getCampusOptions(): array
    {
        return \App\Models\Campus::orderBy('sort_order')
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
    }

    /**
     * 获取优先级文本
     */
    public function getPriorityTextAttribute(): string
    {
        return self::getPriorityOptions()[$this->priority] ?? $this->priority;
    }

    /**
     * 获取来源文本
     */
    public function getSourceTextAttribute(): string
    {
        return self::getSourceOptions()[$this->source] ?? $this->source;
    }

    /**
     * 获取校区文本
     */
    public function getCampusTextAttribute(): string
    {
        $campus = \App\Models\Campus::find($this->campus_id);
        return $campus ? $campus->name : '';
    }

    /**
     * 获取位置信息
     */
    public function getLocationAttribute(): string
    {
        $location = '';
        if ($this->campus_id) {
            $location .= $this->campus_text;
        }
        if ($this->building) {
            $location .= ($location ? ' - ' : '') . $this->building;
        }
        if ($this->location_detail) {
            $location .= ($location ? ' - ' : '') . $this->location_detail;
        }
        return $location;
    }

    /**
     * 获取启用的模板
     */
    public static function getActiveTemplates()
    {
        return self::where('is_active', true)
            ->with(['category', 'creator'])
            ->orderBy('name')
            ->get();
    }

    /**
     * 根据模板创建工单数据
     */
    public function toWorkorderData(): array
    {
        return [
            'description' => $this->description,
            'category_main' => $this->category?->parent_id,
            'category_sub' => $this->category_id,
            'contact_name' => $this->contact_name,
            'contact_phone' => $this->contact_phone,
            'contact_email' => $this->contact_email,
            'campus' => $this->campus,
            'campus_id' => $this->campus_id,
            'building' => $this->building,
            'location_detail' => $this->location_detail,
            'time_limit_hours' => $this->time_limit_hours,
            'priority' => $this->priority,
            'source' => $this->source,
            'department_name' => $this->department_name,
            'need_visit' => $this->need_visit,
            'is_emergency' => $this->is_emergency,
            'phone_assisted' => $this->phone_assisted,
            'other_reason' => $this->other_reason,
        ];
    }

    /**
     * 搜索模板
     */
    public static function searchTemplates($keyword = null, $categoryId = null)
    {
        $query = self::where('is_active', true)
            ->with(['category', 'creator']);

        if ($keyword) {
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                  ->orWhere('description', 'like', "%{$keyword}%");
            });
        }

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        return $query->orderBy('name')->get();
    }
}
