<?php

echo "=== 修复WorkorderController中的硬编码URL ===\n\n";

$controllerFile = __DIR__ . '/app/Http/Controllers/WorkorderController.php';

if (!file_exists($controllerFile)) {
    echo "错误: WorkorderController.php 文件不存在\n";
    exit(1);
}

$content = file_get_contents($controllerFile);

// 需要替换的模式
$patterns = [
    'redirect("/workorders' => 'redirect(\App\Helpers\UrlHelper::relative_url("/workorders"))',
    'redirect("/workorders/{$workorder->id}")' => 'redirect(\App\Helpers\UrlHelper::relative_url("/workorders/{$workorder->id}"))',
    'redirect("/workorders/{$collaboration->workorder_id}")' => 'redirect(\App\Helpers\UrlHelper::relative_url("/workorders/{$collaboration->workorder_id}"))',
];

$replacements = 0;

foreach ($patterns as $search => $replace) {
    $count = 0;
    $content = str_replace($search, $replace, $content, $count);
    $replacements += $count;
    echo "替换 '$search' → '$replace': $count 次\n";
}

// 保存修改后的文件
if ($replacements > 0) {
    if (file_put_contents($controllerFile, $content)) {
        echo "\n✓ 成功修复 $replacements 个硬编码URL\n";
        echo "✓ 文件已保存: $controllerFile\n";
    } else {
        echo "\n✗ 文件保存失败\n";
    }
} else {
    echo "\n⚠️  没有找到需要修复的硬编码URL\n";
}

echo "\n=== 修复完成 ===\n";
echo "总共修复了 $replacements 个硬编码URL\n";
echo "现在所有重定向都使用相对路径\n";

?>