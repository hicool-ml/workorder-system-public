<?php

namespace App\Services\Sms;

use App\Contracts\SmsDriver;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * 短信管理器
 * 根据系统设置动态选择短信驱动，统一管理短信发送。
 */
class SmsManager
{
    private ?SmsDriver $driver = null;

    /**
     * 发送短信（带每日限额熔断：sms_daily_limit，按 app 时区当日计数）
     */
    public function send(string $phone, string $template, array $params = []): array
    {
        $driver = $this->driver();

        if (!$driver) {
            return ['success' => false, 'message' => '短信服务未启用', 'raw' => null];
        }

        // 每日限额：超限直接拒发（防事件风暴/配置失误导致的费用失控）
        $limit = (int) SystemSetting::get('sms_daily_limit', 0);
        if ($limit > 0) {
            $key = 'sms_sent_count:' . now()->format('Ymd');
            $sent = (int) Cache::get($key, 0);
            if ($sent >= $limit) {
                Log::warning("短信达到每日限额 {$limit}，本次拒发", ['template' => $template]);
                return ['success' => false, 'message' => "已达到每日短信限额（{$limit}）", 'raw' => null];
            }
        }

        try {
            $result = $driver->send($phone, $template, $params);

            // 成功才计数（失败不占限额）
            if ($limit > 0 && ($result['success'] ?? false)) {
                $key = 'sms_sent_count:' . now()->format('Ymd');
                Cache::put($key, (int) Cache::get($key, 0) + 1, now()->endOfDay()->addMinute());
            }

            Log::info('短信发送', [
                'phone' => substr_replace($phone, '****', 3, 4),
                'template' => $template,
                'success' => $result['success'],
                'message' => $result['message'],
            ]);

            return $result;
        } catch (\Exception $e) {
            Log::error('短信发送异常', [
                'phone' => substr_replace($phone, '****', 3, 4),
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'message' => $e->getMessage(), 'raw' => null];
        }
    }

    /**
     * 批量发送
     */
    public function sendBatch(array $phones, string $template, array $params = []): array
    {
        $results = [];
        foreach ($phones as $phone) {
            if (!empty($phone)) {
                $results[$phone] = $this->send($phone, $template, $params);
            }
        }
        return $results;
    }

    /**
     * 获取当前短信驱动
     */
    public function driver(): ?SmsDriver
    {
        if ($this->driver) {
            return $this->driver;
        }

        // 从系统设置读取（与现有 SMS 设置页面兼容）
        $enabled = SystemSetting::get('sms_enabled', false);
        if (!$enabled) {
            return null;
        }

        $provider = SystemSetting::get('sms_provider', '');
        $config = $this->resolveConfig($provider);

        return $this->driver = $this->makeDriver($provider, $config);
    }

    /**
     * 短信是否已启用
     */
    public function isEnabled(): bool
    {
        return (bool) SystemSetting::get('sms_enabled', false);
    }

    /**
     * 测试当前驱动
     */
    public function test(): array
    {
        $driver = $this->driver();
        if (!$driver) {
            return ['success' => false, 'message' => '短信服务未启用'];
        }
        return $driver->test();
    }

    /**
     * 创建驱动实例
     */
    private function makeDriver(string $provider, array $config): ?SmsDriver
    {
        return match ($provider) {
            'aliyun'  => new AliyunSmsDriver($config),
            'tencent' => new TencentSmsDriver($config),
            'custom'  => new CustomSmsDriver($config),
            default   => null,
        };
    }

    /**
     * 从系统设置读取驱动配置
     */
    private function resolveConfig(string $provider): array
    {
        if ($provider === 'aliyun') {
            return [
                'access_key_id'     => SystemSetting::get('sms_access_key', config('services.sms.aliyun.access_key_id')),
                'access_key_secret' => SystemSetting::get('sms_access_secret', config('services.sms.aliyun.access_key_secret')),
                'sign_name'         => SystemSetting::get('sms_sign_name', config('services.sms.aliyun.sign_name')),
            ];
        }

        if ($provider === 'tencent') {
            return [
                'secret_id'  => SystemSetting::get('sms_access_key', config('services.sms.tencent.secret_id')),
                'secret_key' => SystemSetting::get('sms_access_secret', config('services.sms.tencent.secret_key')),
                'sign_name'  => SystemSetting::get('sms_sign_name', config('services.sms.tencent.sign_name')),
                'sdk_app_id' => SystemSetting::get('sms_sdk_app_id', config('services.sms.tencent.sdk_app_id')),
            ];
        }

        if ($provider === 'custom') {
            return [
                'url'            => SystemSetting::get('sms_api_url', config('services.sms.custom.url')),
                'method'         => SystemSetting::get('sms_method', config('services.sms.custom.method', 'POST')),
                'api_key'        => SystemSetting::get('sms_api_key', config('services.sms.custom.api_key')),
                'default_params' => config('services.sms.custom.default_params', []),
            ];
        }

        return [];
    }
}
