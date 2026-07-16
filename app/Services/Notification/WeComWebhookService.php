<?php

namespace App\Services\Notification;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * 企业微信通知服务
 *
 * 支持两种推送模式：
 * 1. webhook — 群机器人 Webhook，简单但消息仅限企业微信 App 内查看
 * 2. app     — 企业自建应用，消息可直达个人微信「微信插件」
 *
 * 所有配置存储在 system_settings 表，不硬编码。
 */
class WeComWebhookService
{
    /**
     * 当前推送模式：webhook（群机器人）或 app（自建应用）
     */
    public function getSendMode(): string
    {
        return SystemSetting::get('wecom_send_mode', 'webhook');
    }

    /**
     * 企业微信通知是否启用
     */
    public function isEnabled(): bool
    {
        if ($this->getSendMode() === 'app') {
            return filter_var(SystemSetting::get('wecom_app_enabled', '0'), FILTER_VALIDATE_BOOLEAN);
        }
        return filter_var(SystemSetting::get('wecom_webhook_enabled', '0'), FILTER_VALIDATE_BOOLEAN);
    }

    // ─────────────────────────────────────────────
    //  统一入口（NotificationDispatcher 调用）
    // ─────────────────────────────────────────────

    /**
     * 发送文本消息（根据模式自动路由）
     *
     * @param string   $content              消息内容
     * @param array    $mentionedList        企业微信 userid 列表（群机器人 mentioned_list / 自建应用 touser）
     * @param array    $mentionedMobileList  手机号列表（群机器人 mentioned_mobile_list）
     */
    public function sendText(string $content, array $mentionedList = [], array $mentionedMobileList = []): array
    {
        if ($this->getSendMode() === 'app') {
            return $this->sendAppText($content, $mentionedList);
        }
        return $this->sendWebhookText($content, $mentionedList, $mentionedMobileList);
    }

    /**
     * 发送 Markdown 消息
     * 自建应用模式下自动转为纯文本（兼容个人微信插件）
     */
    public function sendMarkdown(string $content): array
    {
        if ($this->getSendMode() === 'app') {
            return $this->sendAppText($this->markdownToText($content));
        }
        return $this->sendWebhookMarkdown($content);
    }

    /**
     * 向指定 Webhook 地址发送测试消息（仅群机器人模式）
     */
    public function sendMarkdownToUrl(string $url, string $content): array
    {
        if (empty($url)) {
            return ['success' => false, 'message' => '未配置企业微信 Webhook 地址'];
        }

        return $this->send($url, [
            'msgtype'  => 'markdown',
            'markdown' => ['content' => $content],
        ]);
    }

    // ─────────────────────────────────────────────
    //  群机器人 Webhook 模式
    // ─────────────────────────────────────────────

    private function sendWebhookText(string $content, array $mentionedList = [], array $mentionedMobileList = []): array
    {
        $url = SystemSetting::get('wecom_webhook_url', '');

        if (empty($url)) {
            return ['success' => false, 'message' => '未配置企业微信 Webhook 地址'];
        }

        $payload = [
            'msgtype' => 'text',
            'text'    => ['content' => $content],
        ];

        if (!empty($mentionedList)) {
            $payload['text']['mentioned_list'] = $mentionedList;
        }
        if (!empty($mentionedMobileList)) {
            $payload['text']['mentioned_mobile_list'] = $mentionedMobileList;
        }

        return $this->send($url, $payload);
    }

    private function sendWebhookMarkdown(string $content): array
    {
        $url = SystemSetting::get('wecom_webhook_url', '');

        if (empty($url)) {
            return ['success' => false, 'message' => '未配置企业微信 Webhook 地址'];
        }

        return $this->send($url, [
            'msgtype'  => 'markdown',
            'markdown' => ['content' => $content],
        ]);
    }

    // ─────────────────────────────────────────────
    //  企业自建应用模式（消息可直达个人微信）
    // ─────────────────────────────────────────────

    /**
     * 获取 access_token（带缓存，提前 200s 过期防止临界失效）
     */
    private function getAccessToken(): ?string
    {
        $cached = Cache::get('wecom_app_access_token');
        if ($cached) {
            return $cached;
        }

        $corpid = SystemSetting::get('wecom_app_corpid', '');
        $secret = SystemSetting::get('wecom_app_secret', '');

        if (empty($corpid) || empty($secret)) {
            return null;
        }

        try {
            $resp = $this->httpClient()->get('https://qyapi.weixin.qq.com/cgi-bin/gettoken', [
                'corpid'     => $corpid,
                'corpsecret' => $secret,
            ]);

            $data = $resp->json();

            if (($data['errcode'] ?? -1) !== 0 || empty($data['access_token'])) {
                Log::error('企业微信获取access_token失败', ['response' => $data]);
                return null;
            }

            $ttl = ($data['expires_in'] ?? 7200) - 200;
            Cache::put('wecom_app_access_token', $data['access_token'], now()->addSeconds($ttl));

            return $data['access_token'];

        } catch (\Exception $e) {
            Log::error('企业微信获取access_token异常', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * 通过自建应用发送文本消息
     *
     * @param string $content       消息内容
     * @param array  $mentionedList 企业微信用户ID列表，为空则发送给 @all
     */
    private function sendAppText(string $content, array $mentionedList = []): array
    {
        $agentid = SystemSetting::get('wecom_app_agentid', '');

        if (empty($agentid)) {
            return ['success' => false, 'message' => '未配置企业微信应用 AgentID'];
        }

        $token = $this->getAccessToken();
        if (!$token) {
            return ['success' => false, 'message' => '无法获取企业微信 access_token，请检查 CorpID 和 Secret'];
        }

        // 有指定用户ID时发给指定用户，否则 @all
        $touser = !empty($mentionedList) ? implode('|', $mentionedList) : '@all';

        $payload = [
            'touser'  => $touser,
            'msgtype' => 'text',
            'agentid' => (int) $agentid,
            'text'    => ['content' => $content],
        ];

        $url = 'https://qyapi.weixin.qq.com/cgi-bin/message/send?access_token=' . $token;
        $result = $this->send($url, $payload);

        // token 过期时清除缓存并重试一次
        if (!$result['success'] && str_contains($result['message'] ?? '', 'access_token')) {
            Cache::forget('wecom_app_access_token');
            $token = $this->getAccessToken();
            if ($token) {
                $url = 'https://qyapi.weixin.qq.com/cgi-bin/message/send?access_token=' . $token;
                $result = $this->send($url, $payload);
            }
        }

        return $result;
    }

    // ─────────────────────────────────────────────
    //  工具方法
    // ─────────────────────────────────────────────

    /**
     * 将 Markdown 文本转为纯文本（自建应用 text 模式兼容个人微信）
     */
   public function markdownToText(string $markdown): string
   {
       $text = preg_replace('/\*\*(.+?)\*\*/', '$1', $markdown);
       $text = preg_replace('/^>\s*/m', '', $text);
      return trim($text);
  }

    /**
     * 构建带 SSL 配置的 HTTP 客户端
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
     */
    private function send(string $url, array $payload): array
    {
        try {
            $response = $this->httpClient()->post($url, $payload);

            if (!$response->ok()) {
                Log::error('企业微信通知 HTTP 失败', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                return ['success' => false, 'message' => 'HTTP ' . $response->status()];
            }

            $result = $response->json();

            if (($result['errcode'] ?? -1) !== 0) {
                Log::error('企业微信通知返回错误', ['response' => $result]);
                return ['success' => false, 'message' => $result['errmsg'] ?? '未知错误'];
            }

            Log::info('企业微信通知发送成功');
            return ['success' => true, 'message' => 'ok'];

        } catch (\Exception $e) {
            Log::error('企业微信通知异常', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
