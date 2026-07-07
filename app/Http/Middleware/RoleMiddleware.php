<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @param  string  $role
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, string $role)
    {
        if (!Auth::user()) {
            return redirect()->route('login');
        }

        $user = Auth::user();
        
        // 检查用户角色
        if (!$this->hasRole($user, $role)) {
            abort(403, '您没有权限访问此页面');
        }

        return $next($request);
    }

    /**
     * 检查用户是否具有指定角色
     */
    private function hasRole($user, string $role): bool
    {
        switch ($role) {
            case 'admin':
                return $user->isAdmin();
            case 'workorder_manager':
                return $user->isWorkorderManager();
            case 'engineer':
                return $user->isEngineer();
            case 'user':
                return $user->isUser();
            case 'admin_or_engineer':
                return $user->isAdmin() || $user->isEngineer();
            case 'admin_or_workorder_manager':
                return $user->isAdmin() || $user->isWorkorderManager();
            case 'admin_or_workorder_manager_or_engineer':
                return $user->isAdmin() || $user->isWorkorderManager() || $user->isEngineer();
            case 'can_handle_workorders':
                return $user->canHandleWorkorders();
            case 'can_assign_workorders':
                return $user->canAssignWorkorders();
            case 'can_assign_to_self':
                return $user->canAssignWorkorderToSelf();
            case 'can_invite_collaborators':
                return $user->canInviteCollaborators();
            case 'can_manage_attachments':
                return $user->canManageWorkorderAttachments();
            case 'can_add_notes':
                return $user->canAddWorkorderNotes();
            case 'can_add_materials':
                return $user->canAddMaterialsUsage();
            case 'can_manage_types':
                return $user->canManageWorkorderTypes();
            case 'can_manage_departments':
                return $user->canManageDepartments();
            case 'can_view_reports':
                return $user->canViewReports();
            case 'can_use_phone_assist':
                return $user->canUsePhoneAssist();
            case 'can_batch_operate':
                return $user->canBatchOperateWorkorders();
            case 'can_export':
                return $user->canExportWorkorders();
            default:
                return false;
        }
    }
}