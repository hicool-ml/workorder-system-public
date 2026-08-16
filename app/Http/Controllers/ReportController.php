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
        // 报表首页「重点分类 Top10 子类分布」：动态遍历所有根分类，有数据才展示。
        // 不再硬编码「网络 / 多媒体」这类具体业务分类名，任何行业的分类体系均可适配。
        $featuredDistributions = [];
        foreach (WorkorderCategorySimplified::whereNull('parent_id')->orderBy('sort_order')->orderBy('name')->get() as $rootCat) {
            $sub = $this->getSubCategoryDistribution($rootCat->id, $rangeStart, $rangeEnd);
            if (empty($sub)) {
                continue;
            }
            $featuredDistributions[] = [
                'id'   => $rootCat->id,
                'name' => $rootCat->name,
                'data' => collect($sub)->map(fn ($i) => ['name' => $i['name'], 'count' => (int) $i['count']])->values()->all(),
            ];
        }
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
            'sourceDistribution', 'featuredDistributions', 'categoryTrend'
        ));
    }

    /**
     * 计算报告周期（起始日期 + 周期数 → 时间范围）
     */

    /**
     * 计算报表时间范围。
     *
     * 从请求参数读 start_date / end_date（默认最近 6 个月），
     * 按选定粒度（周/月/年）分段。
     */
    private function computeReportPeriods(Request $request)
    {
        $mode = $request->input('cat_mode', 'month');
        $startInput = $request->input('start_date');
        $endInput = $request->input('end_date');

        $rangeStart = $startInput
            ? \Carbon\Carbon::parse($startInput)->startOfDay()
            : now()->copy()->subMonths(5)->startOfMonth();
        $rangeEnd = $endInput
            ? \Carbon\Carbon::parse($endInput)->endOfDay()
            : now()->copy()->endOfDay();

        if ($rangeEnd < $rangeStart) {
            $rangeEnd = $rangeStart->copy()->endOfMonth();
        }

        // 按粒度分段
        $periods = [];
        $cursor = $rangeStart->copy();

        $addInterval = function (&$c) use ($mode) {
            switch ($mode) {
                case 'week':     $c->addWeek(); break;
                case 'month':    $c->addMonth(); break;
                case 'quarter':  $c->addMonths(3); break;
                case 'half':     $c->addMonths(6); break;
                case 'year':     $c->addYear(); break;
            }
        };

        while ($cursor <= $rangeEnd) {
            $segStart = $cursor->copy();
            switch ($mode) {
                case 'week':
                    $segEnd = $cursor->copy()->endOfWeek()->endOfDay();
                    break;
                case 'year':
                    $segEnd = $cursor->copy()->endOfYear()->endOfDay();
                    break;
                case 'quarter':
                    $segEnd = $cursor->copy()->addMonths(3)->subDay()->endOfDay();
                    break;
                case 'half':
                    $segEnd = $cursor->copy()->addMonths(6)->subDay()->endOfDay();
                    break;
                case 'month':
                default:
                    if ($rangeStart->day > 1 && $rangeStart->day <= 28) {
                        // 非自然月：从开始日期的号数开始算周期（如13号→每月13日至下月12日）
                        $segEnd = $cursor->copy()->addMonth()->subDay()->endOfDay();
                    } else {
                        // 自然月（1号开始或29-31号降级为自然月）
                        $segEnd = $cursor->copy()->endOfMonth()->endOfDay();
                    }
                    break;
            }
            if ($segStart < $rangeStart) $segStart = $rangeStart->copy();
            if ($segEnd > $rangeEnd) $segEnd = $rangeEnd->copy();

            $periods[] = [
                'label' => $segStart->format('m/d') . '-' . $segEnd->format('m/d'),
                'start' => $segStart,
                'end' => $segEnd,
            ];

            $addInterval($cursor);
            if (count($periods) > 120) break;
        }

        return [
            'periods' => $periods,
            'rangeStart' => $rangeStart,
            'rangeEnd' => $rangeEnd,
            'mode' => $mode,
            'startStr' => $rangeStart->format('Y-m-d'),
            'endStr' => $rangeEnd->format('Y-m-d'),
            'periodCount' => count($periods),
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
        $dateExpr = DB::getDriverName() === 'pgsql' ? 'created_at::date' : 'DATE(created_at)';
        $created = Workorder::selectRaw("{$dateExpr} as d, COUNT(*) as c")->whereBetween('created_at', [$rangeStart, $rangeEnd])->groupByRaw($dateExpr)->pluck('c', 'd');
        $byCat = [];
        foreach ($topCatNames as $rootId => $rootName) {
            $subIds = array_keys(array_filter($catToRoot, function($v) use ($rootId) { return $v == $rootId; }));
            if (!empty($subIds)) {
                $byCat[$rootId] = Workorder::selectRaw("{$dateExpr} as d, COUNT(*) as c")->whereBetween('created_at', [$rangeStart, $rangeEnd])->whereIn('category_id', $subIds)->groupByRaw($dateExpr)->pluck('c', 'd');
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
        // 单条 GROUP BY 聚合，替代逐状态 COUNT
        $q = Workorder::query();
        if ($rs && $re) $q->whereBetween('created_at', [$rs, $re]);
        $rows = $q->selectRaw('status, COUNT(*) as c')->groupBy('status')->pluck('c', 'status');

        $distribution = [];
        foreach (['pending', 'assigned', 'processing', 'resolved', 'verifying', 'closed', 'rejected'] as $status) {
            $distribution[$status] = $rows->get($status, 0);
        }
        return $distribution;
    }

    private function getCategoryDistribution($rs = null, $re = null)
    {
        $topCats = WorkorderCategorySimplified::whereNull('parent_id')->orderBy('sort_order')->orderBy('name')->get();
        // 一次载入全部分类构建 id→root 映射（分类表规模小），配合单条 GROUP BY 聚合
        $allCats = WorkorderCategorySimplified::select('id', 'parent_id')->get()->keyBy('id');
        $catToRoot = [];
        foreach ($allCats as $id => $cat) {
            $current = $id; $guard = 0;
            while ($allCats->has($current) && $allCats[$current]->parent_id && $guard++ < 10) { $current = $allCats[$current]->parent_id; }
            $catToRoot[$id] = $current;
        }

        $q = Workorder::query();
        if ($rs && $re) $q->whereBetween('created_at', [$rs, $re]);
        $counts = $q->selectRaw('category_id, COUNT(*) as c')->groupBy('category_id')->pluck('c', 'category_id');

        foreach ($topCats as $cat) {
            $total = 0;
            foreach ($catToRoot as $catId => $rootId) {
                if ($rootId == $cat->id) {
                    $total += $counts->get($catId, 0);
                }
            }
            $cat->workorders_count = $total;
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
        $rangeStart = $periodInfo['rangeStart'];
        $rangeEnd = $periodInfo['rangeEnd'];
        $allCats = WorkorderCategorySimplified::select('id', 'parent_id')->get()->keyBy('id');
        $catToRoot = [];
        foreach ($allCats as $id => $cat) {
            $current = $id; $guard = 0;
            while ($allCats->has($current) && $allCats[$current]->parent_id && $guard++ < 10) { $current = $allCats[$current]->parent_id; }
            $catToRoot[$id] = $current;
        }
        $topCats = WorkorderCategorySimplified::whereNull('parent_id')->orderBy('sort_order')->orderBy('name')->get();

        // 单条聚合取回 (category_id, 各周期计数)，替代 分类数×周期数 次 COUNT
        // 注意：每个 SUM 必须显式 AS 别名——PG 对重复列名会折叠覆盖；
        // 用 toBase() 避免 hydrate 成 Eloquent 模型（(array)$model 拿到的是模型内部结构而非列）
        $cases = [];
        $bindings = [];
        foreach ($periods as $i => $p) {
            $cases[] = "SUM(CASE WHEN created_at BETWEEN ? AND ? THEN 1 ELSE 0 END) AS p{$i}";
            $bindings[] = $p['start'];
            $bindings[] = $p['end'];
        }
        $selectRaw = 'category_id, ' . implode(', ', $cases);
        $rows = Workorder::whereBetween('created_at', [$rangeStart, $rangeEnd])
            ->selectRaw($selectRaw, $bindings)
            ->groupBy('category_id')
            ->toBase()
            ->get();

        // 组装 matrix: [catId => [period => count]]
        $periodKeys = [];
        foreach (array_keys($periods) as $i) {
            $periodKeys[] = "p{$i}";
        }
        $matrix = [];
        foreach ($rows as $row) {
            $matrix[$row->category_id] = array_map(fn ($k) => (int) $row->{$k}, $periodKeys);
        }

        $categories = [];
        foreach ($topCats as $cat) {
            $ids = array_merge([$cat->id], array_keys(array_filter($catToRoot, function ($v) use ($cat) { return $v == $cat->id; })));
            $counts = array_fill(0, count($periods), 0);
            foreach ($ids as $id) {
                if (isset($matrix[$id])) {
                    foreach ($matrix[$id] as $i => $c) {
                        $counts[$i] += (int) $c;
                    }
                }
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
            'startStr' => $periodInfo['startStr'], 'endStr' => $periodInfo['endStr'] ?? '',
        ];
    }

    private function getSubCategoryDistribution($rootId, $rs = null, $re = null)
    {
        $subs = WorkorderCategorySimplified::where('parent_id', $rootId)->orderBy('sort_order')->orderBy('name')->get(['id', 'name']);
        // 一次载入全部子分类树（分类表小），单条 GROUP BY 聚合，替代逐子分类 BFS 查询
        $allCats = WorkorderCategorySimplified::select('id', 'parent_id')->get()->keyBy('id');
        $q = Workorder::query();
        if ($rs && $re) $q->whereBetween('created_at', [$rs, $re]);
        $counts = $q->selectRaw('category_id, COUNT(*) as c')->groupBy('category_id')->pluck('c', 'category_id');

        $result = [];
        foreach ($subs as $sub) {
            // 收集该子分类的全部后代 id
            $descendantIds = [$sub->id];
            $queue = [$sub->id];
            while (!empty($queue)) {
                $next = [];
                foreach ($allCats as $catId => $cat) {
                    if (in_array($cat->parent_id, $queue)) {
                        $next[] = $catId;
                    }
                }
                $descendantIds = array_merge($descendantIds, $next);
                $queue = $next;
            }
            $cnt = 0;
            foreach ($descendantIds as $id) {
                $cnt += $counts->get($id, 0);
            }
            if ($cnt > 0) $result[] = ['name' => $sub->name, 'count' => $cnt];
        }
        usort($result, function ($a, $b) { return $b['count'] <=> $a['count']; });
        return array_slice($result, 0, 10);
    }

    private function getCampusStats($rs = null, $re = null)
    {
        // 工单已不再冗余 campus_id 列；通过 location_id 沿父链映射到「日常层级第一级（区域/园区）」节点
        $campusLevelId = \App\Models\LocationLevel::dailyLevelAt(0)?->id;
        if (! $campusLevelId) {
            return [];
        }

        // 拿到所有区域节点，构造其 id => name 映射 + 子孙 id 列表
        $campusNodes = DB::table('locations')
            ->where('level_id', $campusLevelId)
            ->where('status', 'active')
            ->orderBy('sort_order')->orderBy('name')
            ->get(['id', 'name']);
        if ($campusNodes->isEmpty()) {
            return [];
        }

        // 每个 campus 节点的子孙 id（含自身），用于把 workorder.location_id 映射回 campus
        $campusScope = []; // [campusId => [descendant ids...]]
        $locationToCampus = []; // [locationId => campusId]
        foreach ($campusNodes as $cn) {
            $descendants = array_merge([$cn->id], Location::getDescendantIds($cn->id));
            $campusScope[$cn->id] = $descendants;
            foreach ($descendants as $lid) {
                $locationToCampus[$lid] = $cn->id;
            }
        }

        $counts = [];
        foreach ($campusNodes as $cn) {
            $counts[$cn->id] = ['name' => $cn->name, 'total' => 0, 'pending' => 0, 'completed' => 0];
        }

        // 单条 GROUP BY (location_id, status) 聚合，替代全量行拉取 + PHP 分桶
        $q = Workorder::whereNotNull('location_id');
        if ($rs && $re) $q->whereBetween('created_at', [$rs, $re]);
        $rows = $q->selectRaw('location_id, status, COUNT(*) as c')->groupBy('location_id', 'status')->get();

        foreach ($rows as $row) {
            $campusId = $locationToCampus[$row->location_id] ?? null;
            if (! $campusId || ! isset($counts[$campusId])) continue;
            $c = (int) $row->c;
            $counts[$campusId]['total'] += $c;
            if (in_array($row->status, ['pending', 'assigned', 'processing'], true)) {
                $counts[$campusId]['pending'] += $c;
            } elseif (in_array($row->status, ['resolved', 'closed'], true)) {
                $counts[$campusId]['completed'] += $c;
            }
        }

        return $counts;
    }

    private function getBuildingName($locationId): string
    {
        if (! $locationId) return '';
        $l = Location::find($locationId);
        return $l ? $l->name : (string) $locationId;
    }

    private function getProcessingTimeStats($rs = null, $re = null)
    {
        $q = Workorder::whereIn('status', ['resolved', 'closed'])->whereNotNull('resolved_at')->whereNotNull('started_at');
        if ($rs && $re) $q->whereBetween('created_at', [$rs, $re]);
        $minuteDiff = DB::getDriverName() === 'pgsql'
            ? "EXTRACT(EPOCH FROM (resolved_at - started_at)) / 60"
            : (DB::getDriverName() === 'sqlite'
                ? "(julianday(resolved_at) - julianday(started_at)) * 24 * 60"
                : "TIMESTAMPDIFF(MINUTE, started_at, resolved_at)");
        $r = $q->selectRaw("COUNT(*) as total, AVG({$minuteDiff}) as avg_time, MIN({$minuteDiff}) as min_time, MAX({$minuteDiff}) as max_time")->first();
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
            'campus_id' => 'nullable|integer|exists:locations,id',
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

        // 构建查询（用 locationInfo 替代已 drop 的 campusInfo / building 关联）
        $query = Workorder::with(['creator', 'assignee', 'category.parent', 'department', 'collaborations.collaborator', 'visits', 'locationInfo'])
            ->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59']);

        // 应用筛选条件
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        // 区域筛选：campus_id 入参为 level=6 的 Location 节点 id，
        // 该区域下所有楼栋都是它的子节点，转译为 location_id IN（区域 + 子孙）
        if ($request->filled('campus_id')) {
            $campusLocationId = (int) $request->input('campus_id');
            $scope = array_merge([$campusLocationId], \App\Models\Location::getDescendantIds($campusLocationId));
            $query->whereIn('location_id', $scope);
        }

        $workorders = $query->get();

        // 预热地址映射（campus_name 等祖先链 accessor 零 SQL）+ 预取节点名
        \App\Models\Location::allNodesCached();
        $locationIds = $workorders->pluck('location_id')->filter()->unique();
        $locationNameMap = \App\Models\Location::whereIn('id', $locationIds)->get()->pluck('name', 'id');

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

        $callback = function() use ($workorders, $locationNameMap) {
            $file = fopen('php://output', 'w');
            
            // 添加BOM以支持中文
            fwrite($file, "\xEF\xBB\xBF");
            
            
            // CSV头部
            $headers = [
                '日期', '创建时间', '工单号', '类型（工单大类）', '故障分类', '问题描述',
                '报修人', '区域', '地点', '联系电话', '处理人', '处理方式',
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
                $hasVisit = $workorder->visits->isNotEmpty();
                if ($hasVisit) {
                    $visit = $workorder->visits->first();
                    $visitResult = $visit->satisfaction_score ? "满意度：{$visit->satisfaction_score}分" : '已回访';
                }
                
                // 获取工单大类（从分类的父级获取，已 eager load）
                $mainCategory = '';
                $subCategory = '';
                if ($workorder->category) {
                    $subCategory = $workorder->category->name;
                    $parentCategory = $workorder->category->parent;
                    if ($parentCategory) {
                        $mainCategory = $parentCategory->name;
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
                
                // 区域与地点：通过 location_id 沿父链 accessor 解析
                $campusName = $workorder->campus_name;
                $locationId = $workorder->location_id;
                $buildingName = ($locationId && isset($locationNameMap[$locationId]))
                    ? $locationNameMap[$locationId]
                    : $workorder->building_name;
                
                // 确保所有字段都是UTF-8编码
                $rowData = [
                    $workorder->created_at->format('Y-m-d'),
                    $workorder->created_at->format('H:i:s'),
                    $workorder->ticket_no,
                    $mainCategory,
                    $subCategory,
                    $workorder->description,
                    $workorder->contact_name,
                    $campusName,
                    $buildingName . ($workorder->location_detail ? ' - ' . $workorder->location_detail : ''),
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

                fputcsv($file, array_map([self::class, 'csvSafe'], $rowData));
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * CSV 公式注入防护：= + - @ 开头的单元格前加制表符，防止 Excel/Sheets 当公式执行
     */
    private static function csvSafe($value): string
    {
        $value = (string) $value;
        if ($value !== '' && in_array($value[0], ['=', '+', '-', '@'], true)) {
            return "\t" . $value;
        }
        return $value;
    }
}
