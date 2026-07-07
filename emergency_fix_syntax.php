<?php

echo "=== 紧急修复语法错误 ===\n\n";

$controllerFile = __DIR__ . '/app/Http/Controllers/WorkorderController.php';

if (!file_exists($controllerFile)) {
    echo "错误: WorkorderController.php 文件不存在\n";
    exit(1);
}

$content = file_get_contents($controllerFile);

// 检查第352行附近的语法错误
$lines = file($controllerFile);
$problemLine = 351; // 第351行（0-based）
$contextLines = [];

// 获取问题行周围的上下文
for ($i = max(0, $problemLine - 5); $i < min($problemLine + 10, count($lines)); $i++) {
    $contextLines[] = $lines[$i];
}

echo "问题行周围的代码:\n";
foreach ($contextLines as $lineNum => $line) {
    echo sprintf("%4d: %s\n", $lineNum + 1, $line);
}

// 检查具体的语法错误
if (isset($lines[351])) {
    $line351 = $lines[351];
    echo "\n第352行内容: " . trim($line351) . "\n";
    
    // 检查引号匹配问题
    if (strpos($line351, '"/workorders"){$workorder->id}') !== false) {
        echo "发现问题: 引号不匹配\n";
        
        // 修复引号问题
        $fixedLine = str_replace('"/workorders"){$workorder->id}', '"/workorders/{$workorder->id}")', $line351);
        echo "修复后: " . trim($fixedLine) . "\n";
        
        // 替换文件中的这一行
        $lines[351] = $fixedLine;
        $content = implode('', $lines);
        
        // 保存修复后的文件
        if (file_put_contents($controllerFile, $content)) {
            echo "✓ 成功修复语法错误\n";
            echo "✓ 文件已保存: $controllerFile\n";
        } else {
            echo "✗ 文件保存失败\n";
        }
    }
}

echo "\n=== 修复完成 ===\n";

?>