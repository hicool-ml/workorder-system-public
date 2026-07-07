<?php

echo "=== 修复剩余的硬编码重定向 ===\n\n";

$controllerFile = __DIR__ . '/app/Http/Controllers/WorkorderController.php';

if (!file_exists($controllerFile)) {
    echo "错误: WorkorderController.php 文件不存在\n";
    exit(1);
}

$content = file_get_contents($controllerFile);

// 修复剩余的硬编码重定向
$patterns = [
    'redirect(\'/workorders\')' => 'redirect(\App\Helpers\UrlHelper::relative_url(\'/workorders\'))',
];

$replacements = 0;

foreach ($patterns as $search => $replace) {
    $count = 0;
    $content = preg_replace('/' . preg_quote($search, '/') . '/', $replace, $content, $count);
    $replacements += $count;
    echo "替换 '$search' → '$replace': $count 次\n";
}

// 保存修改后的文件
if ($replacements > 0) {
    if (file_put_contents($controllerFile, $content)) {
        echo "\n✓ 成功修复 $replacements 个剩余硬编码URL\n";
        echo "✓ 文件已保存: $controllerFile\n";
    } else {
        echo "\n✗ 文件保存失败\n";
    }
} else {
    echo "\n⚠️  没有找到需要修复的剩余硬编码URL\n";
}

echo "\n=== 验证修复结果 ===\n";

// 验证修复结果
$content = file_get_contents($controllerFile);
$hardcodedRedirects = substr_count($content, "redirect('/workorders')");
$fixedRedirects = substr_count($content, "redirect(\App\Helpers\UrlHelper::relative_url('/workorders')");

echo "剩余硬编码 redirect('/workorders'): $hardcodedRedirects 个\n";
echo "修复后的 redirect(): $fixedRedirects 个\n";

if ($hardcodedRedirects == 0) {
    echo "✓ 所有硬编码重定向已修复\n";
} else {
    echo "✗ 仍有 $hardcodedRedirects 个硬编码重定向需要修复\n";
}

echo "\n=== 修复完成 ===\n";

?>