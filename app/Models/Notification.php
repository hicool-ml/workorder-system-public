<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'title',
        'content',
        'data',
        'user_id',
        'workorder_id',
        'is_read',
        'read_at',
        'is_important',
    ];

    protected $casts = [
        'data' => 'array',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
        'is_important' => 'boolean',
    ];

    // ===== 通知类型常量 =====

    const TYPE_WORKORDER_CREATED = 'workorder_created';
    const TYPE_WORKORDER_ASSIGNED = 'workorder_assigned';
    const TYPE_WORKORDER_STARTED = 'workorder_started';
    const TYPE_WORKORDER_RESOLVED = 'workorder_resolved';
    const TYPE_WORKORDER_COMPLETED = 'workorder_completed';
    const TYPE_WORKORDER_CLOSED = 'workorder_closed';
    const TYPE_WORKORDER_COMMENT = 'workorder_comment';
    const TYPE_WORKORDER_VISIT_COMPLETED = 'workorder_visit_completed';
    const TYPE_WORKORDER_COLLABORATION_INVITED = 'workorder_collaboration_invited';
    const TYPE_WORKORDER_COLLABORATION_ACCEPTED = 'workorder_collaboration_accepted';
    const TYPE_WORKORDER_COLLABORATION_REJECTED = 'workorder_collaboration_rejected';
    const TYPE_SYSTEM_ANNOUNCEMENT = 'system_announcement';

    /** 通知类型元数据：type => [文本, 颜色, 图标] */
    const TYPE_META = [
        self::TYPE_WORKORDER_CREATED => ['text' => '工单创建', 'color' => 'success', 'icon' => 'fa-plus-circle'],
        self::TYPE_WORKORDER_ASSIGNED => ['text' => '工单分配', 'color' => 'info', 'icon' => 'fa-user-plus'],
        self::TYPE_WORKORDER_STARTED => ['text' => '工单开始处理', 'color' => 'warning', 'icon' => 'fa-play-circle'],
        self::TYPE_WORKORDER_RESOLVED => ['text' => '工单已解决', 'color' => 'info', 'icon' => 'fa-check-circle'],
        self::TYPE_WORKORDER_COMPLETED => ['text' => '工单已完结', 'color' => 'success', 'icon' => 'fa-check-double'],
        self::TYPE_WORKORDER_CLOSED => ['text' => '工单已关闭', 'color' => 'secondary', 'icon' => 'fa-times-circle'],
        self::TYPE_WORKORDER_COMMENT => ['text' => '工单处理记录', 'color' => 'primary', 'icon' => 'fa-comment'],
        self::TYPE_WORKORDER_VISIT_COMPLETED => ['text' => '工单回访完成', 'color' => 'success', 'icon' => 'fa-phone'],
        self::TYPE_WORKORDER_COLLABORATION_INVITED => ['text' => '协作邀请', 'color' => 'info', 'icon' => 'fa-handshake'],
        self::TYPE_WORKORDER_COLLABORATION_ACCEPTED => ['text' => '协作接受', 'color' => 'success', 'icon' => 'fa-check'],
        self::TYPE_WORKORDER_COLLABORATION_REJECTED => ['text' => '协作拒绝', 'color' => 'warning', 'icon' => 'fa-times'],
        self::TYPE_SYSTEM_ANNOUNCEMENT => ['text' => '系统公告', 'color' => 'danger', 'icon' => 'fa-bullhorn'],
    ];

    // ===== 关系 =====

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function workorder(): BelongsTo
    {
        return $this->belongsTo(Workorder::class);
    }

    // ===== 展示属性 =====

    public function getTypeTextAttribute(): string
    {
        return self::TYPE_META[$this->type]['text'] ?? $this->type;
    }

    public function getTypeColorAttribute(): string
    {
        return self::TYPE_META[$this->type]['color'] ?? 'secondary';
    }

    public function getTypeIconAttribute(): string
    {
        return self::TYPE_META[$this->type]['icon'] ?? 'fa-info-circle';
    }

    // ===== 已读状态 =====

    public function isRead(): bool
    {
        return $this->is_read;
    }

    public function markAsRead(): bool
    {
        if ($this->is_read) {
            return true;
        }

        return $this->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    // ===== 工单事件通知工厂 =====

    /** 工单创建：通知处理人 */
    public static function createWorkorderCreated(Workorder $workorder): ?self
    {
        if (!self::guardAssigneeExists($workorder, '创建通知')) {
            return null;
        }

        return self::createWorkorderNotification(
            $workorder,
            self::TYPE_WORKORDER_CREATED,
            '新工单创建',
            $workorder->assignee_id,
            "工单 {$workorder->ticket_no} 已创建，请及时处理。",
            ['creator_name' => $workorder->creator?->name ?? '未知用户'],
        );
    }

    /** 工单分配：通知处理人 */
    public static function createWorkorderAssigned(Workorder $workorder): ?self
    {
        if (!self::guardAssigneeExists($workorder, '分配通知')) {
            return null;
        }

        return self::createWorkorderNotification(
            $workorder,
            self::TYPE_WORKORDER_ASSIGNED,
            '工单已分配',
            $workorder->assignee_id,
            "工单 {$workorder->ticket_no} 已分配给您，请及时处理。",
            ['assignee_name' => $workorder->assignee?->name ?? '未分配'],
        );
    }

    /** 开始处理：通知创建人 */
    public static function createWorkorderStarted(Workorder $workorder, bool $notifyCreator = true): ?self
    {
        if (!self::guardAssigneeExists($workorder, '开始处理通知')
            || !self::shouldNotifyCreator($workorder, '开始处理通知')
            || !$notifyCreator) {
            return null;
        }

        return self::createWorkorderNotification(
            $workorder,
            self::TYPE_WORKORDER_STARTED,
            '工单开始处理',
            $workorder->creator_id,
            "工单 {$workorder->ticket_no} 已开始处理。",
            ['assignee_name' => $workorder->assignee?->name ?? '未分配'],
        );
    }

    /** 已解决：通知创建人 */
    public static function createWorkorderResolved(Workorder $workorder, bool $notifyCreator = true): ?self
    {
        if (!self::guardAssigneeExists($workorder, '解决通知')
            || !self::shouldNotifyCreator($workorder, '解决通知')
            || !$notifyCreator) {
            return null;
        }

        return self::createWorkorderNotification(
            $workorder,
            self::TYPE_WORKORDER_RESOLVED,
            '工单已解决',
            $workorder->creator_id,
            "工单 {$workorder->ticket_no} 已解决。",
            [
                'assignee_name' => $workorder->assignee?->name ?? '未分配',
                'solution' => $workorder->solution,
            ],
        );
    }

    /** 已完结：通知创建人 */
    public static function createWorkorderCompleted(Workorder $workorder, bool $notifyCreator = true): ?self
    {
        if (!self::guardAssigneeExists($workorder, '完结通知')
            || !self::shouldNotifyCreator($workorder, '完结通知')
            || !$notifyCreator) {
            return null;
        }

        return self::createWorkorderNotification(
            $workorder,
            self::TYPE_WORKORDER_COMPLETED,
            '工单已完结',
            $workorder->creator_id,
            "工单 {$workorder->ticket_no} 已完结。",
            ['assignee_name' => $workorder->assignee?->name ?? '未分配'],
        );
    }

    /** 已关闭：通知创建人 */
    public static function createWorkorderClosed(Workorder $workorder, bool $notifyCreator = true): ?self
    {
        if (!self::shouldNotifyCreator($workorder, '关闭通知') || !$notifyCreator) {
            return null;
        }

        return self::createWorkorderNotification(
            $workorder,
            self::TYPE_WORKORDER_CLOSED,
            '工单已关闭',
            $workorder->creator_id,
            "工单 {$workorder->ticket_no} 已关闭。",
            ['assignee_name' => $workorder->assignee?->name ?? '未分配'],
        );
    }

    /** 处理记录：通知创建人 */
    public static function createWorkorderComment(Workorder $workorder, string $content, User $user, bool $notifyCreator = true): ?self
    {
        if (!self::shouldNotifyCreator($workorder, '处理记录通知') || !$notifyCreator) {
            return null;
        }

        return self::createWorkorderNotification(
            $workorder,
            self::TYPE_WORKORDER_COMMENT,
            '工单处理记录',
            $workorder->creator_id,
            "工单 {$workorder->ticket_no} 有新的处理记录。",
            [
                'assignee_name' => $workorder->assignee?->name ?? '未分配',
                'comment_content' => $content,
                'user_name' => $user->name,
            ],
            spacious: true,
        );
    }

    /** 回访完成：通知创建人 */
    public static function createWorkorderVisitCompleted(Workorder $workorder, bool $notifyCreator = true): ?self
    {
        if (!self::guardAssigneeExists($workorder, '回访完成通知')
            || !self::shouldNotifyCreator($workorder, '回访完成通知')
            || !$notifyCreator) {
            return null;
        }

        return self::createWorkorderNotification(
            $workorder,
            self::TYPE_WORKORDER_VISIT_COMPLETED,
            '工单回访完成',
            $workorder->creator_id,
            "工单 {$workorder->ticket_no} 已完成回访。",
            ['assignee_name' => $workorder->assignee?->name ?? '未分配'],
            spacious: true,
        );
    }

    /** 协作邀请：通知协作者 */
    public static function createWorkorderCollaborationInvited(Workorder $workorder, User $inviter, User $collaborator, ?string $reason = null): ?self
    {
        if (!self::guardUserExists($inviter->id, '邀请者不存在，跳过协作邀请通知', ['workorder_id' => $workorder->id, 'inviter_id' => $inviter->id])
            || !self::guardUserExists($collaborator->id, '协作者不存在，跳过协作邀请通知', ['workorder_id' => $workorder->id, 'collaborator_id' => $collaborator->id])) {
            return null;
        }

        return self::createWorkorderNotification(
            $workorder,
            self::TYPE_WORKORDER_COLLABORATION_INVITED,
            '协作邀请',
            $collaborator->id,
            "您被邀请协助处理工单 {$workorder->ticket_no}",
            [
                'inviter_name' => $inviter->name,
                'collaborator_name' => $collaborator->name,
                'invitation_reason' => $reason,
            ],
            spacious: true,
        );
    }

    /** 协作接受：通知邀请者 */
    public static function createWorkorderCollaborationAccepted(Workorder $workorder, User $collaborator, User $inviter): ?self
    {
        if (!self::guardUserExists($collaborator->id, '协作者不存在，跳过协作接受通知', ['workorder_id' => $workorder->id, 'collaborator_id' => $collaborator->id])
            || !self::guardUserExists($inviter->id, '邀请者不存在，跳过协作接受通知', ['workorder_id' => $workorder->id, 'inviter_id' => $inviter->id])) {
            return null;
        }

        return self::createWorkorderNotification(
            $workorder,
            self::TYPE_WORKORDER_COLLABORATION_ACCEPTED,
            '协作接受',
            $inviter->id,
            "{$collaborator->name} 已接受您对工单 {$workorder->ticket_no} 的协作邀请",
            [
                'inviter_name' => $inviter->name,
                'collaborator_name' => $collaborator->name,
            ],
            spacious: true,
        );
    }

    /** 协作拒绝：通知邀请者 */
    public static function createWorkorderCollaborationRejected(Workorder $workorder, User $collaborator, User $inviter, ?string $note = null): ?self
    {
        if (!self::guardUserExists($collaborator->id, '协作者不存在，跳过协作拒绝通知', ['workorder_id' => $workorder->id, 'collaborator_id' => $collaborator->id])
            || !self::guardUserExists($inviter->id, '邀请者不存在，跳过协作拒绝通知', ['workorder_id' => $workorder->id, 'inviter_id' => $inviter->id])) {
            return null;
        }

        return self::createWorkorderNotification(
            $workorder,
            self::TYPE_WORKORDER_COLLABORATION_REJECTED,
            '协作拒绝',
            $inviter->id,
            "{$collaborator->name} 已拒绝您对工单 {$workorder->ticket_no} 的协作邀请",
            [
                'inviter_name' => $inviter->name,
                'collaborator_name' => $collaborator->name,
                'response_note' => $note,
            ],
            spacious: true,
        );
    }

    // ===== 系统公告 =====

    /**
     * 创建系统公告通知
     * targetType: all=全部活跃用户 / users=指定用户 / roles=指定角色的活跃用户
     */
    public static function createSystemAnnouncement(string $title, string $content, $targetType = 'all', ?array $targetIds = null, bool $isImportant = false): self
    {
        $userIds = match (true) {
            $targetType === 'all' => User::where('status', 'active')->pluck('id'),
            $targetType === 'users' && $targetIds => collect($targetIds),
            $targetType === 'roles' && $targetIds => User::whereIn('role', $targetIds)->where('status', 'active')->pluck('id'),
            default => null,
        };

        if ($userIds === null) {
            // 默认创建一个没有用户的通知（不推荐使用）
            return self::create([
                'type' => self::TYPE_SYSTEM_ANNOUNCEMENT,
                'title' => $title,
                'content' => $content,
                'is_important' => $isImportant,
                'data' => ['announcement_title' => $title],
            ]);
        }

        $first = null;
        foreach ($userIds as $userId) {
            try {
                $created = self::create([
                    'type' => self::TYPE_SYSTEM_ANNOUNCEMENT,
                    'title' => $title,
                    'content' => $content,
                    'is_important' => $isImportant,
                    'user_id' => $userId,
                    'data' => ['announcement_title' => $title],
                ]);
                if ($first === null) {
                    $first = $created;
                }
            } catch (\Exception $e) {
                Log::error('创建系统公告失败', [
                    'user_id' => $userId,
                    'title' => $title,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $first ?: new self();
    }

    // ===== 查询与已读操作 =====

    public static function getUnreadCount(int $userId): int
    {
        return self::where('user_id', $userId)
            ->where('is_read', false)
            ->count();
    }

    public static function getUserNotifications(int $userId, int $limit = 20)
    {
        return self::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public static function markNotificationAsRead(int $notificationId, int $userId): bool
    {
        $notification = self::where('id', $notificationId)
            ->where('user_id', $userId)
            ->first();

        if ($notification && !$notification->is_read) {
            $notification->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

            return true;
        }

        return false;
    }

    public static function markAllAsRead(int $userId): int
    {
        return self::where('user_id', $userId)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
    }

    // ===== 私有辅助 =====

    /**
     * 校验处理人存在，不存在时记录警告并返回 false
     */
    private static function guardAssigneeExists(Workorder $workorder, string $label): bool
    {
        if (!$workorder->assignee_id) {
            return false;
        }

        if (!User::where('id', $workorder->assignee_id)->exists()) {
            Log::warning("尝试为不存在的处理人创建{$label}", [
                'workorder_id' => $workorder->id,
                'assignee_id' => $workorder->assignee_id,
            ]);
            return false;
        }

        return true;
    }

    /**
     * 校验用户存在，不存在时记录警告并返回 false
     */
    private static function guardUserExists(int $userId, string $message, array $context = []): bool
    {
        if (!User::where('id', $userId)->exists()) {
            Log::warning($message, $context);
            return false;
        }

        return true;
    }

    /**
     * 检查工单创建人是否存在且可被通知。
     * 不存在时记录警告日志，返回 false。
     */
    private static function shouldNotifyCreator(Workorder $workorder, string $context = ''): bool
    {
        if (!$workorder->creator_id || !User::where('id', $workorder->creator_id)->exists()) {
            Log::warning('创建人不存在，跳过通知' . ($context ? '：' . $context : ''), [
                'workorder_id' => $workorder->id,
                'creator_id' => $workorder->creator_id,
            ]);
            return false;
        }
        return true;
    }

    /**
     * 工单事件通知统一创建入口。
     *
     * @param  array  $extraData  追加到 data 字段的键值对（如 assignee_name、solution 等）
     * @param  bool   $spacious   false=紧凑格式（地址-描述-状态-工程师），true=宽松格式（地址 描述 状态（工程师））
     */
    private static function createWorkorderNotification(
        Workorder $workorder,
        string $type,
        string $title,
        int $userId,
        string $fallbackContent,
        array $extraData = [],
        bool $spacious = false
    ): ?self {
        [$address, $description, $status, $assigneeName] = self::workorderSummary($workorder);

        $content = $spacious
            ? trim("{$address} {$description} {$status}（{$assigneeName}）")
            : trim("{$address}-{$description}-{$status}-{$assigneeName}");

        $data = array_merge([
            'workorder_id' => $workorder->id,
            'ticket_no' => $workorder->ticket_no,
            'description' => $workorder->description,
            'priority' => $workorder->priority,
            'address' => $address,
            'fault_type' => $description,
            'status' => $status,
        ], $extraData);

        return self::create([
            'type' => $type,
            'title' => $title,
            'content' => $content ?: $fallbackContent,
            'data' => $data,
            'user_id' => $userId,
            'workorder_id' => $workorder->id,
        ]);
    }

    /**
     * 工单摘要四元组：[地址, 故障描述, 状态, 工程师名]，均带默认值
     */
    private static function workorderSummary(Workorder $workorder): array
    {
        return [
            self::buildWorkorderAddress($workorder) ?: '未知地址',
            $workorder->description ?: '未知故障',
            $workorder->status_text ?: '未知状态',
            $workorder->assignee?->name ?: '未分配',
        ];
    }

    /**
     * 统一构建工单地址字符串
     * building 存的是 locations 表的 id，自动查完整楼名
     */
    private static function buildWorkorderAddress(Workorder $workorder): string
    {
        $parts = [];

        if ($workorder->campus_name) {
            $parts[] = $workorder->campus_name;
        }

        if ($workorder->building_name) {
            $parts[] = $workorder->building_name;
        }

        if ($workorder->location_detail && trim($workorder->location_detail)) {
            $parts[] = trim($workorder->location_detail);
        }

        return implode(' ', array_filter($parts)) ?: '未知地址';
    }
}
