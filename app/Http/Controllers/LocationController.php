<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\Campus;
use App\Models\LocationLevel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class LocationController extends Controller
{

    /**
     * 地址列表页面 —— 树形展示
     */
    public function index(Request $request)
    {
        $levels = LocationLevel::getActiveLevels();

        // 搜索模式：扁平结果
        if ($request->filled('keyword')) {
            $keyword = $request->input('keyword');
            $results = Location::with('level', 'parent')
                ->where('name', 'like', "%{$keyword}%")
                ->orWhere('description', 'like', "%{$keyword}%")
                ->orderBy('sort_order')->orderBy('name')
                ->paginate(20);
            return view('locations.index', compact('results', 'levels'));
        }

        // 默认：树形展示
        $tree = Location::getTree();
        return view('locations.index', compact('tree', 'levels'));
    }

    /**
     * 创建地址页面
     */
    public function create()
    {
        $levels = LocationLevel::getActiveLevels();
        $parentOptions = Location::getSelectOptions();
        return view('locations.create', compact('levels', 'parentOptions'));
    }

    /**
     * 保存地址
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'level_id' => 'required|exists:location_levels,id',
            'parent_id' => 'nullable|exists:locations,id',
            'code' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:500',
            'sort_order' => 'nullable|integer|min:0',
            'status' => 'required|in:active,inactive',
        ]);

        // 校验：父节点的层级必须比当前层级浅一层
        if (!empty($validated['parent_id'])) {
            $parent = Location::with('level')->find($validated['parent_id']);
            $currentLevel = LocationLevel::find($validated['level_id']);
            if ($parent && $currentLevel && $parent->level && $parent->level->level >= $currentLevel->level) {
                return back()->withInput()->with('error', '父节点的层级必须高于当前层级');
            }
        }

        // 沿父链向上继承 campus_id，保证级联选择与统计可用
        $validated['campus_id'] = $this->resolveCampusId($validated['parent_id'] ?? null);

        Location::create($validated);

        return redirect()->route('locations.index', $request->query())
            ->with('success', '地址创建成功');
    }

    /**
     * 地址详情页面
     */
    public function show(Location $location)
    {
        $location->load('level', 'children');
        return view('locations.show', compact('location'));
    }

    /**
     * 编辑地址页面
     */
    public function edit(Location $location)
    {
        $levels = LocationLevel::getActiveLevels();
        $parentOptions = Location::getSelectOptions();
        return view('locations.edit', compact('location', 'levels', 'parentOptions'));
    }

    /**
     * 更新地址
     */
    public function update(Request $request, Location $location)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'level_id' => 'required|exists:location_levels,id',
            'parent_id' => 'nullable|exists:locations,id',
            'code' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:500',
            'sort_order' => 'nullable|integer|min:0',
            'status' => 'required|in:active,inactive',
        ]);

        // 不能把自己设为自己的父节点
        if (!empty($validated['parent_id'])) {
            if ((int) $validated['parent_id'] === (int) $location->id) {
                return back()->withInput()->with('error', '不能将自身设为父节点');
            }
            $parent = Location::with('level')->find($validated['parent_id']);
            $currentLevel = LocationLevel::find($validated['level_id']);
            if ($parent && $currentLevel && $parent->level && $parent->level->level >= $currentLevel->level) {
                return back()->withInput()->with('error', '父节点的层级必须高于当前层级');
            }
        }

        // 沿父链向上继承 campus_id，保证级联选择与统计可用
        $validated['campus_id'] = $this->resolveCampusId($validated['parent_id'] ?? null);

        $location->update($validated);

        return redirect()->route('locations.index', $request->query())
            ->with('success', '地址更新成功');
    }

    /**
     * 删除地址
     */
    public function destroy(Request $request, Location $location)
    {
        if ($location->children()->exists()) {
            return back()->with('error', '该地址下还有子节点，请先删除子节点');
        }

        $location->delete();

        return redirect()->route('locations.index', $request->query())
            ->with('success', '地址删除成功');
    }

    /**
     * AJAX：获取某父节点下的直接子节点（级联选择用）
     */
    public function children(Request $request, $parentId = null)
    {
        $query = Location::query()->where('status', 'active');

        if ($parentId && $parentId !== 'root') {
            $query->where('parent_id', $parentId);
        } else {
            $query->whereNull('parent_id');
        }

        return response()->json(
            $query->orderBy('sort_order')->orderBy('name')
                ->get(['id', 'name', 'parent_id', 'level_id'])
        );
    }

    /**
     * 沿父链向上查找 campus_id（新建/编辑地址时继承父节点的区域）
     */
    private function resolveCampusId($locationId)
    {
        $visited = [];
        $currentId = $locationId;
        while ($currentId && !isset($visited[$currentId])) {
            $visited[$currentId] = true;
            $loc = Location::find($currentId, ['id', 'parent_id', 'campus_id']);
            if (!$loc) break;
            if ($loc->campus_id) return (int) $loc->campus_id;
            $currentId = $loc->parent_id;
        }
        return null;
    }

    /**
     * 区域管理页面
     */
    public function campuses(Request $request)
    {
        $query = Campus::query();

        // 状态筛选
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // 搜索
        if ($request->filled('keyword')) {
            $keyword = $request->input('keyword');
            $query->where(function($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                  ->orWhere('description', 'like', "%{$keyword}%");
            });
        }

        $campuses = $query->orderBy('sort_order')
                          ->orderBy('name')
                          ->paginate(15);

        return view('locations.campuses', compact('campuses'));
    }

    /**
     * 创建区域页面
     */
    public function createCampus()
    {
        return view('locations.create-campus');
    }

    /**
     * 保存区域
     */
    public function storeCampus(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'sort_order' => 'nullable|integer|min:0',
            'status' => 'required|in:active,inactive',
        ]);

        Campus::create($request->all());

        return redirect()->route('locations.campuses', $request->query())
            ->with('success', '区域创建成功');
    }

    /**
     * 区域详情页面
     */
    public function showCampus(Campus $campus)
    {
        $campus->load(['locations' => function($query) {
            $query->orderBy('sort_order')->orderBy('name');
        }]);

        return view('locations.show-campus', compact('campus'));
    }

    /**
     * 编辑区域页面
     */
    public function editCampus(Campus $campus)
    {
        return view('locations.edit-campus', compact('campus'));
    }

    /**
     * 更新区域
     */
    public function updateCampus(Request $request, Campus $campus)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'sort_order' => 'nullable|integer|min:0',
            'status' => 'required|in:active,inactive',
        ]);

        $campus->update($request->all());

        return redirect()->route('locations.campuses', $request->query())
            ->with('success', '区域更新成功');
    }

    /**
     * 删除区域
     */
    public function destroyCampus(Request $request, Campus $campus)
    {
        // 检查是否可以删除
        if (!$campus->canBeDeleted()) {
            return redirect()->route('locations.campuses')
                ->with('error', '该区域下还有地址，无法删除');
        }

        $campus->delete();

        return redirect()->route('locations.campuses', $request->query())
            ->with('success', '区域删除成功');
    }

    /**
     * 切换区域状态
     */
    public function toggleCampusStatus(Request $request, Campus $campus)
    {
        $campus->update([
            'status' => $campus->status === 'active' ? 'inactive' : 'active'
        ]);

        return redirect()->route('locations.campuses', $request->query())
            ->with('success', '区域状态更新成功');
    }

}