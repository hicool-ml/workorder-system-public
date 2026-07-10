<?php

namespace App\Http\Controllers;

use App\Models\WorkorderSource;
use Illuminate\Http\Request;

class WorkorderSourceController extends Controller
{
    /**
     * 工单来源列表
     */
    public function index()
    {
        $sources = WorkorderSource::orderBy('sort_order')
            ->orderBy('name')
            ->paginate(15);

        return view('workorder-sources.index', compact('sources'));
    }

    /**
     * 创建来源页面
     */
    public function create()
    {
        return view('workorder-sources.create');
    }

    /**
     * 保存来源
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:50|unique:workorder_sources,name',
            'description' => 'nullable|string|max:200',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        WorkorderSource::create([
            'name' => $request->input('name'),
            'description' => $request->input('description'),
            'is_active' => $request->boolean('is_active', true),
            'sort_order' => $request->input('sort_order', 0),
        ]);

        return redirect()->route('workorder-sources.index')
            ->with('success', '工单来源创建成功');
    }

    /**
     * 编辑来源页面
     */
    public function edit(WorkorderSource $workorderSource)
    {
        return view('workorder-sources.edit', compact('workorderSource'));
    }

    /**
     * 更新来源
     */
    public function update(Request $request, WorkorderSource $workorderSource)
    {
        $request->validate([
            'name' => 'required|string|max:50|unique:workorder_sources,name,' . $workorderSource->id,
            'description' => 'nullable|string|max:200',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $workorderSource->update([
            'name' => $request->input('name'),
            'description' => $request->input('description'),
            'is_active' => $request->boolean('is_active', true),
            'sort_order' => $request->input('sort_order', 0),
        ]);

        return redirect()->route('workorder-sources.index')
            ->with('success', '工单来源更新成功');
    }

    /**
     * 删除来源
     */
    public function destroy(WorkorderSource $workorderSource)
    {
        if ($workorderSource->workorders()->exists()) {
            return back()->with('error', '无法删除，已有工单使用了此来源');
        }

        $workorderSource->delete();

        return redirect()->route('workorder-sources.index')
            ->with('success', '工单来源删除成功');
    }

    /**
     * 切换来源状态
     */
    public function toggleStatus(WorkorderSource $workorderSource)
    {
        $workorderSource->update([
            'is_active' => !$workorderSource->is_active
        ]);

        $status = $workorderSource->is_active ? '启用' : '禁用';

        return back()->with('success', "工单来源已{$status}");
    }
}
