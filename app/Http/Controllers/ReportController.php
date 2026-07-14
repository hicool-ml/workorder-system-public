<?php

namespace App\Http\Controllers;

use App\Models\Workorder;
use App\Models\User;
use App\Models\Department;
use App\Models\WorkorderType;
use App\Models\WorkorderCategorySimplified;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    /**
     * 统计报表页面（周期筛选驱动所有图表）
     */
    public function index(Request $request)
    {
        $periodInfo = $this->computeReportPeriods($request);
        $rangeStart = $periodInfo['rangeStart'];
        $rangeEnd = $periodInfo['rangeEnd'];

        $rangeTotal = Workorder::whereBetween('created_at', [$rangeStart, $rangeEnd])->count();
        $stats = [
            'total_workorders' => $rangeTotal,
            'pending_workorders' => Workorder::whereIn('status', ['pending', 'assigned', 'processing'])->whereBetween('created_at', [$rangeStart, $rangeEnd])->count(),
            'completed_workorders' => Workorder::whereIn('status', ['resolved', 'closed'])->whereBetween('created_at', [$rangeStart, $rangeEnd])->count(),
            'total_users' => User::count(),
            'total_departments' => Department::count(),
            'total_categories' => WorkorderCategorySimplified::count(),
            'overdue_workorders' => Workorder::whereNotNull('expected_complete_at')->where('expected_complete_at', '<', now())->whereNotIn('status', ['closed', 'resolved'])->whereBetween('created_at', [$rangeStart, $rangeEnd])->count(),
            'emergency_workorders' => Workorder::where('is_emergency', true)->whereNotIn('status', ['closed', 'resolved'])->whereBetween('created_at', [$rangeStart, $rangeEnd])->count(),
            'range_new' => $rangeTotal,
            'range_resolved' => Workorder::whereBetween('resolved_at', [$rangeStart, $rangeEnd])->count(),
            'completion_rate' => round(Workorder::whereIn('status', ['resolved', 'closed'])->whereBetween('created_at', [$rangeStart, $rangeEnd])->count() / max($rangeTotal, 1) * 100, 1),
        ];

        $days = max($rangeStart->diffInDays($rangeEnd) + 1, 1);
        $recentStats = $this->getRecentStats($days, $rangeStart, $rangeEnd);
        $networkSubDistribution = $this->getSubCategoryDistribution(1, $rangeStart, $rangeEnd);
        $mediaSubDistribution = $this->getSubCategoryDistribution(2, $rangeStart, $rangeEnd);
        $sourceDistribution = $this->getSourceDistribution($rangeStart, $rangeEnd);
        $statusDistribution = $this->getStatusDistribution($rangeStart, $rangeEnd);
        $categoryDistribution = $this->getCategoryDistribution($rangeStart, $rangeEnd);
        $campusStats = $this->getCampusStats($rangeStart, $rangeEnd);
        $engineerStats = $this->getEngineerStats($rangeStart, $rangeEnd);
        $processingTimeStats = $this->getProcessingTimeStats($rangeStart, $rangeEnd);
        $satisfactionStats = $this->getSatisfactionStats($rangeStart, $rangeEnd);
        $categoryTrend = $this->getCategoryTrendByPeriod($request);

        return view('reports.index', compact(
            'stats', 'recentStats', 'statusDistribution', 'categoryDistribution',
            'campusStats', 'engineerStats', 'processingTimeStats', 'satisfactionStats',
            'sourceDistribution', 'networkSubDistribution', 'mediaSubDistribution', 'categoryTrend'
        ));
    }

    /**
     * 计算报告周期（起始日期 + 周期数 → 时间范围）
     */

    /**
     * 计算报告周期（起始日期 + 周期数 → 时间范围）
     * 自定义模式下，周期起始日从起始日期的天数自动推导，无需额外输入
     */
    private function computeReportPeriods(Request $request)
    {
        $mode = $request->input('cat_mode', 'custom');
        $periodCount = max(1, min((int) $request->input('cat_periods', 6), 24));
        $startInput = $request->input('cat_start');

        // 周期起始日从起始日期的天数推导（如4月13日 → 每月13日起）
        if ($startInput) {
            $cycleDay = \Carbon\Carbon::parse($startInput)->day;
        } else {
            $cycleDay = now()->day;
        }
        $cycleDay = max(1, min($cycleDay, 28));

        if ($startInput) {
            $probeStart = \Carbon\Carbon::parse($startInput)->startOfDay();
            if ($mode === 'natural') $probeStart = $probeStart->copy()->startOfMonth();
            for ($pc = $periodCount; $pc >= 1; $pc--) {
                $lastEnd = $probeStart->copy()->addMonths($pc - 1);
                $lastEnd = ($mode === 'custom') ? $lastEnd->copy()->addMonth()->subDay()->endOfDay() : $lastEnd->copy()->endOfMonth()->endOfDay();
                if ($lastEnd <= now()->endOfDay()) { $periodCount = $pc; break; }
            }
        }

        if ($startInput) {
            $firstStart = \Carbon\Carbon::parse($startInput)->startOfDay();
            if ($mode === 'natural') $firstStart = $firstStart->copy()->startOfMonth();
        } else {
            $today = now();
            if ($mode === 'custom') {
                if ($today->day >= $cycleDay) {
                    $latestCompleted = $today->copy()->startOfMonth()->subMonth()->addDays($cycleDay - 1)->startOfDay();
                } else {
                    $latestCompleted = $today->copy()->startOfMonth()->subMonths(2)->addDays($cycleDay - 1)->startOfDay();
                }
                $firstStart = $latestCompleted->subMonths($periodCount - 1);
            } else {
                $firstStart = $today->copy()->startOfMonth()->subMonths($periodCount - 1);
            }
        }

        $periods = [];
        for ($i = 0; $i < $periodCount; $i++) {
            $start = $firstStart->copy()->addMonths($i);
            if ($mode === 'custom') {
                $end = $start->copy()->addMonth()->subDay()->endOfDay();
                $periods[] = ['label' => $start->format('m/d') . '-' . $end->format('m/d'), 'start' => $start, 'end' => $end];
            } else {
                $periods[] = ['label' => $start->format('m/d') . '-' . $start->copy()->endOfMonth()->format('m/d'), 'start' => $start->copy()->startOfDay(), 'end' => $start->copy()->endOfMonth()->endOfDay()];
            }
        }

        return [
            'periods' => $periods,
            'rangeStart' => $periods[0]['start'],
            'rangeEnd' => $periods[count($periods) - 1]['end'],
            'mode' => $mode, 'periodCount' => $periodCount, 'cycleDay' => $cycleDay,
            'startStr' => $firstStart->format('Y-m-d'),
        ];
    }
    /**
     * 获取最近N天的统计
     */
    private function getRecentStats($days = 7, $rangeStart = null, $rangeEnd = null)
    {
        $rangeStart = $rangeStart ?? now()->subDays($days - 1)->startOfDay();
        $rangeEnd = $rangeEnd ?? now()->endOfDay();
        $allCats = WorkorderCategorySimplified::select('id', 'parent_id')->get()->keyBy('id');
        $catToRoot = [];
        foreach ($allCats as $id => $cat) {
            $current = $id; $guard = 0;
            while ($allCats->has($current) && $allCats[$current]->parent_id && $guard++ < 10) { $current = $allCats[$current]->parent_id; }
            $catToRoot[$id] = $current;
        }
        $topCats = WorkorderCategorySimplified::whereNull('parent_id')->orderBy('sort_order')->orderBy('name')->get();
        $topCatNames = $topCats->pluck('name', 'id')->toArray();
        $created = Workorder::selectRaw("DATE(created_at) as d, COUNT(*) as c")->whereBetween('created_at', [$rangeStart, $rangeEnd])->groupByRaw("DATE(created_at)")->pluck('c', 'd');
        $byCat = [];
        foreach ($topCatNames as $rootId => $rootName) {
            $subIds = array_keys(array_filter($catToRoot, function($v) use ($rootId) { return $v == $rootId; }));
            if (!empty($subIds)) {
                $byCat[$rootId] = Workorder::selectRaw("DATE(created_at) as d, COUNT(*) as c")->whereBetween('created_at', [$rangeStart, $rangeEnd])->whereIn('category_id', $subIds)->groupByRaw("DATE(created_at)")->pluck('c', 'd');
            }
        }
        $stats = [];
        $cursor = $rangeStart->copy();
        while ($cursor <= $rangeEnd) {
            $date = $cursor->format('Y-m-d');
            $row = ['date' => $date, 'display_date' => $cursor->format('m-d'), 'total' => $created->get($date, 0)];
            foreach ($topCatNames as $rootId => $rootName) { $row['cat_' . $rootId] = isset($byCat[$rootId]) ? $byCat[$rootId]->get($date, 0) : 0; }
            $stats[$date] = $row;
            $cursor->addDay();
        }
        return ['stats' => $stats, 'topCats' => $topCatNames];
    }

    private function getStatusDistribution($rs = null, $re = null)
    {
        $distribution = [];
        foreach (['pending', 'assigned', 'processing', 'resolved', 'verifying', 'closed', 'rejected'] as $status) {
            $q = Workorder::where('status', $status);
            if ($rs && $re) $q->whereBetween('created_at', [$rs, $re]);
            $distribution[$status] = $q->count();
        }
        return $distribution;
    }

    private function getCategoryDistribution($rs = null, $re = null)
    {
        $topCats = WorkorderCategorySimplified::whereNull('parent_id')->orderBy('sort_order')->orderBy('name')->get();
        $allDescendantIds = function($parentId) use (&$allDescendantIds) {
            $ids = WorkorderCategorySimplified::where('parent_id', $parentId)->pluck('id')->toArray();
            $result = $ids;
            foreach ($ids as $id) { $result = array_merge($result, $allDescendantIds($id)); }
            return $result;
        };
        foreach ($topCats as $cat) {
            $allIds = array_merge([$cat->id], $allDescendantIds($cat->id));
            $q = Workorder::whereIn('category_id', $allIds);
            if ($rs && $re) $q->whereBetween('created_at', [$rs, $re]);
            $cat->workorders_count = $q->count();
        }
        return $topCats->sortByDesc('workorders_count')->values();
    }

    /**
     * 获取一级分类按周期趋势（百分比堆积，固定排序：网络→多媒体→专项→其它）
     */
    private function getCategoryTrendByPeriod(Request $request)
    {
        $periodInfo = $this->computeReportPeriods($request);
        $periods = $periodInfo['periods'];
        $allCats = WorkorderCategorySimplified::select('id', 'parent_id')->get()->keyBy('id');
        $catToRoot = [];
        foreach ($allCats as $id => $cat) {
            $current = $id; $guard = 0;
            while ($allCats->has($current) && $allCats[$current]->parent_id && $guard++ < 10) { $current = $allCats[$current]->parent_id; }
            $catToRoot[$id] = $current;
        }
        $topCats = WorkorderCategorySimplified::whereNull('parent_id')->orderBy('sort_order')->orderBy('name')->get();
        $catOrder = ['网络' => 1, '多媒体' => 2, '专项' => 3, '其它' => 4];
        $topCats = $topCats->sortBy(function ($c) use ($catOrder) { return $catOrder[$c->name] ?? 99; })->values();

        $categories = [];
        foreach ($topCats as $cat) {
            $allIds = array_merge([$cat->id], array_keys(array_filter($catToRoot, function ($v) use ($cat) { return $v == $cat->id; })));
            $counts = [];
            foreach ($periods as $p) {
                $counts[] = Workorder::whereIn('category_id', $allIds)->whereBetween('created_at', [$p['start'], $p['end']])->count();
            }
            $categories[] = ['name' => $cat->name, 'counts' => $counts];
        }
        foreach ($categories as &$info) { $info['counts'][] = array_sum($info['counts']); }
        unset($info);
        $periodLabels = array_map(function ($p) { return $p['label']; }, $periods);
        $periodLabels[] = '汇总';
        $numCols = count($periodLabels);
        $colTotals = array_fill(0, $numCols, 0);
        foreach ($categories as $info) { for ($c = 0; $c < $numCols; $c++) { $colTotals[$c] += $info['counts'][$c]; } }
        foreach ($categories as &$info) {
            $percents = [];
            for ($c = 0; $c < $numCols; $c++) { $percents[] = $colTotals[$c] > 0 ? round($info['counts'][$c] / $colTotals[$c] * 100, 1) : 0; }
            $info['percents'] = $percents;
        }
        unset($info);
        return [
            'periodLabels' => $periodLabels, 'categories' => $categories,
            'mode' => $periodInfo['mode'], 'periodCount' => $periodInfo['periodCount'],
            'cycleDay' => $periodInfo['cycleDay'], 'startStr' => $periodInfo['startStr'],
        ];
    }

    private function getSubCategoryDistribution($rootId, $rs = null, $re = null)
    {
        $subs = WorkorderCategorySimplified::where('parent_id', $rootId)->orderBy('sort_order')->orderBy('name')->get(['id', 'name']);
        $result = [];
        foreach ($subs as $sub) {
            $descendantIds = [$sub->id]; $queue = [$sub->id];
            while (!empty($queue)) { $children = WorkorderCategorySimplified::whereIn('parent_id', $queue)->pluck('id')->toArray(); $descendantIds = array_merge($descendantIds, $children); $queue = $children; }
            $q = Workorder::whereIn('category_id', $descendantIds);
            if ($rs && $re) $q->whereBetween('created_at', [$rs, $re]);
            $cnt = $q->count();
            if ($cnt > 0) $result[] = ['name' => $sub->name, 'count' => $cnt];
        }
        usort($result, function ($a, $b) { return $b['count'] <=> $a['count']; });
        return array_slice($result, 0, 10);
    }

    private function getCampusStats($rs = null, $re = null)
    {
        return \App\Models\Campus::leftJoin('workorders', 'campuses.id', '=', 'workorders.campus_id')
            ->whereNull('workorders.deleted_at')
            ->when($rs && $re, function ($q) use ($rs, $re) { $q->whereBetween('workorders.created_at', [$rs, $re]); })
            ->selectRaw("campuses.id, campuses.name, COUNT(workorders.id) as total, SUM(CASE WHEN workorders.status IN ('pending','assigned','processing') THEN 1 ELSE 0 END) as pending, SUM(CASE WHEN workorders.status IN ('resolved','closed') THEN 1 ELSE 0 END) as completed")
            ->groupBy('campuses.id', 'campuses.name')->orderBy('campuses.sort_order')->orderBy('campuses.name')
            ->get()->keyBy('id')->map(function($item) { return ['name' => $item->name, 'total' => (int)$item->total, 'pending' => (int)$item->pending, 'completed' => (int)$item->completed]; })->toArray();
    }

    private function getCampusName($campus) { $m = \App\Models\Campus::find($campus); return $m ? $m->name : $campus; }

    private function getBuildingName($buildingId): string { if (!$buildingId) return ''; $l = Location::find($buildingId); return $l ? $l->name : $buildingId; }

    private function getProcessingTimeStats($rs = null, $re = null)
    {
        $q = Workorder::whereIn('status', ['resolved', 'closed'])->whereNotNull('resolved_at')->whereNotNull('started_at');
        if ($rs && $re) $q->whereBetween('created_at', [$rs, $re]);
        $r = $q->selectRaw("COUNT(*) as total, AVG(TIMESTAMPDIFF(MINUTE, started_at, resolved_at)) as avg_time, MIN(TIMESTAMPDIFF(MINUTE, started_at, resolved_at)) as min_time, MAX(TIMESTAMPDIFF(MINUTE, started_at, resolved_at)) as max_time")->first();
        if (!$r || $r->total == 0) return ['average_time' => 0, 'min_time' => 0, 'max_time' => 0, 'total_completed' => 0];
        return ['average_time' => round($r->avg_time), 'min_time' => (int)$r->min_time, 'max_time' => (int)$r->max_time, 'total_completed' => (int)$r->total];
    }

    private function getSatisfactionStats($rs = null, $re = null)
    {
        $q = \App\Models\WorkorderVisit::with('workorder')->whereNotNull('satisfaction_score');
        if ($rs && $re) { $q->whereHas('workorder', function ($sq) use ($rs, $re) { $sq->whereBetween('created_at', [$rs, $re]); }); }
        $visits = $q->get();
        if ($visits->isEmpty()) return ['average_score' => 0, 'total_visits' => 0, 'distribution' => []];
        $scores = $visits->pluck('satisfaction_score')->toArray();
        $distribution = ['5' => 0, '4' => 0, '3' => 0, '2' => 0, '1' => 0];
        foreach ($scores as $score) { if (isset($distribution[$score])) $distribution[$score]++; }
        return ['average_score' => round(array_sum($scores) / count($scores), 2), 'total_visits' => count($scores), 'distribution' => $distribution];
    }

    private function getSourceDistribution($rs = null, $re = null)
    {
        $q = Workorder::selectRaw("COALESCE(NULLIF(source,''),'unknown') as src, COUNT(*) as cnt");
        if ($rs && $re) $q->whereBetween('created_at', [$rs, $re]);
        $raw = $q->groupByRaw("COALESCE(NULLIF(source,''),'unknown')")->pluck('cnt', 'src');
        $map = ['phone' => '电话', '电话报修' => '电话', 'web' => '网络', '在线平台' => '网络', 'scene' => '现场', '现场报修' => '现场', 'email' => '邮件', 'other' => '其他', '其他来源' => '其他', '巡检发现' => '巡检'];
        $result = ['电话' => 0, '网络' => 0, '现场' => 0, '邮件' => 0, '其他' => 0, '巡检' => 0];
        foreach ($raw as $src => $cnt) { $label = $map[$src] ?? '其他'; $result[$label] = ($result[$label] ?? 0) + $cnt; }
        return array_filter($result, function($v) { return $v > 0; });
    }

    private function getEngineerStats($rs = null, $re = null)
    {
        return User::whereIn('role', ['admin', 'workorder_manager', 'engineer'])
            ->withCount(['assignedWorkorders' => function($query) use ($rs, $re) { if ($rs && $re) $query->whereBetween('created_at', [$rs, $re]); }])
            ->withCount(['assignedWorkorders as pending_workorders_count' => function($query) use ($rs, $re) { $query->whereIn('status', ['pending', 'assigned', 'processing']); if ($rs && $re) $query->whereBetween('created_at', [$rs, $re]); }])
            ->withCount(['assignedWorkorders as completed_workorders_count' => function($query) use ($rs, $re) { $query->whereIn('status', ['resolved', 'closed']); if ($rs && $re) $query->whereBetween('created_at', [$rs, $re]); }])
            ->orderBy('completed_workorders_count', 'desc')->limit(10)->get();
    }

/**
     * 导出工单报表
     */
    public function export(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'format' => 'required|in:xlsx,csv',
            'status' => 'nullable|string',
            'category_id' => 'nullable|integer',
            'campus_id' => 'nullable|integer',
        ]);

        $startDate = $request->input('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->format('Y-m-d'));
        
        // 如果用户提供了更长的时间范围，则使用用户指定的时间
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
            
            // 验证时间范围不超过11个月
            $maxStartDate = now()->subMonths(11)->format('Y-m-d');
            if ($startDate < $maxStartDate) {
                $startDate = $maxStartDate;
            }
        }
        
        $format = $request->input('format');

        // 构建查询
        $query = Workorder::with(['creator', 'assignee', 'category', 'department', 'collaborations.collaborator'])
            ->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59']);

        // 应用筛选条件
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        if ($request->filled('campus_id')) {
            $query->where('campus_id', $request->input('campus_id'));
        }

        $workorders = $query->get();

        if ($format === 'xlsx') {
            // 导出Excel格式 - 使用CSV格式但设置Excel的MIME类型
            $filename = 'workorders_' . date('Y-m-d') . '.csv';
            $headers = [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ];
        } else {
            // 导出CSV格式
            $filename = 'workorders_' . date('Y-m-d') . '.csv';
            $headers = [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ];
        }

        $callback = function() use ($workorders) {
            $file = fopen('php://output', 'w');
            
            // 添加BOM以支持中文
            fwrite($file, "\xEF\xBB\xBF");
            
            
            // CSV头部
            $headers = [
                '日期', '创建时间', '工单号', '类型（工单大类）', '故障分类', '问题描述',
                '报修人', '校区', '地点', '联系电话', '处理人', '处理方式',
                '处理时长', '解决方案', '备件耗材使用', '备注', '是否回访', '回访结果'
            ];
            
            fputcsv($file, $headers);
            
            // CSV数据
            foreach ($workorders as $workorder) {
                // 计算处理时长，格式 hh:mm
                $processingDuration = '';
                if ($workorder->resolved_at) {
                    $mins = (int)$workorder->created_at->diffInMinutes($workorder->resolved_at);
                    $processingDuration = sprintf('%02d:%02d', intdiv($mins, 60), $mins % 60);
                }
                
                // 获取回访信息
                $visitResult = '';
                $hasVisit = $workorder->visits()->exists();
                if ($hasVisit) {
                    $visit = $workorder->visits()->first();
                    $visitResult = $visit->satisfaction_score ? "满意度：{$visit->satisfaction_score}分" : '已回访';
                }
                
                // 获取工单大类（从分类的父级获取）
                $mainCategory = '';
                $subCategory = '';
                if ($workorder->category) {
                    $subCategory = $workorder->category->name;
                    if ($workorder->category->parent_id) {
                        $parentCategory = \App\Models\WorkorderCategorySimplified::find($workorder->category->parent_id);
                        if ($parentCategory) {
                            $mainCategory = $parentCategory->name;
                        }
                    } else {
                        $mainCategory = $subCategory;
                    }
                }
                
                // 获取所有处理人（负责人 + 协助人）
                $processors = [];
                if ($workorder->assignee) {
                    $processors[] = $workorder->assignee->name;
                }
                
                // 添加已接受的协作人
                foreach ($workorder->collaborations as $collaboration) {
                    if ($collaboration->status === 'accepted' && $collaboration->collaborator) {
                        $processors[] = $collaboration->collaborator->name;
                    }
                }
                
                // 用顿号连接所有处理人
                $processorsText = implode('、', array_unique($processors));
                
                // 确保所有字段都是UTF-8编码
                $rowData = [
                    $workorder->created_at->format('Y-m-d'),
                    $workorder->created_at->format('H:i:s'),
                    $workorder->ticket_no,
                    $mainCategory,
                    $subCategory,
                    $workorder->description,
                    $workorder->contact_name,
                    $this->getCampusName($workorder->campus_id),
                    $this->getBuildingName($workorder->building) . ($workorder->location_detail ? ' - ' . $workorder->location_detail : ''),
                    $workorder->contact_phone,
                    $processorsText,
                    $workorder->phone_assisted ? '电话协助' : '现场处理',
                    $processingDuration,
                   $workorder->solution ?? '',
                    $workorder->materials_usage === '无备件耗材使用' ? '无' : ($workorder->materials_usage ?? ''),
                   $workorder->remarks ?? '',
                    $hasVisit ? '是' : '否',
                    $visitResult
                ];
                
                fputcsv($file, $rowData);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
