<?php

namespace App\Http\Controllers;

use App\Models\WorkorderTemplate;
use App\Models\WorkorderCategorySimplified;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WorkorderTemplateController extends Controller
{
    /**
     * 模板列表
     */
    public function index(Request $request)
    {
        $query = WorkorderTemplate::with(['category', 'creator'])
            ->orderBy('name');

        // 搜索
        if ($request->filled('keyword')) {
            $keyword = $request->input('keyword');
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                  ->orWhere('description', 'like', "%{$keyword}%");
            });
        }

        // 分类筛选
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        $templates = $query->paginate(15);
        $categories = WorkorderCategorySimplified::getTopLevelCategories();

        return view('workorder-templates.index', compact('templates', 'categories'));
    }

    /**
     * 创建模板页面
     */
    public function create()
    {
        $categories = WorkorderCategorySimplified::getTopLevelCategories();
        $subCategories = [];
        
        foreach ($categories as $category) {
            $subCategories[$category->id] = WorkorderCategorySimplified::getSubCategories($category->id);
        }
        
        $categoryOptions = [
            'main' => $categories,
            'sub' => $subCategories,
        ];

        return view('workorder-templates.create', compact('categoryOptions'));
    }

    /**
     * 保存模板
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:200',
            'description' => 'required|string',
            'category_main' => 'required|exists:workorder_categories_simplified,id',
            'category_sub' => 'required|exists:workorder_categories_simplified,id',
            'contact_name' => 'nullable|string|max:100',
            'contact_phone' => 'nullable|string|max:20',
            'contact_email' => 'nullable|email|max:100',
            'campus' => 'nullable|string|in:old_campus,new_campus,asean_campus',
            'building' => 'nullable|string|max:200',
            'location_detail' => 'nullable|string|max:500',
            'time_limit_hours' => 'nullable|integer|min:1|max:168',
            'priority' => 'nullable|in:high,medium,low',
            'source' => 'nullable|in:phone,web,email,scene,other',
            'department_name' => 'nullable|string|max:100',
            'need_visit' => 'boolean',
            'is_emergency' => 'boolean',
            'phone_assisted' => 'boolean',
            'other_reason' => 'nullable|string|max:500',
        ]);

        $data = $request->all();
        $data['category_id'] = $data['category_sub'];
        $data['creator_id'] = Auth::id();
        $data['is_active'] = true;

        WorkorderTemplate::create($data);

        return redirect()->route('workorder-templates.index')
            ->with('success', '工单模板创建成功');
    }

    /**
     * 编辑模板页面
     */
    public function edit(WorkorderTemplate $workorderTemplate)
    {
        $categories = WorkorderCategorySimplified::getTopLevelCategories();
        $subCategories = [];
        
        foreach ($categories as $category) {
            $subCategories[$category->id] = WorkorderCategorySimplified::getSubCategories($category->id);
        }
        
        $categoryOptions = [
            'main' => $categories,
            'sub' => $subCategories,
        ];

        return view('workorder-templates.edit', compact('workorderTemplate', 'categoryOptions'));
    }

    /**
     * 更新模板
     */
    public function update(Request $request, WorkorderTemplate $workorderTemplate)
    {
        $request->validate([
            'name' => 'required|string|max:200',
            'description' => 'required|string',
            'category_main' => 'required|exists:workorder_categories_simplified,id',
            'category_sub' => 'required|exists:workorder_categories_simplified,id',
            'contact_name' => 'nullable|string|max:100',
            'contact_phone' => 'nullable|string|max:20',
            'contact_email' => 'nullable|email|max:100',
            'campus' => 'nullable|string|in:old_campus,new_campus,asean_campus',
            'building' => 'nullable|string|max:200',
            'location_detail' => 'nullable|string|max:500',
            'time_limit_hours' => 'nullable|integer|min:1|max:168',
            'priority' => 'nullable|in:high,medium,low',
            'source' => 'nullable|in:phone,web,email,scene,other',
            'department_name' => 'nullable|string|max:100',
            'need_visit' => 'boolean',
            'is_emergency' => 'boolean',
            'phone_assisted' => 'boolean',
            'other_reason' => 'nullable|string|max:500',
        ]);

        $data = $request->all();
        $data['category_id'] = $data['category_sub'];

        $workorderTemplate->update($data);

        return redirect()->route('workorder-templates.index')
            ->with('success', '工单模板更新成功');
    }

    /**
     * 删除模板
     */
    public function destroy(WorkorderTemplate $workorderTemplate)
    {
        $workorderTemplate->delete();

        return redirect()->route('workorder-templates.index')
            ->with('success', '工单模板删除成功');
    }

    /**
     * 根据模板创建工单
     */
    public function createFromTemplate(Request $request, WorkorderTemplate $workorderTemplate)
    {
        // 获取模板数据并转换为工单数据
        $workorderData = $workorderTemplate->toWorkorderData();
        
        // 重定向到工单创建页面并预填充数据
        return redirect()->route('workorders.create', ['template' => $workorderTemplate->id])
            ->with('from_template', true)
            ->with('template_name', $workorderTemplate->name)
            ->withInput($workorderData);
    }

    /**
     * 切换模板状态
     */
    public function toggleStatus(Request $request, WorkorderTemplate $workorderTemplate)
    {
        $workorderTemplate->update([
            'is_active' => !$workorderTemplate->is_active
        ]);

        return response()->json([
            'success' => true,
            'is_active' => $workorderTemplate->is_active
        ]);
    }
}