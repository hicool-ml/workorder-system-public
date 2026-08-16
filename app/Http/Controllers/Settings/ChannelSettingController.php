<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Settings\Concerns\GuardsAdmin;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * 群通知通道配置：企业微信 / 钉钉 / 飞书
 * 三通道结构一致：配置页 + 更新（webhook/app 双模式）+ 测试发送
 */
class ChannelSettingController extends Controller
{
    use GuardsAdmin;

    // ===== 企业微信 =====

    /**
     * 企业微信通知配置页面
     */
    public function wecom()
    {
        if ($denied = $this->guardAdminRedirect()) {
            return $denied;
        }

        $wecomSettings = [
            'send_mode'       => SystemSetting::get('wecom_send_mode', 'webhook'),
            'webhook_enabled' => filter_var(SystemSetting::get('wecom_webhook_enabled', '0'), FILTER_VALIDATE_BOOLEAN),
            'webhook_url'     => SystemSetting::get('wecom_webhook_url', ''),
            'app_enabled'     => filter_var(SystemSetting::get('wecom_app_enabled', '0'), FILTER_VALIDATE_BOOLEAN),
            'app_corpid'      => SystemSetting::get('wecom_app_corpid', ''),
            'app_secret'      => SystemSetting::get('wecom_app_secret', ''),
            'app_agentid'     => SystemSetting::get('wecom_app_agentid', ''),
            'ssl_verify_enabled' => filter_var(SystemSetting::get('ssl_verify_enabled', '1'), FILTER_VALIDATE_BOOLEAN),
            'ssl_cacert_path'    => SystemSetting::get('ssl_cacert_path', ''),
            'ssl_cacert_exists'  => file_exists(SystemSetting::get('ssl_cacert_path', '') ?: ''),
        ];

        return view('system-settings.wecom', compact('wecomSettings'));
    }

    /**
     * 更新企业微信通知配置（群机器人 / 自建应用）
     */
    public function updateWecom(Request $request)
    {
        if ($denied = $this->guardAdminRedirect()) {
            return $denied;
        }

        $mode = $request->input('wecom_send_mode', 'webhook');
        if (!in_array($mode, ['webhook', 'app'])) {
            $mode = 'webhook';
        }
        SystemSetting::set('wecom_send_mode', $mode, 'string', '企业微信推送模式', false);

        if ($mode === 'webhook') {
            $request->validate([
                'wecom_webhook_url' => 'nullable|string|max:500',
            ]);
            $url = trim($request->input('wecom_webhook_url', ''));
            $whEnabled = $request->boolean('wecom_webhook_enabled');
            if ($whEnabled && empty($url)) {
                return back()->withInput()->with('error', '启用前请先填写企业微信 Webhook 地址');
            }
            SystemSetting::set('wecom_webhook_url', $url, 'string', '企业微信群机器人 Webhook 地址', false);
            SystemSetting::set('wecom_webhook_enabled', $whEnabled, 'boolean', '是否启用企业微信群机器人通知', false);
            return back()->with('success', '企业微信配置已保存' . ($whEnabled ? '（已启用）' : '（未启用）'));
        }

        // 自建应用模式
        $request->validate([
            'wecom_app_corpid'   => 'nullable|string|max:200',
            'wecom_app_secret'   => 'nullable|string|max:200',
            'wecom_app_agentid'  => 'nullable|string|max:50',
        ]);
        $appEnabled = $request->boolean('wecom_app_enabled');
        $corpid = trim($request->input('wecom_app_corpid', ''));
        if ($appEnabled && empty($corpid)) {
            return back()->withInput()->with('error', '启用前请先填写企业ID（CorpID）');
        }
        SystemSetting::set('wecom_app_corpid', $corpid, 'string', '企业微信企业ID', false);
        // Secret 密文不回显：留空 = 保留原值
        if ($request->filled('wecom_app_secret')) {
            SystemSetting::set('wecom_app_secret', trim($request->input('wecom_app_secret')), 'string', '企业微信自建应用Secret', false);
        }
        SystemSetting::set('wecom_app_agentid', trim($request->input('wecom_app_agentid', '')), 'string', '企业微信自建应用AgentID', false);
        SystemSetting::set('wecom_app_enabled', $appEnabled, 'boolean', '是否启用企业微信自建应用通知', false);
        Cache::forget('wecom_app_access_token');
        return back()->with('success', '企业微信配置已保存' . ($appEnabled ? '（已启用）' : '（未启用）'));
    }

    /**
     * 发送企业微信测试消息
     */
    public function testWecom(Request $request)
    {
        if ($denied = $this->guardAdminJson()) {
            return $denied;
        }

        $wecom = app(\App\Services\Notification\WeComWebhookService::class);
        $systemName = SystemSetting::get('system_name', '工单系统');

        $content = "【{$systemName}】测试通知\n"
            . "这是一条来自工单系统的测试消息。\n"
            . "收到此消息说明企业微信通知配置成功。";

        $mode = $request->input('wecom_send_mode', $wecom->getSendMode());

        // 检查当前推送通道是否已启用（与工单通知的 isEnabled() 检查一致）
        $enabled = $wecom->isEnabled();

        // 统一用 text 类型发送（纯文本），与工单通知格式一致
        $result = $wecom->sendText($content);

        // 即使测试发送成功，如果通道未启用也必须明确告知用户
        if ($result['success'] && !$enabled) {
            return response()->json([
                'success'         => false,
                'message'         => '测试消息已发送成功，但当前推送通道未启用，工单通知不会发送。请在上方勾选「启用」后再保存。',
                'test_sent'       => true,
                'channel_enabled' => false,
            ]);
        }

        return response()->json($result);
    }

    // ===== 钉钉 =====

    /**
     * 钉钉通知配置页面
     */
    public function dingtalk()
    {
        if ($denied = $this->guardAdminRedirect()) {
            return $denied;
        }

        $dingtalkSettings = [
            'send_mode'         => SystemSetting::get('dingtalk_send_mode', 'webhook'),
            'webhook_enabled'   => filter_var(SystemSetting::get('dingtalk_webhook_enabled', '0'), FILTER_VALIDATE_BOOLEAN),
            'webhook_url'       => SystemSetting::get('dingtalk_webhook_url', ''),
            'webhook_secret'    => SystemSetting::get('dingtalk_webhook_secret', ''),
            'app_enabled'       => filter_var(SystemSetting::get('dingtalk_app_enabled', '0'), FILTER_VALIDATE_BOOLEAN),
            'app_key'           => SystemSetting::get('dingtalk_app_key', ''),
            'app_secret'        => SystemSetting::get('dingtalk_app_secret', ''),
            'app_agentid'       => SystemSetting::get('dingtalk_app_agentid', ''),
        ];

        return view('system-settings.dingtalk', compact('dingtalkSettings'));
    }

    /**
     * 更新钉钉通知配置
     */
    public function updateDingtalk(Request $request)
    {
        if ($denied = $this->guardAdminRedirect()) {
            return $denied;
        }

        $mode = $request->input('dingtalk_send_mode', 'webhook');
        if (!in_array($mode, ['webhook', 'app'])) {
            $mode = 'webhook';
        }
        SystemSetting::set('dingtalk_send_mode', $mode, 'string', '钉钉推送模式', false);

        if ($mode === 'webhook') {
            $request->validate(['dingtalk_webhook_url' => 'nullable|string|max:500']);
            $url = trim($request->input('dingtalk_webhook_url', ''));
            $whEnabled = $request->boolean('dingtalk_webhook_enabled');
            if ($whEnabled && empty($url)) {
                return back()->withInput()->with('error', '启用前请先填写钉钉 Webhook 地址');
            }
            SystemSetting::set('dingtalk_webhook_url', $url, 'string', '钉钉自定义机器人 Webhook 地址', false);
            SystemSetting::set('dingtalk_webhook_secret', trim($request->input('dingtalk_webhook_secret', '')), 'string', '钉钉机器人加签 secret', false);
            SystemSetting::set('dingtalk_webhook_enabled', $whEnabled, 'boolean', '是否启用钉钉机器人通知', false);
            return back()->with('success', '钉钉配置已保存' . ($whEnabled ? '（已启用）' : '（未启用）'));
        }

        // 企业内部应用模式
        $request->validate([
            'dingtalk_app_key'     => 'nullable|string|max:200',
            'dingtalk_app_secret'  => 'nullable|string|max:200',
            'dingtalk_app_agentid' => 'nullable|string|max:50',
        ]);
        $appEnabled = $request->boolean('dingtalk_app_enabled');
        $appKey = trim($request->input('dingtalk_app_key', ''));
        if ($appEnabled && empty($appKey)) {
            return back()->withInput()->with('error', '启用前请先填写钉钉 AppKey');
        }
        SystemSetting::set('dingtalk_app_key', $appKey, 'string', '钉钉应用 AppKey', false);
        // Secret 密文不回显：留空 = 保留原值
        if ($request->filled('dingtalk_app_secret')) {
            SystemSetting::set('dingtalk_app_secret', trim($request->input('dingtalk_app_secret')), 'string', '钉钉应用 AppSecret', false);
        }
        SystemSetting::set('dingtalk_app_agentid', trim($request->input('dingtalk_app_agentid', '')), 'string', '钉钉应用 AgentId', false);
        SystemSetting::set('dingtalk_app_enabled', $appEnabled, 'boolean', '是否启用钉钉工作通知', false);
        Cache::forget('dingtalk_app_access_token');
        return back()->with('success', '钉钉配置已保存' . ($appEnabled ? '（已启用）' : '（未启用）'));
    }

    /**
     * 发送钉钉测试消息
     */
    public function testDingtalk(Request $request)
    {
        if ($denied = $this->guardAdminJson()) {
            return $denied;
        }

        $dingtalk = app(\App\Services\Notification\DingTalkService::class);
        $systemName = SystemSetting::get('system_name', '工单系统');
        $content = "【{$systemName}】测试通知\n这是一条来自工单系统的钉钉测试消息。\n收到此消息说明钉钉通知配置成功。";

        $enabled = $dingtalk->isEnabled();
        $result = $dingtalk->sendText($content);

        if ($result['success'] && !$enabled) {
            return response()->json([
                'success'         => false,
                'message'         => '测试消息已发送成功，但当前推送通道未启用，工单通知不会发送。请在上方勾选「启用」后再保存。',
                'test_sent'       => true,
                'channel_enabled' => false,
            ]);
        }

        return response()->json($result);
    }

    // ===== 飞书 =====

    /**
     * 飞书通知配置页面
     */
    public function feishu()
    {
        if ($denied = $this->guardAdminRedirect()) {
            return $denied;
        }

        $feishuSettings = [
            'send_mode'         => SystemSetting::get('feishu_send_mode', 'webhook'),
            'webhook_enabled'   => filter_var(SystemSetting::get('feishu_webhook_enabled', '0'), FILTER_VALIDATE_BOOLEAN),
            'webhook_url'       => SystemSetting::get('feishu_webhook_url', ''),
            'webhook_secret'    => SystemSetting::get('feishu_webhook_secret', ''),
            'app_enabled'       => filter_var(SystemSetting::get('feishu_app_enabled', '0'), FILTER_VALIDATE_BOOLEAN),
            'app_id'            => SystemSetting::get('feishu_app_id', ''),
            'app_secret'        => SystemSetting::get('feishu_app_secret', ''),
        ];

        return view('system-settings.feishu', compact('feishuSettings'));
    }

    /**
     * 更新飞书通知配置
     */
    public function updateFeishu(Request $request)
    {
        if ($denied = $this->guardAdminRedirect()) {
            return $denied;
        }

        $mode = $request->input('feishu_send_mode', 'webhook');
        if (!in_array($mode, ['webhook', 'app'])) {
            $mode = 'webhook';
        }
        SystemSetting::set('feishu_send_mode', $mode, 'string', '飞书推送模式', false);

        if ($mode === 'webhook') {
            $request->validate(['feishu_webhook_url' => 'nullable|string|max:500']);
            $url = trim($request->input('feishu_webhook_url', ''));
            $whEnabled = $request->boolean('feishu_webhook_enabled');
            if ($whEnabled && empty($url)) {
                return back()->withInput()->with('error', '启用前请先填写飞书 Webhook 地址');
            }
            SystemSetting::set('feishu_webhook_url', $url, 'string', '飞书自定义机器人 Webhook 地址', false);
            SystemSetting::set('feishu_webhook_secret', trim($request->input('feishu_webhook_secret', '')), 'string', '飞书机器人加签 secret', false);
            SystemSetting::set('feishu_webhook_enabled', $whEnabled, 'boolean', '是否启用飞书机器人通知', false);
            return back()->with('success', '飞书配置已保存' . ($whEnabled ? '（已启用）' : '（未启用）'));
        }

        // 自建应用模式
        $request->validate([
            'feishu_app_id'     => 'nullable|string|max:200',
            'feishu_app_secret' => 'nullable|string|max:200',
        ]);
        $appEnabled = $request->boolean('feishu_app_enabled');
        $appId = trim($request->input('feishu_app_id', ''));
        if ($appEnabled && empty($appId)) {
            return back()->withInput()->with('error', '启用前请先填写飞书 App ID');
        }
        SystemSetting::set('feishu_app_id', $appId, 'string', '飞书自建应用 App ID', false);
        // Secret 密文不回显：留空 = 保留原值
        if ($request->filled('feishu_app_secret')) {
            SystemSetting::set('feishu_app_secret', trim($request->input('feishu_app_secret')), 'string', '飞书自建应用 App Secret', false);
        }
        SystemSetting::set('feishu_app_enabled', $appEnabled, 'boolean', '是否启用飞书自建应用通知', false);
        Cache::forget('feishu_tenant_access_token');
        return back()->with('success', '飞书配置已保存' . ($appEnabled ? '（已启用）' : '（未启用）'));
    }

    /**
     * 发送飞书测试消息
     */
    public function testFeishu(Request $request)
    {
        if ($denied = $this->guardAdminJson()) {
            return $denied;
        }

        $feishu = app(\App\Services\Notification\FeishuService::class);
        $systemName = SystemSetting::get('system_name', '工单系统');
        $content = "【{$systemName}】测试通知\n这是一条来自工单系统的飞书测试消息。\n收到此消息说明飞书通知配置成功。";

        $enabled = $feishu->isEnabled();
        $result = $feishu->sendText($content);

        if ($result['success'] && !$enabled) {
            return response()->json([
                'success'         => false,
                'message'         => '测试消息已发送成功，但当前推送通道未启用，工单通知不会发送。请在上方勾选「启用」后再保存。',
                'test_sent'       => true,
                'channel_enabled' => false,
            ]);
        }

        return response()->json($result);
    }
}
