<?php

namespace App\Http\Controllers;

use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LocationController extends Controller
{
    /**
     * 地址列表页面
     */
    public function index(Request $request)
    {
        $query = Location::query();

        // 校区筛选
        if ($request->filled('campus')) {
            $query->byCampus($request->input('campus'));
        }

        // 建筑类型筛选
        if ($request->filled('building_type')) {
            $query->byBuildingType($request->input('building_type'));
        }

        // 状态筛选
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // 搜索
        if ($request->filled('keyword')) {
            $keyword = $request->input('keyword');
            $query->where(function($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                  ->orWhere('building_code', 'like', "%{$keyword}%")
                  ->orWhere('description', 'like', "%{$keyword}%");
            });
        }

        $locations = $query->ordered()->paginate(15);

        return view('locations.index', compact('locations'));
    }

    /**
     * 创建地址页面
     */
    public function create()
    {
        return view('locations.create');
    }

    /**
     * 保存地址
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'campus' => 'required|in:' . implode(',', array_keys(Location::CAMPUSES)),
            'building_type' => 'required|in:' . implode(',', array_keys(Location::BUILDING_TYPES)),
            'building_code' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:500',
            'sort_order' => 'nullable|integer|min:0',
            'status' => 'required|in:' . implode(',', array_keys(Location::STATUSES)),
        ]);

        Location::create($request->all());

        return redirect()->route('locations.index', $request->query())
            ->with('success', '地址创建成功');
    }

    /**
     * 地址详情页面
     */
    public function show(Location $location)
    {
        return view('locations.show', compact('location'));
    }

    /**
     * 编辑地址页面
     */
    public function edit(Location $location)
    {
        return view('locations.edit', compact('location'));
    }

    /**
     * 更新地址
     */
    public function update(Request $request, Location $location)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'campus' => 'required|in:' . implode(',', array_keys(Location::CAMPUSES)),
            'building_type' => 'required|in:' . implode(',', array_keys(Location::BUILDING_TYPES)),
            'building_code' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:500',
            'sort_order' => 'nullable|integer|min:0',
            'status' => 'required|in:' . implode(',', array_keys(Location::STATUSES)),
        ]);

        $location->update($request->all());

        return redirect()->route('locations.index', $request->query())
            ->with('success', '地址更新成功');
    }

    /**
     * 删除地址
     */
    public function destroy(Request $request, Location $location)
    {
        $location->delete();

        return redirect()->route('locations.index', $request->query())
            ->with('success', '地址删除成功');
    }

}