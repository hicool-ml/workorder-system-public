<?php

namespace App\Services\Notification;

use App\Models\Notification as NotificationModel;
use App\Models\SystemSetting;
use App\Models\User;
use App\Models\Workorder;
use App\Services\Sms\SmsManager;
use Illuminate\Support\Facades\Log;

/**
 * 通知调度器
 * 根据通知规则（事件 x 通道）决定是否发送站内通知和短信。
 * 规则存储在 system_settings 表，key = notification_rules，value = JSON。
 *
 * JSON 结构示例:
 * {
 *   "created":   { "in_app": true,  "sms": false },
 *   "assigned":  { "in_app": true,  "sms": true  },
 *   "resolved":  { "in_app": true,  "sms": true  },
 *   "completed": { "in_app": true,  "sms": false },
 *   "overdue":   { "in_app": true,  "sms": true  }
 * }
 */
class NotificationDispatcher
{
    /**
     * 默认通知规则（首次运行时写入数据库）
     */
    public static function defaultRules(): array
    {
        return [
            'created'   => ['in_app' => true,  'sms' => false],
            'assigned'  => ['in_app' => true,  'sms' => true],
            'started'   => ['in_app' => true,  'sms' => false],
            'resolved'  => ['in_app' => true,  'sms' => true],
            'completed' => ['in_app' => true,  'sms' => false],
            'closed'    => ['in_app' => true,  'sms' => false],
            'overdue'   => ['in_app' => true,  'sms' => true],
        ];
    }

    /**
     * 获取当前通知规则
     */
    public static function getRules(): array
    {
        $stored = SystemSetting::get('notification_rules');

        if ($stored && is_array($stored)) {
            // 合并默认规则，确保新增的事件类型不遗漏
            return array_merge(self::defaultRules(), $stored);
        }

        $defaults = self::defaultRules();
        SystemSetting::set('notification_rules', $defaults, 'json', '通知规则（事件x通道）', false);
        return $defaults;
    }

    /**
     * 更新通知规则
     */
    public static function updateRules(array $rules): void
    {
        $merged = array_merge(self::defaultRules(), $rules);
        SystemSetting::set('notification_rules', $merged, 'json', '通知规则（事件x通道）', false);
    }

    /**
     * 检查某事件某通道是否启用
     */
    public static function isChannelEnabled(string $event, string $channel): bool
    {
        $rules = self::getRules();
        return ($rules[$event][$channel] ?? false) === true;
    }

    /**
     * 获取事件标签（中文）
     */
    public static function getEventLabels(): array
    {
        return [
            'created'   => '工单创建',
            'assigned'  => '工单分配',
            'started'   => '开始处理',
            'resolved'  => '工单解决',
            'completed' => '工单完结',
            'closed'    => '工单关闭',
            'overdue'   => '工单超时',
        ];
    }

    /**
     * 根据工单和事件类型发送多通道通知
     *
     * @param Workorder $workorder
     * @param string    $event       事件类型
     * @param array     $extra      额外数据
     */
    public function dispatch(Workorder $workorder, string $event, array $extra = []): void
    {
        $rules = self::getRules();
        $inAppEnabled = ($rules[$event]['in_app'] ?? false) === true;
        $smsEnabled = ($rules[$event]['sms'] ?? false) === true;

        // 如果两个通道都关闭，直接返回
        if (!$inAppEnabled && !$smsEnabled) {
            return;
        }

        // 确定接收者
        $recipients = $this->resolveRecipients($workorder, $event, $extra);

        if (empty($recipients)) {
            return;
        }

        // 站内通知
        if ($inAppEnabled) {
            $this->sendInApp($workorder, $event, $recipients);
        }

        // 短信通知
        if ($smsEnabled) {
            $this->sendSms($workorder, $event, $recipients);
        }
    }

    /**
     * 确定通知接收者
     */
    private function resolveRecipients(Workorder $workorder, string $event, array $extra): array
    {
        $recipients = [];

        switch ($event) {
            case 'created':
                // 创建事件通知管理员/工单管理员
                $managers = User::whereIn('role', ['admin', 'workorder_manager'])
                    ->where('status', 'active')
                    ->get();
                foreach ($managers as $m) {
                    $recipients[$m->id] = $m;
                }
                break;

            case 'assigned':
            case 'started':
            case 'resolved':
            case 'completed':
            case 'closed':
            case 'overdue':
                // 分配给工程师时通知工程师
                if ($workorder->assignee_id) {
                    $assignee = User::find($workorder->assignee_id);
                    if ($assignee && $assignee->status === 'active') {
                        $recipients[$assignee->id] = $assignee;
                    }
                }
                // 解决/完结/关闭时也通知报修人
                if (in_array($event, ['resolved', 'completed', 'closed']) && $workorder->creator_id) {
                    $creator = User::find($workorder->creator_id);
                    if ($creator && $creator->status === 'active') {
                        $recipients[$creator->id] = $creator;
                    }
                }
                break;
        }

        return $recipients;
    }

    /**
     * 发送站内通知
     */
    private function sendInApp(Workorder $workorder, string $event, array $recipients): void
    {
        $eventLabels = self::getEventLabels();
        $label = $eventLabels[$event] ?? $event;
        $title = "[{$workorder->workorder_number}] {$label}";
        $content = $this->buildContent($workorder, $event);

        foreach ($recipients as $user) {
            try {
                NotificationModel::create([
                    'type'          => 'workorder_' . $event,
                    'title'         => $title,
                    'content'       => $content,
                    'data'          => ['workorder_id' => $workorder->id, 'event' => $event],
                    'user_id'       => $user->id,
                    'workorder_id'  => $workorder->id,
                    'is_read'       => false,
                    'is_important'  => in_array($event, ['assigned', 'overdue']),
                ]);
            } catch (\Exception $e) {
                Log::warning('站内通知创建失败', [
                    'workorder_id' => $workorder->id,
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * 发送短信通知
     */
    private function sendSms(Workorder $workorder, string $event, array $recipients): void
    {
        $sms = app(SmsManager::class);

        if (!$sms->isEnabled()) {
            return;
        }

        $template = config("services.sms.templates.{$event}", 'SMS_' . strtoupper($event));
        $params = [
            'workorder_number' => $workorder->workorder_number,
            'title'            => $workorder->title,
            'content'          => $this->buildSmsContent($workorder, $event),
        ];

        foreach ($recipients as $user) {
            if (empty($user->phone)) {
                continue;
            }
            $result = $sms->send($user->phone, $template, $params);
            Log::info('工单短信通知', [
                'workorder_id' => $workorder->id,
                'user_id' => $user->id,
                'event' => $event,
                'success' => $result['success'],
            ]);
        }
    }

    /**
     * 构建站内通知内容
     */
    private function buildContent(Workorder $workorder, string $event): string
    {
        $eventLabels = self::getEventLabels();
        $label = $eventLabels[$event] ?? $event;

        $parts = ["工单「{$workorder->title}」{$label}"];
        if ($workorder->assignee) {
            $parts[] = "处理人：{$workorder->assignee->name}";
        }
        return implode('，', $parts);
    }

    /**
     * 构建短信内容
     */
    private function buildSmsContent(Workorder $workorder, string $event): string
    {
        $eventLabels = self::getEventLabels();
        $label = $eventLabels[$event] ?? $event;
        $systemName = SystemSetting::get('system_name', '工单系统');
        return "【{$systemName}】您的工单「{$workorder->title}」{$label}，编号：{$workorder->workorder_number}";
    }
}
