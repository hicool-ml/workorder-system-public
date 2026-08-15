<?php

namespace App\Http\Controllers;

use App\Models\Workorder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\SystemSetting;

/**
 * 短信回复回写控制器
 * 接收短信服务商的上行回复回调（报修人回复 1=满意 / 0=不满意），
 * 按手机号匹配最近一次发送了满意度调查短信的工单，回写 sms_satisfaction。
 *
 * 多服务商兼容：字段映射按当前 sms_provider 动态选择，
 * 自定义接口模式下字段名可在短信设置页配置。路由需排除 CSRF。
*/
class SmsReplyController extends Controller
{
    /**
     * 短信上行回调入口（服务商 → 系统）
     * 安全校验：优先使用配置的签名/token；若未配置，则按 IP 白名单放行；
     * 两者都未配置时拒绝请求，防止公网任何人伪造满意度回复。
     */
    public function receive(Request $request)
    {
        if (!$this->verifyCaller($request)) {
            Log::warning('短信回复回调鉴权失败', [
                'ip' => $request->ip(),
                'ua' => $request->userAgent(),
            ]);
            return response()->json(['success' => false, 'message' => 'unauthorized'], 401);
        }

        [$phone, $content] = $this->resolveReplyFields($request);
        return $this->recordReply($phone, $content);
    }

    /**
     * 校验回调来源合法性：
     * 1) 若配置了 sms_reply_secret：
     *    - 优先校验 hmac（hmac-sha256(phone|content|timestamp, secret)，时间窗 ±10 分钟，防截获重放）；
     *    - 兼容旧 sign（md5(phone|content|secret)）与静态 token；
     * 2) 否则若配置了 sms_reply_ip_whitelist（逗号分隔 CIDR/IP），按 IP 放行
     *    （仅在配置 TRUSTED_PROXIES 时生效，否则 XFF 可伪造）；
     * 3) 两者都未配置则拒绝对外开放访问。
     */
    private function verifyCaller(Request $request): bool
    {
        $secret = (string) SystemSetting::get('sms_reply_secret', '');
        if ($secret !== '') {
            // 新式 HMAC-SHA256 签名（含时间戳，防重放）
            $hmac = (string) ($request->input('hmac', $request->header('X-Sms-Hmac')) ?? '');
            $timestamp = (string) ($request->input('timestamp', $request->header('X-Sms-Timestamp')) ?? '');
            if ($hmac !== '' && $timestamp !== '' && is_numeric($timestamp)) {
                if (abs(time() - (int) $timestamp) > 600) {
                    return false; // 时间窗外的签名直接拒绝（重放）
                }
                [$phone, $content] = $this->resolveReplyFields($request);
                $expected = hash_hmac('sha256', $phone . '|' . $content . '|' . $timestamp, $secret);
                if (hash_equals($expected, $hmac)) {
                    return true;
                }
                return false;
            }

            // 兼容旧式静态 token
            $token = $request->input('token', $request->header('X-Sms-Token'));
            if (is_string($token) && hash_equals($secret, (string) $token)) {
                return true;
            }
            // 兼容旧式 md5 拼接签名
            [$phone, $content] = $this->resolveReplyFields($request);
            $expectedSign = md5($phone . '|' . $content . '|' . $secret);
            $sign = (string) ($request->input('sign', $request->header('X-Sms-Sign')) ?? '');
            if (hash_equals($expectedSign, $sign)) {
                return true;
            }
            return false;
        }

        // IP 白名单仅在配置了可信代理（TRUSTED_PROXIES）时才有意义：
        // 否则 $request->ip() 来自可伪造的 X-Forwarded-For，白名单形同虚设。
        // 生产环境要求配置 sms_reply_secret（sign/token），白名单仅作叠加防线。
        $ipList = (string) SystemSetting::get('sms_reply_ip_whitelist', '');
        if ($ipList !== '') {
            if (env('TRUSTED_PROXIES', '') === '') {
                \Log::warning('sms/reply：配置了 IP 白名单但未配置 TRUSTED_PROXIES，X-Forwarded-For 可伪造，白名单已失效');
                return false;
            }
            $ips = array_filter(array_map('trim', explode(',', $ipList)));
            foreach ($ips as $allowed) {
                if ($this->ipMatch($request->ip(), $allowed)) {
                    return true;
                }
            }
            return false;
        }

        // 本地环境（开发/测试）默认放行；其它环境必须有鉴权
        return app()->environment('local', 'testing');
    }

    private function ipMatch(string $ip, string $allowed): bool
    {
        if ($ip === $allowed) {
            return true;
        }
        if (str_contains($allowed, '/')) {
            [$subnet, $mask] = explode('/', $allowed);
            $ipLong = ip2long($ip);
            $subnetLong = ip2long($subnet);
            if ($ipLong === false || $subnetLong === false) {
                return false;
            }
            return ($ipLong & (-1 << (32 - (int) $mask))) === ($subnetLong & (-1 << (32 - (int) $mask)));
        }
        return false;
    }

    /**
     * 按当前短信服务商解析回调中的手机号和内容字段。
     * 各服务商字段名不同，自定义接口可配置；保留 fallback 兜底。
     * @return array{0:?string,1:string}  [phone, content]
     */
    private function resolveReplyFields(Request $request): array
    {
        $provider = SystemSetting::get('sms_provider', '');

        // 各服务商上行回调的标准字段名（按官方文档）
        $fieldMap = match ($provider) {
            'aliyun' => [
                'phone'   => ['phone_number', 'PhoneNumber', 'phone', 'mobile'],
                'content' => ['content', 'text', 'MessageContent'],
            ],
            'tencent' => [
                'phone'   => ['PhoneNumber', 'phone_number', 'phone', 'mobile'],
                'content' => ['ReplyContent', 'content', 'text'],
            ],
            'custom' => [
                // 自定义接口：字段名可在短信设置页配置，默认 phone/content
                'phone'   => array_filter(array_merge(
                    [SystemSetting::get('sms_reply_phone_field', 'phone')],
                    ['phone', 'mobile', 'PhoneNumber', 'phone_number']
                )),
                'content' => array_filter(array_merge(
                    [SystemSetting::get('sms_reply_content_field', 'content')],
                    ['content', 'text', 'ReplyContent']
                )),
            ],
            default => [
                'phone'   => ['phone', 'mobile', 'PhoneNumber', 'phone_number'],
                'content' => ['content', 'text', 'MessageContent', 'ReplyContent'],
            ],
        };

        $phone = $this->firstInput($request, $fieldMap['phone']);
        $content = trim((string) $this->firstInput($request, $fieldMap['content']));

        return [$phone, $content];
    }

    /**
     * 按候选字段名列表，从请求中取第一个非空值（同时兼容 JSON 和 form 体）
     */
    private function firstInput(Request $request, array $candidates): ?string
    {
        foreach ($candidates as $field) {
            $val = $request->input($field);
            // 注意：不能用 empty()，因为报修人回复 "0"（不满意）时 '0' 会被当 falsy 跳过
            if ($val !== null && $val !== '') {
                return (string) $val;
            }
        }
        return null;
    }

    /**
     * 核心回写逻辑（独立于具体服务商字段，便于测试和适配）
     * 返回 JSON，HTTP 200 表示已收到（即便匹配失败也返回 200，避免服务商反复重试）
     */
    public function recordReply(?string $phone, string $content)
    {
        // 日志注入防护：短信原文可含换行，压平后入日志防伪造日志行
        $logContent = preg_replace('/\r?\n/', ' ', $content);

        if (empty($phone)) {
            Log::warning('短信回复缺少手机号', ['content' => $logContent]);
            return response()->json(['success' => false, 'message' => 'missing phone'], 200);
        }

        // 解析满意度：1=满意, 0=不满意，其它视为无效回复
        $satisfaction = $this->parseSatisfaction($content);
        if ($satisfaction === null) {
            Log::info('短信回复内容无法识别为满意度', ['phone' => $phone, 'content' => $logContent]);
            return response()->json(['success' => false, 'message' => 'unrecognized content'], 200);
        }

        // 按手机号匹配最近一条已发满意度调查、且尚未回复的工单
        $workorder = Workorder::where('contact_phone', $phone)
            ->whereNotNull('sms_survey_sent_at')
            ->whereNull('sms_satisfaction')
            ->latest('sms_survey_sent_at')
            ->first();

        // contact_phone 可能为空，回退匹配 creator 的 phone
        if (!$workorder) {
            $workorder = Workorder::query()
                ->whereHas('creator', fn($q) => $q->where('phone', $phone))
                ->whereNotNull('sms_survey_sent_at')
                ->whereNull('sms_satisfaction')
                ->latest('sms_survey_sent_at')
                ->first();
        }

        if (!$workorder) {
            Log::info('短信回复未匹配到工单', ['phone' => $phone, 'content' => $logContent]);
            return response()->json(['success' => false, 'message' => 'no matching workorder'], 200);
        }

        $workorder->forceFill([
            'sms_satisfaction' => $satisfaction,
            'sms_satisfaction_at' => now(),
        ])->save();

        Log::info('短信满意度已回写', [
            'workorder_id' => $workorder->id,
            'phone' => $phone,
            'satisfaction' => $satisfaction,
        ]);

        // 仅回写结果，不暴露工单 ID，避免被枚举
        return response()->json(['success' => true]);
    }

    /**
     * 解析回复内容为满意度值
     * 1 / "1" / "满意" → 1；0 / "0" / "不满意" → 0；其它 → null
     */
    private function parseSatisfaction(string $content): ?int
    {
        $c = trim($content);
        if ($c === '1' || $c === '满意') return 1;
        if ($c === '0' || $c === '不满意') return 0;
        return null;
    }
}
