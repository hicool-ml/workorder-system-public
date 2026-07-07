<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Department extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'manager_name',
        'manager_phone',
        'location',
        'description',
        'status',
        'sort_order',
    ];

    /**
     * 获取负责人属性（兼容视图中的manager字段）
     */
    public function getManagerAttribute(): string
    {
        return $this->manager_name ?? '';
    }

    /**
     * 获取联系电话属性（兼容视图中的phone字段）
     */
    public function getPhoneAttribute(): string
    {
        return $this->manager_phone ?? '';
    }

    /**
     * 获取邮箱属性（兼容视图中的email字段）
     */
    public function getEmailAttribute(): string
    {
        return ''; // 部门表中没有email字段，返回空字符串
    }

    /**
     * 获取是否启用属性（兼容视图中的is_active字段）
     */
    public function getIsActiveAttribute(): bool
    {
        return $this->status === 'active';
    }

    protected $casts = [
        'sort_order' => 'integer',
        'status' => 'string',
    ];

    /**
     * 获取部门用户
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * 获取部门工单
     */
    public function workorders(): HasMany
    {
        return $this->hasMany(Workorder::class);
    }
}