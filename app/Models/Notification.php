<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    /**
     * 通知类型常量
     */
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

    /**
     * 获取接收用户
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 获取关联工单
     */
    public function workorder(): BelongsTo
    {
        return $this->belongsTo(Workorder::class);
    }

    /**
     * 获取通知类型文本
     */
    public function getTypeTextAttribute(): string
    {
        $types = [
            self::TYPE_WORKORDER_CREATED => '工单创建',
            self::TYPE_WORKORDER_ASSIGNED => '工单分配',
            self::TYPE_WORKORDER_STARTED => '工单开始处理',
            self::TYPE_WORKORDER_RESOLVED => '工单已解决',
            self::TYPE_WORKORDER_COMPLETED => '工单已完结',
            self::TYPE_WORKORDER_CLOSED => '工单已关闭',
            self::TYPE_WORKORDER_COMMENT => '工单处理记录',
            self::TYPE_WORKORDER_VISIT_COMPLETED => '工单回访完成',
            self::TYPE_WORKORDER_COLLABORATION_INVITED => '协作邀请',
            self::TYPE_WORKORDER_COLLABORATION_ACCEPTED => '协作接受',
            self::TYPE_WORKORDER_COLLABORATION_REJECTED => '协作拒绝',
            self::TYPE_SYSTEM_ANNOUNCEMENT => '系统公告',
        ];
        
        return $types[$this->type] ?? $this->type;
    }

    /**
     * 获取通知类型颜色
     */
    public function getTypeColorAttribute(): string
    {
        $colors = [
            self::TYPE_WORKORDER_CREATED => 'success',
            self::TYPE_WORKORDER_ASSIGNED => 'info',
            self::TYPE_WORKORDER_STARTED => 'warning',
            self::TYPE_WORKORDER_RESOLVED => 'info',
            self::TYPE_WORKORDER_COMPLETED => 'success',
            self::TYPE_WORKORDER_CLOSED => 'secondary',
            self::TYPE_WORKORDER_COMMENT => 'primary',
            self::TYPE_WORKORDER_VISIT_COMPLETED => 'success',
            self::TYPE_WORKORDER_COLLABORATION_INVITED => 'info',
            self::TYPE_WORKORDER_COLLABORATION_ACCEPTED => 'success',
            self::TYPE_WORKORDER_COLLABORATION_REJECTED => 'warning',
            self::TYPE_SYSTEM_ANNOUNCEMENT => 'danger',
        ];
        
        return $colors[$this->type] ?? 'secondary';
    }

    /**
     * 获取通知图标
     */
    public function getTypeIconAttribute(): string
    {
        $icons = [
            self::TYPE_WORKORDER_CREATED => 'fa-plus-circle',
            self::TYPE_WORKORDER_ASSIGNED => 'fa-user-plus',
            self::TYPE_WORKORDER_STARTED => 'fa-play-circle',
            self::TYPE_WORKORDER_RESOLVED => 'fa-check-circle',
            self::TYPE_WORKORDER_COMPLETED => 'fa-check-double',
            self::TYPE_WORKORDER_CLOSED => 'fa-times-circle',
            self::TYPE_WORKORDER_COMMENT => 'fa-comment',
            self::TYPE_WORKORDER_VISIT_COMPLETED => 'fa-phone',
            self::TYPE_WORKORDER_COLLABORATION_INVITED => 'fa-handshake',
            self::TYPE_WORKORDER_COLLABORATION_ACCEPTED => 'fa-check',
            self::TYPE_WORKORDER_COLLABORATION_REJECTED => 'fa-times',
            self::TYPE_SYSTEM_ANNOUNCEMENT => 'fa-bullhorn',
        ];
        
        return $icons[$this->type] ?? 'fa-info-circle';
    }

    /**
     * 检查是否已读
     */
    public function isRead(): bool
    {
        return $this->is_read;
    }

    /**
     * 标记为已读
     */
    public function markAsRead(): bool
    {
        if ($this->is_read) {
            return true;
        }
        
        $result = $this->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
        
        return $result;
    }

    /**
     * 创建工单创建通知
     */
    public static function createWorkorderCreated(Workorder $workorder): ?self
    {
        // 如果有分配处理人，则通知处理人，否则不创建通知
        if (!$workorder->assignee_id) {
            return null;
        }
        
        // 检查处理人是否存在，如果不存在则跳过
        if (!\App\Models\User::where('id', $workorder->assignee_id)->exists()) {
            \Log::warning('尝试为不存在的处理人创建创建通知', [
                'workorder_id' => $workorder->id,
                'assignee_id' => $workorder->assignee_id
            ]);
            return null;
        }
        
        // 获取地址信息，确保格式清晰且人类可读
        $address = self::buildWorkorderAddress($workorder);
        
        // 获取故障描述
        $description = $workorder->description ?: '';
        
        // 获取状态
        $status = $workorder->status_text;
        
        // 获取工程师名
        $assigneeName = '';
        if ($workorder->assignee) {
            $assigneeName = $workorder->assignee->name;
        }
        
        // 生成消息内容：地址+描述+状态+（工程师名）
        // 确保各部分都有值，如果没有则使用默认值
        $address = $address ?: '未知地址';
        $description = $description ?: '未知故障';
        $status = $status ?: '未知状态';
        $assigneeName = $assigneeName ?: '未分配';
        
        $content = trim("{$address}-{$description}-{$status}-{$assigneeName}");
        
        return self::create([
            'type' => self::TYPE_WORKORDER_CREATED,
            'title' => '新工单创建',
            'content' => $content ?: "工单 {$workorder->ticket_no} 已创建，请及时处理。",
            'data' => [
                'workorder_id' => $workorder->id,
                'ticket_no' => $workorder->ticket_no,
                'description' => $workorder->description,
                'priority' => $workorder->priority,
                'creator_name' => $workorder->creator ? $workorder->creator->name : '未知用户',
                'address' => $address,
                'fault_type' => $description,
                'status' => $status,
            ],
            'user_id' => $workorder->assignee_id,
            'workorder_id' => $workorder->id,
        ]);
    }

    /**
     * 创建工单分配通知
     */
    public static function createWorkorderAssigned(Workorder $workorder): ?self
    {
        // 如果没有处理人，则不创建通知
        if (!$workorder->assignee_id) {
            return null;
        }
        
        // 检查处理人是否存在，如果不存在则跳过
        if (!\App\Models\User::where('id', $workorder->assignee_id)->exists()) {
            \Log::warning('尝试为不存在的处理人创建分配通知', [
                'workorder_id' => $workorder->id,
                'assignee_id' => $workorder->assignee_id
            ]);
            return null;
        }
        // 获取地址信息，确保格式清晰且人类可读
        $address = self::buildWorkorderAddress($workorder);
        
        // 获取故障描述
        $description = $workorder->description ?: '';
        
        // 获取状态
        $status = $workorder->status_text;
        
        // 获取工程师名
        $assigneeName = '';
        if ($workorder->assignee) {
            $assigneeName = $workorder->assignee->name;
        }
        
        // 生成消息内容：地址+描述+状态+（工程师名）
        // 确保各部分都有值，如果没有则使用默认值
        $address = $address ?: '未知地址';
        $description = $description ?: '未知故障';
        $status = $status ?: '未知状态';
        $assigneeName = $assigneeName ?: '未分配';
        
        $content = trim("{$address}-{$description}-{$status}-{$assigneeName}");
        
        return self::create([
            'type' => self::TYPE_WORKORDER_ASSIGNED,
            'title' => '工单已分配',
            'content' => $content ?: "工单 {$workorder->ticket_no} 已分配给您，请及时处理。",
            'data' => [
                'workorder_id' => $workorder->id,
                'ticket_no' => $workorder->ticket_no,
                'description' => $workorder->description,
                'priority' => $workorder->priority,
                'assignee_name' => $workorder->assignee ? $workorder->assignee->name : '未分配',
                'address' => $address,
                'fault_type' => $description,
                'status' => $status,
            ],
            'user_id' => $workorder->assignee_id,
            'workorder_id' => $workorder->id,
        ]);
    }

    /**
     * 创建工单开始处理通知
     */
    public static function createWorkorderStarted(Workorder $workorder, bool $notifyCreator = true): ?self
    {
        // 如果没有处理人，则不创建通知
        if (!$workorder->assignee_id) {
            return null;
        }
        
        // 检查处理人是否存在，如果不存在则跳过
        if (!\App\Models\User::where('id', $workorder->assignee_id)->exists()) {
            \Log::warning('尝试为不存在的处理人创建开始处理通知', [
                'workorder_id' => $workorder->id,
                'assignee_id' => $workorder->assignee_id
            ]);
            return null;
        }
        
        if (!self::shouldNotifyCreator($workorder, '开始处理通知')) {
            return null;
        }
        
        // 获取地址信息，确保格式清晰且人类可读
        $address = self::buildWorkorderAddress($workorder);
        
        // 获取故障描述
        $description = $workorder->description ?: '';
        
        // 获取状态
        $status = $workorder->status_text;
        
        // 获取工程师名
        $assigneeName = '';
        if ($workorder->assignee) {
            $assigneeName = $workorder->assignee->name;
        }
        
        // 生成消息内容：地址+描述+状态+（工程师名）
        // 确保各部分都有值，如果没有则使用默认值
        $address = $address ?: '未知地址';
        $description = $description ?: '未知故障';
        $status = $status ?: '未知状态';
        $assigneeName = $assigneeName ?: '未分配';
        
        $content = trim("{$address}-{$description}-{$status}-{$assigneeName}");
        
        // 只为创建人创建通知（如果存在）
        if ($notifyCreator) {
            return self::create([
                'type' => self::TYPE_WORKORDER_STARTED,
                'title' => '工单开始处理',
                'content' => $content ?: "工单 {$workorder->ticket_no} 已开始处理。",
                'data' => [
                    'workorder_id' => $workorder->id,
                    'ticket_no' => $workorder->ticket_no,
                    'description' => $workorder->description,
                    'priority' => $workorder->priority,
                    'assignee_name' => $workorder->assignee ? $workorder->assignee->name : '未分配',
                    'address' => $address,
                    'fault_type' => $description,
                    'status' => $status,
                ],
                'user_id' => $workorder->creator_id,
                'workorder_id' => $workorder->id,
            ]);
        }
        
        // 如果创建人不存在，则不创建通知
        return null;
    }

    /**
     * 创建工单解决通知
     */
    public static function createWorkorderResolved(Workorder $workorder, bool $notifyCreator = true): ?self
    {
        // 如果没有处理人，则不创建通知
        if (!$workorder->assignee_id) {
            return null;
        }
        
        // 检查处理人是否存在，如果不存在则跳过
        if (!\App\Models\User::where('id', $workorder->assignee_id)->exists()) {
            \Log::warning('尝试为不存在的处理人创建解决通知', [
                'workorder_id' => $workorder->id,
                'assignee_id' => $workorder->assignee_id
            ]);
            return null;
        }
        
        if (!self::shouldNotifyCreator($workorder, '解决通知')) {
            return null;
        }
        
        // 获取地址信息，确保格式清晰且人类可读
        $address = self::buildWorkorderAddress($workorder);
        
        // 获取故障描述
        $description = $workorder->description ?: '';
        
        // 获取状态
        $status = $workorder->status_text;
        
        // 获取工程师名
        $assigneeName = '';
        if ($workorder->assignee) {
            $assigneeName = $workorder->assignee->name;
        }
        
        // 生成消息内容：地址+描述+状态+（工程师名）
        // 确保各部分都有值，如果没有则使用默认值
        $address = $address ?: '未知地址';
        $description = $description ?: '未知故障';
        $status = $status ?: '未知状态';
        $assigneeName = $assigneeName ?: '未分配';
        
        $content = trim("{$address}-{$description}-{$status}-{$assigneeName}");
        
        // 只为创建人创建通知（如果存在）
        if ($notifyCreator) {
            return self::create([
                'type' => self::TYPE_WORKORDER_RESOLVED,
                'title' => '工单已解决',
                'content' => $content ?: "工单 {$workorder->ticket_no} 已解决。",
                'data' => [
                    'workorder_id' => $workorder->id,
                    'ticket_no' => $workorder->ticket_no,
                    'description' => $workorder->description,
                    'priority' => $workorder->priority,
                    'assignee_name' => $workorder->assignee ? $workorder->assignee->name : '未分配',
                    'solution' => $workorder->solution,
                    'address' => $address,
                    'fault_type' => $description,
                    'status' => $status,
                ],
                'user_id' => $workorder->creator_id,
                'workorder_id' => $workorder->id,
            ]);
        }
        
        // 如果创建人不存在，则不创建通知
        return null;
    }

    /**
     * 创建工单完结通知
     */
    public static function createWorkorderCompleted(Workorder $workorder, bool $notifyCreator = true): ?self
    {
        // 如果没有处理人，则不创建通知
        if (!$workorder->assignee_id) {
            return null;
        }
        
        // 检查处理人是否存在，如果不存在则跳过
        if (!\App\Models\User::where('id', $workorder->assignee_id)->exists()) {
            \Log::warning('尝试为不存在的处理人创建完结通知', [
                'workorder_id' => $workorder->id,
                'assignee_id' => $workorder->assignee_id
            ]);
            return null;
        }
        
        if (!self::shouldNotifyCreator($workorder, '完结通知')) {
            return null;
        }
        
        // 获取地址信息，确保格式清晰且人类可读
        $address = self::buildWorkorderAddress($workorder);
        
        // 获取故障描述
        $description = $workorder->description ?: '';
        
        // 获取状态
        $status = $workorder->status_text;
        
        // 获取工程师名
        $assigneeName = '';
        if ($workorder->assignee) {
            $assigneeName = $workorder->assignee->name;
        }
        
        // 生成消息内容：地址+描述+状态+（工程师名）
        // 确保各部分都有值，如果没有则使用默认值
        $address = $address ?: '未知地址';
        $description = $description ?: '未知故障';
        $status = $status ?: '未知状态';
        $assigneeName = $assigneeName ?: '未分配';
        
        $content = trim("{$address}-{$description}-{$status}-{$assigneeName}");
        
        // 只为创建人创建通知（如果存在）
        if ($notifyCreator) {
            return self::create([
                'type' => self::TYPE_WORKORDER_COMPLETED,
                'title' => '工单已完结',
                'content' => $content ?: "工单 {$workorder->ticket_no} 已完结。",
                'data' => [
                    'workorder_id' => $workorder->id,
                    'ticket_no' => $workorder->ticket_no,
                    'description' => $workorder->description,
                    'priority' => $workorder->priority,
                    'assignee_name' => $workorder->assignee ? $workorder->assignee->name : '未分配',
                    'address' => $address,
                    'fault_type' => $description,
                    'status' => $status,
                ],
                'user_id' => $workorder->creator_id,
                'workorder_id' => $workorder->id,
            ]);
        }
        
        // 如果创建人不存在，则不创建通知
        return null;
    }

    /**
     * 创建工单关闭通知
     */
    public static function createWorkorderClosed(Workorder $workorder, bool $notifyCreator = true): ?self
    {
        if (!self::shouldNotifyCreator($workorder, '关闭通知')) {
            return null;
        }
        
        // 获取地址信息，确保格式清晰且人类可读
        $address = self::buildWorkorderAddress($workorder);
        
        // 获取故障描述
        $description = $workorder->description ?: '';
        
        // 获取状态
        $status = $workorder->status_text;
        
        // 获取工程师名
        $assigneeName = '';
        if ($workorder->assignee) {
            $assigneeName = $workorder->assignee->name;
        }
        
        // 生成消息内容：地址+描述+状态+（工程师名）
        // 确保各部分都有值，如果没有则使用默认值
        $address = $address ?: '未知地址';
        $description = $description ?: '未知故障';
        $status = $status ?: '未知状态';
        $assigneeName = $assigneeName ?: '未分配';
        
        $content = trim("{$address}-{$description}-{$status}-{$assigneeName}");
        
        // 只为创建人创建通知（如果存在）
        if ($notifyCreator) {
            return self::create([
                'type' => self::TYPE_WORKORDER_CLOSED,
                'title' => '工单已关闭',
                'content' => $content ?: "工单 {$workorder->ticket_no} 已关闭。",
                'data' => [
                    'workorder_id' => $workorder->id,
                    'ticket_no' => $workorder->ticket_no,
                    'description' => $workorder->description,
                    'priority' => $workorder->priority,
                    'assignee_name' => $workorder->assignee ? $workorder->assignee->name : '未分配',
                    'address' => $address,
                    'fault_type' => $description,
                    'status' => $status,
                ],
                'user_id' => $workorder->creator_id,
                'workorder_id' => $workorder->id,
            ]);
        }
        
        // 如果创建人不存在，则不创建通知
        return null;
    }

    /**
     * 创建工单处理记录通知
     */
    public static function createWorkorderComment(Workorder $workorder, string $content, User $user, bool $notifyCreator = true): ?self
    {
        if (!self::shouldNotifyCreator($workorder, '处理记录通知')) {
            return null;
        }
        // 获取地址信息，确保格式清晰且人类可读
        $address = self::buildWorkorderAddress($workorder);
        
        // 获取故障描述
        $description = $workorder->description ?: '';
        
        // 获取状态
        $status = $workorder->status_text;
        
        // 获取工程师名
        $assigneeName = '';
        if ($workorder->assignee) {
            $assigneeName = $workorder->assignee->name;
        }
        
        // 生成消息内容：地址+描述+状态+（工程师名）
        // 确保各部分都有值，如果没有则使用默认值
        $address = $address ?: '未知地址';
        $description = $description ?: '未知故障';
        $status = $status ?: '未知状态';
        $assigneeName = $assigneeName ?: '未分配';
        
        $notificationContent = trim("{$address} {$description} {$status}（{$assigneeName}）");
        
        // 只为创建人创建通知（如果存在）
        if ($notifyCreator) {
            return self::create([
                'type' => self::TYPE_WORKORDER_COMMENT,
                'title' => '工单处理记录',
                'content' => $notificationContent ?: "工单 {$workorder->ticket_no} 有新的处理记录。",
                'data' => [
                    'workorder_id' => $workorder->id,
                    'ticket_no' => $workorder->ticket_no,
                    'description' => $workorder->description,
                    'priority' => $workorder->priority,
                    'assignee_name' => $workorder->assignee ? $workorder->assignee->name : '未分配',
                    'comment_content' => $content,
                    'user_name' => $user->name,
                    'address' => $address,
                    'fault_type' => $description,
                    'status' => $status,
                ],
                'user_id' => $workorder->creator_id,
                'workorder_id' => $workorder->id,
            ]);
        }
        
        // 如果创建人不存在，则不创建通知
        return null;
    }

    /**
     * 创建工单回访完成通知
     */
    public static function createWorkorderVisitCompleted(Workorder $workorder, bool $notifyCreator = true): ?self
    {
        // 如果没有处理人，则不创建通知
        if (!$workorder->assignee_id) {
            return null;
        }
        
        // 检查处理人是否存在，如果不存在则跳过
        if (!\App\Models\User::where('id', $workorder->assignee_id)->exists()) {
            \Log::warning('尝试为不存在的处理人创建回访完成通知', [
                'workorder_id' => $workorder->id,
                'assignee_id' => $workorder->assignee_id
            ]);
            return null;
        }
        
        if (!self::shouldNotifyCreator($workorder, '回访完成通知')) {
            return null;
        }
        
        // 获取地址信息，确保格式清晰且人类可读
        $address = self::buildWorkorderAddress($workorder);
        
        // 获取故障描述
        $description = $workorder->description ?: '';
        
        // 获取状态
        $status = $workorder->status_text;
        
        // 获取工程师名
        $assigneeName = '';
        if ($workorder->assignee) {
            $assigneeName = $workorder->assignee->name;
        }
        
        // 生成消息内容：地址+描述+状态+（工程师名）
        // 确保各部分都有值，如果没有则使用默认值
        $address = $address ?: '未知地址';
        $description = $description ?: '未知故障';
        $status = $status ?: '未知状态';
        $assigneeName = $assigneeName ?: '未分配';
        
        $content = trim("{$address} {$description} {$status}（{$assigneeName}）");
        
        // 只为创建人创建通知（如果存在）
        if ($notifyCreator) {
            return self::create([
                'type' => self::TYPE_WORKORDER_VISIT_COMPLETED,
                'title' => '工单回访完成',
                'content' => $content ?: "工单 {$workorder->ticket_no} 已完成回访。",
                'data' => [
                    'workorder_id' => $workorder->id,
                    'ticket_no' => $workorder->ticket_no,
                    'description' => $workorder->description,
                    'priority' => $workorder->priority,
                    'assignee_name' => $workorder->assignee ? $workorder->assignee->name : '未分配',
                    'address' => $address,
                    'fault_type' => $description,
                    'status' => $status,
                ],
                'user_id' => $workorder->creator_id,
                'workorder_id' => $workorder->id,
            ]);
        }
        
        // 如果创建人不存在，则不创建通知
        return null;
    }

    /**
     * 创建协作邀请通知
     */
    public static function createWorkorderCollaborationInvited(Workorder $workorder, User $inviter, User $collaborator, ?string $reason = null): ?self
    {
        // 检查邀请者和协作者是否存在，如果不存在则跳过
        if (!\App\Models\User::where('id', $inviter->id)->exists()) {
            \Log::warning('邀请者不存在，跳过协作邀请通知', [
                'workorder_id' => $workorder->id,
                'inviter_id' => $inviter->id
            ]);
            return null;
        }
        
        if (!\App\Models\User::where('id', $collaborator->id)->exists()) {
            \Log::warning('协作者不存在，跳过协作邀请通知', [
                'workorder_id' => $workorder->id,
                'collaborator_id' => $collaborator->id
            ]);
            return null;
        }
        // 获取地址信息，确保格式清晰且人类可读
        $address = self::buildWorkorderAddress($workorder);
        
        // 获取故障描述
        $description = $workorder->description ?: '';
        
        // 获取状态
        $status = $workorder->status_text;
        
        // 获取工程师名
        $assigneeName = '';
        if ($workorder->assignee) {
            $assigneeName = $workorder->assignee->name;
        }
        
        // 生成消息内容：地址+描述+状态+（工程师名）
        // 确保各部分都有值，如果没有则使用默认值
        $address = $address ?: '未知地址';
        $description = $description ?: '未知故障';
        $status = $status ?: '未知状态';
        $assigneeName = $assigneeName ?: '未分配';
        
        $content = trim("{$address} {$description} {$status}（{$assigneeName}）");
        
        return self::create([
            'type' => self::TYPE_WORKORDER_COLLABORATION_INVITED,
            'title' => '协作邀请',
            'content' => $content ?: "您被邀请协助处理工单 {$workorder->ticket_no}",
            'data' => [
                'workorder_id' => $workorder->id,
                'ticket_no' => $workorder->ticket_no,
                'description' => $workorder->description,
                'priority' => $workorder->priority,
                'inviter_name' => $inviter->name,
                'collaborator_name' => $collaborator->name,
                'invitation_reason' => $reason,
                'address' => $address,
                'fault_type' => $description,
                'status' => $status,
            ],
            'user_id' => $collaborator->id,
            'workorder_id' => $workorder->id,
        ]);
    }

    /**
     * 创建协作接受通知
     */
    public static function createWorkorderCollaborationAccepted(Workorder $workorder, User $collaborator, User $inviter): ?self
    {
        // 检查协作者和邀请者是否存在，如果不存在则跳过
        if (!\App\Models\User::where('id', $collaborator->id)->exists()) {
            \Log::warning('协作者不存在，跳过协作接受通知', [
                'workorder_id' => $workorder->id,
                'collaborator_id' => $collaborator->id
            ]);
            return null;
        }
        
        if (!\App\Models\User::where('id', $inviter->id)->exists()) {
            \Log::warning('邀请者不存在，跳过协作接受通知', [
                'workorder_id' => $workorder->id,
                'inviter_id' => $inviter->id
            ]);
            return null;
        }
        // 获取地址信息，确保格式清晰
        $address = self::buildWorkorderAddress($workorder);
        
        // 获取故障描述
        $description = $workorder->description ?: '';
        
        // 获取状态
        $status = $workorder->status_text;
        
        // 获取工程师名
        $assigneeName = '';
        if ($workorder->assignee) {
            $assigneeName = $workorder->assignee->name;
        }
        
        // 生成消息内容：地址+描述+状态+（工程师名）
        // 确保各部分都有值，如果没有则使用默认值
        $address = $address ?: '未知地址';
        $description = $description ?: '未知故障';
        $status = $status ?: '未知状态';
        $assigneeName = $assigneeName ?: '未分配';
        
        $content = trim("{$address} {$description} {$status}（{$assigneeName}）");
        
        return self::create([
            'type' => self::TYPE_WORKORDER_COLLABORATION_ACCEPTED,
            'title' => '协作接受',
            'content' => $content ?: "{$collaborator->name} 已接受您对工单 {$workorder->ticket_no} 的协作邀请",
            'data' => [
                'workorder_id' => $workorder->id,
                'ticket_no' => $workorder->ticket_no,
                'description' => $workorder->description,
                'priority' => $workorder->priority,
                'inviter_name' => $inviter->name,
                'collaborator_name' => $collaborator->name,
                'address' => $address,
                'fault_type' => $description,
                'status' => $status,
            ],
            'user_id' => $inviter->id,
            'workorder_id' => $workorder->id,
        ]);
    }

    /**
     * 创建协作拒绝通知
     */
    public static function createWorkorderCollaborationRejected(Workorder $workorder, User $collaborator, User $inviter, ?string $note = null): ?self
    {
        // 检查协作者和邀请者是否存在，如果不存在则跳过
        if (!\App\Models\User::where('id', $collaborator->id)->exists()) {
            \Log::warning('协作者不存在，跳过协作拒绝通知', [
                'workorder_id' => $workorder->id,
                'collaborator_id' => $collaborator->id
            ]);
            return null;
        }
        
        if (!\App\Models\User::where('id', $inviter->id)->exists()) {
            \Log::warning('邀请者不存在，跳过协作拒绝通知', [
                'workorder_id' => $workorder->id,
                'inviter_id' => $inviter->id
            ]);
            return null;
        }
        // 获取地址信息，确保格式清晰
        $address = self::buildWorkorderAddress($workorder);
        
        // 获取故障描述
        $description = $workorder->description ?: '';
        
        // 获取状态
        $status = $workorder->status_text;
        
        // 获取工程师名
        $assigneeName = '';
        if ($workorder->assignee) {
            $assigneeName = $workorder->assignee->name;
        }
        
        // 生成消息内容：地址+描述+状态+（工程师名）
        // 确保各部分都有值，如果没有则使用默认值
        $address = $address ?: '未知地址';
        $description = $description ?: '未知故障';
        $status = $status ?: '未知状态';
        $assigneeName = $assigneeName ?: '未分配';
        
        $content = trim("{$address} {$description} {$status}（{$assigneeName}）");
        
        return self::create([
            'type' => self::TYPE_WORKORDER_COLLABORATION_REJECTED,
            'title' => '协作拒绝',
            'content' => $content ?: "{$collaborator->name} 已拒绝您对工单 {$workorder->ticket_no} 的协作邀请",
            'data' => [
                'workorder_id' => $workorder->id,
                'ticket_no' => $workorder->ticket_no,
                'description' => $workorder->description,
                'priority' => $workorder->priority,
                'inviter_name' => $inviter->name,
                'collaborator_name' => $collaborator->name,
                'response_note' => $note,
                'address' => $address,
                'fault_type' => $description,
                'status' => $status,
            ],
            'user_id' => $inviter->id,
            'workorder_id' => $workorder->id,
        ]);
    }

    /**
     * 创建系统公告通知
     */
    public static function createSystemAnnouncement(string $title, string $content, $targetType = 'all', ?array $targetIds = null, bool $isImportant = false): self
    {
        // 根据目标类型发送通知
        if ($targetType === 'all') {
            // 发送给所有用户
            $users = \App\Models\User::where('status', 'active')->get();
            $notifications = collect();
            foreach ($users as $user) {
                try {
                    $notifications->push(self::create([
                        'type' => self::TYPE_SYSTEM_ANNOUNCEMENT,
                        'title' => $title,
                        'content' => $content,
                        'is_important' => $isImportant,
                        'user_id' => $user->id,
                        'data' => [
                            'announcement_title' => $title,
                        ],
                    ]));
                } catch (\Exception $e) {
                    \Log::error('创建系统公告失败', [
                        'user_id' => $user->id,
                        'title' => $title,
                        'error' => $e->getMessage()
                    ]);
                }
            }
            return $notifications->first() ?: new self();
        } elseif ($targetType === 'users' && $targetIds) {
            // 发送给指定用户
            $notifications = collect();
            foreach ($targetIds as $userId) {
                try {
                    $notifications->push(self::create([
                        'type' => self::TYPE_SYSTEM_ANNOUNCEMENT,
                        'title' => $title,
                        'content' => $content,
                        'is_important' => $isImportant,
                        'user_id' => $userId,
                        'data' => [
                            'announcement_title' => $title,
                        ],
                    ]));
                } catch (\Exception $e) {
                    \Log::error('创建系统公告失败', [
                        'user_id' => $userId,
                        'title' => $title,
                        'error' => $e->getMessage()
                    ]);
                }
            }
            return $notifications->first() ?: new self();
        } elseif ($targetType === 'roles' && $targetIds) {
            // 发送给指定角色的用户
            $notifications = collect();
            foreach ($targetIds as $roleId) {
                $users = \App\Models\User::where('role', $roleId)->where('status', 'active')->get();
                foreach ($users as $user) {
                    try {
                        $notifications->push(self::create([
                            'type' => self::TYPE_SYSTEM_ANNOUNCEMENT,
                            'title' => $title,
                            'content' => $content,
                            'is_important' => $isImportant,
                            'user_id' => $user->id,
                            'data' => [
                                'announcement_title' => $title,
                            ],
                        ]));
                    } catch (\Exception $e) {
                        \Log::error('创建系统公告失败', [
                            'user_id' => $user->id,
                            'role' => $roleId,
                            'title' => $title,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }
            return $notifications->first() ?: new self();
        }
        
        // 默认创建一个没有用户的通知（不推荐使用）
        return self::create([
            'type' => self::TYPE_SYSTEM_ANNOUNCEMENT,
            'title' => $title,
            'content' => $content,
            'is_important' => $isImportant,
            'data' => [
                'announcement_title' => $title,
            ],
        ]);
    }

    /**
     * 获取用户未读通知数量
     */
    public static function getUnreadCount(int $userId): int
    {
        return self::where('user_id', $userId)
            ->where('is_read', false)
            ->count();
    }

    /**
     * 获取用户通知列表
     */
    public static function getUserNotifications(int $userId, int $limit = 20)
    {
        return self::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * 标记通知为已读
     */
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

    /**
     * 标记用户所有通知为已读
     */
    public static function markAllAsRead(int $userId): int
    {
        return self::where('user_id', $userId)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
    }

    /**
     * 获取区域映射配置
     * 从系统设置中获取区域标识到人类可读名称的映射
     */
    private static function getCampusMapping(): array
    {
        // Dynamically load campus mapping from the campuses table
        try {
            return \App\Models\Campus::orderBy('sort_order')
                ->orderBy('name')
                ->pluck('name', 'name')
                ->toArray();
        } catch (\Exception $e) {
            \Log::warning('获取区域列表失败', ['error' => $e->getMessage()]);
        }

        return [];
    }

    /**
     * 检查工单创建人是否存在且可被通知。
     * 不存在时记录警告日志，返回 false。
     */
    private static function shouldNotifyCreator(Workorder $workorder, string $context = ''): bool
    {
        if (!$workorder->creator_id || !\App\Models\User::where('id', $workorder->creator_id)->exists()) {
            \Log::warning('创建人不存在，跳过通知' . ($context ? '：' . $context : ''), [
                'workorder_id' => $workorder->id,
                'creator_id' => $workorder->creator_id,
            ]);
            return false;
        }
        return true;
    }

    /**
     * 统一构建工单地址字符串
     * building 存的是 locations 表的 id，自动查完整楼名
     */
    private static function buildWorkorderAddress(Workorder $workorder): string
    {
        $parts = [];

        if ($workorder->campus && trim($workorder->campus)) {
            $parts[] = trim($workorder->campus);
        }

        if ($workorder->building && trim($workorder->building)) {
            $loc = \App\Models\Location::find(trim($workorder->building));
            $parts[] = $loc ? $loc->name : trim($workorder->building);
        }

        if ($workorder->location_detail && trim($workorder->location_detail)) {
            $parts[] = trim($workorder->location_detail);
        }

        return implode(' ', array_filter($parts)) ?: '未知地址';
    }
}
