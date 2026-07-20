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
     * 检查当前用户是否可以取消此协作邀请
     * 规则：仅工单负责人、工单管理员、系统管理员可取消；
     *       且仅当邀请仍为「待接受(pending)」时可取消——对方一旦接受即不可取消。
     */
    public function canBeCancelledBy(?User $user = null): bool
    {
        $user = $user ?: auth()->user();
        if (!$user) {
            return false;
        }
        // 对方已接受后不可取消
        if ($this->status !== 'pending') {
            return false;
        }
        // 系统管理员/工单管理员可取消任意邀请
        if ($user->isAdmin() || $user->isWorkorderManager()) {
            return true;
        }
        // 仅工单负责人（被分配人）可取消自己发出的邀请
        return $this->workorder && $this->workorder->assignee_id === $user->id;
    }

    /**
     * 取消协作邀请（仅 pending 可取消）
     */
    public function cancel(?User $user = null): bool
    {
        if (!$this->canBeCancelledBy($user)) {
            return false;
        }

        // 物理删除待接受的邀请记录，等同于取消
        return (bool) $this->delete();
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
