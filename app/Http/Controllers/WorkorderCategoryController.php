<?php

namespace App\Http\Controllers;

use App\Models\WorkorderCategory;
use App\Models\WorkorderCategorySimplified;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WorkorderCategoryController extends Controller
{
    /**
     * 分类列表页面
     */
    public function index(Request $request)
    {
        $query = WorkorderCategorySimplified::with(['parent'])->orderBy('sort_order');

        // 搜索条件
        if ($request->filled('keyword')) {
            $keyword = $request->input('keyword');
            $query->where(function($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                  ->orWhere('ticket_prefix', 'like', "%{$keyword}%")
                  ->orWhere('description', 'like', "%{$keyword}%");
            });
        }

        // 层级筛选
        if ($request->filled('level')) {
            if ($request->input('level') == 1) {
                $query->whereNull('parent_id');
            } else {
                $query->whereNotNull('parent_id');
            }
        }

        // 状态筛选
        if ($request->filled('status')) {
            $status = $request->input('status') === 'active' ? true : false;
            $query->where('status', $status);
        }

        $categories = $query->paginate(15);
        
        // 获取顶级分类用于筛选
        $topLevelCategories = WorkorderCategorySimplified::whereNull('parent_id')
            ->where('status', 'active')
            ->orderBy('sort_order')
            ->get();
        
        return view('workorder-categories.index', compact('categories', 'topLevelCategories'));
    }

    /**
     * 创建分类页面
     */
    public function create()
    {
        $parentCategories = WorkorderCategorySimplified::getTopLevelCategories();
            
        return view('workorder-categories.create', compact('parentCategories'));
    }

    /**
     * 保存分类
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'ticket_prefix' => 'nullable|string|max:5',
            'description' => 'nullable|string|max:500',
            'default_hours' => 'nullable|integer|min:1|max:168',
            'color' => 'nullable|string|max:7',
            'parent_id' => 'nullable|exists:workorder_categories_simplified,id',
            'sort_order' => 'nullable|integer|min:0',
            'status' => 'required|string|in:active,inactive',
        ]);

        DB::beginTransaction();
        try {
            $data = $request->all();
            
            // 转换状态值：将字符串转换为布尔值以匹配数据库字段类型
            if (isset($data['status'])) {
                $data['status'] = $data['status'] === 'active' ? true : false;
            }
            
            // 计算分类层级
            if ($request->filled('parent_id')) {
                $parent = WorkorderCategorySimplified::find($request->input('parent_id'));
                if (!$parent) {
                    throw new \Exception('选择的父分类不存在');
                }
                $data['level'] = $parent->level + 1;
                
                // 检查层级限制（最多3级）
                if ($data['level'] > 3) {
                    throw new \Exception('分类层级最多支持3级');
                }
            } else {
                $data['level'] = 1;
            }

            WorkorderCategorySimplified::create($data);
            
            DB::commit();
            
            return redirect()->route('workorder-categories.index', $request->query())
                ->with('success', '分类创建成功');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', '分类创建失败：' . $e->getMessage());
        }
    }

    /**
     * 分类详情页面
     */
    public function show(WorkorderCategorySimplified $workorderCategory)
    {
        $workorderCategory->load(['parent', 'children']);
        
        return view('workorder-categories.show', compact('workorderCategory'));
    }

    /**
     * 编辑分类页面
     */
    public function edit(WorkorderCategorySimplified $workorderCategory)
    {
        $parentCategories = WorkorderCategorySimplified::getTopLevelCategories();
            
        return view('workorder-categories.edit', compact('workorderCategory', 'parentCategories'));
    }

    /**
     * 更新分类
     */
    public function update(Request $request, WorkorderCategorySimplified $workorderCategory)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'ticket_prefix' => 'nullable|string|max:5',
            'description' => 'nullable|string|max:500',
            'default_hours' => 'nullable|integer|min:1|max:168',
            'color' => 'nullable|string|max:7',
            'parent_id' => 'nullable|exists:workorder_categories_simplified,id',
            'sort_order' => 'nullable|integer|min:0',
            'status' => 'required|string|in:active,inactive',
        ]);

        DB::beginTransaction();
        try {
            $data = $request->all();
            
            // 转换状态值：将字符串转换为布尔值以匹配数据库字段类型
            if (isset($data['status'])) {
                $data['status'] = $data['status'] === 'active' ? true : false;
            }
            
            // 计算分类层级
            if ($request->filled('parent_id')) {
                $parent = WorkorderCategorySimplified::find($request->input('parent_id'));
                if (!$parent) {
                    throw new \Exception('选择的父分类不存在');
                }
                $data['level'] = $parent->level + 1;
                
                // 检查层级限制（最多3级）
                if ($data['level'] > 3) {
                    throw new \Exception('分类层级最多支持3级');
                }
                
                // 检查是否将分类设置为自己的子分类
                if ($request->input('parent_id') == $workorderCategory->id) {
                    throw new \Exception('不能将分类设置为自己的子分类');
                }
                
                // 检查是否将分类设置为自己的后代
                if (in_array($request->input('parent_id'), $workorderCategory->getAllChildrenIds())) {
                    throw new \Exception('不能将分类设置为自己的后代分类');
                }
            } else {
                $data['level'] = 1;
            }

            $workorderCategory->update($data);
            
            DB::commit();
            
            return redirect()->route('workorder-categories.index', $request->query())
                ->with('success', '分类更新成功');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', '分类更新失败：' . $e->getMessage());
        }
    }

    /**
     * 删除分类
     */
    public function destroy(WorkorderCategorySimplified $workorderCategory)
    {
        // 检查是否有子分类
        if ($workorderCategory->children()->count() > 0) {
            return back()->with('error', '该分类下还有子分类，无法删除');
        }
        
        try {
            $workorderCategory->delete();
            return redirect()->route('workorder-categories.index', $request->query())
                ->with('success', '分类删除成功');
        } catch (\Exception $e) {
            return back()->with('error', '分类删除失败：' . $e->getMessage());
        }
    }


    /**
     * 获取分类选项（用于API）
     */
    public function options(Request $request)
    {
        $parentId = $request->input('parent_id');
        $options = [];
        
        if ($parentId) {
            $options = WorkorderCategorySimplified::getSubCategories($parentId);
        } else {
            $options = WorkorderCategorySimplified::getTopLevelCategories();
        }
        
        return response()->json($options);
    }

    /**
     * 获取三级联动分类数据（用于API）
     */
    public function cascade()
    {
        $data = [
            'main' => WorkorderCategorySimplified::getTopLevelCategories(),
        ];
        
        foreach ($data['main'] as $category) {
            $data['sub'][$category->id] = WorkorderCategorySimplified::getSubCategories($category->id);
        }
        
        return response()->json($data);
    }

    /**
     * 批量更新排序
     */
    public function updateSort(Request $request)
    {
        $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:workorder_categories_simplified,id',
            'items.*.sort_order' => 'required|integer|min:0',
        ]);
        
        DB::beginTransaction();
        try {
            foreach ($request->input('items') as $item) {
                WorkorderCategorySimplified::where('id', $item['id'])
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
     * 获取分类统计信息
     */
    public function statistics(WorkorderCategorySimplified $workorderCategory)
    {
        $stats = [
            'workorders_count' => 0, // 简化版本暂不统计工单数量
            'children_count' => $workorderCategory->children()->count(),
        ];
        
        return response()->json($stats);
    }
}
