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
        'fields',
        'category_main_id',
        'is_active',
        'creator_id',
    ];

    protected $casts = [
        'fields' => 'array',
        'is_active' => 'boolean',
    ];

    // ===== 系统预设字段定义 =====

    /**
     * 必要字段：每个工单必须有，模板中不可禁用
     */
    public const ESSENTIAL_FIELDS = [
        [
            'name' => 'description',
            'label' => '问题描述',
            'type' => 'textarea',
            'category' => 'essential',
            'required' => true,
        ],
        [
            'name' => 'category_main',
            'label' => '工单大类',
            'type' => 'select',
            'category' => 'essential',
            'required' => true,
        ],
        [
            'name' => 'category_sub',
            'label' => '故障分类',
            'type' => 'select',
            'category' => 'essential',
            'required' => true,
        ],
    ];

    /**
     * 建议字段：系统预设，用户可勾选启用/禁用
     */
    public const SUGGESTED_FIELDS = [
        [
            'name' => 'priority',
            'label' => '优先级',
            'type' => 'select',
            'category' => 'suggested',
            'required' => false,
            'options' => [
                ['value' => 'high', 'label' => '高'],
                ['value' => 'medium', 'label' => '中'],
                ['value' => 'low', 'label' => '低'],
            ],
        ],
        [
            'name' => 'time_limit_hours',
            'label' => '处理时限（小时）',
            'type' => 'number',
            'category' => 'suggested',
            'required' => false,
        ],
        [
            'name' => 'contact_name',
            'label' => '联系人',
            'type' => 'text',
            'category' => 'suggested',
            'required' => false,
        ],
        [
            'name' => 'contact_phone',
            'label' => '联系电话',
            'type' => 'text',
            'category' => 'suggested',
            'required' => false,
        ],
        [
            'name' => 'contact_email',
            'label' => '邮箱',
            'type' => 'text',
            'category' => 'suggested',
            'required' => false,
        ],
        [
            'name' => 'source',
            'label' => '来源',
            'type' => 'select',
            'category' => 'suggested',
            'required' => false,
            'options' => [
                ['value' => 'phone', 'label' => '电话'],
                ['value' => 'web', 'label' => '网页'],
                ['value' => 'scene', 'label' => '现场'],
                ['value' => 'email', 'label' => '邮件'],
            ],
        ],
        [
            'name' => 'department_name',
            'label' => '部门',
            'type' => 'text',
            'category' => 'suggested',
            'required' => false,
        ],
        [
            'name' => 'location_detail',
            'label' => '详细地址',
            'type' => 'text',
            'category' => 'suggested',
            'required' => false,
        ],
        [
            'name' => 'is_emergency',
            'label' => '紧急工单',
            'type' => 'checkbox',
            'category' => 'suggested',
            'required' => false,
        ],
        [
            'name' => 'need_visit',
            'label' => '需要回访',
            'type' => 'checkbox',
            'category' => 'suggested',
            'required' => false,
        ],
        [
            'name' => 'requires_signature',
            'label' => '需签单',
            'type' => 'checkbox',
            'category' => 'suggested',
            'required' => false,
        ],
    ];

    /**
     * 获取所有预设字段（必要 + 建议）
     */
    public static function getPresetFields(): array
    {
        return array_merge(self::ESSENTIAL_FIELDS, self::SUGGESTED_FIELDS);
    }

    // ===== 关系 =====

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function mainCategory(): BelongsTo
    {
        return $this->belongsTo(WorkorderCategorySimplified::class, 'category_main_id');
    }

    // ===== 业务方法 =====

    /**
     * 获取启用的模板
     */
    public static function getActiveTemplates()
    {
        return self::where('is_active', true)
            ->with('creator')
            ->orderBy('name')
            ->get();
    }

    /**
     * 从模板 fields JSON 提取工单表单预填数据
     * 返回 [field_name => value, ...] 仅含 essential + 已勾选的 suggested
     */
    public function toWorkorderData(): array
    {
        $data = [];
        $fields = $this->fields ?? [];

        foreach ($fields as $field) {
            // 只处理必要 + 建议字段的自定义部分（跳过 category=custom）
            if (($field['category'] ?? '') === 'custom') continue;
            if (array_key_exists('value', $field) && $field['value'] !== null && $field['value'] !== '') {
                $data[$field['name']] = $field['value'];
            }
        }

        return $data;
    }

    /**
     * 从模板 fields JSON 提取自定义字段（创建工单后存入 remarks 或额外字段）
     */
    public function getCustomFields(): array
    {
        $custom = [];
        foreach ($this->fields ?? [] as $field) {
            if (($field['category'] ?? '') === 'custom') {
                $custom[$field['label']] = $field['value'] ?? '';
            }
        }
        return $custom;
    }
}
