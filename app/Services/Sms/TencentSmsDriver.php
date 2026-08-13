<?php

namespace App\Services\Sms;

use App\Contracts\SmsDriver;

/**
 * 腾讯云短信驱动
 * 使用腾讯云短信服务 API 发送短信。
 * 需要在 config/services.php 的 sms.tencent 中配置。
 */
class TencentSmsDriver implements SmsDriver
{
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function send(string $phone, string $template, array $params = []): array
    {
        $secretId = $this->config['secret_id'] ?? '';
        $secretKey = $this->config['secret_key'] ?? '';
        $signName = $this->config['sign_name'] ?? '';
        $sdkAppId = $this->config['sdk_app_id'] ?? '';

        if (empty($secretId) || empty($secretKey)) {
            return ['success' => false, 'message' => '腾讯云短信未配置密钥', 'raw' => null];
        }

        // 腾讯云短信 API v3 请求
        // 单位开通后取消注释即可使用。
        try {
            // 腾讯云短信 API v3 要求 E.164 格式：+8613800138000
            $phoneNumber = '+86' . $phone;
            $params = array_values($params);

            $payload = [
                'PhoneNumber'   => $phoneNumber,
                'SmsSdkAppId'   => $sdkAppId,
                'SignName'      => $signName,
                'TemplateId'    => $template,
                'TemplateParamSet' => $params,
            ];

            $timestamp = time();
            $date = gmdate('Y-m-d', $timestamp);
            $service = 'sms';
            $endpoint = 'sms.tencentcloudapi.com';

            $canonicalRequest = "POST\n/\n\n"
                . "content-type:application/json; charset=utf-8\n"
                . "host:{$endpoint}\n"
                . "x-tc-action:SendSms\n\n"
                . "content-type;host;x-tc-action\n"
                . hash('SHA256', json_encode($payload));

            $credentialScope = "{$date}/{$service}/tc3_request";
            $stringToSign = "TC3-HMAC-SHA256\n{$timestamp}\n{$credentialScope}\n" . hash('SHA256', $canonicalRequest);

            $secretDate = hash_hmac('SHA256', $date, 'TC3' . $secretKey, true);
            $secretService = hash_hmac('SHA256', $service, $secretDate, true);
            $secretSigning = hash_hmac('SHA256', 'tc3_request', $secretService, true);
            $signature = hash_hmac('SHA256', $stringToSign, $secretSigning);

            $authorization = "TC3-HMAC-SHA256 Credential={$secretId}/{$credentialScope}, SignedHeaders=content-type;host;x-tc-action, Signature={$signature}";

            $httpClient = new \GuzzleHttp\Client(['timeout' => 10]);
            $response = $httpClient->post("https://{$endpoint}", [
                'headers' => [
                    'Authorization' => $authorization,
                    'Content-Type'  => 'application/json; charset=utf-8',
                    'Host'          => $endpoint,
                    'X-TC-Action'   => 'SendSms',
                    'X-TC-Timestamp' => (string) $timestamp,
                    'X-TC-Version'  => '2021-01-11',
                ],
                'json' => $payload,
            ]);

            $body = json_decode($response->getBody()->getContents(), true);
            $sendStatus = $body['Response']['SendStatusSet'][0] ?? null;

            if ($sendStatus && $sendStatus['Code'] === 'Ok') {
                return ['success' => true, 'message' => '发送成功', 'raw' => $body];
            }

            return ['success' => false, 'message' => $sendStatus['Message'] ?? '发送失败', 'raw' => $body];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage(), 'raw' => null];
        }
    }

    public function test(): array
    {
        $secretId = $this->config['secret_id'] ?? '';
        $secretKey = $this->config['secret_key'] ?? '';

        if (empty($secretId) || empty($secretKey)) {
            return ['success' => false, 'message' => '腾讯云短信密钥未配置'];
        }

        return ['success' => true, 'message' => '腾讯云短信配置已检查'];
    }

    public function name(): string
    {
        return 'tencent';
    }
}
