<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkorderSource extends Model
{
    protected $fillable = [
        'name',
        'description',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Get all active workorder sources, ordered by sort_order then name.
     */
    public static function getActiveSources()
    {
        return self::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * Get all active source names (for validation).
     */
    public static function getActiveSourceCodes(): array
    {
        return self::where('is_active', true)
            ->pluck('name')
            ->toArray();
    }

    /**
     * Find a source by its name (treated as "code").
     */
    public static function findByCode($code)
    {
        return self::where('name', $code)->first();
    }

    /**
     * Check if a source name already exists.
     */
    public static function isCodeExists($code, $excludeId = null)
    {
        $query = self::where('name', $code);
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        return $query->exists();
    }

    /**
     * Initialize default workorder sources.
     */
    public static function initializeDefaultSources()
    {
        $defaultSources = [
            ['name' => '电话报修', 'description' => '用户通过电话直接报修', 'sort_order' => 1],
            ['name' => '在线平台', 'description' => '通过网站或APP在线提交报修', 'sort_order' => 2],
            ['name' => '邮件申请', 'description' => '通过发送邮件申请维修服务', 'sort_order' => 3],
            ['name' => '现场报修', 'description' => '工作人员现场发现并记录的问题', 'sort_order' => 4],
            ['name' => '巡检发现', 'description' => '定期巡检过程中发现的设备问题', 'sort_order' => 5],
            ['name' => '系统预警', 'description' => '监控系统自动发出的预警信息', 'sort_order' => 6],
            ['name' => '其他来源', 'description' => '除上述分类外的其他报修方式', 'sort_order' => 7],
        ];

        foreach ($defaultSources as $source) {
            self::firstOrCreate(
                ['name' => $source['name']],
                $source
            );
        }
    }

    /**
     * Workorders using this source (matched by name).
     */
    public function workorders()
    {
        return $this->hasMany(\App\Models\Workorder::class, 'source', 'name');
    }
}
