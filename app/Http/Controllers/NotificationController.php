<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * 获取通知列表
     */
    public function index(Request $request)
    {
        try {
            $query = Notification::where('user_id', Auth::id())
                ->orderBy('created_at', 'desc');
                
            // 筛选已读/未读
            if ($request->filled('is_read')) {
                $query->where('is_read', $request->boolean('is_read'));
            }
            
            // 筛选通知类型
            if ($request->filled('type')) {
                $query->where('type', $request->input('type'));
            }
            
            $notifications = $query->paginate(20);
            
            // 获取未读通知数量
            $unreadCount = Notification::where('user_id', Auth::id())
                ->where('is_read', false)
                ->count();
            
            return view('notifications.index', compact('notifications', 'unreadCount'));
        } catch (\Exception $e) {
            \Log::error('通知中心加载失败: ' . $e->getMessage());
            return back()->with('error', '通知中心加载失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 标记通知为已读
     */
    public function markAsRead(Request $request, Notification $notification)
    {
        try {
            // 权限检查
            if ($notification->user_id !== Auth::id()) {
                return response()->json(['success' => false, 'message' => '无权操作此通知'], 403);
            }
            
            $result = $notification->markAsRead();
            
            // 如果通知关联了工单，返回工单详情链接
            $actionUrl = null;
            if ($notification->workorder_id) {
                $actionUrl = route('workorders.show', $notification->workorder_id);
            }
            
            return response()->json([
                'success' => true,
                'notification' => [
                    'id' => $notification->id ?? 0,
                    'data' => array_merge($notification->data ?? [], ['action_url' => $actionUrl])
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('标记通知已读失败: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => '操作失败: ' . $e->getMessage()], 500);
        }
    }
    
    /**
     * 批量标记通知为已读
     */
    public function markAllAsRead(Request $request)
    {
        try {
            $count = Notification::where('user_id', Auth::id())
                ->where('is_read', false)
                ->update(['is_read' => true, 'read_at' => now()]);
                
            return response()->json(['success' => true, 'count' => $count]);
        } catch (\Exception $e) {
            \Log::error('批量标记已读失败: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => '操作失败: ' . $e->getMessage()], 500);
        }
    }
    
    /**
     * 删除通知
     */
    public function destroy(Request $request, Notification $notification)
    {
        try {
            // 权限检查
            if ($notification->user_id !== Auth::id()) {
                return response()->json(['success' => false, 'message' => '无权操作此通知'], 403);
            }
            
            $notification->delete();
            
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            \Log::error('删除通知失败: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => '删除失败: ' . $e->getMessage()], 500);
        }
    }
    
    /**
     * 批量删除通知
     */
    public function batchDestroy(Request $request)
    {
        try {
            $request->validate([
                'notification_ids' => 'required|array',
                'notification_ids.*' => 'integer|exists:notifications,id',
            ]);
            
            $notificationIds = $request->input('notification_ids');
            
            $count = Notification::where('user_id', Auth::id())
                ->whereIn('id', $notificationIds)
                ->delete();
                
            return response()->json(['success' => true, 'count' => $count]);
        } catch (\Exception $e) {
            \Log::error('批量删除通知失败: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => '批量删除失败: ' . $e->getMessage()], 500);
        }
    }
    
    /**
     * 批量标记通知为已读
     */
    public function batchMarkAsRead(Request $request)
    {
        try {
            $request->validate([
                'notification_ids' => 'required|array',
                'notification_ids.*' => 'integer|exists:notifications,id',
            ]);
            
            $notificationIds = $request->input('notification_ids');
            
            $count = Notification::where('user_id', Auth::id())
                ->whereIn('id', $notificationIds)
                ->where('is_read', false)
                ->update(['is_read' => true, 'read_at' => now()]);
                
            return response()->json(['success' => true, 'count' => $count]);
        } catch (\Exception $e) {
            \Log::error('批量标记已读失败: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => '批量标记已读失败: ' . $e->getMessage()], 500);
        }
    }
    
    /**
     * 获取未读通知数量（API）
     */
    public function getUnreadCount()
    {
        // 清除任何之前的输出
        if (ob_get_level() > 0) {
            ob_clean();
        }
        
        try {
            // 检查用户认证
            if (!Auth::check()) {
                return response()->json(['count' => 0, 'error' => '用户未认证'], 401);
            }
            
            $count = Notification::where('user_id', Auth::id())
                ->where('is_read', false)
                ->count();
                
            $response = response()->json(['count' => $count]);
            
            // 确保没有额外的输出
            if (ob_get_level() > 0) {
                ob_end_clean();
            }
            
            return $response;
        } catch (\Exception $e) {
            \Log::error('获取未读通知数量失败: ' . $e->getMessage());
            
            // 清除输出缓冲区
            if (ob_get_level() > 0) {
                ob_clean();
            }
            
            return response()->json(['count' => 0, 'error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * 获取最新通知（API）
     */
    public function getLatest(Request $request)
    {
        // 清除任何之前的输出
        if (ob_get_level() > 0) {
            ob_clean();
        }
        
        try {
            // 检查用户认证
            if (!Auth::check()) {
                return response()->json(['error' => '用户未认证'], 401);
            }
            
            $limit = min(max((int) $request->input('limit', 5), 1), 50);
            
            $notifications = Notification::where('user_id', Auth::id())
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get()
                ->map(function ($notification) {
                    // 确保所有字段都有默认值，避免null值导致JSON解析问题
                    return [
                        'id' => $notification->id ?? 0,
                        'title' => $notification->title ?? '未知标题',
                        'content' => $notification->content ?? '',
                        'type' => $notification->type ?? 'info',
                        'is_read' => (bool)($notification->is_read ?? false),
                        'is_important' => (bool)($notification->is_important ?? false),
                        'created_at' => $notification->created_at ? $notification->created_at->diffForHumans() : '未知时间',
                        'data' => $notification->data ?? new \stdClass(),
                    ];
                });
                
            $response = response()->json($notifications);
            
            // 确保没有额外的输出
            if (ob_get_level() > 0) {
                ob_end_clean();
            }
            
            return $response;
        } catch (\Exception $e) {
            \Log::error('获取最新通知失败: ' . $e->getMessage());
            
            // 清除输出缓冲区
            if (ob_get_level() > 0) {
                ob_clean();
            }
            
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * 创建系统公告
     */
    public function createAnnouncement(Request $request)
    {
        // 权限检查：只有管理员可以创建系统公告
        if (!Auth::user()->isAdmin()) {
            return response()->json(['success' => false, 'message' => '您没有权限创建系统公告'], 403);
        }
        
        $request->validate([
            'title' => 'required|string|max:200',
            'content' => 'required|string|max:2000',
            'target_type' => 'required|in:all,users,roles',
            'target_ids' => 'nullable|array',
            'target_ids.*' => 'integer',
        ]);
        
        try {
            $title = $request->input('title');
            $content = $request->input('content');
            $targetType = $request->input('target_type', 'all');
            $targetIds = $request->input('target_ids', []);
            $isImportant = $request->input('is_important') === 'true' || $request->boolean('is_important', false);

            // 三个通道均受通知规则 announcement 事件控制
            $inAppEnabled = \App\Services\Notification\NotificationDispatcher::isChannelEnabled('announcement', 'in_app');
            $smsEnabled = \App\Services\Notification\NotificationDispatcher::isChannelEnabled('announcement', 'sms');
            $wecomEnabled = \App\Services\Notification\NotificationDispatcher::isChannelEnabled('announcement', 'wecom');

            $notification = $inAppEnabled ? Notification::createSystemAnnouncement(
                $title, $content, $targetType, $targetIds, $isImportant
            ) : null;
            
            // 记录日志
            \Log::info('系统公告创建成功', [
                'admin_id' => Auth::id(),
                'title' => $title,
                'target_type' => $targetType,
                'notification_id' => $notification ? $notification->id : null,
                'channels' => ['in_app' => $inAppEnabled, 'sms' => $smsEnabled, 'wecom' => $wecomEnabled],
            ]);
            
            // 短信通知（受开关控制）
            if ($smsEnabled) {
                try {
                    $sms = app(\App\Services\Sms\SmsManager::class);
                    if ($sms->isEnabled()) {
                        $this->sendAnnouncementSms($sms, $title, $content, $targetType, $targetIds);
                    }
                } catch (\Exception $smsEx) {
                    \Log::warning('公告短信通知失败', ['error' => $smsEx->getMessage()]);
                }
            }
            
            // 同步推送到企业微信
            try {
                $wecom = app(\App\Services\Notification\WeComWebhookService::class);
                if ($wecomEnabled && $wecom->isEnabled()) {
                    $systemName = \App\Models\SystemSetting::get('system_name', '工单系统');
                    $timestamp = now()->format('Y-m-d H:i');
                    $message = "【{$systemName}】系统公告\n"
                        . "时间：{$timestamp}\n"
                        . "{$title}\n"
                        . "{$content}";
                    $wecom->sendText($message, ['@all']);
                }
            } catch (\Exception $wecomEx) {
                \Log::warning('公告推送企业微信失败', ['error' => $wecomEx->getMessage()]);
            }
            
            // 检查是否为AJAX请求
            if ($request->expectsJson()) {
                return response()->json(['success' => true, 'message' => '系统公告创建成功']);
            } else {
                return redirect()->route('notifications.index')->with('success', '系统公告创建成功');
            }
        } catch (\Exception $e) {
            \Log::error('系统公告创建失败', [
                'admin_id' => Auth::id(),
                'title' => $request->input('title'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
           return response()->json(['success' => false, 'message' => '系统公告创建失败：' . $e->getMessage()]);
       }
    }

    /**
     * 发送公告短信通知（根据目标类型解析接收人）
     */
    private function sendAnnouncementSms(
        \App\Services\Sms\SmsManager $sms,
        string $title,
        string $content,
        string $targetType,
        array $targetIds
    ): void {
        $systemName = \App\Models\SystemSetting::get('system_name', '工单系统');
        $smsContent = "【{$systemName}】系统公告：{$title}。{$content}";
        if (mb_strlen($smsContent) > 280) {
            $smsContent = mb_substr($smsContent, 0, 277) . '...';
        }

        if ($targetType === 'all') {
            $users = \App\Models\User::where('status', 'active')->get();
        } elseif ($targetType === 'users' && !empty($targetIds)) {
            $users = \App\Models\User::whereIn('id', $targetIds)->where('status', 'active')->get();
        } elseif ($targetType === 'roles' && !empty($targetIds)) {
            $users = \App\Models\User::whereIn('role', $targetIds)->where('status', 'active')->get();
        } else {
            $users = collect();
        }

        foreach ($users as $user) {
            if (empty($user->phone)) {
                continue;
            }
            $sms->send($user->phone, 'SMS_ANNOUNCEMENT', ['content' => $smsContent]);
        }
    }
}
