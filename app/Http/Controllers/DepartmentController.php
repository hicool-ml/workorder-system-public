<?php

namespace App\Http\Controllers;

use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DepartmentController extends Controller
{
    /**
     * 部门列表页面
     */
    public function index(Request $request)
    {
        $query = Department::orderBy('sort_order');

        // 搜索条件
        if ($request->filled('name')) {
            $keyword = $request->input('name');
            $query->where(function($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                  ->orWhere('code', 'like', "%{$keyword}%")
                  ->orWhere('manager_name', 'like', "%{$keyword}%");
            });
        }

        // 状态筛选
        if ($request->filled('is_active')) {
            $status = $request->input('is_active') == '1' ? 'active' : 'inactive';
            $query->where('status', $status);
        }

        $departments = $query->paginate(15);
        
        return view('departments.index', compact('departments'));
    }

    /**
     * 创建部门页面
     */
    public function create()
    {
        return view('departments.create');
    }

    /**
     * 保存部门
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:50|unique:departments,code',
            'manager' => 'nullable|string|max:50',
            'phone' => 'nullable|string|max:20',
            'location' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:500',
            'status' => 'required|in:active,inactive',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        DB::beginTransaction();
        try {
            $data = $request->all();
            
            // 字段映射：将表单字段映射到数据库字段
            if (array_key_exists('manager', $data)) {
                $data['manager_name'] = $data['manager'] ?: null;
                unset($data['manager']);
            }
            
            if (array_key_exists('phone', $data)) {
                $data['manager_phone'] = $data['phone'] ?: null;
                unset($data['phone']);
            }
            
            if (array_key_exists('location', $data)) {
                $data['location'] = $data['location'] ?: null;
            }
            
            if (array_key_exists('description', $data)) {
                $data['description'] = $data['description'] ?: null;
            }
            
            // 如果复选框未选中，设置默认值
            if (!isset($data['status'])) {
                $data['status'] = 'inactive';
            }
            
            Department::create($data);
            
            DB::commit();
            
            return redirect()->route('departments.index', $request->query())
                ->with('success', '部门创建成功');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', '部门创建失败：' . $e->getMessage());
        }
    }

    /**
     * 部门详情页面
     */
    public function show(Department $department)
    {
        $department->load(['users', 'workorders']);
        
        return view('departments.show', compact('department'));
    }

    /**
     * 编辑部门页面
     */
    public function edit(Department $department)
    {
        return view('departments.edit', compact('department'));
    }

    /**
     * 更新部门
     */
    public function update(Request $request, Department $department)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:50|unique:departments,code,' . $department->id,
            'manager' => 'nullable|string|max:50',
            'phone' => 'nullable|string|max:20',
            'location' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:500',
            'status' => 'required|in:active,inactive',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        DB::beginTransaction();
        try {
            $data = $request->all();
            
            // 字段映射：将表单字段映射到数据库字段
            if (array_key_exists('manager', $data)) {
                $data['manager_name'] = $data['manager'] ?: null;
                unset($data['manager']);
            }
            
            if (array_key_exists('phone', $data)) {
                $data['manager_phone'] = $data['phone'] ?: null;
                unset($data['phone']);
            }
            
            if (array_key_exists('location', $data)) {
                $data['location'] = $data['location'] ?: null;
            }
            
            if (array_key_exists('description', $data)) {
                $data['description'] = $data['description'] ?: null;
            }
            
            // 如果复选框未选中，设置默认值
            if (!isset($data['status'])) {
                $data['status'] = 'inactive';
            }
            
            $department->update($data);
            
            DB::commit();
            
            return redirect()->route('departments.index', $request->query())
                ->with('success', '部门更新成功');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', '部门更新失败：' . $e->getMessage());
        }
    }

    /**
     * 删除部门
     */
    public function destroy(Request $request, Department $department)
    {
        // 检查是否有用户
        if ($department->users()->count() > 0) {
            return back()->with('error', '该部门下还有用户，无法删除');
        }

        // 检查是否有关联的工单
        if ($department->workorders()->count() > 0) {
            return back()->with('error', '该部门下还有工单，无法删除');
        }

        try {
            $department->delete();
            return redirect()->route('departments.index', $request->query())
                ->with('success', '部门删除成功');
        } catch (\Exception $e) {
            return back()->with('error', '部门删除失败：' . $e->getMessage());
        }
    }

    /**
     * 获取部门树形结构（用于API）
     */
    public function tree()
    {
        $departments = Department::where('status', 'active')
            ->orderBy('sort_order')
            ->get();
            
        $tree = [];
        foreach ($departments as $department) {
            $tree[] = [
                'id' => $department->id,
                'name' => $department->name,
                'code' => $department->code,
            ];
        }
        
        return response()->json($tree);
    }

    /**
     * 获取部门统计信息
     */
    public function statistics(Department $department)
    {
        $stats = [
            'users_count' => $department->users()->count(),
            'workorders_count' => $department->workorders()->count(),
            'pending_workorders_count' => $department->workorders()
                ->whereIn('status', ['pending', 'assigned', 'processing'])
                ->count(),
            'completed_workorders_count' => $department->workorders()
                ->whereIn('status', ['resolved', 'closed'])
                ->count(),
        ];
        
        return response()->json($stats);
    }
}