<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * CAS / LinkID 统一身份认证控制器
 *
 * 认证流程:
 * 1. 用户点击「统一身份认证」按钮 -> redirect to CAS login page
 * 2. 用户在 CAS 页面登录成功后 -> 回调 /cas/callback?ticket=xxx
 * 3. 系统用 ticket 向 CAS serviceValidate 接口验证并获取用户属性
 * 4. 根据返回的 uid 在本地查找/创建用户，自动登录
 *
 * CAS 与本地账号共存，管理员仍可用用户名密码登录。
 * CAS 用户自动创建为 role=user（报修人）。
 */
class CasAuthController extends Controller
{
    /**
     * 跳转到 CAS 登录页
     */
    public function login(Request $request)
    {
        if (!SystemSetting::get('cas_enabled', false)) {
            return redirect()->route('login')->with('error', '统一身份认证未启用');
        }

        $serviceUrl = \App\Helpers\SystemHelper::absoluteUrl('/cas/callback');
        $casBaseUrl = rtrim(SystemSetting::get('cas_base_url', ''), '/');

        // CAS 标准登录入口
        $loginUrl = "{$casBaseUrl}/login?service=" . urlencode($serviceUrl);

        // 保存 intended URL 以便登录后跳转（仅接受本站相对路径，防开放重定向）
        if ($request->has('intended')) {
            session(['cas.intended' => \App\Helpers\UrlHelper::safeRedirectTarget($request->input('intended'))]);
        }

        return redirect($loginUrl);
    }

    /**
     * CAS 回调处理
     */
    public function callback(Request $request)
    {
        if (!SystemSetting::get('cas_enabled', false)) {
            return redirect()->route('login')->with('error', '统一身份认证未启用');
        }

        $ticket = $request->query('ticket');

        if (!$ticket) {
            return redirect()->route('login')->with('error', '认证回调缺少票据');
        }

        // 向 CAS 验证票据并获取用户属性
        $attributes = $this->validateTicket($ticket);

        if (!$attributes) {
            return redirect()->route('login')->with('error', '统一身份认证失败，请重试');
        }

        // 查找或创建本地用户
        $user = $this->findOrCreateUser($attributes);

        if (!$user) {
            return redirect()->route('login')->with('error', '无法创建用户账户');
        }

        // 禁用账号禁止通过 SSO 登录
        if ($user->status !== 'active') {
            return redirect()->route('login')->with('error', '该账号已被禁用，请联系管理员');
        }

        // 登录并重新生成会话（防止会话固定攻击）
        auth()->login($user, true);
        session()->regenerate(true);

        $intended = \App\Helpers\UrlHelper::safeRedirectTarget(session('cas.intended'));
        session()->forget('cas.intended');

        return redirect($intended);
    }

    /**
     * CAS 登出
     */
    public function logout(Request $request)
    {
        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if (SystemSetting::get('cas_enabled', false)) {
            $casBaseUrl = rtrim(SystemSetting::get('cas_base_url', ''), '/');
            return redirect("{$casBaseUrl}/logout?service=" . urlencode(route('login')));
        }

        return redirect()->route('login');
    }

    /**
     * 验证 CAS 票据并获取用户属性
     *
     * LinkID 的 CAS 协议实现遵循标准 CAS 3.0:
     * GET /serviceValidate?ticket=xxx&service=yyy
     * 返回 XML 格式的用户属性
     */
    private function validateTicket(string $ticket): ?array
    {
        $serviceUrl = \App\Helpers\SystemHelper::absoluteUrl('/cas/callback');
        $casBaseUrl = rtrim(SystemSetting::get('cas_base_url', ''), '/');
        $validateUrl = "{$casBaseUrl}/serviceValidate?ticket=" . urlencode($ticket)
            . "&service=" . urlencode($serviceUrl);

        try {
            $response = Http::timeout(15)->get($validateUrl);

            if (!$response->ok()) {
                Log::error('CAS 票据验证 HTTP 失败', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return null;
            }

            $body = $response->body();

            // 解析 CAS XML 响应
            $attrs = $this->parseCasResponse($body);

            if (!$attrs) {
                Log::error('CAS 票据验证解析失败', ['body' => $body]);
            }

            return $attrs;
        } catch (\Exception $e) {
            Log::error('CAS 票据验证异常', [
                'error' => $e->getMessage(),
                'ticket' => $ticket,
            ]);
            return null;
        }
    }

    /**
     * 解析 CAS 3.0 XML 响应，提取用户属性
     *
     * 标准格式:
     * <cas:serviceResponse>
     *   <cas:authenticationSuccess>
     *     <cas:user>username</cas:user>
     *     <cas:attributes>
     *       <cas:cn>张三</cas:cn>
     *       <cas:mobile>13800138000</cas:mobile>
     *       ...
     *     </cas:attributes>
     *   </cas:authenticationSuccess>
     * </cas:serviceResponse>
     */
    private function parseCasResponse(string $xml): ?array
    {
        try {
            $doc = new \DOMDocument();
            $doc->loadXML($xml);
            $xpath = new \DOMXPath($doc);

            // 注册 CAS 命名空间
            foreach ([
                'cas'   => 'http://www.yale.edu/tp/cas',
                'cas20' => 'http://www.yale.edu/tp/cas',
            ] as $prefix => $uri) {
                $xpath->registerNamespace($prefix, $uri);
            }

            // 检查认证是否成功
            $success = $xpath->query('//*[local-name()="authenticationSuccess"]');
            if ($success->length === 0) {
                return null;
            }

            // 提取 user（用户名/学号工号）
            $userNode = $xpath->query('//*[local-name()="user"]')->item(0);
            $username = $userNode ? trim($userNode->textContent) : null;

            if (!$username) {
                return null;
            }

            // 提取所有属性
            $attrs = ['username' => $username];
            $attrNodes = $xpath->query('//*[local-name()="attributes"]/*');
            foreach ($attrNodes as $node) {
                $localName = $node->localName;
                $attrs[$localName] = trim($node->textContent);
            }

            // 有些 CAS 实现把属性直接放在 authenticationSuccess 下
            if (empty($attrs) || count($attrs) <= 1) {
                $allChildren = $xpath->query('//*[local-name()="authenticationSuccess"]/*[local-name()!="user" and local-name()!="attributes"]');
                foreach ($allChildren as $node) {
                    $attrs[$node->localName] = trim($node->textContent);
                }
            }

            return $attrs;
        } catch (\Exception $e) {
            Log::error('CAS XML 解析异常', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * 根据 CAS 属性查找或创建本地用户
     */
    private function findOrCreateUser(array $casAttrs): ?User
    {
        $attrMap = [
            'username' => SystemSetting::get('cas_attr_username', 'uid'),
            'name'     => SystemSetting::get('cas_attr_name', 'cn'),
            'phone'    => SystemSetting::get('cas_attr_phone', 'mobile'),
            'email'    => SystemSetting::get('cas_attr_email', 'mail'),
            'department' => SystemSetting::get('cas_attr_department', 'department'),
        ];

        // 根据 CAS 返回的属性映射到本地字段
        $casUsername = $casAttrs['username'] ?? '';

        // 安全检查：CAS 必须返回唯一的用户标识，否则拒绝登录
        if (empty($casUsername)) {
            Log::error('CAS 认证返回的用户标识为空，拒绝登录', ['attributes' => $casAttrs]);
            return null;
        }
        $name = $casAttrs[$attrMap['name'] ?? 'cn'] ?? $casAttrs['cn'] ?? $casUsername;
        $phone = $casAttrs[$attrMap['phone'] ?? 'mobile'] ?? $casAttrs['mobile'] ?? $casAttrs['telephoneNumber'] ?? null;
        $email = $casAttrs[$attrMap['email'] ?? 'mail'] ?? $casAttrs['mail'] ?? null;
        $department = $casAttrs[$attrMap['department'] ?? 'department'] ?? $casAttrs['department'] ?? null;

        // 清理手机号（去掉可能的 +86 前缀或空格）
        if ($phone) {
            $phone = preg_replace('/[^0-9]/', '', $phone);
            // 86 + 11 位国内手机号 = 13 位；仅当去掉 86 后恰好是 11 位才剥离，避免误删
            if (str_starts_with($phone, '86') && strlen($phone) === 13) {
                $phone = substr($phone, 2);
            }
        }

        // 仅按不可变标识查找：工号（CAS 侧受管字段）与历史 CAS 用户名。
        // 安全红线：禁止按手机号自动关联本地账号（IdP 侧手机号可自助修改，
        // 弱属性匹配 = 零密码接管任意同手机号本地账户，含管理员）。
        $user = User::where('employee_id', $casUsername)->first();

        // 特权账号禁止被 CAS 工号自动关联（防止 IdP 侧账号配置错误直接映射到 admin）
        if ($user && in_array($user->role, ['admin', 'workorder_manager'], true)) {
            Log::warning('CAS 工号命中特权账号，拒绝自动关联', [
                'employee_id' => $casUsername,
                'role' => $user->role,
            ]);
            $user = null;
        }

        // 再按用户名查找（CAS 账号）
        if (!$user) {
            $user = User::where('username', 'cas_' . $casUsername)->first();
        }

        if ($user) {
            // 已有用户，更新 CAS 相关信息
            // 注意：不重置 status，避免把管理员已禁用的账号在 SSO 登录时自动重新激活
            $user->update([
                'employee_id' => $user->employee_id ?: $casUsername,
                'name'        => $name ?: $user->name,
                'phone'       => $phone ?: $user->phone,
                'email'       => $email ?: $user->email,
                'account_type' => 'cas',
            ]);
            return $user->fresh();
        }

        // 创建新用户（默认为报修人）；email 可空且需唯一——缺失或撞库时生成占位邮箱
        $safeEmail = $email;
        if ($safeEmail && User::where('email', $safeEmail)->exists()) {
            Log::warning('CAS 用户邮箱与本地账号冲突，使用占位邮箱', ['email' => $safeEmail]);
            $safeEmail = null;
        }
        if (!$safeEmail) {
            $safeEmail = 'cas_' . str()->random(16) . '@migrated.local';
        }
        try {
            return User::create([
                'name'         => $name ?: $casUsername,
                'username'     => 'cas_' . $casUsername,
                'employee_id'  => $casUsername,
                'phone'        => $phone,
                'email'        => $safeEmail,
                'password'     => bcrypt(str()->random(32)),
                'role'         => 'user',
                'status'       => 'active',
                'account_type' => 'cas',
                // SSO 用户不走本地密码：直接视为已过改密节点，
                // 否则 ForcePasswordChange 会锁死（随机密码用户无从知晓，改密页要求 current_password）
                'password_changed_at' => now(),
                'remarks'      => $department ? "部门：{$department}" : null,
            ]);
        } catch (\Exception $e) {
            Log::error('CAS 用户创建失败', [
                'cas_username' => $casUsername,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
}
