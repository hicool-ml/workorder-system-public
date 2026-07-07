<?php

namespace App\Http\Controllers;

use App\Models\Workorder;
use App\Models\User;
use App\Models\Department;
use App\Models\WorkorderType;
use App\Models\WorkorderCategorySimplified;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    /**
     * 统计报表页面
     */
    public function index(Request $request)
    {
        // 获取统计数据
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
        ];

        // 获取时间范围
        $dateRange = $request->input('date_range', '7days');
        $days = $dateRange === '30days' ? 30 : ($dateRange === '90days' ? 90 : 7);
        
        // 获取最近N天的工单统计
        $recentStats = $this->getRecentStats($days);

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
            'dateRange'
        ));
    }

    /**
     * 获取最近N天的统计
     */
    private function getRecentStats($days = 7)
    {
        $stats = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $stats[$date] = [
                'date' => $date,
                'display_date' => now()->subDays($i)->format('m-d'),
                'total' => Workorder::whereDate('created_at', $date)->count(),
                'completed' => Workorder::whereDate('resolved_at', $date)->count(),
                'pending' => Workorder::whereDate('created_at', $date)
                    ->whereIn('status', ['pending', 'assigned', 'processing'])
                    ->count(),
                'emergency' => Workorder::whereDate('created_at', $date)
                    ->where('is_emergency', true)
                    ->count(),
            ];
        }

        return $stats;
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
        return WorkorderCategorySimplified::withCount('workorders')
            ->whereNull('parent_id')
            ->orderBy('workorders_count', 'desc')
            ->limit(10)
            ->get();
    }

    /**
     * 获取校区工单统计
     */
    private function getCampusStats()
    {
        $campuses = ['old_campus', 'new_campus', 'asean_campus'];
        $stats = [];

        foreach ($campuses as $campus) {
            $stats[$campus] = [
                'name' => $this->getCampusName($campus),
                'total' => Workorder::where('campus', $campus)->count(),
                'pending' => Workorder::where('campus', $campus)
                    ->whereIn('status', ['pending', 'assigned', 'processing'])
                    ->count(),
                'completed' => Workorder::where('campus', $campus)
                    ->whereIn('status', ['resolved', 'closed'])
                    ->count(),
            ];
        }

        return $stats;
    }

    /**
     * 获取校区名称
     */
    private function getCampusName($campus)
    {
        $names = [
            'old_campus' => '老校区',
            'new_campus' => '新校区',
            'asean_campus' => '东盟校区',
        ];

        return $names[$campus] ?? $campus;
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
        $completedWorkorders = Workorder::whereIn('status', ['resolved', 'closed'])
            ->whereNotNull('resolved_at')
            ->whereNotNull('started_at')
            ->get();

        if ($completedWorkorders->isEmpty()) {
            return [
                'average_time' => 0,
                'min_time' => 0,
                'max_time' => 0,
                'total_completed' => 0,
            ];
        }

        $times = [];
        foreach ($completedWorkorders as $workorder) {
            $startTime = $workorder->started_at ?? $workorder->created_at;
            $times[] = $startTime->diffInMinutes($workorder->resolved_at);
        }

        return [
            'average_time' => round(array_sum($times) / count($times)),
            'min_time' => min($times),
            'max_time' => max($times),
            'total_completed' => count($times),
        ];
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
        return User::whereIn('role', ['admin', 'engineer'])
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
            'campus' => 'nullable|string',
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

        if ($request->filled('campus')) {
            $query->where('campus', $request->input('campus'));
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
            
            // 设置UTF-8编码过滤器
            stream_filter_append($file, 'convert.iconv.utf8.utf8', STREAM_FILTER_WRITE);
            
            // CSV头部
            $headers = [
                '日期', '创建时间', '工单号', '类型（工单大类）', '故障分类', '问题描述',
                '报修人', '校区', '地点', '联系电话', '处理人', '处理方式',
                '处理时长', '解决方案', '备件耗材使用', '备注', '是否回访', '回访结果'
            ];
            
            // 潬换编码并写入CSV头部
            $convertedHeaders = array_map(function($header) {
                return mb_convert_encoding($header, 'UTF-8', 'auto');
            }, $headers);
            fputcsv($file, $convertedHeaders);
            
            // CSV数据
            foreach ($workorders as $workorder) {
                // 计算处理时长（分钟，取整）
                $processingDuration = '';
                if ($workorder->resolved_at) {
                    $processingDuration = (int)$workorder->created_at->diffInMinutes($workorder->resolved_at);
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
                    mb_convert_encoding($workorder->created_at->format('Y-m-d'), 'UTF-8', 'auto'),
                    mb_convert_encoding($workorder->created_at->format('H:i:s'), 'UTF-8', 'auto'),
                    mb_convert_encoding($workorder->ticket_no, 'UTF-8', 'auto'),
                    mb_convert_encoding($mainCategory, 'UTF-8', 'auto'),
                    mb_convert_encoding($subCategory, 'UTF-8', 'auto'),
                    mb_convert_encoding($workorder->description, 'UTF-8', 'auto'),
                    mb_convert_encoding($workorder->contact_name, 'UTF-8', 'auto'),
                    mb_convert_encoding($this->getCampusName($workorder->campus), 'UTF-8', 'auto'),
                    mb_convert_encoding($this->getBuildingName($workorder->building) . ($workorder->location_detail ? ' - ' . $workorder->location_detail : ''), 'UTF-8', 'auto'),
                    mb_convert_encoding($workorder->contact_phone, 'UTF-8', 'auto'),
                    mb_convert_encoding($processorsText, 'UTF-8', 'auto'),
                    mb_convert_encoding($workorder->phone_assisted ? '电话协助' : '现场处理', 'UTF-8', 'auto'),
                    mb_convert_encoding($processingDuration, 'UTF-8', 'auto'),
                    mb_convert_encoding($workorder->solution ?? '', 'UTF-8', 'auto'),
                    mb_convert_encoding($workorder->materials_usage ?? '', 'UTF-8', 'auto'),
                    mb_convert_encoding($workorder->remarks ?? '', 'UTF-8', 'auto'),
                    mb_convert_encoding($hasVisit ? '是' : '否', 'UTF-8', 'auto'),
                    mb_convert_encoding($visitResult, 'UTF-8', 'auto')
                ];
                
                fputcsv($file, $rowData);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}