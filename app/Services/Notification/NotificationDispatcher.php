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
            'created'   => ['in_app' => true,  'sms' => false, 'wecom' => false, 'dingtalk' => false, 'feishu' => false],
            'assigned'  => ['in_app' => true,  'sms' => true,  'wecom' => true,  'dingtalk' => true,  'feishu' => false],
            'started'   => ['in_app' => true,  'sms' => false, 'wecom' => false, 'dingtalk' => false, 'feishu' => false],
            'resolved'  => ['in_app' => true,  'sms' => true,  'wecom' => false, 'dingtalk' => false, 'feishu' => false],
            'completed' => ['in_app' => true,  'sms' => false, 'wecom' => false, 'dingtalk' => false, 'feishu' => false],
            'closed'    => ['in_app' => true,  'sms' => false, 'wecom' => false, 'dingtalk' => false, 'feishu' => false],
            'overdue'   => ['in_app' => true,  'sms' => true,  'wecom' => true,  'dingtalk' => true,  'feishu' => false],
            'announcement' => ['in_app' => true, 'sms' => false, 'wecom' => true, 'dingtalk' => false, 'feishu' => false],
        ];
    }

    /**
     * 获取当前通知规则
     */
    public static function getRules(): array
    {
        $stored = SystemSetting::get('notification_rules');

        if ($stored && is_array($stored)) {
            // 逐事件深度合并，确保每个事件都包含所有通道（in_app/sms/wecom/dingtalk/feishu）
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
        // 逐事件深度合并，确保每个事件都有完整的多通道结构
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
     * 报修人短信开关（独立于事件×通道矩阵）
     * 存于 system_settings.creator_sms_enabled，默认关闭。
     */
    public static function isCreatorSmsEnabled(): bool
    {
        return filter_var(SystemSetting::get('creator_sms_enabled', '0'), FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * 满意度调查短信开关（独立于受理通知开关）
     * 存于 system_settings.creator_survey_enabled，默认关闭。
     */
    public static function isCreatorSurveyEnabled(): bool
    {
        return filter_var(SystemSetting::get('creator_survey_enabled', '0'), FILTER_VALIDATE_BOOLEAN);
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
        $dingtalkEnabled = ($rules[$event]['dingtalk'] ?? false) === true;
        $feishuEnabled = ($rules[$event]['feishu'] ?? false) === true;

        // 如果所有通道都关闭，直接返回
        if (!$inAppEnabled && !$smsEnabled && !$wecomEnabled && !$dingtalkEnabled && !$feishuEnabled) {
            return;
        }

        // 确定接收者
        $recipients = $this->resolveRecipients($workorder, $event, $extra);

        // Don't push a notification back to the user who triggered the action
        // (e.g. an engineer clicking "start" shouldn't receive their own notice).
        $actorId = auth()->id();
        if ($actorId && isset($recipients[$actorId])) {
            unset($recipients[$actorId]);
        }

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

        // 钉钉通知
        if ($dingtalkEnabled) {
            $this->sendDingTalk($workorder, $event, $recipients);
        }

        // 飞书通知
        if ($feishuEnabled) {
            $this->sendFeishu($workorder, $event, $recipients);
        }

        // 报修人短信：内容独立于内部广播通道，整单生命周期只发两条，
        // 由 sent_at 标记保证不重复：
        // 1. 受理通知——"已受理"时（创建即分配或后续接单），用 sms_acceptance_sent_at 防重
        // 2. 满意度调查——完结（completed）时，用 sms_survey_sent_at 防重
        if (($event === 'assigned') || ($event === 'created' && $workorder->assignee_id)) {
            $this->sendCreatorAcceptanceSms($workorder);
        }
        if ($event === 'completed') {
            $this->sendCreatorSurveySms($workorder);
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
                // 创建工单广播给内部人员（管理员/工单管理员/工程师），便于工程师接单；
                // 报修人（CAS 用户）属服务对象、不在内部用户池，不参与此广播，
                // 其服务确认短信在下方 sendCreatorConfirmationSms 单独处理。
                $users = User::whereIn('role', ['admin', 'workorder_manager', 'engineer'])
                    ->where('status', 'active')
                    ->get();
                foreach ($users as $u) {
                    $recipients[$u->id] = $u;
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
     * 报修人短信通用前置：短信接口、报修人电话是否就绪（不含开关，开关由各方法自查）。
     * 返回报修人手机号，不满足前置条件返回 null。
     */
    private function resolveCreatorSmsTarget(Workorder $workorder): ?string
    {
        if (!app(SmsManager::class)->isEnabled()) {
            return null;
        }
        // 报修人电话优先用工单联系电话，回退到报修人账号手机号
        $phone = $workorder->contact_phone;
        if (!$phone && $workorder->creator_id) {
            $creator = User::find($workorder->creator_id);
            $phone = $creator ? $creator->phone : null;
        }
        return $phone && trim($phone) !== '' ? trim($phone) : null;
    }

    /**
     * 报修人受理通知短信（整单只发一次）
     * 模板内容在「短信配置」页可改；工程师电话来自 assignee，无工程师不发。
     * sms_acceptance_sent_at 既防重发，也作为回复关联依据。
     */
    private function sendCreatorAcceptanceSms(Workorder $workorder): void
    {
        // 受理通知独立开关
        if (!self::isCreatorSmsEnabled()) {
            return;
        }
        // 没有工程师接单就不算受理，不发
        if (!$workorder->assignee_id) {
            return;
        }
        // 整单只发一次：已发过则跳过（防 created+assigned 双触发等意外）
        if ($workorder->sms_acceptance_sent_at) {
            return;
        }
        $phone = $this->resolveCreatorSmsTarget($workorder);
        if (!$phone) {
            return;
        }

        $engineer = User::find($workorder->assignee_id);
        $engineerPhone = $engineer ? trim((string) $engineer->phone) : '';

        // 读取后台模板（带占位符 {工程师电话} {预约时间} {系统名称}），按是否有预约替换
        $systemName = SystemSetting::get('system_name', '工单系统');
        $appointment = trim((string) $workorder->appointment_time);
        if ($appointment !== '') {
            $template = SystemSetting::get(
                'sms_creator_acceptance_tpl_with_appt',
                "【{系统名称}】您的报修已受理，工程师\"{工程师电话}\"预计{预约时间}上门为您服务。"
            );
        } else {
            $template = SystemSetting::get(
                'sms_creator_acceptance_tpl_no_appt',
                "【{系统名称}】您的报修已受理，请保持电话畅通，便于工程师\"{工程师电话}\"能联系到您并为您服务。"
            );
        }
        $content = strtr($template, [
            '{系统名称}' => $systemName,
            '{工程师电话}' => $engineerPhone,
            '{预约时间}' => $appointment,
            '{工单编号}' => $workorder->ticket_no,
        ]);

        $sms = app(SmsManager::class);
        $result = $sms->send($phone, SystemSetting::get('sms_creator_acceptance_code', 'SMS_ACCEPTANCE'), [
            'workorder_number' => $workorder->ticket_no,
            'content' => $content,
        ]);

        // 无论成功失败都记录发送时间（失败不重试，避免对报修人反复打扰）
        if ($result['success'] ?? false) {
            $workorder->forceFill(['sms_acceptance_sent_at' => now()])->save();
        }
        Log::info('报修人受理短信', [
            'workorder_id' => $workorder->id, 'to' => $phone, 'success' => $result['success'] ?? false,
        ]);
    }

    /**
     * 报修人满意度调查短信（整单只发一次，工单完结时发送）
     * 报修人回复 1=满意 / 0=不满意，由 SmsReplyController 回写 sms_satisfaction。
     * sms_survey_sent_at 既防重发，也作为回复关联依据。
     */
    private function sendCreatorSurveySms(Workorder $workorder): void
    {
        // 满意度调查独立开关（与受理通知开关相互独立）
        if (!self::isCreatorSurveyEnabled()) {
            return;
        }
        // 整单只发一次：已发过则跳过
        if ($workorder->sms_survey_sent_at) {
            return;
        }
        $phone = $this->resolveCreatorSmsTarget($workorder);
        if (!$phone) {
            return;
        }

        $systemName = SystemSetting::get('system_name', '工单系统');
        $template = SystemSetting::get(
            'sms_creator_survey_tpl',
            "【{系统名称}】您的报修服务已完成，请对本次服务进行评价：满意回复 1，不满意回复 0。"
        );
        $content = strtr($template, [
            '{系统名称}' => $systemName,
            '{工单编号}' => $workorder->ticket_no,
        ]);

        $sms = app(SmsManager::class);
        $result = $sms->send($phone, SystemSetting::get('sms_creator_survey_code', 'SMS_SURVEY'), [
            'workorder_number' => $workorder->ticket_no,
            'content' => $content,
        ]);

        if ($result['success'] ?? false) {
            $workorder->forceFill(['sms_survey_sent_at' => now()])->save();
        }
        Log::info('报修人满意度调查短信', [
            'workorder_id' => $workorder->id, 'to' => $phone, 'success' => $result['success'] ?? false,
        ]);
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

        $content = $this->buildBroadcastContent($workorder, $event);

        if ($event === 'created') {
            // 创建时 @所有人，提醒管理员有新工单
            $result = $wecom->sendText($content, ['@all']);
        } else {
            // assigned/overdue 只 @ 对应的工程师
            [$mentionedUserIds, $mentionedMobiles] = $this->collectWeComMentions($recipients);
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

    /**
     * 企业微信 @ 收集：userid 优先，手机号兜底
     */
    private function collectWeComMentions(array $recipients): array
    {
        $userIds = [];
        $mobiles = [];
        foreach ($recipients as $user) {
            if (!empty($user->wecom_userid)) {
                $userIds[] = $user->wecom_userid;
            } elseif (!empty($user->phone)) {
                $mobiles[] = $user->phone;
            }
        }
        return [$userIds, $mobiles];
    }

    /**
     * 发送钉钉通知（自定义机器人 / 企业内部应用工作通知）
     * @ 逻辑参照企业微信：创建 @all，分配/超时 @ 工程师（userid 优先，手机号兜底）
     */
    private function sendDingTalk(Workorder $workorder, string $event, array $recipients): void
    {
        $dingtalk = app(DingTalkService::class);

        if (!$dingtalk->isEnabled()) {
            return;
        }

        $content = $this->buildBroadcastContent($workorder, $event);

        if ($event === 'created') {
            $result = $dingtalk->sendText($content, [], [], true);
        } else {
            // 钉钉：userid → atUserIds，手机号 → atMobiles
            $atUserIds = [];
            $atMobiles = [];
            foreach ($recipients as $user) {
                if (!empty($user->dingtalk_userid)) {
                    $atUserIds[] = $user->dingtalk_userid;
                } elseif (!empty($user->phone)) {
                    $atMobiles[] = $user->phone;
                }
            }
            $shouldMention = in_array($event, ['assigned', 'overdue'])
                && (!empty($atUserIds) || !empty($atMobiles));
            $result = $dingtalk->sendText(
                $content,
                $shouldMention ? $atUserIds : [],
                $shouldMention ? $atMobiles : []
            );
        }

        Log::info('工单钉钉通知', [
            'workorder_id' => $workorder->id,
            'event' => $event,
            'success' => $result['success'],
        ]);
    }

    /**
     * 发送飞书通知（自定义机器人 / 自建应用）
     * @ 逻辑：创建 @all，分配/超时 @ 工程师（user_id/open_id 优先）
     */
    private function sendFeishu(Workorder $workorder, string $event, array $recipients): void
    {
        $feishu = app(FeishuService::class);

        if (!$feishu->isEnabled()) {
            return;
        }

        $content = $this->buildBroadcastContent($workorder, $event);

        if ($event === 'created') {
            // 群机器人 @all；自建应用模式下 sendText 内部会要求指定接收人
            $result = $feishu->sendText($content, [], [], true);
        } else {
            $userIds = [];
            foreach ($recipients as $user) {
                if (!empty($user->feishu_user_id)) {
                    $userIds[] = $user->feishu_user_id;
                }
            }
            $shouldMention = in_array($event, ['assigned', 'overdue']) && !empty($userIds);
            $result = $feishu->sendText($content, $shouldMention ? $userIds : []);
        }

        Log::info('工单飞书通知', [
            'workorder_id' => $workorder->id,
            'event' => $event,
            'success' => $result['success'],
        ]);
    }

    /**
     * 构建企业微信/钉钉/飞书等 IM 通道的统一广播内容（标题/时间/编号/地点/描述/处理人/状态/链接）
     */
    private function buildBroadcastContent(Workorder $workorder, string $event): string
    {
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

        $assigneeNames = $workorder->assignee ? $workorder->assignee->name : '';
        if ($assigneeNames) {
            $content .= "\n处理人：{$assigneeNames}";
        } elseif ($event === 'created') {
            $content .= "\n处理人：待分配";
        }
        $content .= "\n状态：{$status}";

        $baseUrl = rtrim(SystemSetting::get('system_url', config('app.url', '')), '/');
        if ($baseUrl) {
            $content .= "\n{$baseUrl}/workorders/{$workorder->id}";
        }

        return $content;
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
