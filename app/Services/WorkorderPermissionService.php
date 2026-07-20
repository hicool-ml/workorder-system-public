<?php

namespace App\Services;

use App\Models\User;
use App\Models\Workorder;

/**
 * 工单权限检查服务类
 * 提供统一的权限检查逻辑，避免在多个页面中重复代码
 */
class WorkorderPermissionService
{
    /**
     * 检查用户是否可以解决工单
     * @param Workorder $workorder
     * @param User|null $user
     * @return bool
     */
    public static function canResolveWorkorder(Workorder $workorder, User $user = null): bool
    {
        $user = $user ?: auth()->user();
        
        // 协作工程师（仅已接受邀请）可在 assigned/processing 状态下解决工单；
        // 待接受状态不允许操作，需先接受邀请。
        if ($workorder->collaborations()
            ->where('collaborator_id', $user->id)
            ->where('status', 'accepted')
            ->exists() && in_array($workorder->status, ['assigned', 'processing'])) {
            return true;
        }
        
        // 对于其他用户，仍然使用原来的逻辑
        if (!$workorder->canBeResolved()) {
            return false;
        }
        
        // 工单负责人
        if ($workorder->assignee_id == $user->id) {
            return true;
        }
        
        // 管理员和工单管理员
        if ($user->isAdmin() || $user->isWorkorderManager()) {
            return true;
        }
        
        return false;
    }

    /**
     * 检查用户是否可以开始处理工单
     * @param Workorder $workorder
     * @param User|null $user
     * @return bool
     */
    public static function canStartWorkorder(Workorder $workorder, User $user = null): bool
    {
        $user = $user ?: auth()->user();
        
        // 协作工程师（仅已接受邀请）可在 assigned/processing 状态下开始处理工单；
        // 待接受状态不允许操作，需先接受邀请。
        if ($workorder->collaborations()
            ->where('collaborator_id', $user->id)
            ->where('status', 'accepted')
            ->exists() && in_array($workorder->status, ['assigned', 'processing'])) {
            return true;
        }
        
        // 对于其他用户，仍然使用原来的逻辑
        if (!$workorder->canBeStarted()) {
            return false;
        }
        
        // 工单负责人
        if ($workorder->assignee_id == $user->id) {
            return true;
        }
        
        // 管理员和工单管理员
        if ($user->isAdmin() || $user->isWorkorderManager()) {
            return true;
        }
        
        return false;
    }

    /**
     * 检查用户是否可以邀请协作
     * @param Workorder $workorder
     * @param User|null $user
     * @return bool
     */
    public static function canInviteCollaboration(Workorder $workorder, User $user = null): bool
    {
        $user = $user ?: auth()->user();
        
        // 检查工单状态
        if (!in_array($workorder->status, ['processing', 'assigned'])) {
            return false;
        }
        
        // 工单负责人
        if ($workorder->assignee_id == $user->id) {
            return true;
        }
        
        // 管理员和工单管理员
        if ($user->isAdmin() || $user->isWorkorderManager()) {
            return true;
        }
        
        // 已接受的协作工程师
        return $workorder->collaborations()
            ->where('collaborator_id', $user->id)
            ->where('status', 'accepted')
            ->exists();
    }

    /**
     * 检查用户是否可以上传附件
     * @param Workorder $workorder
     * @param User|null $user
     * @return bool
     */
    public static function canUploadAttachment(Workorder $workorder, User $user = null): bool
    {
        $user = $user ?: auth()->user();
        
        // 协作工程师（仅已接受邀请）可在 pending/processing/assigned 状态下上传附件；
        // 待接受状态不允许操作，需先接受邀请。
        if ($workorder->collaborations()
            ->where('collaborator_id', $user->id)
            ->where('status', 'accepted')
            ->exists() && in_array($workorder->status, ['pending', 'processing', 'assigned'])) {
            return true;
        }
        
        // 对于其他用户，仍然使用原来的逻辑
        // 检查工单状态
        if (!in_array($workorder->status, ['pending', 'processing', 'assigned'])) {
            return false;
        }
        
        // 管理员和工单管理员
        if ($user->isAdmin() || $user->isWorkorderManager()) {
            return true;
        }
        
        // 工单负责人
        if ($workorder->assignee_id == $user->id) {
            return true;
        }
        
        return false;
    }

    /**
     * 检查用户是否可以删除附件
     * @param Workorder $workorder
     * @param User|null $user
     * @return bool
     */
    public static function canDeleteAttachment(Workorder $workorder, User $user = null): bool
    {
        $user = $user ?: auth()->user();
        
        // 检查工单状态
        if (!in_array($workorder->status, ['pending', 'processing', 'assigned'])) {
            return false;
        }
        
        // 管理员和工单管理员
        if ($user->isAdmin() || $user->isWorkorderManager()) {
            return true;
        }
        
        // 工单创建人
        if ($workorder->creator_id == $user->id) {
            return true;
        }
        
        // 工单负责人
        if ($workorder->assignee_id == $user->id) {
            return true;
        }
        
        // 协作工程师（仅已接受邀请；待接受状态无操作权限，需先接受邀请）
        return $workorder->collaborations()
            ->where('collaborator_id', $user->id)
            ->where('status', 'accepted')
            ->exists();
    }

    /**
     * 检查用户是否可以编辑备件耗材使用情况
     * @param Workorder $workorder
     * @param User|null $user
     * @return bool
     */
    public static function canEditMaterialsUsage(Workorder $workorder, User $user = null): bool
    {
        $user = $user ?: auth()->user();
        
        // 检查工单状态
        if (!in_array($workorder->status, ['assigned', 'processing', 'resolved', 'completed'])) {
            return false;
        }
        
        // 管理员和工单管理员
        if ($user->isAdmin() || $user->isWorkorderManager()) {
            return true;
        }
        
        // 工单负责人
        if ($workorder->assignee_id == $user->id) {
            return true;
        }
        
        // 协作工程师（仅已接受邀请；待接受状态无操作权限，需先接受邀请）
        return $workorder->collaborations()
            ->where('collaborator_id', $user->id)
            ->where('status', 'accepted')
            ->exists();
    }

    /**
     * 检查用户是否有协作邀请待处理
     * @param Workorder $workorder
     * @param User|null $user
     * @return bool
     */
    public static function hasPendingCollaboration(Workorder $workorder, User $user = null): bool
    {
        $user = $user ?: auth()->user();
        
        return $workorder->collaborations()
            ->where('collaborator_id', $user->id)
            ->where('status', 'pending')
            ->exists();
    }
}
