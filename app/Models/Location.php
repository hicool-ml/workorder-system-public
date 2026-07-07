<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'campus',
        'building_type',
        'building_code',
        'description',
        'sort_order',
        'status',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    // 校区选项
    const CAMPUSES = [
        'old_campus' => '老校区',
        'new_campus' => '新校区',
        'asean_campus' => '东盟校区',
    ];

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
        return self::CAMPUSES[$this->campus] ?? $this->campus;
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
    public function scopeByCampus($query, $campus)
    {
        return $query->where('campus', $campus);
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
        return [
            'old_campus' => '老校区',
            'new_campus' => '新校区',
            'asean_campus' => '东盟校区',
        ];
    }
    
    /**
     * 获取校区楼栋数据，用于前端选择
     */
    public static function getCampusBuildings(): array
    {
        $locations = self::active()->orderBy('campus')->orderBy('sort_order')->get();
        
        $result = [];
        
        foreach ($locations as $location) {
            $campus = $location->campus;
            
            if (!isset($result[$campus])) {
                $result[$campus] = [];
            }
            
            $result[$campus][] = [
                'id' => $location->id,
                'name' => $location->name,
                'address' => $location->address ?? '',
            ];
        }
        
        return $result;
    }
}