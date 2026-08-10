<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;


    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'phone',
        'employee_id',
        'wecom_userid',
        'dingtalk_userid',
        'feishu_user_id',
        'oidc_sub',
        'wechat_openid',
        'department_id',
        'role',
        'status',
        'location',
        'remarks',
        'account_type',
        'password_changed_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => 'string',
            'status' => 'string',
            'password_changed_at' => 'datetime',
        ];
    }

    /**
     * 获取是否启用属性（兼容视图中的is_active字段）
     */
    public function getIsActiveAttribute(): bool
    {
        return $this->status === 'active';
    }

    /**
     * 获取所属部门
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * 获取创建的工单
     */
    public function createdWorkorders(): HasMany
    {
        return $this->hasMany(Workorder::class, 'creator_id');
    }

    /**
     * 获取分配的工单
     */
    public function assignedWorkorders(): HasMany
    {
        return $this->hasMany(Workorder::class, 'assignee_id');
    }

    /**
     * 获取工单处理记录
     */
    public function workorderLogs(): HasMany
    {
        return $this->hasMany(WorkorderLog::class);
    }

    /**
     * 获取上传的附件
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(WorkorderAttachment::class);
    }

    /**
     * 获取回访记录
     */
    public function visits(): HasMany
    {
        return $this->hasMany(WorkorderVisit::class, 'visitor_id');
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
     * 检查是否为管理员
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }


    /**
     * 检查是否为工程师
     */
    public function isEngineer(): bool
    {
        return $this->role === 'engineer';
    }

    /**
     * 检查是否为工单管理员
     */
    public function isWorkorderManager(): bool
    {
        return $this->role === 'workorder_manager';
    }

    /**
     * 检查是否为普通用户
     */
    public function isUser(): bool
    {
        return $this->role === 'user';
    }

    /**
     * 检查是否为 CAS 统一身份认证用户
     */
    public function isCasUser(): bool
    {
        return $this->account_type === 'cas';
    }

    /**
     * 检查是否为 OIDC 统一身份认证用户
     */
    public function isOidcUser(): bool
    {
        return $this->account_type === 'oidc';
    }

    /**
     * 检查是否为任意统一身份认证用户（CAS 或 OIDC）
     */
    public function isSsoUser(): bool
    {
        return in_array($this->account_type, ['cas', 'oidc']);
    }

    /**
     * 检查用户是否具有指定角色
     */
    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    /**
     * 检查是否可以创建工单
     */
    public function canCreateWorkorders(): bool
    {
        return in_array($this->role, ['admin', 'workorder_manager', 'engineer']);
    }

    /**
     * 检查是否可以处理工单
     */
    public function canHandleWorkorders(): bool
    {
        return in_array($this->role, ['admin', 'engineer']);
    }

    /**
    * 检查是否可以分配工单
    */
   public function canAssignWorkorders(): bool
   {
       return in_array($this->role, ['admin', 'workorder_manager']);
   }

    /**
     * 检查是否可以回滚工单状态
     * 仅工单管理员和系统管理员可将工单回滚到更早的流程节点
     */
    public function canRollbackWorkorder(): bool
    {
        return in_array($this->role, ['admin', 'workorder_manager']);
    }

    /**
     * 检查是否可以接单（工程师、工单管理员、系统管理员）
     */
    public function canAcceptWorkorders(): bool
    {
        return in_array($this->role, ['admin', 'workorder_manager', 'engineer']);
    }

    /**
     * 检查是否可以关闭工单
     */
    public function canCloseWorkorders(): bool
    {
        return in_array($this->role, ['admin', 'workorder_manager']);
    }

    /**
     * 检查是否可以批量操作工单
     */
    public function canBatchOperateWorkorders(): bool
    {
        return in_array($this->role, ['admin', 'workorder_manager']);
    }

    /**
     * 检查是否可以导出工单
     */
    public function canExportWorkorders(): bool
    {
        return in_array($this->role, ['admin', 'workorder_manager']);
    }

    /**
     * 检查是否可以删除工单（软删除）
     */
    public function canDeleteWorkorders(): bool
    {
        return in_array($this->role, ['admin', 'workorder_manager']);
    }

    /**
     * 检查是否可以彻底删除工单
     */
    public function canForceDeleteWorkorders(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * 检查是否可以使用电话协助功能
     */
    public function canUsePhoneAssist(): bool
    {
        return in_array($this->role, ['admin', 'workorder_manager']);
    }

    /**
     * 检查是否可以指派工单给自己（工程师接单）
     */
    public function canAssignWorkorderToSelf(): bool
    {
        return in_array($this->role, ['admin', 'workorder_manager', 'engineer']);
    }

    /**
     * 检查是否可以邀请其他工程师协助
     */
    public function canInviteCollaborators(): bool
    {
        return in_array($this->role, ['admin', 'workorder_manager', 'engineer']);
    }

    /**
     * 检查是否可以管理工单附件
     * 传入工单时按工单上下文判断；未传入（如角色中间件调用）时按角色判断。
     */
    public function canManageWorkorderAttachments(?Workorder $workorder = null): bool
    {
        if ($workorder) {
            return $this->canUploadAttachment($workorder);
        }
        return in_array($this->role, ['admin', 'workorder_manager', 'engineer']);
    }

    /**
     * 检查是否可以添加工单备注
     */
    public function canAddWorkorderNotes(): bool
    {
        return in_array($this->role, ['admin', 'workorder_manager', 'engineer']);
    }

    /**
     * 检查是否可以添加备件耗材使用情况
     */
    public function canAddMaterialsUsage(): bool
    {
        return in_array($this->role, ['admin', 'workorder_manager', 'engineer']);
    }

    /**
     * 检查是否可以查看工单详情
     */
    public function canViewWorkorder(Workorder $workorder): bool
    {
        // 管理员和工单管理员可以查看所有工单
        if (in_array($this->role, ['admin', 'workorder_manager'])) {
            return true;
        }
        
        // 工程师可以查看自己创建的、分配给自己的或协作的工单
        if ($this->role === 'engineer') {
            if ($workorder->creator_id === $this->id || $workorder->assignee_id === $this->id) {
                return true;
            }
            // 所有未完结工单对工程师可见（便于就近接单、协作、了解全局）。
            // 必须与 getWorkorderQueryScope() 中 ->orWhereIn('status', ...) 保持一致，
            // 否则列表能看到却进不了详情页（403）。
            if (in_array($workorder->status, ['pending', 'assigned', 'processing', 'resolved'])) {
                return true;
            }

            // 已完结工单：仅当本人是创建人/负责人/（待接受或已接受）协作者时可见，用于跟进历史。
            // 检查是否是协作工程师（含待接受：被邀请人需先看到工单才能接受邀请）
            return $workorder->collaborations()
                ->where('collaborator_id', $this->id)
                ->whereIn('status', ['pending', 'accepted'])
                ->exists();
        }
        
        // 普通用户只能查看自己创建的工单
        return $this->role === 'user' && $workorder->creator_id === $this->id;
    }

    /**
     * 检查是否可以编辑工单
     */
    public function canEditWorkorder(Workorder $workorder): bool
    {
        // 管理员和工单管理员可以编辑所有工单
        if (in_array($this->role, ['admin', 'workorder_manager'])) {
            return true;
        }
        
        // 工程师只能编辑自己创建的或分配给自己的工单
        if ($this->role === 'engineer') {
            return $workorder->creator_id === $this->id || $workorder->assignee_id === $this->id;
        }
        
        // 普通用户只能编辑自己创建的工单
        return $this->role === 'user' && $workorder->creator_id === $this->id;
    }

    /**
     * 检查是否可以上传附件到工单
     */
    public function canUploadAttachment(Workorder $workorder): bool
    {
        // 管理员和工单管理员可以上传到所有工单
        if (in_array($this->role, ['admin', 'workorder_manager'])) {
            return true;
        }
        
        // 工程师可以上传到自己创建的、分配给自己的或协作的工单
        if ($this->role === 'engineer') {
            if ($workorder->creator_id === $this->id || $workorder->assignee_id === $this->id) {
                return true;
            }
            
            // 检查是否是已接受邀请的协作工程师（待接受无操作权限，需先接受邀请）
            return $workorder->collaborations()
                ->where('collaborator_id', $this->id)
                ->where('status', 'accepted')
                ->exists();
        }
        
        // 普通用户只能上传到自己创建的工单
        return $this->role === 'user' && $workorder->creator_id === $this->id;
    }
    
    /**
     * 检查是否可以管理工单模板
     */
    public function canManageWorkorderTemplates(): bool
    {
        return in_array($this->role, ['admin', 'workorder_manager']);
    }
    
    /**
     * 检查是否可以管理工单类型
     */
    public function canManageWorkorderTypes(): bool
    {
        return in_array($this->role, ['admin', 'workorder_manager']);
    }

    /**
     * 检查是否可以管理部门
     */
    public function canManageDepartments(): bool
    {
        return in_array($this->role, ['admin', 'workorder_manager']);
    }

    /**
     * 检查是否可以查看统计报表
     */
    public function canViewReports(): bool
    {
        return in_array($this->role, ['admin', 'workorder_manager']);
    }

    /**
     * 获取待处理的工单数量
     */
    public function getPendingWorkordersCountAttribute(): int
    {
        if (!$this->canHandleWorkorders()) {
            return 0;
        }
        
        return $this->assignedWorkorders()
            ->whereIn('status', ['pending', 'assigned', 'processing'])
            ->count();
    }

    /**
     * 获取今日处理的工单数量
     */
    public function getTodayWorkordersCountAttribute(): int
    {
        if (!$this->canHandleWorkorders()) {
            return 0;
        }
        
        return $this->assignedWorkorders()
            ->whereDate('updated_at', today())
            ->count();
    }

    /**
     * 获取所有可用的角色选项
     */
    public static function getRoleOptions(): array
    {
        return [
            'admin' => 'admin',
            'workorder_manager' => '工单管理员',
            'engineer' => '工程师',
            'user' => '普通用户',
        ];
    }

    /**
     * 获取所有可用的状态选项
     */
    public static function getStatusOptions(): array
    {
        return [
            'active' => '启用',
            'inactive' => '禁用',
        ];
    }

    /**
     * 获取可分配的工程师列表
     */
    public static function getAssignableEngineers(): \Illuminate\Database\Eloquent\Collection
    {
        return static::whereIn('role', ['engineer', 'workorder_manager'])
            ->where('status', 'active')
            ->orderBy('name')
            ->get();
    }
 
    /**
     * 按角色返回工单查询作用域，控制不同角色可见的工单范围。
     */
    public function getWorkorderQueryScope()
    {
        // 管理员/工单管理员：可见全部工单
        if (in_array($this->role, ['admin', 'workorder_manager'])) {
            return Workorder::query();
        }

        // 工程师：可见全部未完结工单（待分配/已分配/处理中/已解决），便于就近接单、协作；
        // 自己创建/负责/被邀请协作的工单即使在完结后仍可见（用于跟进历史）。
        // 必须与 canViewWorkorder() 中工程师分支保持一致，否则列表能看到却进不了详情（403）。
        if ($this->role === 'engineer') {
            return Workorder::where(function ($q) {
                $q->where('creator_id', $this->id)
                  ->orWhere('assignee_id', $this->id)
                  ->orWhereHas('collaborations', function ($collabQ) {
                      // 含待接受：被邀请人需在列表里看到工单才能接受邀请
                      $collabQ->where('collaborator_id', $this->id)
                              ->whereIn('status', ['pending', 'accepted']);
                  })
                  ->orWhereIn('status', ['pending', 'assigned', 'processing', 'resolved']);
            });
        }

        // 普通用户：仅可见自己创建的工单
        return Workorder::where('creator_id', $this->id);
    }
}
