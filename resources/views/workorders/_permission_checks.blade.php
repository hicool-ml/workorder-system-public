<?php
/**
 * 工单权限检查组件
 * 提供统一的权限检查逻辑，避免在多个页面中重复代码
 */

use App\Services\WorkorderPermissionService;

/**
 * 检查用户是否可以解决工单
 * @param App\Models\Workorder $workorder
 * @param App\Models\User|null $user
 * @return bool
 */
if (!function_exists('canResolveWorkorder')) {
    function canResolveWorkorder($workorder, $user = null) {
        return WorkorderPermissionService::canResolveWorkorder($workorder, $user);
    }
}

/**
 * 检查用户是否可以开始处理工单
 * @param App\Models\Workorder $workorder
 * @param App\Models\User|null $user
 * @return bool
 */
if (!function_exists('canStartWorkorder')) {
    function canStartWorkorder($workorder, $user = null) {
        return WorkorderPermissionService::canStartWorkorder($workorder, $user);
    }
}

/**
 * 检查用户是否可以邀请协作
 * @param App\Models\Workorder $workorder
 * @param App\Models\User|null $user
 * @return bool
 */
if (!function_exists('canInviteCollaboration')) {
    function canInviteCollaboration($workorder, $user = null) {
        return WorkorderPermissionService::canInviteCollaboration($workorder, $user);
    }
}

/**
 * 检查用户是否可以上传附件
 * @param App\Models\Workorder $workorder
 * @param App\Models\User|null $user
 * @return bool
 */
if (!function_exists('canUploadAttachment')) {
    function canUploadAttachment($workorder, $user = null) {
        return WorkorderPermissionService::canUploadAttachment($workorder, $user);
    }
}

/**
 * 检查用户是否可以删除附件
 * @param App\Models\Workorder $workorder
 * @param App\Models\User|null $user
 * @return bool
 */
if (!function_exists('canDeleteAttachment')) {
    function canDeleteAttachment($workorder, $user = null) {
        return WorkorderPermissionService::canDeleteAttachment($workorder, $user);
    }
}

/**
 * 检查用户是否可以编辑备件耗材使用情况
 * @param App\Models\Workorder $workorder
 * @param App\Models\User|null $user
 * @return bool
 */
if (!function_exists('canEditMaterialsUsage')) {
    function canEditMaterialsUsage($workorder, $user = null) {
        return WorkorderPermissionService::canEditMaterialsUsage($workorder, $user);
    }
}

/**
 * 检查用户是否有协作邀请待处理
 * @param App\Models\Workorder $workorder
 * @param App\Models\User|null $user
 * @return bool
 */
if (!function_exists('hasPendingCollaboration')) {
    function hasPendingCollaboration($workorder, $user = null) {
        return WorkorderPermissionService::hasPendingCollaboration($workorder, $user);
    }
}

/**
 * 获取工单操作按钮HTML
 * @param App\Models\Workorder $workorder
 * @param bool $isMobileView 是否为移动端视图
 * @return string
 */
if (!function_exists('getWorkorderActionButtons')) {
    function getWorkorderActionButtons($workorder, $isMobileView = false) {
        $buttons = '';
        
        // 查看按钮
        $buttons .= '<a href="' . \App\Helpers\UrlHelper::relative_url('/workorders/' . $workorder->id) . '"
                   class="btn ' . ($isMobileView ? 'btn-outline-primary' : 'btn-outline-primary btn-sm') . '" title="查看">
                   <i class="fas fa-eye"></i>
                   </a>';
        
        // 分配按钮
        if ($workorder->canBeAssigned() && auth()->user()->canAssignWorkorders()) {
            $buttons .= '<button type="button"
                        class="btn ' . ($isMobileView ? 'btn-outline-success' : 'btn-outline-success btn-sm') . '"
                        data-bs-toggle="modal" data-bs-target="#assignModal"
                        data-workorder-id="' . $workorder->id . '" title="分配">
                        <i class="fas fa-user-plus"></i>
                        </button>';
        } elseif ($workorder->canBeAssigned() && auth()->user()->isEngineer() && !$workorder->assignee_id) {
            $buttons .= '<form method="POST" action="' . route('workorders.claim', $workorder->id) . '" class="d-inline">
                        <input type="hidden" name="_token" value="' . csrf_token() . '">
                        <button type="submit"
                                class="btn ' . ($isMobileView ? 'btn-outline-success' : 'btn-outline-success btn-sm') . '"
                                onclick="return confirm(\'确认接单吗？\')" title="接单">
                        <i class="fas fa-hand-paper"></i>
                        </button>
                        </form>';
        }
        
        // 开始处理按钮 - 只有在未开始处理时才显示
        if (canStartWorkorder($workorder) && !$workorder->started_at) {
            $buttons .= '<form method="POST" action="' . route('workorders.start', $workorder->id) . '" class="d-inline">
                        <input type="hidden" name="_token" value="' . csrf_token() . '">
                        <button type="submit"
                                class="btn ' . ($isMobileView ? 'btn-outline-warning' : 'btn-outline-warning btn-sm') . '"
                                onclick="return confirm(\'确认开始处理此工单吗？\')" title="开始处理">
                        <i class="fas fa-play"></i>
                        </button>
                        </form>';
        }
        
        // 解决按钮
        if (canResolveWorkorder($workorder)) {
            $buttons .= '<button type="button"
                        class="btn ' . ($isMobileView ? 'btn-outline-info' : 'btn-outline-info btn-sm') . '"
                        data-bs-toggle="modal" data-bs-target="#resolveModal"
                        data-workorder-id="' . $workorder->id . '" title="解决">
                        <i class="fas fa-check"></i>
                        </button>';
        }
        
        // 关闭按钮
        if ($workorder->canBeClosed() && auth()->user()->canCloseWorkorders()) {
            $buttons .= '<form method="POST" action="' . route('workorders.close', $workorder->id) . '" class="d-inline">
                        <input type="hidden" name="_token" value="' . csrf_token() . '">
                        <button type="submit"
                                class="btn ' . ($isMobileView ? 'btn-outline-danger' : 'btn-outline-danger btn-sm') . '"
                                onclick="return confirm(\'确认关闭此工单吗？\')" title="关闭">
                        <i class="fas fa-times"></i>
                        </button>
                        </form>';
        }
        
        return $buttons;
    }
}

/**
 * 获取协作邀请图标HTML
 * @param App\Models\Workorder $workorder
 * @return string
 */
if (!function_exists('getCollaborationIcon')) {
    function getCollaborationIcon($workorder) {
        if (hasPendingCollaboration($workorder)) {
            return '<i class="fas fa-handshake text-info ms-1" title="您有协作邀请待处理"></i>';
        }
        return '';
    }
}
?>