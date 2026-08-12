<?php

namespace App\Http\Controllers;

use App\Models\Campus;
use App\Models\Location;
use App\Models\LocationLevel;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    /**
     * 地址列表页面 —— 树形展示
     */
    public function index(Request $request)
    {
        $levels = LocationLevel::getActiveLevels();
        $baseInitialized = Location::isBaseAddressInitialized();
        $baseRoot = $baseInitialized ? Location::getDailyRoot() : null;
        $baseAddress = $baseRoot ? $baseRoot->full_address_delimited : null;

        // 搜索模式：扁平结果
        if ($request->filled('keyword')) {
            $keyword = $request->input('keyword');
            $results = Location::with('level', 'parent')
                ->where('name', 'like', "%{$keyword}%")
                ->orWhere('description', 'like', "%{$keyword}%")
                ->orderBy('sort_order')->orderBy('name')
                ->paginate(20);

            return view('locations.index', compact('results', 'levels', 'baseInitialized', 'baseAddress'));
        }

        // 默认：仅日常地址树（基础地址作为一行展示，不再逐级展开）
        $tree = $baseInitialized ? Location::getDailyTree() : collect();

        return view('locations.index', compact('tree', 'levels', 'baseInitialized', 'baseAddress'));
    }

    /**
     * 基础地址初始化页面
     */
    public function baseAddressForm()
    {
        $baseLevels = LocationLevel::baseLevels();
        $existing = [];

        $root = Location::getDailyRoot();
        if ($root) {
            foreach ($root->getAncestors() as $node) {
                $existing[$node->level_id] = $node;
            }
        }

        return view('locations.base-address', compact('baseLevels', 'existing'));
    }

    /**
     * 保存基础地址（省→市→区县→街道→门牌 一次性初始化）
     */
    public function initBaseAddress(Request $request)
    {
        $baseLevels = LocationLevel::baseLevels();
        if ($baseLevels->isEmpty()) {
            return back()->with('error', '尚未配置基础地址层级，请先在「层级定义」中配置');
        }

        $rules = [];
        foreach ($baseLevels as $lv) {
            $rules["name_{$lv->code}"] = 'required|string|max:255';
            $rules["code_{$lv->code}"] = 'nullable|string|max:50';
        }
        $validated = $request->validate($rules);

        // 已初始化的场景：就地更新现有链，避免重复建节点
        $chain = [];
        if ($root = Location::getDailyRoot()) {
            foreach ($root->getAncestors() as $node) {
                $chain[$node->level_id] = $node;
            }
        }

        $parentId = null;
        $changed = false;
        foreach ($baseLevels as $lv) {
            $name = trim($validated["name_{$lv->code}"]);
            $code = trim($validated["code_{$lv->code}"] ?? '');

            $node = $chain[$lv->id] ?? Location::where('level_id', $lv->id)->where('name', $name)->first();
            if ($node) {
                $node->name = $name;
                $node->code = $code ?: null;
                $node->parent_id = $parentId;
                $node->status = 'active';
                $node->save();
            } else {
                $node = Location::create([
                    'name' => $name,
                    'code' => $code ?: null,
                    'level_id' => $lv->id,
                    'parent_id' => $parentId,
                    'sort_order' => 1,
                    'status' => 'active',
                ]);
            }
            $parentId = $node->id;
            $changed = true;
        }

        if (! $changed) {
            return back()->with('info', '基础地址未发生变化');
        }

        return redirect()->route('locations.index')->with('success', '基础地址初始化完成');
    }

    /**
     * 日常地址批量导入页面
     */
    public function importForm()
    {
        $baseInitialized = Location::isBaseAddressInitialized();
        $dailyLevels = LocationLevel::dailyLevels();
        $root = $baseInitialized ? Location::getDailyRoot() : null;

        return view('locations.import', compact('baseInitialized', 'dailyLevels', 'root'));
    }

    /**
     * 下载导入模板（CSV，列 = 日常层级，带 UTF-8 BOM 便于 Excel 打开）
     */
    public function importTemplate()
    {
        $dailyLevels = LocationLevel::dailyLevels();
        if ($dailyLevels->isEmpty()) {
            return back()->with('error', '尚未配置日常层级（如校区/园区、楼栋、房间），无法生成模板');
        }

        $headers = $dailyLevels->pluck('name')->all();
        $example = $dailyLevels->map(function ($lv) {
            return match ($lv->code) {
                'campus' => '总部园区',
                'building' => 'A 楼',
                'room' => '101 室',
                default => $lv->name.'示例',
            };
        })->all();

        $callback = function () use ($headers, $example) {
            $fh = fopen('php://output', 'w');
            fwrite($fh, "\xEF\xBB\xBF");
            fputcsv($fh, $headers);
            fputcsv($fh, $example);
            fclose($fh);
        };

        return response()->streamDownload($callback, '地址导入模板.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * 处理日常地址 CSV 批量导入（逐级查找或创建，复用已有节点）
     */
    public function importStore(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $dailyLevels = LocationLevel::dailyLevels()->values();
        if ($dailyLevels->isEmpty()) {
            return back()->with('error', '尚未配置日常层级，请先在「层级定义」中启用校区/园区、楼栋等层级');
        }
        if (! Location::isBaseAddressInitialized()) {
            return back()->with('error', '基础地址尚未初始化，请先完成基础地址初始化');
        }

        $root = Location::getDailyRoot();
        $file = $request->file('file');
        $fh = fopen($file->getRealPath(), 'r');
        if ($fh === false) {
            return back()->with('error', '无法读取上传文件');
        }

        fgetcsv($fh); // 跳过表头
        $created = 0;
        $found = 0;
        $skipped = 0;
        $errors = [];
        $rowNum = 1;

        while (($row = fgetcsv($fh)) !== false) {
            $rowNum++;
            $row = array_map(fn ($v) => trim((string) ($v ?? '')), $row);
            if (count(array_filter($row)) === 0) {
                continue; // 空行
            }

            $current = $root;
            $path = [];
            foreach ($dailyLevels as $i => $lv) {
                $name = $row[$i] ?? '';
                if ($name === '') {
                    if ($i > 0) {
                        $skipped++;
                        $errors[] = "第 {$rowNum} 行：中间层级为空，已跳过（".implode(' / ', $path).'）';
                    }
                    break;
                }

                $child = $current->children()->where('level_id', $lv->id)->where('name', $name)->first();
                if ($child) {
                    $found++;
                    $current = $child;
                    $path[] = $name;

                    continue;
                }

                $child = $current->children()->create([
                    'name' => $name,
                    'code' => null,
                    'level_id' => $lv->id,
                    'campus_id' => $this->resolveImportedCampusId($lv, $name, $current),
                    'sort_order' => $current->children()->count(),
                    'status' => 'active',
                ]);
                $created++;
                $current = $child;
                $path[] = $name;
            }
        }

        fclose($fh);

        $message = "导入完成：新增 {$created} 个节点，命中已有 {$found} 个节点";
        if ($skipped > 0) {
            $message .= "，跳过 {$skipped} 行";
            $message .= '。错误示例：'.implode('；', array_slice($errors, 0, 5));
        }

        return redirect()->route('locations.import')->with('success', $message);
    }

    /**
     * 导入时确定校区节点对应的 campus_id（按名称匹配历史 campuses 表）
     */
    private function resolveImportedCampusId($level, string $name, $parent)
    {
        if ($level->code === 'campus') {
            $campus = Campus::where('name', $name)->first();

            return $campus ? (int) $campus->id : null;
        }

        return $parent->campus_id ? (int) $parent->campus_id : null;
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
        if (! empty($validated['parent_id'])) {
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
        if (! empty($validated['parent_id'])) {
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
        while ($currentId && ! isset($visited[$currentId])) {
            $visited[$currentId] = true;
            $loc = Location::find($currentId, ['id', 'parent_id', 'campus_id']);
            if (! $loc) {
                break;
            }
            if ($loc->campus_id) {
                return (int) $loc->campus_id;
            }
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
            $query->where(function ($q) use ($keyword) {
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
        $campus->load(['locations' => function ($query) {
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
        if (! $campus->canBeDeleted()) {
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
            'status' => $campus->status === 'active' ? 'inactive' : 'active',
        ]);

        return redirect()->route('locations.campuses', $request->query())
            ->with('success', '区域状态更新成功');
    }
}
