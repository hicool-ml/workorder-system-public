<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Campus extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'address',
        'contact_phone',
        'contact_person',
        'sort_order',
        'status',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    /**
     * 获取地址列表
     */
    public function locations(): HasMany
    {
        return $this->hasMany(Location::class);
    }

    /**
     * 获取活跃的地址列表
     */
    public function activeLocations(): HasMany
    {
        return $this->hasMany(Location::class)->where('status', 'active');
    }

    /**
     * 获取状态文本
     */
    public function getStatusTextAttribute(): string
    {
        return $this->status === 'active' ? '启用' : '禁用';
    }

    /**
     * 获取所有活跃的校区选项
     */
    public static function getActiveOptions(): array
    {
        return self::where('status', 'active')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
    }

    /**
     * 根据代码获取校区
     */
    public static function findByCode(string $code): ?self
    {
        return self::where('code', $code)->first();
    }

    /**
     * 检查是否可以删除
     */
    public function canBeDeleted(): bool
    {
        // 如果有关联的地址，则不能删除
        return !$this->locations()->exists();
    }
}
