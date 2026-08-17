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
     * 密钥类设置键：值不得在页面回显/透传（视图只显示"已设置/未设置"）
     */
    public function isSecretKey(): bool
    {
        return static::isSecretKeyString($this->key);
    }

    public static function isSecretKeyString(string $key): bool
    {
        return (bool) preg_match('/(?:secret|password|token|api_key|access_key)/i', $key);
    }

    /**
     * 获取设置值（静态方法）— 请求级 + 永久缓存，set() 时失效
     */
    public static function get(string $key, $default = null)
    {
        return static::remember(function () use ($key) {
            return static::where('key', $key)->first();
        }, $key)?->typed_value ?? $default;
    }

    /**
     * 设置值（静态方法）
     */
    public static function set(string $key, $value, string $type = 'string', ?string $description = null, bool $isPublic = false)
    {
        $result = static::updateOrCreate(
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

        static::flushCache($key);

        return $result;
    }

    /**
     * 请求级静态缓存（避免同一请求内重复查询；设置读取频率极高）
     */
    private static array $cache = [];

    private static function remember(callable $loader, string $key): ?static
    {
        if (!array_key_exists($key, static::$cache)) {
            static::$cache[$key] = $loader();
        }

        return static::$cache[$key];
    }

    public static function flushCache(?string $key = null): void
    {
        if ($key === null) {
            static::$cache = [];
        } else {
            unset(static::$cache[$key]);
        }
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
     * 获取地址前缀根节点（工单/地址管理默认只展示该节点的子树）
     */
    public static function getAddressPrefixId(): ?int
    {
        $value = static::get('address_prefix_location_id', null);
        if ($value === null || $value === '' || $value === 0) {
            return null;
        }

        return (int) $value;
    }

    /**
     * 设置地址前缀根节点
     */
    public static function setAddressPrefixId(?int $locationId): void
    {
        static::set(
            'address_prefix_location_id',
            $locationId ?? 0,
            'integer',
            '地址前缀截止节点 ID（该节点之上层级在工单/管理界面默认隐藏）',
            false
        );
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
        // 部署版本从 VERSION 文件读取，作为 system_version 默认值，避免与仓库版本脱节
        $deployVersion = '2.0.0';
        $versionFile = base_path('VERSION');
        if (file_exists($versionFile)) {
            $v = trim((string) file_get_contents($versionFile));
            if ($v !== '') {
                $deployVersion = $v;
            }
        }

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
                'value' => $deployVersion,
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
