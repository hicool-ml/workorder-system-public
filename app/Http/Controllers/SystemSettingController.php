<?php

namespace App\Http\Controllers;

use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SystemSettingController extends Controller
{
    /**
     * 系统设置列表页面
     */
    public function index()
    {
        $settings = SystemSetting::orderBy('key')->get();
        
        // 按类别分组设置
        $groupedSettings = [
            'registration' => $settings->filter(fn($s) => str_contains($s->key, 'registration')),
            'user' => $settings->filter(fn($s) => str_contains($s->key, 'user')),
            'system' => $settings->filter(fn($s) => str_contains($s->key, 'system')),
            'version' => $settings->filter(fn($s) => in_array($s->key, ['system_version', 'system_release_date'])),
            'other' => $settings->filter(fn($s) => !in_array($s->key, [
                'registration_enabled', 'default_user_role', 'require_email_verification', 'system_name', 'system_version', 'system_release_date'
            ])),
        ];
        
        return view('system-settings.index', compact('groupedSettings', 'settings'));
    }

    /**
     * 更新系统设置
     */
    public function update(Request $request)
    {
        $request->validate([
            'settings' => 'required|array',
            'settings.*' => 'required|string',
        ]);

        DB::beginTransaction();
        try {
            foreach ($request->input('settings') as $key => $value) {
                $setting = SystemSetting::where('key', $key)->first();
                if ($setting) {
                    // 根据类型转换值
                    $convertedValue = match($setting->type) {
                        'boolean' => $value === '1' || $value === 'true',
                        'integer' => (int) $value,
                        'float' => (float) $value,
                        'json', 'array' => is_string($value) ? json_decode($value, true) : $value,
                        default => $value,
                    };
                    
                    $setting->setTypedValueAttribute($convertedValue);
                    $setting->save();
                }
            }
            
            DB::commit();
            
            return back()->with('success', '系统设置更新成功');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', '系统设置更新失败：' . $e->getMessage());
        }
    }

    /**
     * 切换注册开关
     */
    public function toggleRegistration(Request $request)
    {
        $enabled = $request->boolean('enabled', false);
        
        try {
            SystemSetting::toggleRegistration($enabled);
            
            $message = $enabled ? '开放注册已启用' : '开放注册已禁用';
            
            // 如果是AJAX请求，返回JSON响应
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message
                ]);
            }
            
            return back()->with('success', $message);
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            if (empty($errorMessage)) {
                $errorMessage = '系统设置更新失败';
            }
            
            // 如果是AJAX请求，返回JSON响应
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage
                ]);
            }
            
            return back()->with('error', '操作失败：' . $errorMessage);
        }
    }

    /**
     * 创建新设置
     */
    public function store(Request $request)
    {
        $request->validate([
            'key' => 'required|string|max:100|unique:system_settings,key',
            'value' => 'required|string',
            'type' => 'required|in:string,boolean,integer,float,json,array',
            'description' => 'nullable|string|max:255',
            'is_public' => 'boolean',
        ]);

        try {
            SystemSetting::create($request->all());
            
            return back()->with('success', '设置创建成功');
        } catch (\Exception $e) {
            return back()->with('error', '设置创建失败：' . $e->getMessage());
        }
    }

    /**
     * 删除设置
     */
    public function destroy(SystemSetting $systemSetting)
    {
        try {
            $systemSetting->delete();
            
            return back()->with('success', '设置删除成功');
        } catch (\Exception $e) {
            return back()->with('error', '设置删除失败：' . $e->getMessage());
        }
    }

    /**
     * 获取公开设置（API）
     */
    public function publicSettings()
    {
        $settings = SystemSetting::where('is_public', true)->get()->mapWithKeys(function ($setting) {
            return [$setting->key => $setting->typed_value];
        });
        
        return response()->json($settings);
    }

    /**
     * 初始化默认设置
     */
    public function initializeDefaults(Request $request)
    {
        try {
            SystemSetting::initializeDefaults();
            
            $message = '默认设置初始化成功';
            
            // 如果是AJAX请求，返回JSON响应
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message
                ]);
            }
            
            return back()->with('success', $message);
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            if (empty($errorMessage)) {
                $errorMessage = '默认设置初始化失败';
            }
            
            // 如果是AJAX请求，返回JSON响应
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage
                ]);
            }
            
            return back()->with('error', '默认设置初始化失败：' . $errorMessage);
        }
    }

    /**
     * 更新系统版本
     */
    public function updateVersion(Request $request)
    {
        $request->validate([
            'version' => 'required|string|max:20',
            'release_date' => 'required|date',
            'release_notes' => 'nullable|string|max:1000',
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

            // 如果有发布说明，保存到版本历史
            if ($request->filled('release_notes')) {
                SystemSetting::set(
                    'version_notes_' . str_replace('.', '_', $request->input('version')),
                    $request->input('release_notes'),
                    'text',
                    "版本 {$request->input('version')} 发布说明",
                    false
                );
            }

            DB::commit();
            
            $message = '系统版本更新成功';
            
            // 如果是AJAX请求，返回JSON响应
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
            
            // 如果是AJAX请求，返回JSON响应
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
            $version = str_replace(['version_notes_', '_'], ['.', '.'], $setting->key);
            $versionHistory[] = [
                'version' => $version,
                'notes' => $setting->value,
                'created_at' => $setting->created_at->format('Y-m-d H:i:s')
            ];
        }

        return response()->json($versionHistory);
    }
}
