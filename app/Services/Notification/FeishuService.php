<?php

namespace App\Services\Notification;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * 飞书通知服务
 *
 * 参照 WeComWebhookService，支持两种推送模式：
 * 1. webhook  — 自定义群机器人 Webhook（可选加签 secret）
 * 2. app      — 自建应用（app_id/app_secret 换 tenant_access_token，可 @ 或私信指定用户）
 *
 * 所有配置存储在 system_settings 表，不硬编码。
 */
class FeishuService
{
    /**
     * 当前推送模式：webhook（自定义机器人）或 app（自建应用）
     */
    public function getSendMode(): string
    {
        return SystemSetting::get('feishu_send_mode', 'webhook');
    }

    /**
     * 飞书通知是否启用（按当前模式检查对应开关）
     */
    public function isEnabled(): bool
    {
        if ($this->getSendMode() === 'app') {
            return filter_var(SystemSetting::get('feishu_app_enabled', '0'), FILTER_VALIDATE_BOOLEAN);
        }
        return filter_var(SystemSetting::get('feishu_webhook_enabled', '0'), FILTER_VALIDATE_BOOLEAN);
    }

    // ──────────────────────────────────────────────────────────────
    //  统一入口（NotificationDispatcher 调用）
    // ──────────────────────────────────────────────────────────────

    /**
     * 发送文本消息（按当前模式自动路由）
     *
     * @param string   $content     消息内容
     * @param array    $userIds     飞书 user_id / open_id 列表（@ 指定用户）
     * @param array    $mobiles     手机号列表（仅群机器人模式下可 @）
     */
    public function sendText(string $content, array $userIds = [], array $mobiles = [], bool $isAtAll = false): array
    {
        if ($this->getSendMode() === 'app') {
            return $this->sendAppMessage($content, $userIds);
        }
        return $this->sendWebhookText($content, $userIds, $mobiles, $isAtAll);
    }

    /**
     * 发送 Markdown（机器人原生 post；自建应用降级为纯文本）
     */
    public function sendMarkdown(string $content): array
    {
        if ($this->getSendMode() === 'app') {
            return $this->sendAppMessage($this->markdownToText($content));
        }
        return $this->sendWebhookText($this->markdownToText($content));
    }

    // ──────────────────────────────────────────────────────────────
    //  自定义机器人 Webhook 模式
    // ──────────────────────────────────────────────────────────────

    private function sendWebhookText(string $content, array $userIds = [], array $mobiles = [], bool $isAtAll = false): array
    {
        $url = SystemSetting::get('feishu_webhook_url', '');
        if (empty($url)) {
            return ['success' => false, 'message' => '未配置飞书 Webhook 地址'];
        }

        // 飞书 text 消息的 @ 必须内联在 content.text 中：
        //   <at user_id="ou_xxx"></at> @指定人（open_id）
        //   <at user_id="all"></at> @所有人
        // 官方 webhook 请求体不存在 at 对象字段（旧实现发送无效 at 字段导致 @ 全部静默失效）
        $atPrefix = '';
        if ($isAtAll) {
            $atPrefix .= '<at user_id="all"></at> ';
        }
        foreach ($userIds as $uid) {
            $atPrefix .= '<at user_id="' . $uid . '"></at> ';
        }

        $payload = array_merge($this->webhookSignEnvelope(), [
            'msg_type' => 'text',
            'content'  => ['text' => $atPrefix . $content],
        ]);

        return $this->send($url, $payload);
    }

    /**
     * 若启用了加签 secret，则在请求体外层包裹 timestamp + sign
     */
    private function webhookSignEnvelope(): array
    {
        $secret = SystemSetting::get('feishu_webhook_secret', '');
        if (empty($secret)) {
            return [];
        }
        // 飞书群机器人加签：key = timestamp+"\n"+secret，对空串做 HMAC-SHA256 后 base64
        $timestamp = (string) time();
        $sign = base64_encode(hash_hmac('sha256', '', $timestamp . "\n" . $secret, true));
        return ['timestamp' => $timestamp, 'sign' => $sign];
    }

    // ──────────────────────────────────────────────────────────────
    //  自建应用模式
    // ──────────────────────────────────────────────────────────────

    /**
     * 获取 tenant_access_token（带缓存）
     * 接口：POST https://open.feishu.cn/open-apis/auth/v3/tenant_access_token/internal
     */
    private function getTenantAccessToken(): ?string
    {
        $cached = Cache::get('feishu_tenant_access_token');
        if ($cached) {
            return $cached;
        }

        $appId = SystemSetting::get('feishu_app_id', '');
        $appSecret = SystemSetting::get('feishu_app_secret', '');
        if (empty($appId) || empty($appSecret)) {
            return null;
        }

        try {
            $resp = $this->httpClient()->post('https://open.feishu.cn/open-apis/auth/v3/tenant_access_token/internal', [
                'app_id'     => $appId,
                'app_secret' => $appSecret,
            ]);
            $data = $resp->json();

            if (($data['code'] ?? -1) !== 0 || empty($data['tenant_access_token'])) {
                Log::error('飞书获取 tenant_access_token 失败', ['response' => $data]);
                return null;
            }

            $ttl = ($data['expire'] ?? 7200) - 200;
            Cache::put('feishu_tenant_access_token', $data['tenant_access_token'], now()->addSeconds($ttl));
            return $data['tenant_access_token'];
        } catch (\Exception $e) {
            Log::error('飞书获取 tenant_access_token 异常', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * 通过自建应用向用户发送文本消息（IM 消息）
     * 接口：POST /open-apis/im/v1/messages?receive_id_type=user_id
     *
     * @param string $content  消息内容
     * @param array  $userIds  飞书 user_id / open_id 列表；为空则跳过（应用消息需指定接收人）
     */
    private function sendAppMessage(string $content, array $userIds = []): array
    {
        if (empty($userIds)) {
            // 应用消息无「@all」语义，必须指定接收人；为空则提示配置
            Log::warning('飞书自建应用发送跳过：未配置接收用户的 feishu_user_id');
            return ['success' => false, 'message' => '自建应用模式下需指定接收用户（请填写用户的飞书 user_id/open_id）'];
        }

        $token = $this->getTenantAccessToken();
        if (!$token) {
            return ['success' => false, 'message' => '无法获取飞书 tenant_access_token，请检查 App ID / App Secret'];
        }

        $overall = ['success' => true, 'message' => 'ok'];
        foreach ($userIds as $uid) {
            $payload = [
                'receive_id' => $uid,
                'msg_type'   => 'text',
                'content'    => json_encode(['text' => $content]),
            ];

            // open_id（ou_ 前缀）与 user_id 走不同的 receive_id_type，自动识别
            $receiveType = str_starts_with($uid, 'ou_') ? 'open_id' : 'user_id';
            $url = "https://open.feishu.cn/open-apis/im/v1/messages?receive_id_type={$receiveType}";
            $resp = $this->sendWithBearer($url, $payload, $token);
            if (!$resp['success']) {
                $overall = $resp;
            }
        }
        return $overall;
    }

    // ──────────────────────────────────────────────────────────────
    //  工具方法
    // ──────────────────────────────────────────────────────────────

    /**
     * Markdown 转纯文本（自建应用文本模式）
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
     * 群机器人请求（飞书成功返回 code=0 或 StatusCode=0）
     */
    private function send(string $url, array $payload): array
    {
        try {
            $response = $this->httpClient()->post($url, $payload);

            if (!$response->ok()) {
                Log::error('飞书通知 HTTP 失败', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                return ['success' => false, 'message' => 'HTTP ' . $response->status()];
            }

            $result = $response->json();
            $code = $result['code'] ?? $result['StatusCode'] ?? -1;
            if ((int) $code !== 0) {
                Log::error('飞书通知返回错误', ['response' => $result]);
                return ['success' => false, 'message' => $result['msg'] ?? '未知错误'];
            }

            Log::info('飞书通知发送成功');
            return ['success' => true, 'message' => 'ok'];
        } catch (\Exception $e) {
            Log::error('飞书通知异常', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * 自建应用请求（带 Bearer token；成功返回 code=0）
     */
    private function sendWithBearer(string $url, array $payload, string $token): array
    {
        try {
            $response = $this->httpClient()->withToken($token)->post($url, $payload);

            if (!$response->ok()) {
                Log::error('飞书自建应用 HTTP 失败', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                return ['success' => false, 'message' => 'HTTP ' . $response->status()];
            }

            $result = $response->json();
            if (($result['code'] ?? -1) !== 0) {
                Log::error('飞书自建应用返回错误', ['response' => $result]);
                return ['success' => false, 'message' => $result['msg'] ?? '未知错误'];
            }

            return ['success' => true, 'message' => 'ok'];
        } catch (\Exception $e) {
            Log::error('飞书自建应用异常', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
