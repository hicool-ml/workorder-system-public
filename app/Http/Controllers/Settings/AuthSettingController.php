<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Settings\Concerns\GuardsAdmin;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * 统一身份认证配置：CAS / OIDC / 微信公众号 OAuth
 */
class AuthSettingController extends Controller
{
    use GuardsAdmin;

    /**
     * CAS / 统一身份认证配置页面
     */
    public function cas()
    {
        if ($denied = $this->guardAdminRedirect()) {
            return $denied;
        }

        $casSettings = [
            'enabled'    => (bool) SystemSetting::get('cas_enabled', false),
            'base_url'   => SystemSetting::get('cas_base_url', ''),
            'service_id' => SystemSetting::get('cas_service_id', ''),
            'attr_username' => SystemSetting::get('cas_attr_username', 'uid'),
            'attr_name'  => SystemSetting::get('cas_attr_name', 'cn'),
            'attr_phone' => SystemSetting::get('cas_attr_phone', 'mobile'),
            'attr_email' => SystemSetting::get('cas_attr_email', 'mail'),
            'attr_department' => SystemSetting::get('cas_attr_department', 'department'),
        ];

        return view('system-settings.cas', compact('casSettings'));
    }

    /**
     * 更新 CAS 配置
     */
    public function updateCas(Request $request)
    {
        if ($denied = $this->guardAdminRedirect()) {
            return $denied;
        }

        $request->validate([
            'cas_base_url' => 'nullable|string|max:500',
            'cas_service_id' => 'nullable|string|max:200',
            'cas_attr_username' => 'required|string|max:50',
            'cas_attr_name'  => 'required|string|max:50',
            'cas_attr_phone' => 'nullable|string|max:50',
            'cas_attr_email' => 'nullable|string|max:50',
            'cas_attr_department' => 'nullable|string|max:50',
        ]);

        $fields = [
            'cas_base_url'   => $request->input('cas_base_url'),
            'cas_service_id' => $request->input('cas_service_id'),
            'cas_attr_username' => $request->input('cas_attr_username'),
            'cas_attr_name'  => $request->input('cas_attr_name'),
            'cas_attr_phone' => $request->input('cas_attr_phone'),
            'cas_attr_email' => $request->input('cas_attr_email'),
            'cas_attr_department' => $request->input('cas_attr_department'),
        ];

        foreach ($fields as $key => $value) {
            SystemSetting::set($key, $value, 'string');
        }

        // 启用/禁用
        $enabled = $request->boolean('cas_enabled');
        SystemSetting::set('cas_enabled', $enabled, 'boolean', '是否启用CAS统一身份认证', false);

        return back()->with('success', 'CAS认证配置已保存' . ($enabled ? '（已启用）' : '（未启用）'));
    }

    /**
     * OIDC / OAuth2 统一身份认证配置页面
     */
    public function oidc()
    {
        if ($denied = $this->guardAdminRedirect()) {
            return $denied;
        }

        $oidcSettings = [
            'enabled'             => (bool) SystemSetting::get('oidc_enabled', false),
            'issuer'              => SystemSetting::get('oidc_issuer', ''),
            'client_id'           => SystemSetting::get('oidc_client_id', ''),
            'client_secret'       => SystemSetting::get('oidc_client_secret', ''),
            'scope'               => SystemSetting::get('oidc_scope', 'openid profile email'),
            'authorize_endpoint'  => SystemSetting::get('oidc_authorize_endpoint', ''),
            'token_endpoint'      => SystemSetting::get('oidc_token_endpoint', ''),
            'userinfo_endpoint'   => SystemSetting::get('oidc_userinfo_endpoint', ''),
            'end_session_endpoint' => SystemSetting::get('oidc_end_session_endpoint', ''),
            'jwks_uri'            => SystemSetting::get('oidc_jwks_uri', ''),
        ];

        return view('system-settings.oidc', compact('oidcSettings'));
    }

    /**
     * 更新 OIDC 配置
     */
    public function updateOidc(Request $request)
    {
        if ($denied = $this->guardAdminRedirect()) {
            return $denied;
        }

        $request->validate([
            'oidc_issuer'               => 'nullable|string|max:500',
            'oidc_client_id'            => 'nullable|string|max:200',
            'oidc_client_secret'        => 'nullable|string|max:500',
            'oidc_scope'                => 'nullable|string|max:200',
            'oidc_authorize_endpoint'   => 'nullable|string|max:500',
            'oidc_token_endpoint'       => 'nullable|string|max:500',
            'oidc_userinfo_endpoint'    => 'nullable|string|max:500',
            'oidc_end_session_endpoint' => 'nullable|string|max:500',
            'oidc_jwks_uri'             => 'nullable|string|max:500',
        ]);

        $fields = [
            'oidc_issuer'              => $request->input('oidc_issuer'),
            'oidc_client_id'           => $request->input('oidc_client_id'),
            'oidc_client_secret'       => $request->input('oidc_client_secret'),
            'oidc_scope'               => $request->input('oidc_scope') ?: 'openid profile email',
            'oidc_authorize_endpoint'  => $request->input('oidc_authorize_endpoint'),
            'oidc_token_endpoint'      => $request->input('oidc_token_endpoint'),
            'oidc_userinfo_endpoint'   => $request->input('oidc_userinfo_endpoint'),
            'oidc_end_session_endpoint' => $request->input('oidc_end_session_endpoint'),
            'oidc_jwks_uri'            => $request->input('oidc_jwks_uri'),
        ];

        foreach ($fields as $key => $value) {
            SystemSetting::set($key, $value, 'string');
        }

        // 清除 Discovery 缓存，使配置变更后重新发现
        Cache::forget('oidc_discovery');

        // 启用/禁用
        $enabled = $request->boolean('oidc_enabled');
        SystemSetting::set('oidc_enabled', $enabled, 'boolean', '是否启用OIDC统一身份认证', false);

        return back()->with('success', 'OIDC认证配置已保存' . ($enabled ? '（已启用）' : '（未启用）'));
    }

    /**
     * 微信公众号 OAuth 登录配置页面
     */
    public function wechatOauth()
    {
        if ($denied = $this->guardAdminRedirect()) {
            return $denied;
        }

        $wechatOauthSettings = [
            'enabled' => (bool) SystemSetting::get('wechat_oauth_enabled', false),
            'appid'   => SystemSetting::get('wechat_oauth_appid', ''),
            'secret'  => SystemSetting::get('wechat_oauth_secret', ''),
            'scope'   => SystemSetting::get('wechat_oauth_scope', 'snsapi_base'),
        ];

        return view('system-settings.wechat-oauth', compact('wechatOauthSettings'));
    }

    /**
     * 更新微信公众号 OAuth 登录配置
     */
    public function updateWechatOauth(Request $request)
    {
        if ($denied = $this->guardAdminRedirect()) {
            return $denied;
        }

        $request->validate([
            'wechat_oauth_appid'  => 'nullable|string|max:128',
            'wechat_oauth_secret' => 'nullable|string|max:200',
            'wechat_oauth_scope'  => 'nullable|string|max:50|in:snsapi_base,snsapi_userinfo',
        ]);

        SystemSetting::set('wechat_oauth_appid', trim($request->input('wechat_oauth_appid', '')), 'string', '微信公众号 AppID', false);
        SystemSetting::set('wechat_oauth_secret', trim($request->input('wechat_oauth_secret', '')), 'string', '微信公众号 AppSecret', false);
        SystemSetting::set('wechat_oauth_scope', $request->input('wechat_oauth_scope') ?: 'snsapi_base', 'string', '微信网页授权 scope', false);

        // 启用/禁用
        $enabled = $request->boolean('wechat_oauth_enabled');
        SystemSetting::set('wechat_oauth_enabled', $enabled, 'boolean', '是否启用微信登录', false);

        return back()->with('success', '微信登录配置已保存' . ($enabled ? '（已启用）' : '（未启用）'));
    }
}
