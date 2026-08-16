<?php

namespace App\Http\Controllers;

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

        // 默认：按项目分组展示地址树
        $projectTrees = $baseInitialized ? Location::getProjectTrees() : collect();

        return view('locations.index', compact('projectTrees', 'levels', 'baseInitialized', 'baseAddress'));
    }

    /**
     * 项目列表页面（基础地址 Tab）
     */
    public function baseAddressForm()
    {
        $projects = Location::getProjectRoots();
        $baseLevels = LocationLevel::baseLevels();
        $projectData = [];

        foreach ($projects as $root) {
            $chain = [];
            foreach ($root->getAncestors() as $node) {
                $chain[$node->level_id] = $node;
            }
            $childCount = $root->children()->count();
            $projectData[] = [
                'root' => $root,
                'chain' => $chain,
                'full_address' => $root->full_address_delimited,
                'child_count' => $childCount,
            ];
        }

        return view('locations.base-address', compact('projectData', 'baseLevels'));
    }

    /**
     * 新增项目页面（行政区划级联选择）
     */
    public function createProject()
    {
        $baseLevels = LocationLevel::baseLevels();
        if ($baseLevels->isEmpty()) {
            return back()->with('error', '尚未配置基础地址层级');
        }
        return view('locations.project-create', compact('baseLevels'));
    }

    /**
     * 保存新项目
     */
    public function storeProject(Request $request)
    {
        $baseLevels = LocationLevel::baseLevels();
        if ($baseLevels->isEmpty()) {
            return back()->with('error', '尚未配置基础地址层级');
        }

        $rules = [];
        foreach ($baseLevels as $lv) {
            $rules["name_{$lv->code}"] = 'required|string|max:255';
            $rules["code_{$lv->code}"] = 'nullable|string|max:50';
        }
        $validated = $request->validate($rules);

        $parentId = null;
        foreach ($baseLevels as $lv) {
            $name = trim($validated["name_{$lv->code}"]);
            $code = trim($validated["code_{$lv->code}"] ?? '');

            // 同一 parent 下同 level 同名复用
            $node = Location::where('level_id', $lv->id)
                ->where('name', $name)
                ->where('parent_id', $parentId)
                ->first();

            if (! $node) {
                $node = Location::create([
                    'name' => $name,
                    'code' => $code ?: null,
                    'level_id' => $lv->id,
                    'parent_id' => $parentId,
                    'sort_order' => Location::where('level_id', $lv->id)->max('sort_order') + 1,
                    'status' => 'active',
                ]);
            } else {
                if ($code) {
                    $node->code = $code;
                    $node->save();
                }
            }
            $parentId = $node->id;
        }

        return redirect()->route('locations.base-address')
            ->with('success', '项目地址已创建');
    }

    /**
     * 编辑项目门牌/路段
     */
    public function editProject($id)
    {
        $root = Location::findOrFail($id);
        $baseLevels = LocationLevel::baseLevels();
        $chain = [];
        foreach ($root->getAncestors() as $node) {
            $chain[$node->level_id] = $node;
        }
        return view('locations.project-edit', compact('root', 'chain', 'baseLevels'));
    }

    /**
     * 更新项目门牌/路段
     */
    public function updateProject(Request $request, $id)
    {
        $root = Location::findOrFail($id);
        $roadLevel = LocationLevel::where('code', 'road')->first();
        if ($root->level_id !== $roadLevel?->id) {
            return back()->with('error', '目标节点不是项目根');
        }

        $validated = $request->validate([
            'name_road' => 'required|string|max:255',
            'code_road' => 'nullable|string|max:50',
        ]);

        $root->name = trim($validated['name_road']);
        $root->code = trim($validated['code_road'] ?? '') ?: null;
        $root->save();

        return redirect()->route('locations.base-address')
            ->with('success', '项目地址已更新');
    }

    /**
     * 删除项目（仅当无子节点时）
     */
    public function destroyProject($id)
    {
        $root = Location::findOrFail($id);
        if ($root->children()->exists()) {
            return back()->with('error', '该项目下还有地址节点，请先清空子节点');
        }

        // 删除整条基础地址链（仅当链上节点没有其它子节点时逐级清理）
        $current = $root;
        while ($current) {
            $parent = $current->parent;
            // 如果当前节点有其它子节点（属于其它项目），不能删
            if ($current->children()->where('id', '!=', $root->id)->exists() && $current->id !== $root->id) {
                break;
            }
            $current->delete();
            $current = $parent;
        }

        return redirect()->route('locations.base-address')
            ->with('success', '项目已删除');
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
        $example = $dailyLevels->values()->map(function ($lv, $idx) {
            return match ($idx) {
                0 => '总部园区',
                1 => 'A 楼',
                2 => '101 室',
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
}
