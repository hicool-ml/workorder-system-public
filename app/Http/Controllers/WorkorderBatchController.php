<?php

namespace App\Http\Controllers;

use App\Models\Workorder;
use Illuminate\Http\Request;

/**
 * 工单批量操作（分配/开始/解决/关闭/完结）
 */
class WorkorderBatchController extends Controller
{
    /**
     * 批量分配工单
     */
    public function assign(Request $request)
    {
        if (!auth()->user()->canAssignWorkorders()) {
            return response()->json(['success' => false, 'message' => '您没有权限分配工单'], 403);
        }

        $request->validate([
            'workorder_ids' => 'required|string',
            'assignee_id' => 'required|exists:users,id',
            'note' => 'nullable|string|max:500',
        ]);

        $workorderIds = $this->parseWorkorderIds($request);
        $assigneeId = $request->input('assignee_id');
        $note = $request->input('note');

        return $this->runBatch($workorderIds, '批量分配', function (Workorder $wo) use ($assigneeId, $note) {
            if (!$wo->canBeAssigned()) return false;
            return $wo->assign($assigneeId, $note);
        });
    }

    /**
     * 批量开始处理
     */
    public function start(Request $request)
    {
        if (!auth()->user()->canHandleWorkorders()) {
            return response()->json(['success' => false, 'message' => '您没有权限处理工单'], 403);
        }

        $request->validate(['workorder_ids' => 'required|string']);
        $workorderIds = $this->parseWorkorderIds($request);

        return $this->runBatch($workorderIds, '批量开始处理', function (Workorder $wo) {
            if (!$wo->canBeOperatedBy(auth()->user(), 'resolve')) return false;
            if (!$wo->canBeStarted()) return false;
            return $wo->start();
        });
    }

    /**
     * 批量解决（支持通用方案 / 逐单方案两种模式）
     */
    public function resolve(Request $request)
    {
        if (!auth()->user()->canHandleWorkorders()) {
            return response()->json(['success' => false, 'message' => '您没有权限处理工单'], 403);
        }

        $request->validate([
            'workorder_ids' => 'required|string',
            'solution_type' => 'required|in:common,individual',
        ]);

        $workorderIds = $this->parseWorkorderIds($request);
        $workorders = $this->prefetchWorkorders($workorderIds);
        $solutionType = $request->input('solution_type');

        $commonSolution = null;
        $commonMaterials = null;
        if ($solutionType === 'common') {
            $request->validate(['solution' => 'required|string|max:2000']);
            $commonSolution = $request->input('solution');
            $noMaterials = $request->boolean('no_materials');
            $commonMaterials = $noMaterials ? '无备件耗材使用' : $request->input('materials_usage');
        }

        $solutions = $request->input('solutions', []);
        $noMaterialsArray = $request->input('no_materials_array', []);
        $materialsUsageArray = $request->input('materials_usage_array', []);

        $successCount = 0;
        $failedCount = 0;
        $failedWorkorders = [];

        foreach ($workorderIds as $workorderId) {
            $workorder = $workorders->get($workorderId);

            if (!$workorder || !$workorder->canBeResolved() || !$workorder->canBeOperatedBy(auth()->user(), 'resolve')) {
                $failedCount++;
                $failedWorkorders[] = $workorder?->ticket_no ?? 'Unknown';
                continue;
            }

            if ($solutionType === 'common') {
                $solution = $commonSolution;
                $materialsUsage = $commonMaterials;
            } else {
                $solution = $solutions[$workorderId] ?? '';
                $noMaterials = $noMaterialsArray[$workorderId] ?? false;
                $materialsUsage = $noMaterials ? '无备件耗材使用' : ($materialsUsageArray[$workorderId] ?? '');
            }

            if ($workorder->resolve($solution)) {
                $workorder->materials_usage = $materialsUsage;
                $workorder->save();
                $workorder->addLog('materials_updated', '更新了备件耗材使用情况');
                $successCount++;
            } else {
                $failedCount++;
                $failedWorkorders[] = $workorder->ticket_no;
            }
        }

        return $this->batchResponse('成功解决', $successCount, $failedCount, $failedWorkorders, '批量解决失败');
    }

    /**
     * 批量关闭
     */
    public function close(Request $request)
    {
        if (!auth()->user()->canCloseWorkorders()) {
            return response()->json(['success' => false, 'message' => '您没有权限关闭工单'], 403);
        }

        $request->validate(['workorder_ids' => 'required|string']);
        $workorderIds = $this->parseWorkorderIds($request);

        return $this->runBatch($workorderIds, '批量关闭', function (Workorder $wo) {
            if (!$wo->canBeClosed()) return false;
            return $wo->close();
        });
    }

    /**
     * 批量完结
     */
    public function complete(Request $request)
    {
        if (!auth()->user()->canHandleWorkorders()) {
            return response()->json(['success' => false, 'message' => '您没有权限处理工单'], 403);
        }

        $request->validate([
            'workorder_ids' => 'required|string',
            'completion_note' => 'nullable|string|max:1000',
        ]);

        $workorderIds = $this->parseWorkorderIds($request);
        $completionNote = $request->input('completion_note', '批量完结工单');

        return $this->runBatch($workorderIds, '批量完结', function (Workorder $wo) use ($completionNote) {
            if (!$wo->canBeOperatedBy(auth()->user(), 'resolve')) return false;
            if (!$wo->canBeCompleted()) return false;
            if ($wo->complete()) {
                if (!empty($completionNote)) {
                    $wo->addLog('completed', $completionNote);
                }
                return true;
            }
            return false;
        });
    }

    // ===== 辅助方法 =====

    /**
     * 通用批量执行器：预取 + 逐条执行回调 + 统一响应
     */
    private function runBatch(array $workorderIds, string $actionLabel, \Closure $handler)
    {
        try {
            $successCount = 0;
            $failedCount = 0;
            $failedWorkorders = [];
            $workorders = $this->prefetchWorkorders($workorderIds);

            foreach ($workorderIds as $workorderId) {
                $workorder = $workorders->get($workorderId);

                if (!$workorder) {
                    $failedCount++;
                    $failedWorkorders[] = 'Unknown';
                    continue;
                }

                if ($handler($workorder)) {
                    $successCount++;
                } else {
                    $failedCount++;
                    $failedWorkorders[] = $workorder->ticket_no;
                }
            }

            return $this->batchResponse("成功{$actionLabel}", $successCount, $failedCount, $failedWorkorders, "{$actionLabel}失败");
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => "{$actionLabel}失败：" . $e->getMessage()]);
        }
    }

    private function batchResponse(string $prefix, int $successCount, int $failedCount, array $failedWorkorders, string $errorLabel)
    {
        $message = "{$prefix} {$successCount} 个工单";
        if ($failedCount > 0) {
            $message .= "，失败 {$failedCount} 个工单：" . implode(', ', $failedWorkorders);
        }
        return response()->json(['success' => true, 'message' => $message]);
    }

    private function parseWorkorderIds(Request $request): array
    {
        $ids = explode(',', $request->input('workorder_ids', ''));
        $ids = array_filter($ids, fn($id) => is_numeric($id) && $id > 0);
        return array_unique(array_map('intval', $ids));
    }

    private function prefetchWorkorders(array $ids): \Illuminate\Support\Collection
    {
        if (empty($ids)) {
            return collect();
        }
        return Workorder::whereIn('id', $ids)->get()->keyBy('id');
    }
}
