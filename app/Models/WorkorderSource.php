<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkorderSource extends Model
{
    use HasFactory;

    protected $table = 'workorder_sources';

    protected $fillable = [
        'name',
        'code',
        'description',
        'sort_order',
        'status',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'status' => 'string',
    ];

    /**
     * 默认的工单来源（与数据库 enum 字段保持一致）
     */
    public const DEFAULT_SOURCES = [
        'phone' => '电话',
        'web' => '网络',
        'email' => '邮件',
        'scene' => '现场',
        'other' => '其他',
    ];

    /**
     * 获取所有启用的来源代码列表
     */
    public static function getActiveSourceCodes(): array
    {
        $tableExists = self::tableExists();

        if (!$tableExists) {
            return array_keys(self::DEFAULT_SOURCES);
        }

        $codes = self::where('status', 'active')
            ->orderBy('sort_order')
            ->pluck('code')
            ->toArray();

        // 如果表中没有数据，回退到默认来源
        if (empty($codes)) {
            return array_keys(self::DEFAULT_SOURCES);
        }

        return $codes;
    }

    /**
     * 获取所有启用的来源选项（code => name）
     */
    public static function getActiveOptions(): array
    {
        $tableExists = self::tableExists();

        if (!$tableExists) {
            return self::DEFAULT_SOURCES;
        }

        $options = self::where('status', 'active')
            ->orderBy('sort_order')
            ->pluck('name', 'code')
            ->toArray();

        if (empty($options)) {
            return self::DEFAULT_SOURCES;
        }

        return $options;
    }

    /**
     * 检查表是否存在（避免迁移前调用报错）
     */
    private static function tableExists(): bool
    {
        try {
            return \Schema::hasTable('workorder_sources');
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 获取状态文本
     */
    public function getStatusTextAttribute(): string
    {
        return $this->status === 'active' ? '启用' : '禁用';
    }
}
