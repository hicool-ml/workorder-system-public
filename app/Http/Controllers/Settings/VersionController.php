<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Settings\Concerns\GuardsAdmin;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * 系统版本管理：唯一版本源（DB + VERSION 文件同步写入）
 */
class VersionController extends Controller
{
    use GuardsAdmin;

    /**
     * 更新系统版本
     */
    public function updateVersion(Request $request)
    {
        $this->guardAdminAbort();

        $request->validate([
            'version' => 'required|string|max:20|regex:/^\d+\.\d+\.\d+$/',
            'release_date' => 'required|date',
            'release_notes' => 'required|string|max:1000',
        ], [
            'version.regex' => '版本号格式应为 X.Y.Z（如 3.0.1）',
        ]);

        DB::beginTransaction();
        try {
            // 更新版本号
            SystemSetting::set(
                'system_version',
                $request->input('version'),
                'string',
                '系统版本号',
                true
            );

            // 更新发布日期
            SystemSetting::set(
                'system_release_date',
                $request->input('release_date'),
                'string',
                '系统发布日期',
                true
            );

            // 保存发布说明到版本历史（必填）
            SystemSetting::set(
                'version_notes_' . str_replace('.', '_', $request->input('version')),
                $request->input('release_notes'),
                'text',
                "版本 {$request->input('version')} 发布说明",
                false
            );

            // 同步写入 VERSION 文件（部署版本与页面版本保持一致）
            @file_put_contents(base_path('VERSION'), trim($request->input('version')) . PHP_EOL);

            DB::commit();

            $message = '系统版本更新成功（VERSION 文件已同步）';

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'version' => $request->input('version'),
                    'release_date' => $request->input('release_date')
                ]);
            }

            return back()->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();
            $errorMessage = $e->getMessage();
            if (empty($errorMessage)) {
                $errorMessage = '版本更新失败';
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage
                ]);
            }

            return back()->with('error', '版本更新失败：' . $errorMessage);
        }
    }

    /**
     * 获取版本历史
     */
    public function getVersionHistory()
    {
        $versionSettings = SystemSetting::where('key', 'like', 'version_notes_%')
            ->orderBy('key', 'desc')
            ->get();

        $versionHistory = [];
        foreach ($versionSettings as $setting) {
            $version = Str::after($setting->key, 'version_notes_');
            $version = str_replace('_', '.', $version);
            $versionHistory[] = [
                'version' => $version,
                'notes' => $setting->value,
                'created_at' => $setting->created_at->format('Y-m-d H:i:s')
            ];
        }

        return response()->json($versionHistory);
    }

    /**
     * 删除单条版本历史记录
     */
    public function deleteVersionHistory(Request $request)
    {
        if ($denied = $this->guardAdminJson()) {
            return $denied;
        }

        $request->validate(['version' => 'required|string|max:20']);

        $key = 'version_notes_' . str_replace('.', '_', $request->input('version'));
        SystemSetting::where('key', $key)->delete();

        return response()->json(['success' => true, 'message' => '版本记录已删除']);
    }
}
