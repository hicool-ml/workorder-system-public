<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
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
    ];

    /**
     * 获取所属校区
     */
    public function campus()
    {
        return $this->belongsTo(Campus::class);
    }

    // 建筑类型选项
    const BUILDING_TYPES = [
        'teaching_building' => '教学楼',
        'dormitory' => '学生宿舍',
        'office_building' => '办公楼',
        'library' => '图书馆',
        'laboratory' => '实验室',
        'canteen' => '食堂',
        'sports_facility' => '体育设施',
        'other' => '其他',
    ];

    // 状态选项
    const STATUSES = [
        'active' => '启用',
        'inactive' => '禁用',
    ];

    /**
     * 获取校区文本
     */
    public function getCampusTextAttribute()
    {
        return $this->campus ? $this->campus->name : '未设置校区';
    }

    /**
     * 获取建筑类型文本
     */
    public function getBuildingTypeTextAttribute()
    {
        return self::BUILDING_TYPES[$this->building_type] ?? $this->building_type;
    }

    /**
     * 获取状态文本
     */
    public function getStatusTextAttribute()
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    /**
     * 获取完整地址名称
     */
    public function getFullNameAttribute()
    {
        $campus = $this->getCampusTextAttribute();
        $buildingType = $this->getBuildingTypeTextAttribute();
        
        if ($this->building_code) {
            return "{$campus} - {$buildingType} ({$this->building_code}) - {$this->name}";
        }
        
        return "{$campus} - {$buildingType} - {$this->name}";
    }

    /**
     * 查询活跃的地址
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * 按校区查询
     */
    public function scopeByCampus($query, $campusId)
    {
        return $query->where('campus_id', $campusId);
    }

    /**
     * 按建筑类型查询
     */
    public function scopeByBuildingType($query, $buildingType)
    {
        return $query->where('building_type', $buildingType);
    }

    /**
     * 按排序顺序查询
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }
    
    /**
     * 获取所有可用的校区选项
     */
    public static function getCampusOptions(): array
    {
        return Campus::where('status', 'active')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
    }
    
    /**
     * 获取校区楼栋数据，用于前端选择
     */
    public static function getCampusBuildings(): array
    {
        $locations = self::with('campus')
            ->active()
            ->orderBy('campus_id')
            ->orderBy('sort_order')
            ->get();
        
        $result = [];
        
        foreach ($locations as $location) {
            $campusId = $location->campus_id;
            $campusName = $location->campus ? $location->campus->name : '未设置校区';
            
            if (!isset($result[$campusId])) {
                $result[$campusId] = [
                    'name' => $campusName,
                    'buildings' => []
                ];
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
