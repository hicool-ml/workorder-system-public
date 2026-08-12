<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * 地址层级定义 —— 用户自主配置的分级方案。
 * 每条记录代表一个层级（如"市""区""街道"），locations 节点通过 level_id 引用。
 */
class LocationLevel extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'level',
        'description',
        'sort_order',
        'is_active',
        'is_daily_use',
    ];

    protected $casts = [
        'level' => 'integer',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
        'is_daily_use' => 'boolean',
    ];

    /**
     * 该层级下的所有地址节点
     */
    public function locations(): HasMany
    {
        return $this->hasMany(Location::class, 'level_id');
    }

    /**
     * 获取按层级深度排序的启用层级列表
     */
    public static function getActiveLevels(): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('is_active', true)
            ->orderBy('level')
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * 基础地址层级（is_daily_use=false）：省→市→区县→街道→门牌
     */
    public static function baseLevels(): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('is_active', true)
            ->where('is_daily_use', false)
            ->orderBy('level')
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * 日常使用层级（is_daily_use=true）：校区/园区→楼栋→房间
     */
    public static function dailyLevels(): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('is_active', true)
            ->where('is_daily_use', true)
            ->orderBy('level')
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * 是否基础地址层级
     */
    public function isBase(): bool
    {
        return ! $this->is_daily_use;
    }

    /**
     * 获取层级选项（id => name），按深度排列
     */
    public static function getOptions(): array
    {
        return self::where('is_active', true)
            ->orderBy('level')
            ->orderBy('sort_order')
            ->pluck('name', 'id')
            ->toArray();
    }

    /**
     * 获取最大层级深度
     */
    public static function maxDepth(): int
    {
        return (int) self::max('level') ?? 0;
    }
}
