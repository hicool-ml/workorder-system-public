<?php

echo "=== HTTP/HTTPS端口切换问题修复验证 ===\n\n";

$checks = [
    '1. .env配置文件' => function() {
        $env = file_get_contents('.env');
        $hasCorrectAppUrl = strpos($env, 'APP_URL=http://localhost') !== false;
        $hasAssetUrlCommented = strpos($env, '# ASSET_URL=https://work.66107166.xyz') !== false;
        return $hasCorrectAppUrl && $hasAssetUrlCommented;
    },
    
    '2. TrustProxies中间件' => function() {
        return file_exists('app/Http/Middleware/TrustProxies.php');
    },
    
    '3. TrustProxies中间件注册' => function() {
        $bootstrap = file_get_contents('bootstrap/app.php');
        return strpos($bootstrap, 'TrustProxies::class') !== false;
    },
    
    '4. AppServiceProvider动态URL检测' => function() {
        $provider = file_get_contents('app/Providers/AppServiceProvider.php');
        return strpos($provider, 'URL::forceRootUrl') !== false;
    },
    
    '5. .htaccess外网HTTPS重定向' => function() {
        $htaccess = file_get_contents('public/.htaccess');
        return strpos($htaccess, 'Cloudflare隧道') !== false &&
               strpos($htaccess, 'CF-Visitor') !== false;
    },
    
    '6. .htaccess内网HTTP保持' => function() {
        $htaccess = file_get_contents('public/.htaccess');
        return strpos($htaccess, 'localhost|127\.0\.0\.1') !== false && 
               strpos($htaccess, 'RewriteCond %{HTTPS} on') !== false;
    }
];

$allPassed = true;

foreach ($checks as $description => $check) {
    $result = $check();
    $status = $result ? '✓ 通过' : '✗ 失败';
    echo "$description: $status\n";
    if (!$result) {
        $allPassed = false;
    }
}

echo "\n" . str_repeat("=", 50) . "\n";

if ($allPassed) {
    echo "🎉 所有修复验证通过！\n\n";
    echo "修复摘要:\n";
    echo "✓ .env配置已修正\n";
    echo "✓ TrustProxies中间件已创建并注册\n";
    echo "✓ AppServiceProvider已添加动态URL检测\n";
    echo "✓ .htaccess重定向规则已配置\n\n";
    
    echo "下一步操作:\n";
    echo "1. 重启Web服务器 (Apache/Nginx)\n";
    echo "2. 测试内网访问: http://localhost\n";
    echo "3. 测试外网访问: https://work.66107166.xyz\n";
    echo "4. 验证表单提交功能\n";
} else {
    echo "❌ 部分修复验证失败，请检查上述失败项\n";
}

echo "\n修复详情请参考: HTTP_HTTPS_PORT_SWITCHING_FIX.md\n";

?>