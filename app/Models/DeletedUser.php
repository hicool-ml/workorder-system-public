<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeletedUser extends Model
{
    use HasFactory;

    protected $fillable = [
        'original_user_id',
        'name',
        'email',
        'username',
        'phone',
        'employee_id',
        'department_id',
        'role',
        'status',
        'location',
        'remarks',
        'account_type',
        'delete_reason',
        'deleted_by',
        'deleted_at',
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
    ];

    /**
     * 获取原用户信息
     */
    public function originalUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'original_user_id');
    }

    /**
     * 获取删除操作人
     */
    public function deletedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    /**
     * 获取角色文本
     */
    public function getRoleTextAttribute(): string
    {
        $roles = [
            'admin' => 'admin',
            'workorder_manager' => '工单管理员',
            'engineer' => '工程师',
            'user' => '普通用户',
        ];
        
        return $roles[$this->role] ?? $this->role;
    }

    /**
     * 获取状态文本
     */
    public function getStatusTextAttribute(): string
    {
        $statuses = [
            'active' => '启用',
            'inactive' => '禁用',
        ];
        
        return $statuses[$this->status] ?? $this->status;
    }

    /**
     * 创建已删除用户记录
     */
    public static function createFromUser(User $user, ?string $deleteReason = null, ?int $deletedBy = null): self
    {
        return static::create([
            'original_user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'username' => $user->username,
            'phone' => $user->phone,
            'employee_id' => $user->employee_id,
            'department_id' => $user->department_id,
            'role' => $user->role,
            'status' => $user->status,
            'location' => $user->location,
            'remarks' => $user->remarks,
            'account_type' => $user->account_type,
            'delete_reason' => $deleteReason,
            'deleted_by' => $deletedBy,
            'deleted_at' => now(),
        ]);
    }

    /**
     * 根据原用户ID查找已删除用户
     */
    public static function findByOriginalUserId(int $userId): ?self
    {
        return static::where('original_user_id', $userId)->first();
    }

    /**
     * 检查用户是否已删除
     */
    public static function isUserDeleted(int $userId): bool
    {
        return static::where('original_user_id', $userId)->exists();
    }
}