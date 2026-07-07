<?php

namespace App\Http\Controllers;

use App\Models\WorkorderType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WorkorderTypeController extends Controller
{
    /**
     * 工单类型列表页面
     */
    public function index(Request $request)
    {
        $query = WorkorderType::orderBy('sort_order')->orderBy('created_at', 'desc');

        // 搜索条件
        if ($request->filled('keyword')) {
            $keyword = $request->input('keyword');
            $query->where(function($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                  ->orWhere('code', 'like', "%{$keyword}%")
                  ->orWhere('description', 'like', "%{$keyword}%");
            });
        }

        // 来源筛选
        if ($request->filled('source')) {
            $query->where('source', $request->input('source'));
        }

        // 状态筛选
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $workorderTypes = $query->paginate(15);
        
        return view('workorder-types.index', compact('workorderTypes'));
    }

    /**
     * 创建工单类型页面
     */
    public function create()
    {
        $sources = WorkorderType::getSourceOptions();
        $priorities = WorkorderType::getPriorityOptions();
        
        return view('workorder-types.create', compact('sources', 'priorities'));
    }

    /**
     * 保存工单类型
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:50|unique:workorder_types,code',
            'description' => 'nullable|string|max:500',
            'source' => 'required|in:phone,web,email,scene,other',
            'subcategory' => 'nullable|string|max:100',
            'default_priority' => 'required|integer|in:1,2,3',
            'default_hours' => 'required|integer|min:1|max:168', // 最大7天
            'status' => 'required|in:active,inactive',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        DB::beginTransaction();
        try {
            WorkorderType::create($request->all());
            
            DB::commit();
            
            return redirect()->route('workorder-types.index')
                ->with('success', '工单类型创建成功');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', '工单类型创建失败：' . $e->getMessage());
        }
    }

    /**
     * 工单类型详情页面
     */
    public function show(WorkorderType $workorderType)
    {
        $workorderType->load(['workorders' => function($query) {
            $query->latest()->limit(10);
        }]);
        
        // 获取最近工单
        $recentWorkorders = $workorderType->workorders()
            ->with('creator')
            ->latest()
            ->limit(10)
            ->get();
        
        return view('workorder-types.show', compact('workorderType', 'recentWorkorders'));
    }

    /**
     * 编辑工单类型页面
     */
    public function edit(WorkorderType $workorderType)
    {
        $sources = WorkorderType::getSourceOptions();
        $priorities = WorkorderType::getPriorityOptions();
        
        return view('workorder-types.edit', compact('workorderType', 'sources', 'priorities'));
    }

    /**
     * 更新工单类型
     */
    public function update(Request $request, WorkorderType $workorderType)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:50|unique:workorder_types,code,' . $workorderType->id,
            'description' => 'nullable|string|max:500',
            'source' => 'required|in:phone,web,email,scene,other',
            'subcategory' => 'nullable|string|max:100',
            'default_priority' => 'required|integer|in:1,2,3',
            'default_hours' => 'required|integer|min:1|max:168',
            'status' => 'required|in:active,inactive',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        DB::beginTransaction();
        try {
            $workorderType->update($request->all());
            
            DB::commit();
            
            return redirect()->route('workorder-types.index')
                ->with('success', '工单类型更新成功');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', '工单类型更新失败：' . $e->getMessage());
        }
    }

    /**
     * 删除工单类型
     */
    public function destroy(WorkorderType $workorderType)
    {
        // 检查是否有关联的工单
        if ($workorderType->workorders()->count() > 0) {
            return back()->with('error', '该工单类型下还有工单，无法删除');
        }

        try {
            $workorderType->delete();
            return redirect()->route('workorder-types.index')
                ->with('success', '工单类型删除成功');
        } catch (\Exception $e) {
            return back()->with('error', '工单类型删除失败：' . $e->getMessage());
        }
    }

    /**
     * 获取工单类型统计信息
     */
    public function statistics(WorkorderType $workorderType)
    {
        $stats = [
            'total_workorders' => $workorderType->workorders()->count(),
            'pending_workorders' => $workorderType->workorders()
                ->whereIn('status', ['pending', 'assigned', 'processing'])
                ->count(),
            'completed_workorders' => $workorderType->workorders()
                ->whereIn('status', ['resolved', 'closed'])
                ->count(),
            'avg_processing_time' => $workorderType->workorders()
                ->whereNotNull('processing_duration')
                ->avg('processing_duration'),
            'overdue_count' => $workorderType->workorders()
                ->where('status', '!=', 'closed')
                ->where('expected_complete_at', '<', now())
                ->count(),
        ];
        
        return response()->json($stats);
    }

    /**
     * 获取工单类型选项（用于API）
     */
    public function options(Request $request)
    {
        $query = WorkorderType::where('status', 'active');
        
        if ($request->filled('source')) {
            $query->where('source', $request->input('source'));
        }
        
        $types = $query->orderBy('sort_order')->get()->map(function($type) {
            return [
                'id' => $type->id,
                'name' => $type->name,
                'full_name' => $type->full_name,
                'source' => $type->source,
                'subcategory' => $type->subcategory,
                'default_priority' => $type->default_priority,
                'default_hours' => $type->default_hours,
            ];
        });
        
        return response()->json($types);
    }

    /**
     * 批量更新排序
     */
    public function updateSort(Request $request)
    {
        $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:workorder_types,id',
            'items.*.sort_order' => 'required|integer|min:0',
        ]);

        DB::beginTransaction();
        try {
            foreach ($request->input('items') as $item) {
                WorkorderType::where('id', $item['id'])
                    ->update(['sort_order' => $item['sort_order']]);
            }
            
            DB::commit();
            
            return response()->json(['message' => '排序更新成功']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => '排序更新失败：' . $e->getMessage()], 500);
        }
    }

    /**
     * 复制工单类型
     */
    public function duplicate(WorkorderType $workorderType)
    {
        try {
            $newType = $workorderType->replicate();
            $newType->name = $workorderType->name . ' (副本)';
            $newType->code = $workorderType->code . '_copy_' . time();
            $newType->save();
            
            return redirect()->route('workorder-types.edit', $newType->id)
                ->with('success', '工单类型复制成功');
        } catch (\Exception $e) {
            return back()->with('error', '工单类型复制失败：' . $e->getMessage());
        }
    }
}