<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;

// 创建应用实例
$app = require_once __DIR__ . '/bootstrap/app.php';

// 启动应用
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== 调试注册开关切换问题 ===\n\n";

try {
    // 测试1: 检查registration_enabled设置是否存在
    echo "1. 检查registration_enabled设置...\n";
    $setting = \App\Models\SystemSetting::where('key', 'registration_enabled')->first();
    
    if ($setting) {
        echo "   ✓ registration_enabled设置存在\n";
        echo "   当前值: {$setting->value}\n";
        echo "   类型: {$setting->type}\n";
        echo "   ID: {$setting->id}\n";
    } else {
        echo "   ✗ registration_enabled设置不存在\n";
        // 创建设置
        echo "   正在创建registration_enabled设置...\n";
        \App\Models\SystemSetting::create([
            'key' => 'registration_enabled',
            'value' => '0',
            'type' => 'boolean',
            'description' => '是否开放用户注册',
            'is_public' => true,
        ]);
        echo "   ✓ registration_enabled设置创建成功\n";
    }
    
    // 测试2: 尝试直接更新设置
    echo "\n2. 测试直接更新设置...\n";
    $setting = \App\Models\SystemSetting::where('key', 'registration_enabled')->first();
    if ($setting) {
        try {
            $setting->value = '1';
            $setting->save();
            echo "   ✓ 直接更新成功\n";
            
            $setting->value = '0';
            $setting->save();
            echo "   ✓ 直接更新回原值成功\n";
        } catch (\Exception $e) {
            echo "   ✗ 直接更新失败: " . $e->getMessage() . "\n";
            echo "   错误类型: " . get_class($e) . "\n";
        }
    }
    
    // 测试3: 尝试使用toggleRegistration方法
    echo "\n3. 测试toggleRegistration方法...\n";
    try {
        $result = \App\Models\SystemSetting::toggleRegistration(true);
        echo "   ✓ 启用注册成功\n";
        echo "   返回值: " . (is_object($result) ? get_class($result) : $result) . "\n";
        
        $result = \App\Models\SystemSetting::toggleRegistration(false);
        echo "   ✓ 禁用注册成功\n";
        echo "   返回值: " . (is_object($result) ? get_class($result) : $result) . "\n";
    } catch (\Exception $e) {
        echo "   ✗ toggleRegistration失败: " . $e->getMessage() . "\n";
        echo "   错误类型: " . get_class($e) . "\n";
        echo "   文件: " . $e->getFile() . "\n";
        echo "   行号: " . $e->getLine() . "\n";
        
        // 检查是否有之前的异常
        if ($e->getPrevious()) {
            echo "   上一个异常: " . $e->getPrevious()->getMessage() . "\n";
        }
    }
    
    // 测试4: 检查数据库连接
    echo "\n4. 检查数据库连接...\n";
    try {
        \Illuminate\Support\Facades\DB::connection()->getPdo();
        echo "   ✓ 数据库连接正常\n";
    } catch (\Exception $e) {
        echo "   ✗ 数据库连接失败: " . $e->getMessage() . "\n";
    }
    
    // 测试5: 检查表结构
    echo "\n5. 检查表结构...\n";
    $tableExists = \Illuminate\Support\Facades\Schema::hasTable('system_settings');
    echo "   system_settings表存在: " . ($tableExists ? '是' : '否') . "\n";
    
    if ($tableExists) {
        $columns = \Illuminate\Support\Facades\Schema::getColumnListing('system_settings');
        echo "   表字段: " . implode(', ', $columns) . "\n";
    }
    
    echo "\n=== 调试完成 ===\n";
    
} catch (Exception $e) {
    echo "✗ 调试过程中出现错误: " . $e->getMessage() . "\n";
    echo "文件: " . $e->getFile() . "\n";
    echo "行号: " . $e->getLine() . "\n";
}