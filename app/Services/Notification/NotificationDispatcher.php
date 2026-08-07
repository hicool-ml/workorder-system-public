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
            'created'   => ['in_app' => true,  'sms' => false, 'wecom' => false],
            'assigned'  => ['in_app' => true,  'sms' => true,  'wecom' => true],
            'started'   => ['in_app' => true,  'sms' => false, 'wecom' => false],
            'resolved'  => ['in_app' => true,  'sms' => true,  'wecom' => false],
            'completed' => ['in_app' => true,  'sms' => false, 'wecom' => false],
            'closed'    => ['in_app' => true,  'sms' => false, 'wecom' => false],
            'overdue'   => ['in_app' => true,  'sms' => true,  'wecom' => true],
            'announcement' => ['in_app' => true, 'sms' => false, 'wecom' => true],
        ];
    }

    /**
     * 获取当前通知规则
     */
    public static function getRules(): array
    {
        $stored = SystemSetting::get('notification_rules');

        if ($stored && is_array($stored)) {
            // 逐事件深度合并，确保每个事件都包含所有通道（in_app/sms/wecom）
            $defaults = self::defaultRules();
            $merged = [];
            foreach ($defaults as $event => $defaultChannels) {
                $storedChannels = $stored[$event] ?? [];
                $merged[$event] = array_merge($defaultChannels, $storedChannels);
            }
            // 保留 stored 中可能有但 defaults 里没有的事件
            foreach ($stored as $event => $channels) {
                if (!isset($merged[$event])) {
                    $merged[$event] = $channels;
                }
            }
            return $merged;
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
        // 逐事件深度合并，确保每个事件都有完整的三通道结构
        $defaults = self::defaultRules();
        $merged = [];
        foreach ($defaults as $event => $defaultChannels) {
            $storedChannels = $rules[$event] ?? [];
            $merged[$event] = array_merge($defaultChannels, $storedChannels);
        }
        foreach ($rules as $event => $channels) {
            if (!isset($merged[$event])) {
                $merged[$event] = $channels;
            }
        }
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
            'announcement' => '系统公告',
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
        $wecomEnabled = ($rules[$event]['wecom'] ?? false) === true;

        // 如果所有通道都关闭，直接返回
        if (!$inAppEnabled && !$smsEnabled && !$wecomEnabled) {
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

        // 企业微信群通知
        if ($wecomEnabled) {
            $this->sendWeCom($workorder, $event, $recipients);
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
                // assigned handler (if any) is also notified, preserving original coverage
                if ($workorder->assignee_id) {
                    $assignee = User::find($workorder->assignee_id);
                    if ($assignee && $assignee->status === 'active') {
                        $recipients[$assignee->id] = $assignee;
                    }
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
                if (in_array($event, ['started', 'resolved', 'completed', 'closed']) && $workorder->creator_id) {
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

        // Build content in same format as old system: address - description - status - assignee
        $content = $this->buildContent($workorder, $event);

        // Title matches old system style: e.g. "新工单创建", "工单开始处理"
        $titleMap = [
            'created'   => '新工单创建',
            'assigned'  => '工单已分配',
            'started'   => '工单开始处理',
            'resolved'  => '工单已解决',
            'completed' => '工单已完结',
            'closed'    => '工单已关闭',
            'overdue'   => '工单超时提醒',
        ];
        $title = $titleMap[$event] ?? $label;

        // CAS / self-service workorder gets a source prefix
        $sourcePrefix = '';
        if ($workorder->source === '本台') {
            $sourcePrefix = '[自助报修] ';
        }

        // Rich data matching old system
        $addressParts = $this->buildAddress($workorder);
        $data = [
            'workorder_id'  => $workorder->id,
            'ticket_no'     => $workorder->ticket_no,
            'description'   => $workorder->description,
            'priority'      => $workorder->priority,
            'creator_name'  => $workorder->creator ? $workorder->creator->name : '未知用户',
            'address'       => $addressParts,
            'fault_type'    => $workorder->description ?: '',
            'status'        => $workorder->status_text,
            'event'         => $event,
            'source'        => $workorder->source,
            'action_url'    => '/workorders/' . $workorder->id,
        ];

        foreach ($recipients as $user) {
            try {
                NotificationModel::create([
                    'type'          => 'workorder_' . $event,
                    'title'         => $sourcePrefix . $title,
                    'content'       => $content,
                    'data'          => $data,
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
            'workorder_number' => $workorder->ticket_no,
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
    /**
     * Build address string from workorder campus/building/location
     */
    private function buildAddress(Workorder $workorder): string
    {
        $parts = [];
        if ($workorder->campus && trim($workorder->campus)) {
            $parts[] = trim($workorder->campus);
        }
        if ($workorder->building && trim($workorder->building)) {
            $b = trim($workorder->building);
            $b = preg_replace('/^(new_|old_|asean_)campus\s*[-_]?\s*/i', '', $b);
            // building 存的是 locations 表的 id，查完整楼名
            $loc = \App\Models\Location::find($b);
            if ($loc) {
                $b = $loc->name;
            }
            $parts[] = $b;
        }
        if ($workorder->location_detail && trim($workorder->location_detail)) {
            $parts[] = trim($workorder->location_detail);
        }
        $address = implode(' ', array_unique(array_filter($parts)));
        return $address ?: '未知地址';
    }

    /**
     * Build in-app notification content
     * Format matches old system: address - description - status - assignee
     */
    /**
     * 发送企业微信群通知
     */
    private function sendWeCom(Workorder $workorder, string $event, array $recipients): void
    {
        $wecom = app(WeComWebhookService::class);

        if (!$wecom->isEnabled()) {
            return;
        }

        $eventLabels = self::getEventLabels();
        $label = $eventLabels[$event] ?? $event;
        $systemName = SystemSetting::get('system_name', '工单系统');
        $address = $this->buildAddress($workorder);
        $description = mb_substr($workorder->description ?: $workorder->title ?: '未知故障', 0, 30);
        $status = $workorder->status_text ?: '未知状态';

        $timestamp = now()->format('Y-m-d H:i');
        $content = "【{$systemName}】{$label}\n"
            . "时间：{$timestamp}\n"
            . "编号：{$workorder->ticket_no}\n"
            . "地点：{$address}\n"
            . "描述：{$description}";

        // 收集 @ 信息：优先企业微信 userid，手机号作为 mentioned_mobile_list
        $mentionedUserIds = [];
        $mentionedMobiles = [];
        foreach ($recipients as $user) {
            if (!empty($user->wecom_userid)) {
                // 有 userid 优先用 userid @
                $mentionedUserIds[] = $user->wecom_userid;
            } elseif (!empty($user->phone)) {
                // 没填 userid 则用手机号 @
                $mentionedMobiles[] = $user->phone;
            }
        }
        // 处理人只显示工单实际的 assignee，不是所有通知接收者
        $assigneeNames = $workorder->assignee ? $workorder->assignee->name : '';
        if ($assigneeNames) {
            $content .= "\n处理人：{$assigneeNames}";
        } elseif ($event === 'created') {
            $content .= "\n处理人：待分配";
        }
        $content .= "\n状态：{$status}";

        // 工单链接（放在最后一行，去掉"链接："前缀，微信可能自动识别）
        $baseUrl = rtrim(SystemSetting::get('system_url', config('app.url', '')), '/');
        if ($baseUrl) {
            $content .= "\n{$baseUrl}/workorders/{$workorder->id}";
        }

        if ($event === 'created') {
            // 创建时 @所有人，提醒管理员有新工单
            $result = $wecom->sendText($content, ['@all']);
        } else {
            // assigned/overdue 只 @ 对应的工程师
            $shouldMention = in_array($event, ['assigned', 'overdue'])
                && (!empty($mentionedUserIds) || !empty($mentionedMobiles));
            $result = $wecom->sendText(
                $content,
                $shouldMention ? $mentionedUserIds : [],
                $shouldMention ? $mentionedMobiles : []
            );
        }

        Log::info('工单企业微信通知', [
            'workorder_id' => $workorder->id,
            'event' => $event,
            'success' => $result['success'],
        ]);
    }
    private function buildContent(Workorder $workorder, string $event): string
    {
        $address = $this->buildAddress($workorder);
        $description = $workorder->description ?: $workorder->title ?: '未知故障';
        $status = $workorder->status_text ?: '未知状态';
        $assigneeName = $workorder->assignee ? $workorder->assignee->name : '未分配';

        $content = trim("{$address}-{$description}-{$status}-{$assigneeName}");
        return $content;
    }

    /**
     * 构建短信内容
     */
    private function buildSmsContent(Workorder $workorder, string $event): string
    {
        $eventLabels = self::getEventLabels();
        $label = $eventLabels[$event] ?? $event;
        $systemName = SystemSetting::get('system_name', '工单系统');
        // 短信内容脱敏：不含工单标题（可能含用户故障描述），仅用编号
        return "【{$systemName}】{$label}，编号：{$workorder->ticket_no}，请登录系统查看详情。";
    }
}
