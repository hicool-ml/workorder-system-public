<?php

namespace App\Services\Notification;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * 钉钉通知服务
 *
 * 参照 WeComWebhookService，支持两种推送模式：
 * 1. webhook  — 自定义群机器人 Webhook（含加签 secret，消息仅限钉钉群内查看）
 * 2. app      — 企业内部应用「工作通知」（appKey/appSecret 换 access_token，直达个人钉钉）
 *
 * 所有配置存储在 system_settings 表，不硬编码。
 */
class DingTalkService
{
    /**
     * 当前推送模式：webhook（自定义机器人）或 app（企业内部应用工作通知）
     */
    public function getSendMode(): string
    {
        return SystemSetting::get('dingtalk_send_mode', 'webhook');
    }

    /**
     * 钉钉通知是否启用（按当前模式检查对应开关）
     */
    public function isEnabled(): bool
    {
        if ($this->getSendMode() === 'app') {
            return filter_var(SystemSetting::get('dingtalk_app_enabled', '0'), FILTER_VALIDATE_BOOLEAN);
        }
        return filter_var(SystemSetting::get('dingtalk_webhook_enabled', '0'), FILTER_VALIDATE_BOOLEAN);
    }

    // ──────────────────────────────────────────────────────────────
    //  统一入口（NotificationDispatcher 调用）
    // ──────────────────────────────────────────────────────────────

    /**
     * 发送文本消息（按当前模式自动路由）
     *
     * @param string   $content          消息内容
     * @param array    $atUserIds        钉钉 userid 列表（机器人 atUserIds / 工作通知 userid 列表）
     * @param array    $atMobiles        手机号列表（机器人 atMobiles）
     */
    public function sendText(string $content, array $atUserIds = [], array $atMobiles = [], bool $isAtAll = false): array
    {
        if ($this->getSendMode() === 'app') {
            return $this->sendAppWorkNotice($content, $atUserIds);
        }
        return $this->sendWebhookText($content, $atUserIds, $atMobiles, $isAtAll);
    }

    /**
     * 发送 Markdown（机器人原生支持；工作通知降级为纯文本）
     */
    public function sendMarkdown(string $content): array
    {
        if ($this->getSendMode() === 'app') {
            return $this->sendAppWorkNotice($this->markdownToText($content));
        }
        return $this->sendWebhookMarkdown($content);
    }

    // ──────────────────────────────────────────────────────────────
    //  自定义机器人 Webhook 模式
    // ──────────────────────────────────────────────────────────────

    private function sendWebhookText(string $content, array $atUserIds = [], array $atMobiles = [], bool $isAtAll = false): array
    {
        $url = SystemSetting::get('dingtalk_webhook_url', '');
        if (empty($url)) {
            return ['success' => false, 'message' => '未配置钉钉 Webhook 地址'];
        }

        $payload = [
            'msgtype' => 'text',
            'text'    => ['content' => $content],
            'at'      => [
                'atMobiles' => array_values($atMobiles),
                'atUserIds' => array_values($atUserIds),
                'isAtAll'   => $isAtAll,
            ],
        ];

        return $this->send($this->signedWebhookUrl($url), $payload);
    }

    private function sendWebhookMarkdown(string $content): array
    {
        $url = SystemSetting::get('dingtalk_webhook_url', '');
        if (empty($url)) {
            return ['success' => false, 'message' => '未配置钉钉 Webhook 地址'];
        }

        return $this->send($this->signedWebhookUrl($url), [
            'msgtype'  => 'markdown',
            'markdown' => ['title' => mb_substr($content, 0, 20) ?: '工单通知', 'text' => $content],
        ]);
    }

    /**
     * 给 Webhook URL 追加钉钉加签参数（timestamp + sign）
     * 启用加签（secret 已配置）时才追加，使用 HMAC-SHA256 + base64
     */
    private function signedWebhookUrl(string $url): string
    {
        $secret = SystemSetting::get('dingtalk_webhook_secret', '');
        if (empty($secret)) {
            return $url;
        }
        $timestamp = (string) intval(microtime(true) * 1000);
        $stringToSign = $timestamp . "\n" . $secret;
        $sign = urlencode(base64_encode(hash_hmac('sha256', $stringToSign, $secret, true)));
        $sep = str_contains($url, '?') ? '&' : '?';
        return $url . $sep . 'timestamp=' . $timestamp . '&sign=' . $sign;
    }

    // ──────────────────────────────────────────────────────────────
    //  企业内部应用工作通知模式
    // ──────────────────────────────────────────────────────────────

    /**
     * 获取 access_token（带缓存，提前 200s 过期防止临界失效）
     * 接口：https://oapi.dingtalk.com/gettoken?appkey=..&appsecret=..
     */
    private function getAccessToken(): ?string
    {
        $cached = Cache::get('dingtalk_app_access_token');
        if ($cached) {
            return $cached;
        }

        $appkey = SystemSetting::get('dingtalk_app_key', '');
        $appsecret = SystemSetting::get('dingtalk_app_secret', '');
        if (empty($appkey) || empty($appsecret)) {
            return null;
        }

        try {
            $resp = $this->httpClient()->get('https://oapi.dingtalk.com/gettoken', [
                'appkey'    => $appkey,
                'appsecret' => $appsecret,
            ]);
            $data = $resp->json();

            if (($data['errcode'] ?? -1) !== 0 || empty($data['access_token'])) {
                Log::error('钉钉获取 access_token 失败', ['response' => $data]);
                return null;
            }

            $ttl = ($data['expires_in'] ?? 7200) - 200;
            Cache::put('dingtalk_app_access_token', $data['access_token'], now()->addSeconds($ttl));
            return $data['access_token'];
        } catch (\Exception $e) {
            Log::error('钉钉获取 access_token 异常', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * 通过工作通知发送文本消息
     * 接口：/topapi/message/corpconversation/asyncsend_v2
     *
     * @param string $content    消息内容
     * @param array  $atUserIds  钉钉 userid 列表
     */
    private function sendAppWorkNotice(string $content, array $atUserIds = []): array
    {
        $agentId = SystemSetting::get('dingtalk_app_agentid', '');
        if (empty($agentId)) {
            return ['success' => false, 'message' => '未配置钉钉应用 AgentId'];
        }

        // 安全：无接收人 userid 时拒绝发送而不是全员广播（工单内容含隐私）
        if (empty($atUserIds)) {
            Log::warning('钉钉工作通知发送跳过：接收用户未配置 dingtalk_userid');
            return ['success' => false, 'message' => '接收用户未配置钉钉 userid，已跳过发送（不向全员广播）'];
        }

        $token = $this->getAccessToken();
        if (!$token) {
            return ['success' => false, 'message' => '无法获取钉钉 access_token，请检查 AppKey / AppSecret'];
        }

        // 官方规范：userid_list 为逗号分隔（企微用 |，此处是历史笔误）
        $payload = [
            'agent_id'    => (int) $agentId,
            'to_all_user' => false,
            'userid_list' => implode(',', $atUserIds),
            'msg'         => [
                'msgtype' => 'text',
                'text'    => ['content' => $content],
            ],
        ];

        $url = 'https://oapi.dingtalk.com/topapi/message/corpconversation/asyncsend_v2?access_token=' . $token;
        $result = $this->send($url, $payload);

        // token 过期时清缓存重试一次（钉钉 token 失效提示是 "access token is not exist" 带空格，兼容下划线格式）
        if (!$result['success'] && str_contains(strtolower($result['message'] ?? ''), 'access')) {
            Cache::forget('dingtalk_app_access_token');
            $token = $this->getAccessToken();
            if ($token) {
                $url = 'https://oapi.dingtalk.com/topapi/message/corpconversation/asyncsend_v2?access_token=' . $token;
                $result = $this->send($url, $payload);
            }
        }

        return $result;
    }

    // ──────────────────────────────────────────────────────────────
    //  工具方法
    // ──────────────────────────────────────────────────────────────

    /**
     * Markdown 转纯文本（工作通知文本模式，去掉 ** 加粗与 > 引用前缀）
     */
    public function markdownToText(string $markdown): string
    {
        $text = preg_replace('/\*\*(.+?)\*\*/', '$1', $markdown);
        $text = preg_replace('/^>\s*/m', '', $text);
        return trim($text);
    }

    /**
     * 构建带 SSL 配置的 HTTP 客户端（与企业微信共用 SSL 证书设置）
     */
    private function httpClient()
    {
        $http = Http::timeout(10);
        $verifyEnabled = filter_var(SystemSetting::get('ssl_verify_enabled', '1'), FILTER_VALIDATE_BOOLEAN);
        if (!$verifyEnabled) {
            $http = $http->withOptions(['verify' => false]);
        } else {
            $cacert = SystemSetting::get('ssl_cacert_path', '');
            if (!empty($cacert) && file_exists($cacert)) {
                $http = $http->withOptions(['verify' => $cacert]);
            }
        }
        return $http;
    }

    /**
     * 实际发送 HTTP 请求
     * 钉钉成功返回 errcode=0
     */
    private function send(string $url, array $payload): array
    {
        try {
            $response = $this->httpClient()->post($url, $payload);

            if (!$response->ok()) {
                Log::error('钉钉通知 HTTP 失败', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                return ['success' => false, 'message' => 'HTTP ' . $response->status()];
            }

            $result = $response->json();
            if (($result['errcode'] ?? -1) !== 0) {
                Log::error('钉钉通知返回错误', ['response' => $result]);
                return ['success' => false, 'message' => $result['errmsg'] ?? '未知错误'];
            }

            Log::info('钉钉通知发送成功');
            return ['success' => true, 'message' => 'ok'];
        } catch (\Exception $e) {
            Log::error('钉钉通知异常', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
