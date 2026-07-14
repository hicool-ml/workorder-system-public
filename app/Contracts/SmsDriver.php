<?php

namespace App\Contracts;

/**
 * 短信驱动接口
 * 所有短信服务提供商（阿里云、腾讯云、自定义等）均实现此接口。
 */
interface SmsDriver
{
    /**
     * 发送短信
     *
     * @param  string       $phone    手机号
     * @param  string       $template 短信模板ID/标识
     * @param  array        $params   模板参数
     * @return array        ['success' => bool, 'message' => string, 'raw' => mixed]
     */
    public function send(string $phone, string $template, array $params = []): array;

    /**
     * 测试短信连接/配置是否可用
     */
    public function test(): array;

    /**
     * 驱动名称
     */
    public function name(): string;
}
