<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * 微信公众号 OAuth2 登录控制器
 *
 * 认证流程 (公众号网页授权):
 * 1. 用户点击「微信登录」-> redirect to open.weixin.qq.com 授权页
 * 2. 用户在微信内授权后 -> 回调 /wechat/callback?code=xxx&state=xxx
 * 3. 系统用 code 向 api.weixin.qq.com 换取 openid（snsapi_base 静默，snsapi_userinfo 需授权）
 * 4. 按 wechat_openid 查找本地用户：
 *    - 已绑定 -> 直接登录（remember）
 *    - 未绑定 -> 跳转绑定页，用户输入系统账号密码完成一次性绑定
 *
 * 前置条件：公众号为【已认证】类型，且后台「网页授权域名」配置为本系统域名。
 * 说明：openid 是匿名标识（不含姓名/工号），无法自动建号，必须先绑定系统账号。
 */
class WechatOauthController extends Controller
{
    private const AUTHORIZE_URL = 'https://open.weixin.qq.com/connect/oauth2/authorize';
    private const TOKEN_URL = 'https://api.weixin.qq.com/sns/oauth2/access_token';
    private const USERINFO_URL = 'https://api.weixin.qq.com/sns/userinfo';

    /**
     * 跳转到微信授权页
     */
    public function login(Request $request)
    {
        if (!SystemSetting::get('wechat_oauth_enabled', false)) {
            return redirect()->route('login')->with('error', '微信登录未启用');
        }

        $appid = SystemSetting::get('wechat_oauth_appid', '');
        if (empty($appid)) {
            return redirect()->route('login')->with('error', '微信登录配置不完整，请联系管理员');
        }

        $scope = SystemSetting::get('wechat_oauth_scope', 'snsapi_base');

        // state: 防止 CSRF
        $state = Str::random(32);
        session(['wechat.state' => $state]);

        // 保存 intended URL 以便登录后跳转（仅接受本站相对路径，防开放重定向）
        if ($request->has('intended')) {
            session(['wechat.intended' => \App\Helpers\UrlHelper::safeRedirectTarget($request->input('intended'))]);
        }

        $params = http_build_query([
            'appid'         => $appid,
            'redirect_uri'  => route('wechat.callback'),
            'response_type' => 'code',
            'scope'         => $scope,
            'state'         => $state,
        ]);

        return redirect(self::AUTHORIZE_URL . '?' . $params . '#wechat_redirect');
    }

    /**
     * 微信授权回调
     */
    public function callback(Request $request)
    {
        if (!SystemSetting::get('wechat_oauth_enabled', false)) {
            return redirect()->route('login')->with('error', '微信登录未启用');
        }

        // 用户拒绝授权等错误
        if ($request->has('error')) {
            $errorDesc = $request->input('error_description', $request->input('error'));
            Log::warning('微信授权失败', ['error' => $errorDesc]);
            return redirect()->route('login')->with('error', '微信授权失败：' . $errorDesc);
        }

        // 验证 state（CSRF 防护）
        $state = $request->input('state');
        $sessionState = session('wechat.state');

        if (!$state || !$sessionState || !hash_equals($sessionState, $state)) {
            Log::error('微信 state 验证失败', ['received' => $state, 'expected' => $sessionState]);
            return redirect()->route('login')->with('error', '认证回调验证失败，请重试');
        }

        $code = $request->input('code');
        if (!$code) {
            return redirect()->route('login')->with('error', '认证回调缺少授权码');
        }

        // 用 code 换取 token（code 一次性，只能兑换一次）
        $token = $this->exchangeCode($code);

        if (!$token || empty($token['openid'])) {
            return redirect()->route('login')->with('error', '微信认证失败，请重试');
        }

        $openid = $token['openid'];

        // 可选：snsapi_userinfo 时拉取昵称/头像，用于绑定页展示
        $nickname = null;
        $headimgurl = null;
        if (SystemSetting::get('wechat_oauth_scope', 'snsapi_base') === 'snsapi_userinfo') {
            $userInfo = $this->fetchUserInfo($token['access_token'] ?? '', $openid);
            $nickname = $userInfo['nickname'] ?? null;
            $headimgurl = $userInfo['headimgurl'] ?? null;
        }

        session()->forget('wechat.state');

        // 已绑定 -> 直接登录
        $user = User::where('wechat_openid', $openid)->first();

        if ($user) {
            if ($user->status !== 'active') {
                return redirect()->route('login')->with('error', '该账号已被禁用，请联系管理员');
            }

            $intended = \App\Helpers\UrlHelper::safeRedirectTarget(session('wechat.intended'));

            session()->forget(['wechat.state', 'wechat.pending_openid', 'wechat.nickname', 'wechat.headimgurl', 'wechat.intended']);

            auth()->login($user, true);
            session()->regenerate(true);

            return redirect($intended);
        }

        // 未绑定 -> 进入绑定页
        session([
            'wechat.pending_openid' => $openid,
            'wechat.nickname'       => $nickname,
            'wechat.headimgurl'     => $headimgurl,
        ]);

        return redirect()->route('wechat.bind');
    }

    /**
     * 绑定页（展示 pending openid 对应的微信，提示输入系统账号密码）
     */
    public function bindPage(Request $request)
    {
        if (!SystemSetting::get('wechat_oauth_enabled', false)) {
            return redirect()->route('login')->with('error', '微信登录未启用');
        }

        $pendingOpenid = session('wechat.pending_openid');

        if (!$pendingOpenid) {
            return redirect()->route('login');
        }

        return view('auth.wechat-bind', [
            'nickname'   => session('wechat.nickname'),
            'headimgurl' => session('wechat.headimgurl'),
        ]);
    }

    /**
     * 执行绑定：校验系统账号密码后，把当前 openid 绑定到该账号并登录
     */
    public function bind(Request $request)
    {
        if (!SystemSetting::get('wechat_oauth_enabled', false)) {
            return redirect()->route('login')->with('error', '微信登录未启用');
        }

        $pendingOpenid = session('wechat.pending_openid');

        if (!$pendingOpenid) {
            return redirect()->route('login');
        }

        $credentials = $request->validate([
            'login'    => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('username', $credentials['login'])
            ->orWhere('email', $credentials['login'])
            ->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return back()->withErrors(['login' => '用户名或密码错误。'])->onlyInput('login');
        }

        if ($user->status !== 'active') {
            return back()->withErrors(['login' => '该账号已被禁用，请联系管理员。'])->onlyInput('login');
        }

        // 该账号已绑定其他微信时拒绝，避免微信误绑他人账号
        if ($user->wechat_openid && $user->wechat_openid !== $pendingOpenid) {
            return back()->withErrors(['login' => '该账号已绑定其他微信，如需更换请联系管理员解绑。'])->onlyInput('login');
        }

        // 绑定 openid（不覆盖 account_type：微信只是登录方式，账号来源保持不变）
        $user->update(['wechat_openid' => $pendingOpenid]);

        session()->forget(['wechat.pending_openid', 'wechat.nickname', 'wechat.headimgurl']);

        auth()->login($user, true);
        session()->regenerate(true);

        $intended = \App\Helpers\UrlHelper::safeRedirectTarget(session('wechat.intended'));
        session()->forget('wechat.intended');

        return redirect($intended);
    }

    /**
     * 用授权码换取 token（含 openid / access_token）
     *
     * 注意：微信授权码是一次性的，code 只能成功兑换一次，切勿重复调用。
     */
    private function exchangeCode(string $code): ?array
    {
        $appid = SystemSetting::get('wechat_oauth_appid', '');
        $secret = SystemSetting::get('wechat_oauth_secret', '');

        if (empty($appid) || empty($secret)) {
            return null;
        }

        try {
            $response = Http::timeout(15)->get(self::TOKEN_URL, [
                'appid'      => $appid,
                'secret'     => $secret,
                'code'       => $code,
                'grant_type' => 'authorization_code',
            ]);

            if (!$response->ok()) {
                Log::error('微信换取 token HTTP 失败', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                return null;
            }

            $data = $response->json();

            if (!empty($data['errcode'])) {
                Log::error('微信换取 token 失败', [
                    'errcode' => $data['errcode'],
                    'errmsg'  => $data['errmsg'] ?? '',
                ]);
                return null;
            }

            return $data;
        } catch (\Exception $e) {
            Log::error('微信换取 token 异常', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * 拉取微信用户信息（仅 snsapi_userinfo 有效；snsapi_base 拿不到昵称头像）
     */
    private function fetchUserInfo(string $accessToken, string $openid): ?array
    {
        if (empty($accessToken)) {
            return null;
        }

        try {
            $response = Http::timeout(15)->get(self::USERINFO_URL, [
                'access_token' => $accessToken,
                'openid'       => $openid,
                'lang'         => 'zh_CN',
            ]);

            if (!$response->ok() || !empty($response->json('errcode'))) {
                Log::warning('微信获取用户信息失败', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                return null;
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('微信获取用户信息异常', ['error' => $e->getMessage()]);
            return null;
        }
    }
}
