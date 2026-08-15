<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

function moveTickets(int $from, int $to, ?string $like = null): int {
    $q = DB::table('workorders')->where('category_id', $from);
    if ($like !== null) $q->where('description', 'like', "%{$like}%");
    $n = $q->update(['category_id' => $to]);
    DB::table('workorder_templates')->where('category_id', $from)->update(['category_id' => $to]);
    return $n;
}

DB::beginTransaction();
try {
    // ===== 1. #94 物联网(网络) 按内容拆分 =====
    echo "[#94 物联网拆分]" . PHP_EOL;
    echo "  动环 -> 专项#25: " . moveTickets(94, 25, '动环') . " 单" . PHP_EOL;
    echo "  班牌 -> 多媒体#95: " . moveTickets(94, 95, '班牌') . " 单" . PHP_EOL;
    echo "  录播 -> 多媒体#96: " . moveTickets(94, 96, '录播') . " 单" . PHP_EOL;
    echo "  其余(真物联网) -> 专项#77: " . moveTickets(94, 77) . " 单" . PHP_EOL;
    DB::table('workorder_categories_simplified')->where('id', 94)->delete();

    // ===== 2. #35 网络故障(多媒体) 整体并入 #93，#93 更名 =====
    echo "[#35 -> #93]" . PHP_EOL;
    echo "  迁移: " . moveTickets(35, 93) . " 单" . PHP_EOL;
    DB::table('workorder_categories_simplified')->where('id', 35)->delete();
    DB::table('workorder_categories_simplified')->where('id', 93)->update(['name' => '上网故障']);
    echo "  #93 更名「上网故障」" . PHP_EOL;

    // ===== 3. #74 电脑故障(其它) 逐单拆分 =====
    echo "[#74 电脑故障拆分]" . PHP_EOL;
    echo "  咨询类 -> 网络#92: " . moveTickets(74, 92, '咨询') . " 单" . PHP_EOL;
    echo "  电脑类 -> 多媒体#14: " . moveTickets(74, 14) . " 单" . PHP_EOL;
    DB::table('workorder_categories_simplified')->where('id', 74)->delete();

    // ===== 4. #64 机房异响(实为人脸识别) -> 专项#77 =====
    echo "[#64 -> 专项#77]: " . moveTickets(64, 77) . " 单" . PHP_EOL;
    DB::table('workorder_categories_simplified')->where('id', 64)->delete();

    // ===== 5. #65 监控故障 改挂专项 =====
    DB::table('workorder_categories_simplified')->where('id', 65)->update(['parent_id' => 3]);
    echo "[#65 监控故障 改挂专项]" . PHP_EOL;

    // ===== 6. 网络 咨询类碎类合并进 #92 电话咨询 =====
    foreach ([46, 48, 49, 50, 51, 53] as $id) {
        $n = moveTickets($id, 92);
        DB::table('workorder_categories_simplified')->where('id', $id)->delete();
        echo "[#{$id} 咨询类 -> #92 电话咨询]: {$n} 单，已删除" . PHP_EOL;
    }

    // ===== 7. 删除空类 #61 系统迁移 =====
    $cnt = DB::table('workorders')->where('category_id', 61)->count();
    if ($cnt === 0) {
        DB::table('workorder_categories_simplified')->where('id', 61)->delete();
        echo "[#61 系统迁移 空类已删除]" . PHP_EOL;
    }

    // ===== 校验 =====
    $orphan = DB::table('workorders')->whereNotNull('category_id')
        ->whereNotIn('category_id', DB::table('workorder_categories_simplified')->pluck('id'))->count();
    if ($orphan > 0) throw new Exception("孤儿引用 {$orphan}，中止");
    $tplOrphan = DB::table('workorder_templates')->whereNotNull('category_id')
        ->whereNotIn('category_id', DB::table('workorder_categories_simplified')->pluck('id'))->count();
    echo PHP_EOL . "孤儿工单=0，孤儿模板={$tplOrphan}" . PHP_EOL;

    echo PHP_EOL . "=== 最终结构 ===" . PHP_EOL;
    foreach (DB::table('workorder_categories_simplified')->whereNull('parent_id')->orderBy('id')->get() as $top) {
        $subIds = DB::table('workorder_categories_simplified')->where('parent_id', $top->id)->pluck('id')->push($top->id);
        $woCnt = DB::table('workorders')->whereIn('category_id', $subIds)->count();
        $subs = DB::table('workorder_categories_simplified')->where('parent_id', $top->id)
            ->orderBy('name')->get();
        echo sprintf("#%d %s（子类 %d 个，工单 %d）", $top->id, $top->name, $subs->count(), $woCnt) . PHP_EOL;
        foreach ($subs as $s) {
            $c = DB::table('workorders')->where('category_id', $s->id)->count();
            echo "    - {$s->name} ({$c})" . PHP_EOL;
        }
    }

    DB::commit();
    echo PHP_EOL . "✔ 提交成功" . PHP_EOL;
} catch (Exception $e) {
    DB::rollBack();
    echo "✘ 回滚：" . $e->getMessage() . PHP_EOL;
}
