<?php

/**
 * 工单 UI 辅助函数
 *
 * 这些函数原本定义在 _permission_checks.blade.php 中，
 * 依赖 Blade 视图渲染才能注册，导致测试和命令行中不可用。
 * 现移至此文件，通过 composer autoload 全局可用。
 */

use App\Services\WorkorderPermissionService;

if (!function_exists('canResolveWorkorder')) {
    function canResolveWorkorder($workorder, $user = null) {
        return WorkorderPermissionService::canResolveWorkorder($workorder, $user);
    }
}

if (!function_exists('canStartWorkorder')) {
    function canStartWorkorder($workorder, $user = null) {
        return WorkorderPermissionService::canStartWorkorder($workorder, $user);
    }
}

if (!function_exists('canInviteCollaboration')) {
    function canInviteCollaboration($workorder, $user = null) {
        return WorkorderPermissionService::canInviteCollaboration($workorder, $user);
    }
}

if (!function_exists('canUploadAttachment')) {
    function canUploadAttachment($workorder, $user = null) {
        return WorkorderPermissionService::canUploadAttachment($workorder, $user);
    }
}

if (!function_exists('canDeleteAttachment')) {
    function canDeleteAttachment($workorder, $user = null) {
        return WorkorderPermissionService::canDeleteAttachment($workorder, $user);
    }
}

if (!function_exists('canEditMaterialsUsage')) {
    function canEditMaterialsUsage($workorder, $user = null) {
        return WorkorderPermissionService::canEditMaterialsUsage($workorder, $user);
    }
}

if (!function_exists('hasPendingCollaboration')) {
    function hasPendingCollaboration($workorder, $user = null) {
        return WorkorderPermissionService::hasPendingCollaboration($workorder, $user);
    }
}

if (!function_exists('svg_icon')) {
    function svg_icon($name, $class = 'w-4 h-4') {
        $icons = [
            'eye' => '<svg class="' . $class . '" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>',
            'user-plus' => '<svg class="' . $class . '" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 7a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM19 8v6M22 11h-6"/></svg>',
            'hand' => '<svg class="' . $class . '" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18 11V6a2 2 0 0 0-2-2v0a2 2 0 0 0-2 2v0M14 10V4a2 2 0 0 0-2-2v0a2 2 0 0 0-2 2v2M10 10.5V6a2 2 0 0 0-2-2v0a2 2 0 0 0-2 2v8M18 8a2 2 0 1 1 4 0v6a8 8 0 0 1-8 8h-2c-2.8 0-4.5-.86-5.99-2.34l-3.6-3.6a2 2 0 0 1 2.83-2.82L7 15"/></svg>',
            'play' => '<svg class="' . $class . '" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 3l14 9-14 9V3z"/></svg>',
            'check' => '<svg class="' . $class . '" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/></svg>',
            'close' => '<svg class="' . $class . '" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18 6 6 18M6 6l12 12"/></svg>',
            'handshake' => '<svg class="' . $class . '" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m11 17 2 2a1 1 0 1 0 3-3M14 14l2.5 2.5a1 1 0 1 0 3-3l-3.88-3.88a3 3 0 0 0-4.24 0l-.88.88a1 1 0 1 1-3-3l2.81-2.81a5.79 5.79 0 0 1 7.06-.87l.47.28a2 2 0 0 0 1.42.25L21 4M3 11l2-2m1.42 2.58L11 15.5"/></svg>',
        ];
        return $icons[$name] ?? '';
    }
}

if (!function_exists('getWorkorderActionButtons')) {
    function getWorkorderActionButtons($workorder, $isMobileView = false) {
        $btnClass = $isMobileView ? 'btn btn-sm' : 'btn btn-icon';
        $buttons = '';

        $buttons .= '<a href="' . route('workorders.show', $workorder->id) . '" class="' . $btnClass . ' btn-secondary" title="查看">' . svg_icon('eye') . '</a>';

        if ($workorder->canBeAssigned() && auth()->user()->canAssignWorkorders()) {
            $buttons .= '<button type="button" class="' . $btnClass . ' btn-secondary" data-assign-workorder="' . $workorder->id . '" title="分配">' . svg_icon('user-plus') . '</button>';
        } elseif ($workorder->canBeAssigned() && auth()->user()->isEngineer() && !$workorder->assignee_id) {
            $buttons .= '<form method="POST" action="' . route('workorders.claim', $workorder->id) . '" class="inline-flex"><input type="hidden" name="_token" value="' . csrf_token() . '"><button type="submit" class="' . $btnClass . ' btn-secondary" onclick="return confirm(\'确认接单吗？\')" title="接单">' . svg_icon('hand') . '</button></form>';
        }

        if (canStartWorkorder($workorder) && !$workorder->started_at) {
            $buttons .= '<form method="POST" action="' . route('workorders.start', $workorder->id) . '" class="inline-flex"><input type="hidden" name="_token" value="' . csrf_token() . '"><button type="submit" class="' . $btnClass . ' btn-secondary" onclick="return confirm(\'确认开始处理此工单吗？\')" title="开始处理">' . svg_icon('play') . '</button></form>';
        }

        if (canResolveWorkorder($workorder)) {
            $buttons .= '<button type="button" class="' . $btnClass . ' btn-secondary" data-resolve-workorder="' . $workorder->id . '" title="解决">' . svg_icon('check') . '</button>';
        }

        if ($workorder->canBeClosed() && auth()->user()->canCloseWorkorders()) {
            $buttons .= '<form method="POST" action="' . route('workorders.close', $workorder->id) . '" class="inline-flex"><input type="hidden" name="_token" value="' . csrf_token() . '"><button type="submit" class="' . $btnClass . ' btn-danger" onclick="return confirm(\'确认关闭此工单吗？\')" title="关闭">' . svg_icon('close') . '</button></form>';
        }

        return $buttons;
    }
}

if (!function_exists('getCollaborationIcon')) {
    function getCollaborationIcon($workorder) {
        if (hasPendingCollaboration($workorder)) {
            return '<span class="inline-flex items-center ml-1 text-blue-500" title="您有协作邀请待处理">' . svg_icon('handshake', 'w-3.5 h-3.5') . '</span>';
        }
        return '';
    }
}