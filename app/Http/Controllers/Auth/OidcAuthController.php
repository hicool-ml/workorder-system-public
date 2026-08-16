<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * OIDC / OAuth2 统一身份认证控制器
 *
 * 认证流程 (Authorization Code Flow):
 * 1. 用户点击「统一身份认证」按钮 -> redirect to IdP authorization endpoint
 * 2. 用户在 IdP 页面登录成功后 -> 回调 /oidc/callback?code=xxx&state=xxx
 * 3. 系统用 code 向 IdP token endpoint 换取 access_token + id_token
 * 4. 用 access_token 向 userinfo endpoint 获取用户属性
 * 5. 根据返回的用户标识在本地查找/创建用户，自动登录
 *
 * 支持 OIDC Discovery（自动发现 endpoint）和手动配置 endpoint 两种模式。
 * 兼容泛微令信通、派拉、宁盾、阿里云 IDaaS、TOPIAM 等主流 IAM 平台。
 */
class OidcAuthController extends Controller
{
    /**
     * 跳转到 IdP 授权页面
     */
    public function login(Request $request)
    {
        if (!SystemSetting::get('oidc_enabled', false)) {
            return redirect()->route('login')->with('error', '统一身份认证未启用');
        }

        $config = $this->getConfig();
        if (!$config) {
            return redirect()->route('login')->with('error', 'OIDC 配置不完整，请联系管理员');
        }

        // PKCE: 生成 code_verifier 和 code_challenge，增强安全性
        $codeVerifier = Str::random(64);
        $codeChallenge = rtrim(strtr(base64_encode(hash('sha256', $codeVerifier, true)), '+/', '-_'), '=');

        // state: 防止 CSRF
        $state = Str::random(32);
        $nonce = Str::random(32);

        session([
            'oidc.state'         => $state,
            'oidc.nonce'         => $nonce,
            'oidc.code_verifier' => $codeVerifier,
        ]);

        // 保存 intended URL 以便登录后跳转（仅接受本站相对路径，防开放重定向）
        if ($request->has('intended')) {
            session(['oidc.intended' => \App\Helpers\UrlHelper::safeRedirectTarget($request->input('intended'))]);
        }

        $params = http_build_query([
            'response_type'      => 'code',
            'client_id'          => $config['client_id'],
            'redirect_uri'       => \App\Helpers\SystemHelper::absoluteUrl('/oidc/callback'),
            'scope'              => $config['scope'],
            'state'              => $state,
            'nonce'              => $nonce,
            'code_challenge'      => $codeChallenge,
            'code_challenge_method' => 'S256',
        ]);

        return redirect($config['authorize_endpoint'] . '?' . $params);
    }

    /**
     * OIDC 回调处理
     */
    public function callback(Request $request)
    {
        if (!SystemSetting::get('oidc_enabled', false)) {
            return redirect()->route('login')->with('error', '统一身份认证未启用');
        }

        // 检查错误回调（用户拒绝授权等）
        if ($request->has('error')) {
            $errorDesc = $request->input('error_description', $request->input('error'));
            Log::warning('OIDC 授权失败', ['error' => $errorDesc]);
            return redirect()->route('login')->with('error', '认证失败：' . $errorDesc);
        }

        // 验证 state（CSRF 防护；hash_equals 防时序侧信道）
        $state = $request->input('state');
        $sessionState = session('oidc.state');

        if (!$state || !hash_equals((string) $sessionState, (string) $state)) {
            Log::error('OIDC state 验证失败', [
                'received' => $state,
                'expected' => $sessionState,
            ]);
            return redirect()->route('login')->with('error', '认证回调验证失败，请重试');
        }

        $code = $request->input('code');
        if (!$code) {
            return redirect()->route('login')->with('error', '认证回调缺少授权码');
        }

        $config = $this->getConfig();
        if (!$config) {
            return redirect()->route('login')->with('error', 'OIDC 配置不完整');
        }

        // 用 code 换取 token
        $tokens = $this->exchangeCodeForToken($code, $config);

        if (!$tokens) {
            return redirect()->route('login')->with('error', '统一身份认证失败，请重试');
        }

        // 校验 id_token（nonce / exp / aud / iss，签名在可用时验证）
        if (empty($tokens['id_token'])) {
            return redirect()->route('login')->with('error', '统一身份认证响应缺少身份令牌');
        }

        if (!$this->validateIdToken($tokens['id_token'], $config)) {
            return redirect()->route('login')->with('error', '身份令牌验证失败，请重试');
        }

        // 获取用户信息
        $userInfo = $this->getUserInfo($tokens, $config);

        if (!$userInfo) {
            return redirect()->route('login')->with('error', '无法获取用户信息');
        }

        // 查找或创建本地用户
        $user = $this->findOrCreateUser($userInfo);

        if (!$user) {
            return redirect()->route('login')->with('error', '无法创建用户账户');
        }

        // 禁用账号禁止通过 SSO 登录
        if ($user->status !== 'active') {
            return redirect()->route('login')->with('error', '该账号已被禁用，请联系管理员');
        }

        // 清理 session 中的临时数据；保存 id_token 供单点登出（id_token_hint，
        // Keycloak/IDaaS 等要求该参数才接受 post_logout_redirect_uri）
        session()->forget(['oidc.state', 'oidc.nonce', 'oidc.code_verifier']);
        session(['oidc.id_token' => $tokens['id_token']]);

        // 登录并重新生成会话
        auth()->login($user, true);
        session()->regenerate(true);

        $intended = \App\Helpers\UrlHelper::safeRedirectTarget(session('oidc.intended'));
        session()->forget('oidc.intended');

        return redirect($intended);
    }

    /**
     * OIDC 登出
     */
    public function logout(Request $request)
    {
        $config = $this->getConfig();
        $idTokenHint = session('oidc.id_token');
        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // 如果 IdP 支持 end_session_endpoint，跳转到 IdP 统一登出
        if ($config && !empty($config['end_session_endpoint'])) {
            $params = [
                'post_logout_redirect_uri' => route('login'),
                'client_id'                => $config['client_id'],
            ];
            // 多数 IdP（Keycloak/IDaaS）要求 id_token_hint 才接受 post_logout_redirect_uri
            if ($idTokenHint) {
                $params['id_token_hint'] = $idTokenHint;
            }
            return redirect($config['end_session_endpoint'] . '?' . http_build_query($params));
        }

        return redirect()->route('login');
    }

    /**
     * 获取 OIDC 配置（合并 Discovery 和手动配置）
     */
    private function getConfig(): ?array
    {
        $clientId = SystemSetting::get('oidc_client_id', '');
        $clientSecret = SystemSetting::get('oidc_client_secret', '');

        if (empty($clientId)) {
            return null;
        }

        $scope = SystemSetting::get('oidc_scope', 'openid profile email');
        $issuer = SystemSetting::get('oidc_issuer', '');

        // 优先使用手动配置的 endpoints
        $authorizeEndpoint = SystemSetting::get('oidc_authorize_endpoint', '');
        $tokenEndpoint = SystemSetting::get('oidc_token_endpoint', '');
        $userinfoEndpoint = SystemSetting::get('oidc_userinfo_endpoint', '');
        $endSessionEndpoint = SystemSetting::get('oidc_end_session_endpoint', '');
        $jwksUri = SystemSetting::get('oidc_jwks_uri', '');

        // 如果没有手动配置 endpoints，则尝试 OIDC Discovery
        if ((empty($tokenEndpoint) || empty($jwksUri)) && !empty($issuer)) {
            $discovery = $this->discover($issuer);
            if ($discovery) {
                $authorizeEndpoint = $authorizeEndpoint ?: ($discovery['authorization_endpoint'] ?? '');
                $tokenEndpoint = $tokenEndpoint ?: ($discovery['token_endpoint'] ?? '');
                $userinfoEndpoint = $userinfoEndpoint ?: ($discovery['userinfo_endpoint'] ?? '');
                $endSessionEndpoint = $endSessionEndpoint ?: ($discovery['end_session_endpoint'] ?? '');
                $jwksUri = $jwksUri ?: ($discovery['jwks_uri'] ?? '');
            }
        }

        if (empty($authorizeEndpoint) || empty($tokenEndpoint)) {
            return null;
        }

        return [
            'client_id'             => $clientId,
            'client_secret'         => $clientSecret,
            'scope'                 => $scope,
            'authorize_endpoint'    => $authorizeEndpoint,
            'token_endpoint'        => $tokenEndpoint,
            'userinfo_endpoint'     => $userinfoEndpoint,
            'end_session_endpoint'  => $endSessionEndpoint,
            'jwks_uri'              => $jwksUri,
        ];
    }

    /**
     * OIDC Discovery: 从 Issuer 的 /.well-known/openid-configuration 获取配置
     */
    private function discover(string $issuer): ?array
    {
        // 缓存 discovery 结果 1 小时，避免每次请求都查
        return Cache::remember('oidc_discovery', 3600, function () use ($issuer) {
            $url = rtrim($issuer, '/') . '/.well-known/openid-configuration';

            try {
                $response = Http::timeout(15)->get($url);

                if (!$response->ok()) {
                    Log::error('OIDC Discovery 请求失败', [
                        'url' => $url,
                        'status' => $response->status(),
                    ]);
                    return null;
                }

                $data = $response->json();

                Log::info('OIDC Discovery 成功', ['issuer' => $issuer]);

                return $data;
            } catch (\Exception $e) {
                Log::error('OIDC Discovery 异常', [
                    'url' => $url,
                    'error' => $e->getMessage(),
                ]);
                return null;
            }
        });
    }

    /**
     * 用 authorization code 换取 token
     */
    private function exchangeCodeForToken(string $code, array $config): ?array
    {
        $codeVerifier = session('oidc.code_verifier');

        $payload = [
            'grant_type'    => 'authorization_code',
            'code'          => $code,
            'redirect_uri'  => \App\Helpers\SystemHelper::absoluteUrl('/oidc/callback'),
            'client_id'     => $config['client_id'],
        ];

        // client_secret 非空时使用 (confidential client)
        // 为空时依赖 PKCE (public client)
        // 兼容两种客户端认证：body 直传（client_secret_post）+ HTTP Basic（client_secret_basic），
        // 严格 IdP（仅支持 Basic）也能通过
        $basicAuth = null;
        if (!empty($config['client_secret'])) {
            $payload['client_secret'] = $config['client_secret'];
            $basicAuth = [$config['client_id'], $config['client_secret']];
        }

        if ($codeVerifier) {
            $payload['code_verifier'] = $codeVerifier;
        }

        try {
            $request = Http::timeout(15)->asForm();
            if ($basicAuth) {
                $request = $request->withBasicAuth($basicAuth[0], $basicAuth[1]);
            }
            $response = $request->post($config['token_endpoint'], $payload);

            if (!$response->ok()) {
                // body 截断脱敏：错误响应偶含部分令牌片段/PII，只留前 200 字符排障用
                Log::error('OIDC Token 交换失败', [
                    'status' => $response->status(),
                    'body' => mb_substr($response->body(), 0, 200),
                ]);
                return null;
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('OIDC Token 交换异常', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * 用 access_token 获取用户信息
     */
    private function getUserInfo(array $tokens, array $config): ?array
    {
        // 如果没有 userinfo endpoint，尝试从 id_token 解析
        if (empty($config['userinfo_endpoint'])) {
            if (isset($tokens['id_token'])) {
                return $this->parseIdToken($tokens['id_token']);
            }
            Log::error('OIDC 无法获取用户信息：无 userinfo endpoint 且无 id_token');
            return null;
        }

        $accessToken = $tokens['access_token'] ?? null;

        if (!$accessToken) {
            Log::error('OIDC Token 响应中缺少 access_token', ['tokens' => array_keys($tokens)]);
            return null;
        }

        try {
            $response = Http::timeout(15)
                ->withToken($accessToken)
                ->get($config['userinfo_endpoint']);

            if (!$response->ok()) {
                // body 截断脱敏：userinfo 响应含 PII（邮箱/手机号），只留前 200 字符排障用
                Log::error('OIDC UserInfo 请求失败', [
                    'status' => $response->status(),
                    'body' => mb_substr($response->body(), 0, 200),
                ]);
                return null;
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('OIDC UserInfo 请求异常', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * 解析 id_token (JWT) 的 payload 部分（不验签，验签应由 IdP 保证）
     */
    private function parseIdToken(string $idToken): ?array
    {
        $parts = explode('.', $idToken);
        if (count($parts) < 2) {
            return null;
        }

        $payload = $parts[1];
        // JWT base64url 补齐 padding
        $payload = str_pad($payload, strlen($payload) % 4 === 0 ? strlen($payload) : strlen($payload) + 4 - (strlen($payload) % 4), '=', STR_PAD_RIGHT);

        $decoded = json_decode(base64_decode(strtr($payload, '-_', '+/')), true);

        return $decoded ?: null;
    }

    /**
     * 校验 id_token：nonce / exp / aud / iss 必须全部存在且匹配；签名验证为强制项。
     *
     * 安全背景：此前 exp/aud/iss 缺失时全部跳过、验签仅在配置 jwks_uri 时"尽力而为"——
     * 未配置验签的 id_token 等于 base64 明文，任何能影响 token endpoint 响应的攻击者
     * 可注入任意 sub 直接铸造身份。OIDC 规范要求客户端必须验签。
     */
    private function validateIdToken(string $idToken, array $config): bool
    {
        $payload = $this->parseIdToken($idToken);
        if (!$payload) {
            Log::error('OIDC id_token 解析失败');
            return false;
        }

        // nonce：防止重放（双向必须存在——我们发起时总是携带）
        $expectedNonce = session('oidc.nonce');
        if (empty($payload['nonce']) || empty($expectedNonce)
            || !hash_equals((string) $expectedNonce, (string) $payload['nonce'])) {
            Log::error('OIDC id_token nonce 缺失或不匹配');
            return false;
        }

        // exp：必须存在且未过期
        if (empty($payload['exp']) || (int) $payload['exp'] < time()) {
            Log::error('OIDC id_token 缺少 exp 或已过期');
            return false;
        }

        // aud：必须存在且包含本应用 client_id
        if (empty($payload['aud'])) {
            Log::error('OIDC id_token 缺少 aud');
            return false;
        }
        $auds = is_array($payload['aud']) ? $payload['aud'] : [$payload['aud']];
        if (!in_array($config['client_id'], $auds, true)) {
            Log::error('OIDC id_token aud 不匹配', ['aud' => $payload['aud']]);
            return false;
        }

        // iss：必须与配置的 issuer 一致（已配置时）
        $issuer = SystemSetting::get('oidc_issuer', '');
        if (!empty($issuer)) {
            if (empty($payload['iss']) || rtrim((string) $payload['iss'], '/') !== rtrim((string) $issuer, '/')) {
                Log::error('OIDC id_token iss 缺失或不匹配', ['iss' => $payload['iss'] ?? null]);
                return false;
            }
        }

        // 签名验证（强制）：jwks_uri 缺失时从 discovery 获取，仍拿不到则拒绝启用
        $jwksUri = $config['jwks_uri'] ?? '';
        if (empty($jwksUri)) {
            $issuer = SystemSetting::get('oidc_issuer', '');
            $discovery = $issuer ? $this->discover($issuer) : null;
            $jwksUri = $discovery['jwks_uri'] ?? '';
        }
        if (empty($jwksUri)) {
            Log::error('OIDC 无法获取 jwks_uri，id_token 无法验签——拒绝登录（请在设置中配置 JWKS 地址或补全 issuer 以启用 discovery）');
            return false;
        }

        if (!$this->verifyIdTokenSignature($idToken, $jwksUri)) {
            Log::error('OIDC id_token 签名验证失败');
            return false;
        }

        return true;
    }

    /**
     * 使用 JWKS 验证 id_token 签名（仅支持 RS256）
     */
    private function verifyIdTokenSignature(string $idToken, string $jwksUri): bool
    {
        $parts = explode('.', $idToken);
        if (count($parts) !== 3) {
            return false;
        }

        $header = $this->decodeJwtPart($parts[0]);
        $alg = $header['alg'] ?? '';
        if (!in_array($alg, ['RS256', 'ES256'], true)) {
            Log::error('OIDC id_token 算法不支持（仅 RS256/ES256）', ['alg' => $alg ?: null]);
            return false;
        }

        try {
            $response = Http::timeout(15)->get($jwksUri);
            if (!$response->ok()) {
                Log::error('OIDC JWKS 获取失败', ['status' => $response->status()]);
                return false;
            }

            $keys = $response->json('keys', []);
            $kid = $header['kid'] ?? null;
            $wantKty = $alg === 'RS256' ? 'RSA' : 'EC';
            $key = null;
            foreach ($keys as $candidate) {
                if (($candidate['kty'] ?? '') !== $wantKty) continue;
                if ($kid && ($candidate['kid'] ?? null) === $kid) {
                    $key = $candidate;
                    break;
                }
                if (!$kid && !$key) {
                    $key = $candidate;
                }
            }

            if (!$key) {
                Log::error('OIDC JWKS 中未找到匹配密钥', ['kid' => $kid, 'kty' => $wantKty]);
                return false;
            }

            $pem = $alg === 'RS256'
                ? $this->rsaJwkToPem($key['n'] ?? '', $key['e'] ?? '')
                : $this->ecJwkToPem($key['crv'] ?? 'P-256', $key['x'] ?? '', $key['y'] ?? '');
            if (!$pem) {
                return false;
            }

            $signature = $this->base64UrlDecode($parts[2]);
            $data = $parts[0] . '.' . $parts[1];

            if ($alg === 'RS256') {
                return openssl_verify($data, $signature, $pem, OPENSSL_ALGO_SHA256) === 1;
            }

            // ES256：JWS 签名为 raw r||s 各 32 字节，需转 DER 才能被 openssl 接受
            $derSig = $this->ecRawToDer($signature, 32);
            if ($derSig === null) {
                Log::error('OIDC ES256 签名长度非法', ['len' => strlen($signature)]);
                return false;
            }
            return openssl_verify($data, $derSig, $pem, OPENSSL_ALGO_SHA256) === 1;
        } catch (\Exception $e) {
            Log::error('OIDC 签名验证异常', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * ECDSA raw 签名（r||s 定长）转 DER SEQUENCE 编码
     */
    private function ecRawToDer(string $sig, int $partLen): ?string
    {
        if (strlen($sig) !== $partLen * 2) {
            return null;
        }
        $r = $this->derInteger(substr($sig, 0, $partLen));
        $s = $this->derInteger(substr($sig, $partLen));
        return "\x30" . $this->derLength(strlen($r . $s)) . $r . $s;
    }

    /**
     * 将 EC JWK（crv/x/y，P-256）转换为 PEM（仅支持 ES256 所需的 P-256）
     */
    private function ecJwkToPem(string $crv, string $x, string $y): ?string
    {
        if ($crv !== 'P-256') {
            Log::error('OIDC EC 密钥曲线不支持（仅 P-256）', ['crv' => $crv]);
            return null;
        }
        $xBin = $this->base64UrlDecode($x);
        $yBin = $this->base64UrlDecode($y);
        if ($xBin === null || $yBin === null || strlen($xBin) !== 32 || strlen($yBin) !== 32) {
            return null;
        }

        // SubjectPublicKeyInfo：AlgorithmIdentifier(id-ecPublicKey 1.2.840.10045.2.1 + prime256v1 1.2.840.10045.3.1.7)
        //   + BIT STRING( uncompressed point 0x04 || x || y )
        $algId = "\x30\x13\x06\x07\x2a\x86\x48\xce\x3d\x02\x01\x06\x08\x2a\x86\x48\xce\x3d\x03\x01\x07";
        $point = "\x04" . $xBin . $yBin;
        $bitString = "\x03" . $this->derLength(strlen($point) + 1) . "\x00" . $point;
        $spki = "\x30" . $this->derLength(strlen($algId) + strlen($bitString)) . $algId . $bitString;

        return "-----BEGIN PUBLIC KEY-----\n" . chunk_split(base64_encode($spki), 64, "\n") . "-----END PUBLIC KEY-----";
    }

    /**
     * 解析 JWT 的 header / payload 段
     */
    private function decodeJwtPart(string $part): ?array
    {
        $decoded = $this->base64UrlDecode($part);
        if ($decoded === null) {
            return null;
        }
        $data = json_decode($decoded, true);
        return is_array($data) ? $data : null;
    }

    /**
     * base64url 解码（自动补齐 padding）
     */
    private function base64UrlDecode(string $data): ?string
    {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $data .= str_repeat('=', 4 - $remainder);
        }
        $decoded = base64_decode(strtr($data, '-_', '+/'), true);
        return $decoded === false ? null : $decoded;
    }

    /**
     * 将 RSA JWK（n / e）转换为 X.509 SubjectPublicKeyInfo PEM
     */
    private function rsaJwkToPem(string $n, string $e): ?string
    {
        $nBytes = $this->base64UrlDecode($n);
        $eBytes = $this->base64UrlDecode($e);
        if ($nBytes === null || $eBytes === null) {
            return null;
        }

        $pkcs1 = "\x30" . $this->derLength(strlen($this->derInteger($nBytes) . $this->derInteger($eBytes)))
            . $this->derInteger($nBytes) . $this->derInteger($eBytes);

        // AlgorithmIdentifier: rsaEncryption (1.2.840.113549.1.1.1) + NULL
        $algId = "\x30\x0d\x06\x09\x2a\x86\x48\x86\xf7\x0d\x01\x01\x01\x05\x00";

        // BIT STRING 包裹 PKCS#1
        $bitString = "\x03" . $this->derLength(strlen($pkcs1) + 1) . "\x00" . $pkcs1;

        $spki = "\x30" . $this->derLength(strlen($algId) + strlen($bitString)) . $algId . $bitString;

        $pem = "-----BEGIN PUBLIC KEY-----\n" . chunk_split(base64_encode($spki), 64, "\n") . "-----END PUBLIC KEY-----";
        return $pem;
    }

    /**
     * DER 长度编码
     */
    private function derLength(int $length): string
    {
        if ($length < 0x80) {
            return chr($length);
        }
        $bytes = '';
        while ($length > 0) {
            $bytes = chr($length & 0xff) . $bytes;
            $length >>= 8;
        }
        return chr(0x80 | strlen($bytes)) . $bytes;
    }

    /**
     * DER INTEGER 编码（正数，必要时补 0x00）
     */
    private function derInteger(string $bytes): string
    {
        if (ord($bytes[0]) & 0x80) {
            $bytes = "\x00" . $bytes;
        }
        return "\x02" . $this->derLength(strlen($bytes)) . $bytes;
    }

    /**
     * 根据 OIDC 用户信息查找或创建本地用户
     */
    private function findOrCreateUser(array $oidcUser): ?User
    {
        $sub = $oidcUser['sub'] ?? '';

        // 安全检查：OIDC 必须返回唯一的用户标识 (sub)
        if (empty($sub)) {
            Log::error('OIDC 认证返回的用户标识(sub)为空，拒绝登录', ['userinfo' => $oidcUser]);
            return null;
        }

        $username = $oidcUser['preferred_username']
            ?? $oidcUser['username']
            ?? $oidcUser['login']
            ?? $sub;

        $name = $oidcUser['name']
            ?? $oidcUser['nickname']
            ?? $oidcUser['cn']
            ?? $username;

        $email = $oidcUser['email'] ?? null;
        $phone = $oidcUser['phone_number']
            ?? $oidcUser['phone']
            ?? $oidcUser['mobile']
            ?? null;
        $department = $oidcUser['department']
            ?? $oidcUser['department_name']
            ?? null;

        // 清理手机号（去掉可能的 +86 前缀或空格）
        if ($phone) {
            $phone = preg_replace('/[^0-9]/', '', $phone);
            // 86 + 11 位国内手机号 = 13 位；仅当去掉 86 后恰好是 11 位才剥离，避免误删
            if (str_starts_with($phone, '86') && strlen($phone) === 13) {
                $phone = substr($phone, 2);
            }
        }

        // 仅按不可变标识查找：oidc_sub（首次登录后绑定）与 username（工号，IdP 侧受管字段）。
        // 安全红线：禁止按手机号/邮箱等用户可在 IdP 自助修改的属性自动关联本地账号，
        // 否则攻击者在 IdP 侧把邮箱改成与管理员相同即可零密码接管管理员账户。
        $user = User::where('oidc_sub', $sub)->first();

        if (!$user && $username !== $sub) {
            $candidate = User::where('employee_id', $username)->first();
            // 工号匹配仅对普通用户放行；管理员/工单管理员账户禁止被 SSO 属性自动关联
            if ($candidate && !in_array($candidate->role, ['admin', 'workorder_manager'], true)) {
                $user = $candidate;
            } elseif ($candidate) {
                Log::warning('OIDC 工号命中特权账号，拒绝自动关联', [
                    'employee_id' => $username,
                    'role' => $candidate->role,
                ]);
            }
        }

        // 再按历史 OIDC 保留用户名查找（同样是 IdP 受管标识）
        if (!$user) {
            $user = User::where('username', 'oidc_' . $username)->first();
        }

        if ($user) {
            // 已有用户，更新 OIDC 相关信息
            // 注意：不重置 status，避免把管理员已禁用的账号在 SSO 登录时自动重新激活
            $user->update([
                'oidc_sub'     => $sub,
                'name'         => $name ?: $user->name,
                'phone'        => $phone ?: $user->phone,
                'email'        => $email ?: $user->email,
                'account_type' => 'oidc',
            ]);
            return $user->fresh();
        }

        // 创建新用户（默认为报修人）；email 可空且需唯一——缺失或撞库时生成占位邮箱
        $safeEmail = $email;
        if ($safeEmail && User::where('email', $safeEmail)->exists()) {
            Log::warning('OIDC 用户邮箱与本地账号冲突，使用占位邮箱', ['email' => $safeEmail]);
            $safeEmail = null;
        }
        if (!$safeEmail) {
            $safeEmail = 'oidc_' . Str::random(16) . '@migrated.local';
        }
        try {
            return User::create([
                'name'         => $name ?: $username,
                'username'     => 'oidc_' . $username,
                'employee_id'  => ($username !== $sub) ? $username : null,
                'oidc_sub'     => $sub,
                'phone'        => $phone,
                'email'        => $safeEmail,
                'password'     => bcrypt(Str::random(32)),
                'role'         => 'user',
                'status'       => 'active',
                'account_type' => 'oidc',
                // SSO 用户不走本地密码：直接视为已过改密节点（防 ForcePasswordChange 锁死）
                'password_changed_at' => now(),
                'remarks'      => $department ? "部门：{$department}" : null,
            ]);
        } catch (\Exception $e) {
            Log::error('OIDC 用户创建失败', [
                'sub' => $sub,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
}
