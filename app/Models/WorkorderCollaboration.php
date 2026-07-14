<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkorderCollaboration extends Model
{
    use HasFactory;

    protected $fillable = [
        'workorder_id',
        'inviter_id',
        'collaborator_id',
        'invitation_reason',
        'status',
        'accepted_at',
        'response_note',
    ];

    protected $casts = [
        'accepted_at' => 'datetime',
    ];

    /**
     * 状态选项
     */
    public static function getStatusOptions()
    {
        return [
            'pending' => '待确认',
            'accepted' => '已接受',
            'rejected' => '已拒绝',
        ];
    }

    /**
     * 获取状态文本
     */
    public function getStatusTextAttribute()
    {
        $options = self::getStatusOptions();
        return $options[$this->status] ?? '未知';
    }

    /**
     * 获取状态颜色
     */
    public function getStatusColorAttribute()
    {
        return match($this->status) {
            'pending' => 'warning',
            'accepted' => 'success',
            'rejected' => 'danger',
            default => 'secondary',
        };
    }

    /**
     * 关联工单
     */
    public function workorder()
    {
        return $this->belongsTo(Workorder::class);
    }

    /**
     * 关联邀请人
     */
    public function inviter()
    {
        return $this->belongsTo(User::class, 'inviter_id');
    }

    /**
     * 关联协作人
     */
    public function collaborator()
    {
        return $this->belongsTo(User::class, 'collaborator_id');
    }

    /**
     * 检查是否可以接受邀请
     */
    public function canBeAccepted()
    {
        return $this->status === 'pending' && $this->collaborator_id === auth()->id();
    }

    /**
     * 检查是否可以拒绝邀请
     */
    public function canBeRejected()
    {
        return $this->status === 'pending' && $this->collaborator_id === auth()->id();
    }

    /**
     * 接受邀请
     */
    public function accept($note = null)
    {
        if (!$this->canBeAccepted()) {
            return false;
        }

        // 乐观锁：仅当邀请仍为 pending 时才能接受，防止并发重复接受
        $affected = static::where('id', $this->id)
            ->where('status', 'pending')
            ->update([
                'status' => 'accepted',
                'accepted_at' => now(),
                'response_note' => $note,
            ]);

        if ($affected > 0) {
            $this->status = 'accepted';
            $this->accepted_at = now();
            $this->response_note = $note;
            return true;
        }

        return false;
    }

    /**
     * 拒绝邀请
     */
    public function reject($note = null)
    {
        if (!$this->canBeRejected()) {
            return false;
        }

        $this->status = 'rejected';
        $this->response_note = $note;
        
        return $this->save();
    }

    /**
     * 创建协作邀请
     */
    public static function createInvitation($workorderId, $collaboratorId, $invitationReason = null)
    {
        // 检查是否已经存在邀请
        $existing = self::where('workorder_id', $workorderId)
            ->where('collaborator_id', $collaboratorId)
            ->where('status', 'pending')
            ->first();

        if ($existing) {
            return false;
        }

        return self::create([
            'workorder_id' => $workorderId,
            'inviter_id' => auth()->id(),
            'collaborator_id' => $collaboratorId,
            'invitation_reason' => $invitationReason,
            'status' => 'pending',
        ]);
    }
}