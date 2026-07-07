<?php
/**
 * 深度调试HTTPS问题
 * 找出真正导致https://192.168.1.19的根源
 */

echo "=== 深度调试HTTPS问题 ===\n\n";

// 1. 检查所有可能影响URL生成的配置
echo "1. 检查所有可能的配置源:\n";

// 检查AppServiceProvider
$appServiceProvider = file_get_contents(__DIR__ . '/app/Providers/AppServiceProvider.php');
echo "   AppServiceProvider中的URL配置:\n";
if (strpos($appServiceProvider, 'URL::forceRootUrl(null)') !== false) {
    echo "     ✅ URL::forceRootUrl(null) 已设置\n";
} else {
    echo "     ❌ URL::forceRootUrl(null) 未设置\n";
}

if (strpos($appServiceProvider, 'URL::forceScheme(null)') !== false) {
    echo "     ✅ URL::forceScheme(null) 已设置\n";
} else {
    echo "     ❌ URL::forceScheme(null) 未设置\n";
}

// 检查是否有其他地方设置了forceScheme
if (preg_match_all('/URL::forceScheme\([\'"]([^\'"]+)\[\'"]/', $appServiceProvider, $matches)) {
    echo "     ⚠️  发现其他forceScheme调用: " . $matches[1][0] . "\n";
}

// 检查config/app.php
$configApp = file_get_contents(__DIR__ . '/config/app.php');
echo "   config/app.php中的URL配置:\n";
if (strpos($configApp, "'url' => env('APP_URL', 'http://localhost')") !== false) {
    echo "     ✅ 默认URL设置为http://localhost\n";
} else {
    echo "     ❌ config/app.php配置不正确\n";
}

// 检查是否有其他配置文件
$configFiles = glob(__DIR__ . '/config/*.php');
foreach ($configFiles as $file) {
    if (strpos($file, 'app.php') === false) {
        $content = file_get_contents($file);
        if (strpos($content, 'https') !== false) {
            echo "     ⚠️  发现配置文件中有HTTPS: $file\n";
        }
    }
}

echo "\n";

// 2. 检查所有中间件
echo "2. 检查所有中间件:\n";

$middlewarePath = __DIR__ . '/app/Http/Middleware';
if (is_dir($middlewarePath)) {
    $middlewareFiles = scandir($middlewarePath);
    foreach ($middlewareFiles as $file) {
        if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
            $content = file_get_contents($middlewarePath . '/' . $file);
            if (strpos($content, 'https') !== false && strpos($file, 'TrustProxies') === false) {
                echo "     ⚠️  中间件 {$file} 中包含HTTPS相关代码\n";
            }
            if (strpos($content, 'forceScheme') !== false) {
                echo "     ⚠️  中间件 {$file} 中包含forceScheme代码\n";
            }
        }
    }
}

echo "\n";

// 3. 检查是否有其他地方覆盖了URL::forceScheme
echo "3. 检查是否有其他地方覆盖了URL::forceScheme:\n";

$allPhpFiles = glob(__DIR__ . '/**/*.php');
foreach ($allPhpFiles as $file) {
    if (is_file($file)) {
        $content = file_get_contents($file);
        if (preg_match('/URL::forceScheme\([\'"]([^\'"]+)\[\'"]/', $content, $matches)) {
            $scheme = $matches[1][0];
            if ($scheme !== 'null') {
                echo "     ⚠️  文件 {$file} 中设置了forceScheme为: {$scheme}\n";
            }
        }
    }
}

echo "\n";

// 4. 检查服务器环境变量
echo "4. 检查服务器环境变量:\n";
echo "   \$_SERVER['HTTPS']: " . ($_SERVER['HTTPS'] ?? '未设置') . "\n";
echo "   \$_SERVER['REQUEST_SCHEME']: " . ($_SERVER['REQUEST_SCHEME'] ?? '未设置') . "\n";
echo "   \$_SERVER['HTTP_X_FORWARDED_PROTO']: " . ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '未设置') . "\n";
echo "   \$_SERVER['HTTP_X_FORWARDED_HOST']: " . ($_SERVER['HTTP_X_FORWARDED_HOST'] ?? '未设置') . "\n";
echo "   \$_SERVER['HTTP_X_FORWARDED_SSL']: " . ($_SERVER['HTTP_X_FORWARDED_SSL'] ?? '未设置') . "\n";

echo "\n";

// 5. 检查Laravel的URL生成
echo "5. 测试Laravel的URL生成:\n";

// 模拟Laravel的URL::to方法
class MockUrlGenerator {
    public function to($path, $extra = [], $secure = null) {
        // 模拟Laravel的默认行为
        $scheme = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $scheme . '://' . $host . '/' . ltrim($path, '/');
    }
    
    public function route($name, $parameters = [], $absolute = true) {
        $routes = [
            'workorders.index' => '/workorders',
            'workorders.create' => '/workorders/create',
        ];
        
        $path = $routes[$name] ?? '/';
        if (!empty($parameters)) {
            foreach ($parameters as $key => $value) {
                $path = str_replace('{' . $key . '}', $value, $path);
            }
        }
        
        $scheme = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $scheme . '://' . $host . $path;
    }
}

// 测试当前的URL生成行为
$mockUrl = new MockUrlGenerator();

echo "   当前环境下的URL生成:\n";
echo "   - URL::to('/workorders'): " . $mockUrl->to('/workorders') . "\n";
echo "   - URL::route('workorders.index'): " . $mockUrl->route('workorders.index') . "\n";

echo "\n";

// 6. 分析问题
echo "6. 问题分析:\n";
echo "   如果仍然出现 https://192.168.1.19，可能的原因:\n";
echo "   1. 有其他地方在运行时重新设置了URL::forceScheme('https')\n";
echo "   2. 有其他地方在运行时重新设置了\$_SERVER['HTTPS']\n";
echo "   3. 有其他中间件在请求处理过程中修改了协议\n";
echo "   4. 浏览器缓存问题\n";
echo "   5. 服务器配置问题（如nginx强制HTTPS重定向）\n";

echo "\n";

// 7. 建议的解决方案
echo "7. 建议的解决方案:\n";
echo "   1. 在AppServiceProvider的boot方法最后添加:\n";
echo "      // 强制清除所有可能的HTTPS设置\n";
echo "      \$_SERVER['HTTPS'] = 'off';\n";
echo "      \$_SERVER['REQUEST_SCHEME'] = 'http';\n";
echo "      \$_SERVER['HTTP_X_FORWARDED_PROTO'] = 'http';\n";
echo "      URL::forceScheme('http');\n";

echo "\n=== 调试完成 ===\n";