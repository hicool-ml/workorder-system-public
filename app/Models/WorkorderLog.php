<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkorderLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'workorder_id',
        'user_id',
        'action',
        'content',
        'old_value',
        'new_value',
        'processing_time',
        'is_system',
    ];

    protected $casts = [
        'processing_time' => 'integer',
        'is_system' => 'boolean',
    ];

    /**
     * 获取关联的工单
     */
    public function workorder(): BelongsTo
    {
        return $this->belongsTo(Workorder::class);
    }

    /**
     * 获取操作用户
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 获取操作类型文本
     */
    public function getActionTextAttribute(): string
    {
        $actions = [
            'created' => '创建工单',
            'assigned' => '分配工单',
            'accepted' => '接单',
            'started' => '开始处理',
            'paused' => '暂停处理',
            'resumed' => '恢复处理',
            'transferred' => '转派',
            'resolved' => '已解决',
            'completed' => '工单已完结',
            'rejected' => '拒绝处理',
            'closed' => '关闭工单',
            'reopened' => '重新打开',
            'comment' => '添加备注',
            'attachment_uploaded' => '上传附件',
            'materials_updated' => '更新备件耗材',
            'collaboration_invited' => '邀请协作',
            'collaboration_accepted' => '接受协作',
            'collaboration_rejected' => '拒绝协作',
            'visit_completed' => '完成回访',
            'phone_assisted' => '电话协助完成',
        ];
        
        return $actions[$this->action] ?? $this->action;
    }

    /**
     * 获取格式化的处理时间
     */
    public function getFormattedProcessingTimeAttribute(): string
    {
        if (!$this->processing_time) {
            return '';
        }
        
        $hours = floor($this->processing_time / 60);
        $minutes = $this->processing_time % 60;
        
        if ($hours > 0) {
            return "{$hours}小时{$minutes}分钟";
        }
        
        return "{$minutes}分钟";
    }

    /**
     * 检查是否为状态变更操作
     */
    public function isStatusChange(): bool
    {
        return in_array($this->action, [
            'assigned', 'started', 'paused', 'resumed',
            'transferred', 'resolved', 'completed', 'rejected', 'closed', 'reopened'
        ]);
    }

    /**
     * 检查是否为用户操作
     */
    public function isUserAction(): bool
    {
        return !$this->is_system;
    }

    /**
     * 获取操作描述
     */
    public function getDescriptionAttribute(): string
    {
        $description = $this->action_text;
        
        if ($this->content) {
            $description .= ': ' . $this->content;
        }
        
        if ($this->is_status_change && $this->old_value && $this->new_value) {
            $description .= " ({$this->old_value} → {$this->new_value})";
        }
        
        return $description;
    }

    /**
     * 创建系统日志
     */
    public static function createSystemLog(int $workorderId, string $action, string $content = null): self
    {
        return static::create([
            'workorder_id' => $workorderId,
            'user_id' => null,
            'action' => $action,
            'content' => $content,
            'is_system' => true,
        ]);
    }

    /**
     * 创建用户日志
     */
    public static function createUserLog(int $workorderId, string $action, string $content = null, int $userId = null): self
    {
        return static::create([
            'workorder_id' => $workorderId,
            'user_id' => $userId ?? auth()->id(),
            'action' => $action,
            'content' => $content,
            'is_system' => false,
        ]);
    }

    /**
     * 创建状态变更日志
     */
    public static function createStatusChangeLog(int $workorderId, string $action, string $oldValue, string $newValue, int $userId = null): self
    {
        return static::create([
            'workorder_id' => $workorderId,
            'user_id' => $userId ?? auth()->id(),
            'action' => $action,
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'is_system' => false,
        ]);
    }

    /**
     * 获取所有可用的操作类型
     */
    public static function getActionOptions(): array
    {
        return [
            'created' => '创建工单',
            'assigned' => '分配工单',
            'accepted' => '接单',
            'started' => '开始处理',
            'paused' => '暂停处理',
            'resumed' => '恢复处理',
            'transferred' => '转派',
            'resolved' => '已解决',
            'completed' => '工单已完结',
            'rejected' => '拒绝处理',
            'closed' => '关闭工单',
            'reopened' => '重新打开',
            'comment' => '添加备注',
            'attachment_uploaded' => '上传附件',
            'materials_updated' => '更新备件耗材',
            'collaboration_invited' => '邀请协作',
            'collaboration_accepted' => '接受协作',
            'collaboration_rejected' => '拒绝协作',
            'visit_completed' => '完成回访',
            'phone_assisted' => '电话协助完成',
        ];
    }
}