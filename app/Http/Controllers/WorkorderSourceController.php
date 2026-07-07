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
        $sources = WorkorderSource::orderBy('sort_order')->paginate(20);
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
            'name' => 'required|string|max:50',
            'code' => 'required|string|max:30|unique:workorder_sources,code',
            'description' => 'nullable|string|max:200',
            'sort_order' => 'nullable|integer|min:0',
            'status' => 'required|in:active,inactive',
        ]);

        WorkorderSource::create($request->only(['name', 'code', 'description', 'sort_order', 'status']));

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
            'name' => 'required|string|max:50',
            'code' => 'required|string|max:30|unique:workorder_sources,code,' . $workorderSource->id,
            'description' => 'nullable|string|max:200',
            'sort_order' => 'nullable|integer|min:0',
            'status' => 'required|in:active,inactive',
        ]);

        $workorderSource->update($request->only(['name', 'code', 'description', 'sort_order', 'status']));

        return redirect()->route('workorder-sources.index')
            ->with('success', '工单来源更新成功');
    }

    /**
     * 删除来源
     */
    public function destroy(WorkorderSource $workorderSource)
    {
        $workorderSource->delete();

        return redirect()->route('workorder-sources.index')
            ->with('success', '工单来源已删除');
    }

    /**
     * 切换来源状态
     */
    public function toggleStatus(WorkorderSource $workorderSource)
    {
        $workorderSource->update([
            'status' => $workorderSource->status === 'active' ? 'inactive' : 'active',
        ]);

        return back()->with('success', '状态已切换');
    }
}
