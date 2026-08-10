<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * 修复 system_settings 描述字段：
 * 早期迁移文件因导入编码问题写入了一串 '?'，另有部分短信配置项
 * 被 SystemSetting::set() 无描述覆盖，统一回填正确的中文说明。
 */
return new class extends Migration
{
    public function up(): void
    {
        $descriptions = [
            // 会话有效期（原为占位描述）
            'session_lifetime' => '登录会话有效期（分钟）',
            // 短信配置（原描述为空）
            'sms_provider' => '短信服务提供商（aliyun/tencent/custom）',
            'sms_method' => '短信接口请求方式（GET/POST）',
            'sms_api_url' => '短信服务商自定义接口地址',
            'sms_api_key' => '短信服务商 API 密钥',
            'sms_access_key' => '短信服务 Access Key ID',
            'sms_access_secret' => '短信服务 Access Key Secret',
            'sms_sdk_app_id' => '短信服务 SDK AppID（腾讯云等使用）',
            'sms_sign_name' => '短信签名',
            // 企业微信（原为乱码）
            'wecom_send_mode' => '企业微信推送模式（webhook/自建应用）',
            'wecom_app_enabled' => '是否启用企业微信自建应用通知',
            'wecom_app_corpid' => '企业微信企业 ID（CorpID）',
            'wecom_app_secret' => '企业微信自建应用 Secret',
            'wecom_app_agentid' => '企业微信自建应用 AgentID',
            // SSL 安全（原为乱码）
            'ssl_verify_enabled' => '是否启用 HTTPS SSL 证书验证',
            'ssl_cacert_path' => '自定义 CA 证书路径（留空使用系统默认证书库）',
        ];

        foreach ($descriptions as $key => $description) {
            DB::table('system_settings')->where('key', $key)->update(['description' => $description]);
        }
    }

    public function down(): void
    {
        // 描述仅为展示用途，不回滚
    }
};
