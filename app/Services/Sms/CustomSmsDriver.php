<?php

namespace App\Services\Sms;

use App\Contracts\SmsDriver;

/**
 * 自定义短信驱动
 * 通过 HTTP 接口对接单位自有短信服务。
 * 支持自定义 URL、请求方法、参数映射。
 */
class CustomSmsDriver implements SmsDriver
{
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function send(string $phone, string $template, array $params = []): array
    {
        $url = $this->config['url'] ?? '';

        if (empty($url)) {
            return ['success' => false, 'message' => '自定义短信接口URL未配置', 'raw' => null];
        }

        try {
            // 将手机号、模板参数合并到请求体中
            $payload = array_merge($this->config['default_params'] ?? [], [
                'phone'    => $phone,
                'template' => $template,
                'content'  => $params['content'] ?? '',
                'params'   => json_encode($params, JSON_UNESCAPED_UNICODE),
            ]);

            $method = strtoupper($this->config['method'] ?? 'POST');
            $httpClient = new \GuzzleHttp\Client(['timeout' => 10]);

            $options = [];
            if ($method === 'GET') {
                $options['query'] = $payload;
            } else {
                $options['json'] = $payload;
            }

            // 如果配置了 API Key，加入 header
            if (!empty($this->config['api_key'])) {
                $options['headers'] = ['Authorization' => 'Bearer ' . $this->config['api_key']];
            }

            $response = $httpClient->request($method, $url, $options);
            $body = $response->getBody()->getContents();
            $json = json_decode($body, true);

            // 成功判断：HTTP 200 且响应为 JSON 且 success=true 或 code=0（宽松转型，兼容 "0" 字符串）。
            // 非 JSON 响应（网关错误页/HTML）一律判失败，避免厂商错误被静默当成功。
            if ($response->getStatusCode() !== 200 || !is_array($json)) {
                return [
                    'success' => false,
                    'message' => is_array($json) ? ($json['message'] ?? '发送失败') : '响应非 JSON：' . mb_substr($body, 0, 200),
                    'raw' => $json ?? $body,
                ];
            }
            $success = (($json['success'] ?? null) === true) || ((int) ($json['code'] ?? -1) === 0);

            return [
                'success' => $success,
                'message' => $success ? '发送成功' : ($json['message'] ?? '发送失败'),
                'raw' => $json,
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage(), 'raw' => null];
        }
    }

    public function test(): array
    {
        $url = $this->config['url'] ?? '';

        if (empty($url)) {
            return ['success' => false, 'message' => '自定义短信接口URL未配置'];
        }

        return ['success' => true, 'message' => '自定义短信接口URL已配置'];
    }

    public function name(): string
    {
        return 'custom';
    }
}
