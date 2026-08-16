<?php

namespace App\Services\Sms;

use App\Contracts\SmsDriver;

/**
 * 阿里云短信驱动
 * 使用阿里云短信服务 API 发送短信。
 * 需要在 config/services.php 的 sms.aliyun 中配置。
 */
class AliyunSmsDriver implements SmsDriver
{
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function send(string $phone, string $template, array $params = []): array
    {
        $accessKeyId = $this->config['access_key_id'] ?? '';
        $accessKeySecret = $this->config['access_key_secret'] ?? '';
        $signName = $this->config['sign_name'] ?? '';

        if (empty($accessKeyId) || empty($accessKeySecret)) {
            return ['success' => false, 'message' => '阿里云短信未配置密钥', 'raw' => null];
        }

        // 阿里云短信 API 签名 + HTTP 请求
        // 当单位开通阿里云短信服务后，取消下方注释即可使用。
        try {
            $apiParams = [
                'PhoneNumbers'  => $phone,
                'SignName'      => $signName,
                'TemplateCode'  => $template,
                'TemplateParam' => json_encode($params, JSON_UNESCAPED_UNICODE),
                'AccessKeyId'   => $accessKeyId,
                'Format'        => 'JSON',
                'Version'       => '2017-05-25',
                'SignatureMethod' => 'HMAC-SHA1',
                'Timestamp'     => gmdate('Y-m-d\TH:i:s\Z'),
                'SignatureVersion' => '1.0',
                'SignatureNonce' => uniqid(),
                'Action'        => 'SendSms',
                'RegionId'      => 'cn-hangzhou',
            ];

            // 计算签名
            $apiParams['Signature'] = $this->computeSignature($apiParams, $accessKeySecret);

            // http_errors=false：阿里云 API 级错误（模板非法/签名错误/限流）返回 HTTP 400 + JSON Code/Message，
            // 默认 Guzzle 抛异常会吞掉真实原因只剩 "400 Bad Request"，排障极难
            $httpClient = new \GuzzleHttp\Client(['timeout' => 10, 'http_errors' => false]);
            // 注意用 GET：computeSignature 按 POP RPC 规范以 "GET&%2F&" 构造签名字符串，
            // 发送方法必须与签名方法一致（此前用 POST 发送 → 签名校验必然失败 InvalidSignature）
            $response = $httpClient->get('https://dysmsapi.aliyuncs.com/', [
                'query' => $apiParams,
            ]);

            $body = json_decode($response->getBody()->getContents(), true);

            if (isset($body['Code']) && $body['Code'] === 'OK') {
                return ['success' => true, 'message' => '发送成功', 'raw' => $body];
            }

            // 透出阿里云错误码（isv.SMS_TEMPLATE_ILLEGAL 等）便于定位
            $errCode = $body['Code'] ?? 'HTTP_' . $response->getStatusCode();
            return ['success' => false, 'message' => ($body['Message'] ?? '发送失败') . "（{$errCode}）", 'raw' => $body];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage(), 'raw' => null];
        }
    }

    /**
     * 计算阿里云 API 签名
     */
    private function computeSignature(array $params, string $secret): string
    {
        ksort($params);
        $sortedQueryString = '';
        foreach ($params as $key => $value) {
            $sortedQueryString .= '&' . $this->encode($key) . '=' . $this->encode($value);
        }
        $stringToSign = 'GET&%2F&' . $this->encode(substr($sortedQueryString, 1));
        return base64_encode(hash_hmac('sha1', $stringToSign, $secret . '&', true));
    }

    private function encode(string $str): string
    {
        $res = urlencode($str);
        $res = str_replace(['+', '*', '%7E'], ['%20', '%2A', '~'], $res);
        return $res;
    }

    public function test(): array
    {
        $accessKeyId = $this->config['access_key_id'] ?? '';
        $accessKeySecret = $this->config['access_key_secret'] ?? '';

        if (empty($accessKeyId) || empty($accessKeySecret)) {
            return ['success' => false, 'message' => '阿里云短信密钥未配置'];
        }

        return ['success' => true, 'message' => '阿里云短信配置已检查'];
    }

    public function name(): string
    {
        return 'aliyun';
    }
}
