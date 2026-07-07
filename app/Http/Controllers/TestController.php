<?php

namespace App\Http\Controllers;

use App\Models\WorkorderType;
use Illuminate\Http\Request;

class TestController extends Controller
{
    /**
     * 测试分类功能
     */
    public function categories()
    {
        return view('test-simple');
    }
    
    /**
     * 获取子分类API
     */
    public function getSubCategories(Request $request)
    {
        $parentId = $request->input('parent_id');
        
        if (!$parentId) {
            return response()->json([]);
        }
        
        $categories = WorkorderType::where('parent_id', $parentId)
            ->where('status', 'active')
            ->orderBy('sort_order')
            ->get(['id', 'name']);
            
        return response()->json($categories);
    }
}