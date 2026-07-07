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
            
            $limit = $request->input('limit', 5);
            
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
            $notification = Notification::createSystemAnnouncement(
                $request->input('title'),
                $request->input('content'),
                $request->input('target_type', 'all'),
                $request->input('target_ids', []),
                $request->input('is_important') === 'true' || $request->boolean('is_important', false)
            );
            
            // 记录日志
            \Log::info('系统公告创建成功', [
                'admin_id' => Auth::id(),
                'title' => $request->input('title'),
                'target_type' => $request->input('target_type'),
                'notification_id' => $notification ? $notification->id : null
            ]);
            
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
}