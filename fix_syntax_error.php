<?php

echo "=== 修复语法错误 ===\n\n";

$controllerFile = __DIR__ . '/app/Http/Controllers/WorkorderController.php';

if (!file_exists($controllerFile)) {
    echo "错误: WorkorderController.php 文件不存在\n";
    exit(1);
}

$content = file_get_contents($controllerFile);

// 修复语法错误：引号不匹配
$content = str_replace(
    'redirect(\App\Helpers\UrlHelper::relative_url("/workorders"){$workorder->id}")',
    'redirect(\App\Helpers\UrlHelper::relative_url("/workorders/{$workorder->id}"))',
    $content
);

// 保存修改后的文件
if (file_put_contents($controllerFile, $content)) {
    echo "✓ 成功修复语法错误\n";
    echo "✓ 文件已保存: $controllerFile\n";
} else {
    echo "✗ 文件保存失败\n";
}

echo "\n=== 验证修复结果 ===\n";

// 验证修复结果
$content = file_get_contents($controllerFile);
$syntaxErrors = substr_count($content, 'redirect(\App\Helpers\UrlHelper::relative_url("/workorders"){$workorder->id}")');
$fixedSyntax = substr_count($content, 'redirect(\App\Helpers\UrlHelper::relative_url("/workorders/{$workorder->id}"))');

echo "语法错误: $syntaxErrors 个\n";
echo "修复语法: $fixedSyntax 个\n";

if ($syntaxErrors == 0) {
    echo "✓ 所有语法错误已修复\n";
} else {
    echo "✗ 仍有 $syntaxErrors 个语法错误需要修复\n";
}

echo "\n=== 修复完成 ===\n";

?>