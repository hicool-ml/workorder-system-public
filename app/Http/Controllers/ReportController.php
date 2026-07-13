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
     * 统计报表页面
     */
    public function index(Request $request)
    {
       // 获取时间范围
        $dateRange = $request->input('date_range', '7days');
        $customFrom = $request->input('custom_from');
        $customTo = $request->input('custom_to');
        $rangeEnd = now()->endOfDay();

        if ($dateRange === 'custom' && $customFrom) {
            $rangeStart = \Carbon\Carbon::parse($customFrom)->startOfDay();
            if ($customTo) { $rangeEnd = \Carbon\Carbon::parse($customTo)->endOfDay(); }
            $days = max($rangeStart->diffInDays($rangeEnd) + 1, 1);
        } else {
            $days = $dateRange === '30days' ? 30 : ($dateRange === '90days' ? 90 : 7);
            $rangeStart = now()->subDays($days - 1)->startOfDay();
        }

        // 统计概览
        $stats = [
            'total_workorders' => Workorder::count(),
            'pending_workorders' => Workorder::whereIn('status', ['pending', 'assigned', 'processing'])->count(),
            'completed_workorders' => Workorder::whereIn('status', ['resolved', 'closed'])->count(),
            'total_users' => User::count(),
            'total_departments' => Department::count(),
            'total_categories' => WorkorderCategorySimplified::count(),
            'overdue_workorders' => Workorder::whereNotNull('expected_complete_at')
                ->where('expected_complete_at', '<', now())
                ->whereNotIn('status', ['closed', 'resolved'])
                ->count(),
            'emergency_workorders' => Workorder::where('is_emergency', true)
                ->whereNotIn('status', ['closed', 'resolved'])
                ->count(),
            'range_new' => Workorder::whereBetween('created_at', [$rangeStart, $rangeEnd])->count(),
            'range_resolved' => Workorder::whereBetween('resolved_at', [$rangeStart, $rangeEnd])->count(),
            'completion_rate' => round(Workorder::whereIn('status', ['resolved', 'closed'])->count() / max(Workorder::count(), 1) * 100, 1),
        ];

        // 获取工单趋势统计
        $recentStats = $this->getRecentStats($days, $rangeStart, $rangeEnd);

        // 获取来源分布和优先级分布
        $sourceDistribution = $this->getSourceDistribution();
        $priorityDistribution = $this->getPriorityDistribution();

        // 获取工单状态分布
        $statusDistribution = $this->getStatusDistribution();

        // 获取工单分类分布
        $categoryDistribution = $this->getCategoryDistribution();

        // 获取校区工单统计
        $campusStats = $this->getCampusStats();

        // 获取工程师处理统计
        $engineerStats = $this->getEngineerStats();

        // 获取处理时长统计
        $processingTimeStats = $this->getProcessingTimeStats();

        // 获取满意度统计
        $satisfactionStats = $this->getSatisfactionStats();

        return view('reports.index', compact(
            'stats',
            'recentStats',
            'statusDistribution',
            'categoryDistribution',
            'campusStats',
            'engineerStats',
            'processingTimeStats',
           'satisfactionStats',
            'dateRange',
            'sourceDistribution',
            'priorityDistribution'
        ));
    }

    /**
     * 获取最近N天的统计
     */
    private function getRecentStats($days = 7, $rangeStart = null, $rangeEnd = null)
    {
        $rangeStart = $rangeStart ?? now()->subDays($days - 1)->startOfDay();
        $rangeEnd = $rangeEnd ?? now()->endOfDay();

        // 构建 category_id → 一级分类ID 的映射
        $allCats = WorkorderCategorySimplified::select('id', 'parent_id')->get()->keyBy('id');
        $catToRoot = [];
        foreach ($allCats as $id => $cat) {
            $current = $id;
            $guard = 0;
            while ($allCats->has($current) && $allCats[$current]->parent_id && $guard++ < 10) {
                $current = $allCats[$current]->parent_id;
            }
            $catToRoot[$id] = $current;
        }

        // 获取一级分类名称
        $topCats = WorkorderCategorySimplified::whereNull('parent_id')->orderBy('sort_order')->orderBy('name')->get();
        $topCatNames = $topCats->pluck('name', 'id')->toArray();

        // 每日新建数（总量）
        $created = Workorder::selectRaw("DATE(created_at) as d, COUNT(*) as c")
            ->whereBetween('created_at', [$rangeStart, $rangeEnd])
            ->groupByRaw("DATE(created_at)")
            ->pluck('c', 'd');

        // 每日按一级分类的新建数
        $byCat = [];
        foreach ($topCatNames as $rootId => $rootName) {
            $subIds = array_keys(array_filter($catToRoot, function($v) use ($rootId) { return $v == $rootId; }));
            if (!empty($subIds)) {
                $byCat[$rootId] = Workorder::selectRaw("DATE(created_at) as d, COUNT(*) as c")
                    ->whereBetween('created_at', [$rangeStart, $rangeEnd])
                    ->whereIn('category_id', $subIds)
                    ->groupByRaw("DATE(created_at)")
                    ->pluck('c', 'd');
            }
        }

        $stats = [];
        $cursor = $rangeStart->copy();
        while ($cursor <= $rangeEnd) {
            $date = $cursor->format('Y-m-d');
            $row = [
                'date' => $date,
                'display_date' => $cursor->format('m-d'),
                'total' => $created->get($date, 0),
            ];
            foreach ($topCatNames as $rootId => $rootName) {
                $row['cat_' . $rootId] = isset($byCat[$rootId]) ? $byCat[$rootId]->get($date, 0) : 0;
            }
            $stats[$date] = $row;
            $cursor->addDay();
        }

        return ['stats' => $stats, 'topCats' => $topCatNames];
    }

    /**
     * 获取工单状态分布
     */
    private function getStatusDistribution()
    {
        $statuses = ['pending', 'assigned', 'processing', 'resolved', 'verifying', 'closed', 'rejected'];
        $distribution = [];

        foreach ($statuses as $status) {
            $distribution[$status] = Workorder::where('status', $status)->count();
        }

        return $distribution;
    }

    /**
     * 获取工单分类分布
     */
    private function getCategoryDistribution()
    {
        // 统计每个一级分类下所有工单（含子分类）
        $topCats = WorkorderCategorySimplified::whereNull('parent_id')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $allDescendantIds = function($parentId) use (&$allDescendantIds) {
            $ids = WorkorderCategorySimplified::where('parent_id', $parentId)->pluck('id')->toArray();
            $result = $ids;
            foreach ($ids as $id) { $result = array_merge($result, $allDescendantIds($id)); }
            return $result;
        };

         foreach ($topCats as $cat) {
             $subIds = $allDescendantIds($cat->id);
             $allIds = array_merge([$cat->id], $subIds);
            $cat->workorders_count = Workorder::whereIn('category_id', $allIds)->count();
        }

         return $topCats->sortByDesc('workorders_count')->values();
     }

    /**
     * 获取校区工单统计
     */
    private function getCampusStats()
    {
        return \App\Models\Campus::leftJoin('workorders', 'campuses.id', '=', 'workorders.campus_id')
            ->whereNull('workorders.deleted_at')
            ->selectRaw("campuses.id, campuses.name,
                COUNT(workorders.id) as total,
                SUM(CASE WHEN workorders.status IN ('pending','assigned','processing') THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN workorders.status IN ('resolved','closed') THEN 1 ELSE 0 END) as completed")
            ->groupBy('campuses.id', 'campuses.name')
            ->orderBy('campuses.sort_order')
            ->orderBy('campuses.name')
            ->get()
            ->keyBy('id')
            ->map(function($item) {
                return ['name' => $item->name, 'total' => (int)$item->total, 'pending' => (int)$item->pending, 'completed' => (int)$item->completed];
            })
            ->toArray();
    }

    /**
     * 获取校区名称
     */
    private function getCampusName($campus)
    {
        $campusModel = \App\Models\Campus::find($campus);
        return $campusModel ? $campusModel->name : $campus;
    }

    /**
     * 获取楼栋名称
     */
    private function getBuildingName($buildingId): string
    {
        if (!$buildingId) {
            return '';
        }

        $location = Location::find($buildingId);
        return $location ? $location->name : $buildingId;
    }

    /**
     * 获取处理时长统计
     */
    private function getProcessingTimeStats()
    {
        $r = Workorder::whereIn('status', ['resolved', 'closed'])
            ->whereNotNull('resolved_at')
            ->whereNotNull('started_at')
            ->selectRaw("COUNT(*) as total,
                AVG(TIMESTAMPDIFF(MINUTE, started_at, resolved_at)) as avg_time,
                MIN(TIMESTAMPDIFF(MINUTE, started_at, resolved_at)) as min_time,
                MAX(TIMESTAMPDIFF(MINUTE, started_at, resolved_at)) as max_time")
            ->first();
        if (!$r || $r->total == 0) {
            return ['average_time' => 0, 'min_time' => 0, 'max_time' => 0, 'total_completed' => 0];
        }
        return ['average_time' => round($r->avg_time), 'min_time' => (int)$r->min_time, 'max_time' => (int)$r->max_time, 'total_completed' => (int)$r->total];
    }

    /**
     * 获取满意度统计
     */
    private function getSatisfactionStats()
    {
        $visits = \App\Models\WorkorderVisit::with('workorder')
            ->whereNotNull('satisfaction_score')
            ->get();

        if ($visits->isEmpty()) {
            return [
                'average_score' => 0,
                'total_visits' => 0,
                'distribution' => [],
            ];
        }

        $scores = $visits->pluck('satisfaction_score')->toArray();
        $distribution = [
            '5' => 0,
            '4' => 0,
            '3' => 0,
            '2' => 0,
            '1' => 0,
        ];

        foreach ($scores as $score) {
            if (isset($distribution[$score])) {
                $distribution[$score]++;
            }
        }

        return [
            'average_score' => round(array_sum($scores) / count($scores), 2),
            'total_visits' => count($scores),
            'distribution' => $distribution,
        ];
    }

    /**
     * 获取部门工单统计
     */
    /**
     * 获取来源分布
     */
    private function getSourceDistribution()
    {
        // 兼容代码值(phone/web/scene)和中文值(电话报修/现场报修等)
        $raw = Workorder::selectRaw("COALESCE(NULLIF(source,''),'unknown') as src, COUNT(*) as cnt")
            ->groupByRaw("COALESCE(NULLIF(source,''),'unknown')")
            ->pluck('cnt', 'src');

        $map = [
            'phone' => '电话', '电话报修' => '电话',
            'web' => '网络', '在线平台' => '网络',
            'scene' => '现场', '现场报修' => '现场',
            'email' => '邮件',
            'other' => '其他', '其他来源' => '其他',
            '巡检发现' => '巡检',
        ];

        $result = ['电话' => 0, '网络' => 0, '现场' => 0, '邮件' => 0, '其他' => 0, '巡检' => 0];
        foreach ($raw as $src => $cnt) {
            $label = $map[$src] ?? '其他';
            $result[$label] = ($result[$label] ?? 0) + $cnt;
        }
        // 移除为0的类别
        return array_filter($result, function($v) { return $v > 0; });
    }

    /**
     * 获取优先级分布
     */
    private function getPriorityDistribution()
    {
        $raw = Workorder::selectRaw("priority, COUNT(*) as cnt")
            ->groupBy('priority')
            ->pluck('cnt', 'priority');
        return [
            'high' => $raw->get('high', 0),
            'medium' => $raw->get('medium', 0),
            'low' => $raw->get('low', 0),
        ];
    }

    /**
     * 获取部门工单统计
     */
    private function getDepartmentStats()
    {
        return Department::withCount(['workorders', 'workorders as pending_workorders' => function($query) {
                $query->whereIn('status', ['pending', 'assigned', 'processing']);
            }])
            ->withCount(['workorders as completed_workorders' => function($query) {
                $query->whereIn('status', ['resolved', 'closed']);
            }])
            ->orderBy('completed_workorders_count', 'desc')
            ->limit(10)
            ->get();
    }

    /**
     * 获取工程师处理统计
     */
    private function getEngineerStats()
    {
        return User::whereIn('role', ['admin', 'workorder_manager', 'engineer'])
            ->withCount(['assignedWorkorders'])
            ->withCount(['assignedWorkorders as pending_workorders_count' => function($query) {
                $query->whereIn('status', ['pending', 'assigned', 'processing']);
            }])
            ->withCount(['assignedWorkorders as completed_workorders_count' => function($query) {
                $query->whereIn('status', ['resolved', 'closed']);
            }])
            ->orderBy('completed_workorders_count', 'desc')
            ->limit(10)
            ->get();
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
