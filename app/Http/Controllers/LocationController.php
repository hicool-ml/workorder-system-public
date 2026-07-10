<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\Campus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class LocationController extends Controller
{
    /**
     * 构造函数，设置权限检查
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            if (!Auth::user()->isAdmin() && !Auth::user()->isWorkorderManager()) {
                abort(403, '只有管理员或工单管理员可以管理地址');
            }
            
            return $next($request);
        });
    }

    /**
     * 地址列表页面
     */
    public function index(Request $request)
    {
        $query = Location::query();

        // 校区筛选
        if ($request->filled('campus_id')) {
            $query->where('campus_id', $request->input('campus_id'));
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

        $locations = $query->with('campus')->ordered()->paginate(15);
        $campuses = Campus::getActiveOptions();

        return view('locations.index', compact('locations', 'campuses'));
    }

    /**
     * 创建地址页面
     */
    public function create()
    {
        $campuses = Campus::getActiveOptions();
        return view('locations.create', compact('campuses'));
    }

    /**
     * 保存地址
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'campus_id' => 'required|exists:campuses,id',
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
        $campuses = Campus::getActiveOptions();
        return view('locations.edit', compact('location', 'campuses'));
    }

    /**
     * 更新地址
     */
    public function update(Request $request, Location $location)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'campus_id' => 'required|exists:campuses,id',
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

    /**
     * 校区管理页面
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
     * 创建校区页面
     */
    public function createCampus()
    {
        return view('locations.create-campus');
    }

    /**
     * 保存校区
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
            ->with('success', '校区创建成功');
    }

    /**
     * 校区详情页面
     */
    public function showCampus(Campus $campus)
    {
        $campus->load(['locations' => function($query) {
            $query->orderBy('sort_order')->orderBy('name');
        }]);

        return view('locations.show-campus', compact('campus'));
    }

    /**
     * 编辑校区页面
     */
    public function editCampus(Campus $campus)
    {
        return view('locations.edit-campus', compact('campus'));
    }

    /**
     * 更新校区
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
            ->with('success', '校区更新成功');
    }

    /**
     * 删除校区
     */
    public function destroyCampus(Request $request, Campus $campus)
    {
        // 检查是否可以删除
        if (!$campus->canBeDeleted()) {
            return redirect()->route('locations.campuses')
                ->with('error', '该校区下还有地址，无法删除');
        }

        $campus->delete();

        return redirect()->route('locations.campuses', $request->query())
            ->with('success', '校区删除成功');
    }

    /**
     * 切换校区状态
     */
    public function toggleCampusStatus(Request $request, Campus $campus)
    {
        $campus->update([
            'status' => $campus->status === 'active' ? 'inactive' : 'active'
        ]);

        return redirect()->route('locations.campuses', $request->query())
            ->with('success', '校区状态更新成功');
    }

}