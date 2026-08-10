<?php

namespace App\Http\Controllers;

use App\Models\LocationLevel;
use Illuminate\Http\Request;

/**
 * 地址层级定义管理 —— 用户自主配置分级方案。
 * 例如：省→市→区→街道→社区，或 园区→楼栋→楼层→房间。
 */
class LocationLevelController extends Controller
{
    public function index()
    {
        $levels = LocationLevel::orderBy('level')->orderBy('sort_order')->get();
        return view('location-levels.index', compact('levels'));
    }

    public function create()
    {
        $nextLevel = (LocationLevel::max('level') ?? 0) + 1;
        return view('location-levels.create', compact('nextLevel'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50',
            'code' => 'required|string|max:30|unique:location_levels,code',
            'level' => 'required|integer|min:1',
            'description' => 'nullable|string|max:200',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        LocationLevel::create($validated);

        return redirect()->route('location-levels.index', $request->query())
            ->with('success', '层级定义创建成功');
    }

    public function edit(LocationLevel $locationLevel)
    {
        return view('location-levels.edit', compact('locationLevel'));
    }

    public function update(Request $request, LocationLevel $locationLevel)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50',
            'code' => 'required|string|max:30|unique:location_levels,code,' . $locationLevel->id,
            'level' => 'required|integer|min:1',
            'description' => 'nullable|string|max:200',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        $locationLevel->update($validated);

        return redirect()->route('location-levels.index', $request->query())
            ->with('success', '层级定义更新成功');
    }

    public function destroy(Request $request, LocationLevel $locationLevel)
    {
        if ($locationLevel->locations()->exists()) {
            return back()->with('error', '该层级下已有地址节点，无法删除');
        }

        $locationLevel->delete();

        return redirect()->route('location-levels.index', $request->query())
            ->with('success', '层级定义删除成功');
    }
}
