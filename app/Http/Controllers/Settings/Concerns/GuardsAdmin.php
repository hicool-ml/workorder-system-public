<?php

namespace App\Http\Controllers\Settings\Concerns;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

/**
 * 设置控制器管理员守卫（三种响应形态：abort / JSON 403 / 重定向）
 * 防御层：不依赖路由中间件，防止路由重组后越权
 */
trait GuardsAdmin
{
    private function guardAdminAbort(): void
    {
        if (!Auth::user() || !Auth::user()->isAdmin()) {
            abort(403, '仅管理员可访问系统设置');
        }
    }

    private function guardAdminJson(): ?JsonResponse
    {
        if (!Auth::user() || !Auth::user()->isAdmin()) {
            return response()->json(['success' => false, 'message' => '无权操作'], 403);
        }

        return null;
    }

    private function guardAdminRedirect(): ?RedirectResponse
    {
        if (!Auth::user() || !Auth::user()->isAdmin()) {
            return redirect()->route('system-settings.index')->with('error', '无权操作');
        }

        return null;
    }
}
