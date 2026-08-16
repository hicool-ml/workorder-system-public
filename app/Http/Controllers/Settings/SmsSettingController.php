<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Settings\Concerns\GuardsAdmin;
use App\Models\SystemSetting;
use Illuminate\Http\Request;

class SmsSettingController extends Controller
{
    use GuardsAdmin;

    /**
     * 短信配置页面
     */
    public function sms()
    {
        if ($denied = $this->guardAdminRedirect()) {
            return $denied;
        }

        $smsSettings = [
            'enabled'    => (bool) SystemSetting::get('sms_enabled', false),
            'provider'   => SystemSetting::get('sms_provider', 'aliyun'),
            'sign_name'  => SystemSetting::get('sms_sign_name', ''),
            'access_key' => SystemSetting::get('sms_access_key', ''),
            'access_secret' => SystemSetting::get('sms_access_secret', ''),
            'sdk_app_id' => SystemSetting::get('sms_sdk_app_id', ''),
            'api_url'    => SystemSetting::get('sms_api_url', ''),
            'method'     => SystemSetting::get('sms_method', 'POST'),
            'api_key'    => SystemSetting::get('sms_api_key', ''),

            // 报修人短信开关
            'creator_sms_enabled'    => (bool) SystemSetting::get('creator_sms_enabled', false),
            'creator_survey_enabled' => (bool) SystemSetting::get('creator_survey_enabled', false),

            // 报修人短信模板
            'tpl_acceptance_with_appt' => SystemSetting::get('sms_creator_acceptance_tpl_with_appt',
                "【{系统名称}】您的报修已受理，工程师\"{工程师电话}\"预计{预约时间}上门为您服务。"),
            'tpl_acceptance_no_appt' => SystemSetting::get('sms_creator_acceptance_tpl_no_appt',
                "【{系统名称}】您的报修已受理，请保持电话畅通，便于工程师\"{工程师电话}\"能联系到您并为您服务。"),
            'tpl_survey' => SystemSetting::get('sms_creator_survey_tpl',
                "【{系统名称}】您的报修服务已完成，请对本次服务进行评价：满意回复 1，不满意回复 0。"),

            // 云厂商模板代码（阿里云/腾讯云必填，报修人短信发送用）
            'acceptance_code' => SystemSetting::get('sms_creator_acceptance_code', ''),
            'survey_code' => SystemSetting::get('sms_creator_survey_code', ''),

            // 回调鉴权（回显空 = 未配置；密钥不留空回填，避免页面源码泄露）
            'reply_secret' => '',
            'reply_secret_set' => SystemSetting::get('sms_reply_secret', '') !== '',
            'reply_ip_whitelist' => SystemSetting::get('sms_reply_ip_whitelist', ''),
        ];

        return view('system-settings.sms', compact('smsSettings'));
    }

    /**
     * 更新短信配置
     */
    public function updateSms(Request $request)
    {
        if ($denied = $this->guardAdminRedirect()) {
            return $denied;
        }

        $request->validate([
            'sms_provider'   => 'required|in:aliyun,tencent,custom',
            'sms_sign_name'  => 'nullable|string|max:100',
            'sms_access_key' => 'nullable|string|max:200',
            'sms_access_secret' => 'nullable|string|max:200',
            'sms_sdk_app_id' => 'nullable|string|max:100',
            'sms_api_url'    => 'nullable|string|max:500',
            'sms_method'     => 'nullable|in:GET,POST',
            'sms_api_key'    => 'nullable|string|max:200',
            'sms_enabled'    => 'nullable|boolean',
            'sms_creator_acceptance_code' => 'nullable|string|max:100',
            'sms_creator_survey_code'     => 'nullable|string|max:100',
            'sms_reply_secret'            => 'nullable|string|max:200',
            'sms_reply_ip_whitelist'      => 'nullable|string|max:500',
        ]);

        $fields = [
            'sms_provider'   => $request->input('sms_provider'),
            'sms_sign_name'  => $request->input('sms_sign_name'),
            'sms_access_key' => $request->input('sms_access_key'),
            'sms_access_secret' => $request->input('sms_access_secret'),
            'sms_sdk_app_id' => $request->input('sms_sdk_app_id'),
            'sms_api_url'    => $request->input('sms_api_url'),
            'sms_method'     => $request->input('sms_method', 'POST'),
            'sms_api_key'    => $request->input('sms_api_key'),
        ];

        $fieldDescriptions = [
            'sms_provider'      => '短信服务提供商（aliyun/tencent/custom）',
            'sms_sign_name'     => '短信签名',
            'sms_access_key'    => '短信服务 Access Key ID',
            'sms_access_secret' => '短信服务 Access Key Secret',
            'sms_sdk_app_id'    => '短信服务 SDK AppID（腾讯云等使用）',
            'sms_api_url'       => '短信服务商自定义接口地址',
            'sms_method'        => '短信接口请求方式（GET/POST）',
            'sms_api_key'       => '短信服务商 API 密钥',
        ];

        foreach ($fields as $key => $value) {
            SystemSetting::set($key, $value, 'string', $fieldDescriptions[$key] ?? null);
        }

        // 短信总开关
        SystemSetting::set('sms_enabled', $request->boolean('sms_enabled') ? '1' : '0', 'boolean', '短信通知总开关', false);

        // 短信回调鉴权：密钥空提交 = 保留原值（页面不回显，避免源码泄露）；白名单空提交 = 清空（非敏感）
        if ($request->filled('sms_reply_secret')) {
            SystemSetting::set('sms_reply_secret', $request->input('sms_reply_secret'), 'string', '短信回复回调密钥（token 或 sign secret）', false);
        }
        SystemSetting::set('sms_reply_ip_whitelist', $request->input('sms_reply_ip_whitelist', ''), 'string', '短信回复回调 IP 白名单（逗号分隔）', false);

        // 报修人短信模板代码（阿里云/腾讯云的模板 CODE）
        SystemSetting::set('sms_creator_acceptance_code', $request->input('sms_creator_acceptance_code', ''), 'string', '报修人受理短信模板代码', false);
        SystemSetting::set('sms_creator_survey_code', $request->input('sms_creator_survey_code', ''), 'string', '报修人满意度调查短信模板代码', false);

        // 报修人短信开关
        SystemSetting::set('creator_sms_enabled', $request->boolean('creator_sms_enabled') ? '1' : '0', 'boolean', '报修人受理短信开关', false);
        SystemSetting::set('creator_survey_enabled', $request->boolean('creator_survey_enabled') ? '1' : '0', 'boolean', '报修人满意度调查开关', false);

        // 报修人短信模板（支持 {系统名称} {工程师电话} {预约时间} {工单编号} 占位符）
        SystemSetting::set('sms_creator_acceptance_tpl_with_appt', $request->input('tpl_acceptance_with_appt', ''), 'text', '受理短信模板（有预约）', false);
        SystemSetting::set('sms_creator_acceptance_tpl_no_appt', $request->input('tpl_acceptance_no_appt', ''), 'text', '受理短信模板（无预约）', false);
        SystemSetting::set('sms_creator_survey_tpl', $request->input('tpl_survey', ''), 'text', '满意度调查短信模板', false);

        return back()->with('success', '短信配置已保存');
    }

    /**
     * 测试短信发送
     */
    public function testSms(Request $request)
    {
        if ($denied = $this->guardAdminJson()) {
            return $denied;
        }

        $request->validate(['phone' => 'required|string']);

        $sms = app(\App\Services\Sms\SmsManager::class);
        $result = $sms->send($request->input('phone'), 'SMS_TEST', [
            'content' => '【测试】这是一条来自工单系统的测试短信',
        ]);

        return response()->json($result);
    }
}
