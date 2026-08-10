<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SystemSetting extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'key',
        'value',
        'type',
        'description',
        'is_public',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_public' => 'boolean',
        'value' => 'string',
    ];

    /**
     * 获取设置值并根据类型转换
     */
    public function getTypedValueAttribute()
    {
        return match($this->type) {
            'boolean' => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $this->value,
            'float' => (float) $this->value,
            'json' => json_decode($this->value, true),
            'array' => json_decode($this->value, true),
            default => $this->value,
        };
    }

    /**
     * 设置值并自动转换为字符串存储
     */
    public function setTypedValueAttribute($value)
    {
        $this->attributes['value'] = match($this->type) {
            'json', 'array' => json_encode($value),
            'boolean' => $value ? '1' : '0',
            default => (string) $value,
        };
    }

    /**
     * 获取设置值（静态方法）
     */
    public static function get(string $key, $default = null)
    {
        $setting = static::where('key', $key)->first();
        
        if (!$setting) {
            return $default;
        }

        return $setting->typed_value;
    }

    /**
     * 设置值（静态方法）
     */
    public static function set(string $key, $value, string $type = 'string', string $description = null, bool $isPublic = false)
    {
        return static::updateOrCreate(
            ['key' => $key],
            [
                'value' => match($type) {
                    'json', 'array' => json_encode($value),
                    'boolean' => $value ? '1' : '0',
                    default => (string) $value,
                },
                'type' => $type,
                'description' => $description,
                'is_public' => $isPublic,
            ]
        );
    }

    /**
     * 检查开放注册是否启用
     */
    public static function isRegistrationEnabled(): bool
    {
        return static::get('registration_enabled', false);
    }

    /**
     * 启用/禁用开放注册
     */
    public static function toggleRegistration(bool $enabled = true)
    {
        return static::set(
            'registration_enabled',
            $enabled,
            'boolean',
            '是否开放用户注册',
            true
        );
    }

    /**
     * 获取默认用户角色
     */
    public static function getDefaultUserRole(): string
    {
        return static::get('default_user_role', 'user');
    }

    /**
     * 设置默认用户角色
     */
    public static function setDefaultUserRole(string $role)
    {
        return static::set(
            'default_user_role',
            $role,
            'string',
            '新注册用户的默认角色',
            false
        );
    }

    /**
     * 初始化默认系统设置
     */
    public static function initializeDefaults()
    {
        $defaults = [
            [
                'key' => 'registration_enabled',
                'value' => '0',
                'type' => 'boolean',
                'description' => '是否开放用户注册',
                'is_public' => true,
            ],
            [
                'key' => 'default_user_role',
                'value' => 'user',
                'type' => 'string',
                'description' => '新注册用户的默认角色',
                'is_public' => false,
            ],
            [
                'key' => 'system_name',
                'value' => '工单管理系统',
                'type' => 'string',
                'description' => '系统名称',
                'is_public' => true,
            ],
            [
                'key' => 'system_version',
                'value' => '2.0.0',
                'type' => 'string',
                'description' => '系统版本号',
                'is_public' => true,
            ],
            [
                'key' => 'system_release_date',
                'value' => '2025-12-16',
                'type' => 'string',
                'description' => '系统发布日期',
                'is_public' => true,
            ],
            [
                'key' => 'require_email_verification',
                'value' => '0',
                'type' => 'boolean',
                'description' => '是否需要邮箱验证',
                'is_public' => true,
            ],
            [
                'key' => 'session_lifetime',
                'value' => '120',
                'type' => 'integer',
                'description' => '登录会话有效期（分钟）',
                'is_public' => false,
            ],
        ];

        foreach ($defaults as $setting) {
            static::firstOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
