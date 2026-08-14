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
        $query = WorkorderTemplate::with('creator')->orderBy('name');

        if ($request->filled('keyword')) {
            $kw = $request->input('keyword');
            $query->where('name', 'like', "%{$kw}%");
        }

        $templates = $query->paginate(15);
        return view('workorder-templates.index', compact('templates'));
    }

    /**
     * 创建模板页面
     */
    public function create()
    {
        $presetFields = WorkorderTemplate::getPresetFields();
        $categories = WorkorderCategorySimplified::getTopLevelCategories();

        // 已被其它模板绑定的大类，用于前端标记
        $boundCategoryIds = WorkorderTemplate::whereNotNull('category_main_id')
            ->where('is_active', true)
            ->pluck('category_main_id')
            ->all();

        return view('workorder-templates.create', compact('presetFields', 'categories', 'boundCategoryIds'));
    }

    /**
     * 保存模板
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:200',
            'fields' => 'required|json',
            'category_main_id' => 'nullable|integer',
        ]);

        $fields = json_decode($request->input('fields'), true);

        $catMainId = $request->filled('category_main_id') ? (int) $request->input('category_main_id') : null;

        // 一个大类只能绑定一个模板
        if ($catMainId) {
            WorkorderTemplate::where('category_main_id', $catMainId)->update(['category_main_id' => null]);
        }

        WorkorderTemplate::create([
            'name' => $request->input('name'),
            'fields' => $fields,
            'category_main_id' => $catMainId,
            'is_active' => true,
            'creator_id' => Auth::id(),
        ]);

        return redirect()->route('workorder-templates.index')
            ->with('success', '模板创建成功');
    }

    /**
     * 编辑模板页面
     */
    public function edit(WorkorderTemplate $workorderTemplate)
    {
        $presetFields = WorkorderTemplate::getPresetFields();
        $categories = WorkorderCategorySimplified::getTopLevelCategories();
        $existingFields = $workorderTemplate->fields ?? [];

        $boundCategoryIds = WorkorderTemplate::whereNotNull('category_main_id')
            ->where('is_active', true)
            ->where('id', '!=', $workorderTemplate->id)
            ->pluck('category_main_id')
            ->all();

        return view('workorder-templates.edit', compact('workorderTemplate', 'presetFields', 'categories', 'existingFields', 'boundCategoryIds'));
    }

    /**
     * 更新模板
     */
    public function update(Request $request, WorkorderTemplate $workorderTemplate)
    {
        $request->validate([
            'name' => 'required|string|max:200',
            'fields' => 'required|json',
            'category_main_id' => 'nullable|integer',
        ]);

        $fields = json_decode($request->input('fields'), true);
        $catMainId = $request->filled('category_main_id') ? (int) $request->input('category_main_id') : null;

        if ($catMainId) {
            WorkorderTemplate::where('category_main_id', $catMainId)
                ->where('id', '!=', $workorderTemplate->id)
                ->update(['category_main_id' => null]);
        }

        $workorderTemplate->update([
            'name' => $request->input('name'),
            'fields' => $fields,
            'category_main_id' => $catMainId,
        ]);

        return redirect()->route('workorder-templates.index')
            ->with('success', '模板更新成功');
    }

    /**
     * 删除模板
     */
    public function destroy(WorkorderTemplate $workorderTemplate)
    {
        $workorderTemplate->delete();
        return redirect()->route('workorder-templates.index')
            ->with('success', '模板已删除');
    }

    /**
     * 根据模板创建工单（预填数据）
     */
    public function createFromTemplate(Request $request, WorkorderTemplate $workorderTemplate)
    {
        $data = $workorderTemplate->toWorkorderData();

        return redirect()->route('workorders.create', ['template' => $workorderTemplate->id])
            ->with('from_template', true)
            ->with('template_name', $workorderTemplate->name)
            ->withInput($data);
    }

    /**
     * 按大类 ID 获取绑定的模板（工单创建页 AJAX 调用）
     */
    public function getByCategory($categoryId)
    {
        $template = WorkorderTemplate::where('category_main_id', $categoryId)
            ->where('is_active', true)
            ->first();

        if (!$template) {
            return response()->json(['found' => false]);
        }

        // 提取已启用字段名列表（essential 永远启用，suggested 看模板里有没有）
        $enabledFields = [];
        $fields = $template->fields ?? [];
        $essentialNames = array_column(WorkorderTemplate::ESSENTIAL_FIELDS, 'name');
        foreach ($essentialNames as $n) $enabledFields[] = $n;
        foreach ($fields as $f) {
            if (($f['category'] ?? '') === 'suggested') {
                $enabledFields[] = $f['name'];
            }
        }

        return response()->json([
            'found' => true,
            'template_name' => $template->name,
            'fields' => $template->toWorkorderData(),
            'custom_fields' => $template->getCustomFields(),
            'enabled_fields' => $enabledFields,
        ]);
    }

    /**
     * 切换模板状态
     */
    public function toggleStatus(Request $request, WorkorderTemplate $workorderTemplate)
    {
        $workorderTemplate->update(['is_active' => !$workorderTemplate->is_active]);

        return response()->json([
            'success' => true,
            'is_active' => $workorderTemplate->is_active,
        ]);
    }
}
