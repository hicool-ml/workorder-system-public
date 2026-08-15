<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Settings\Concerns\GuardsAdmin;
use App\Models\SystemSetting;
use Illuminate\Http\Request;

/**
 * SSL 证书管理：上传 / 开关 / 删除
 */
class SslSettingController extends Controller
{
    use GuardsAdmin;

    /**
     * 上传 CA 证书文件
     */
    public function uploadCacert(Request $request)
    {
        if ($denied = $this->guardAdminJson()) {
            return $denied;
        }

        $request->validate([
            'cacert_file' => 'required|file|max:1024',
        ]);

        $file = $request->file('cacert_file');
        $content = file_get_contents($file->getRealPath());

        // 校验是否为有效的 PEM 格式 CA 证书
        if (strpos($content, 'BEGIN CERTIFICATE') === false) {
            return response()->json(['success' => false, 'message' => '文件不是有效的 PEM 格式 CA 证书（缺少 BEGIN CERTIFICATE 标记）']);
        }

        // 存储到 storage/app/cacert.pem
        $path = storage_path('app/cacert.pem');
        file_put_contents($path, $content);

        SystemSetting::set('ssl_cacert_path', $path, 'string', '自定义 CA 证书路径', false);
        // 上传证书后自动开启 SSL 验证
        SystemSetting::set('ssl_verify_enabled', true, 'boolean', '是否启用 HTTPS SSL 证书验证', false);

        return response()->json([
            'success' => true,
            'message' => 'CA 证书上传成功，已自动启用 SSL 验证',
            'path' => $path,
        ]);
    }

    /**
     * 切换 SSL 验证开关
     */
    public function toggleSslVerify(Request $request)
    {
        if ($denied = $this->guardAdminJson()) {
            return $denied;
        }

        $enabled = $request->boolean('enabled');
        SystemSetting::set('ssl_verify_enabled', $enabled, 'boolean', '是否启用 HTTPS SSL 证书验证', false);

        return response()->json([
            'success' => true,
            'message' => $enabled ? 'SSL 验证已开启' : 'SSL 验证已关闭（仅限测试环境）',
            'enabled' => $enabled,
        ]);
    }

    /**
     * 删除已上传的 CA 证书
     */
    public function deleteCacert(Request $request)
    {
        if ($denied = $this->guardAdminJson()) {
            return $denied;
        }

        $path = SystemSetting::get('ssl_cacert_path', '');
        if (!empty($path) && file_exists($path)) {
            @unlink($path);
        }
        SystemSetting::set('ssl_cacert_path', '', 'string', '自定义 CA 证书路径', false);

        return response()->json(['success' => true, 'message' => 'CA 证书已删除，将使用系统默认配置']);
    }
}
