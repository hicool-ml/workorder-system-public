<?php

namespace App\Console\Commands;

use App\Models\Location;
use App\Models\Workorder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * 诊断历史工单地址迁移结果。
 *
 * 用法：
 *   php artisan workorders:audit-locations           # 输出汇总
 *   php artisan workorders:audit-locations --detail   # 列出每条异常工单
 *
 * 输出三类工单：
 *   - clean         location_id 指向正常楼栋节点
 *   - unclassified  location_id 指向"未分类/未分类校区"子树（building 文本楼名匹配失败被收容）
 *   - null          location_id 为空（旧工单既无 building 也无 campus_id）
 */
class AuditWorkorderLocations extends Command
{
    protected $signature = 'workorders:audit-locations
                            {--detail : 列出每条异常工单，而不只是汇总}';

    protected $description = '诊断历史工单的 location_id 迁移结果，输出汇总与异常列表';

    public function handle(): int
    {
        $this->info('扫描 workorders.location_id 迁移结果...');
        $this->newLine();

        $total = Workorder::count();
        $nullCount = Workorder::whereNull('location_id')->count();

        // 找到所有"未分类" / "未分类校区"节点
        $uncategorizedRootIds = Location::query()
            ->whereIn('name', ['未分类', '未分类校区'])
            ->pluck('id')
            ->all();

        $unclassifiedScope = [];
        foreach ($uncategorizedRootIds as $id) {
            $unclassifiedScope = array_merge($unclassifiedScope, [$id], Location::getDescendantIds($id));
        }
        $unclassifiedScope = array_unique($unclassifiedScope);

        $unclassifiedCount = empty($unclassifiedScope)
            ? 0
            : Workorder::whereIn('location_id', $unclassifiedScope)->count();

        $cleanCount = $total - $nullCount - $unclassifiedCount;

        $this->table(['类别', '数量', '占比', '说明'], [
            ['clean（正常）', $cleanCount, $this->pct($cleanCount, $total), 'location_id 指向正常楼栋节点'],
            ['unclassified（被收容）', $unclassifiedCount, $this->pct($unclassifiedCount, $total), '指向"未分类"或"未分类校区"子树下的节点（building 文本楼名匹配失败）'],
            ['null（无地址）', $nullCount, $this->pct($nullCount, $total), 'location_id 为空（旧工单既无 building 也无 campus_id）'],
            ['总计', $total, '100%', ''],
        ]);

        if (empty($uncategorizedRootIds)) {
            $this->newLine();
            $this->info('未发现"未分类"或"未分类校区"节点 → 没有工单被收容，迁移结果应该全部是 clean/null。');
        }

        if ($this->option('detail') && ($nullCount > 0 || $unclassifiedCount > 0)) {
            $this->newLine();
            $this->warn('--- 异常工单明细（最多 100 条）---');

            $query = Workorder::query()
                ->where(function ($q) use ($unclassifiedScope) {
                    $q->whereNull('location_id');
                    if (! empty($unclassifiedScope)) {
                        $q->orWhereIn('location_id', $unclassifiedScope);
                    }
                })
                ->orderByDesc('created_at')
                ->limit(100)
                ->get(['id', 'ticket_no', 'contact_name', 'location_id', 'created_at']);

            $rows = $query->map(function (Workorder $w) {
                $loc = $w->locationInfo?->full_address_delimited ?? '(无)';
                return [
                    'id' => $w->id,
                    'ticket_no' => $w->ticket_no,
                    'contact' => $w->contact_name,
                    'location_id' => $w->location_id ?? 'NULL',
                    'resolved_path' => $loc,
                    'created_at' => $w->created_at?->format('Y-m-d H:i'),
                ];
            })->toArray();

            $this->table(['ID', '工单号', '联系人', 'location_id', '解析路径', '创建时间'], $rows);

            if ($nullCount + $unclassifiedCount > 100) {
                $this->warn('（仅显示前 100 条，请用页面筛选或 SQL 查询完整列表）');
            }
        }

        return self::SUCCESS;
    }

    private function pct(int $n, int $total): string
    {
        if ($total === 0) {
            return '0%';
        }
        return round($n / $total * 100, 1) . '%';
    }
}
