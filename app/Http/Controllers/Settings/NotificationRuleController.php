<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Settings\Concerns\GuardsAdmin;
use App\Services\Notification\NotificationDispatcher;
use Illuminate\Http\Request;

class NotificationRuleController extends Controller
{
    use GuardsAdmin;

    /**
     * 获取通知规则
     */
    public function getNotificationRules()
    {
        return response()->json([
            'success' => true,
            'rules' => NotificationDispatcher::getRules(),
            'events' => NotificationDispatcher::getEventLabels(),
        ]);
    }

    /**
     * 更新通知规则
     */
    public function updateNotificationRules(Request $request)
    {
        if ($denied = $this->guardAdminJson()) {
            return $denied;
        }

        $rules = $request->input('rules', []);
        NotificationDispatcher::updateRules($rules);

        return response()->json(['success' => true, 'message' => '通知规则已更新']);
    }
}
