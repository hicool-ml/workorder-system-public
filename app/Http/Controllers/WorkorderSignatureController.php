<?php

namespace App\Http\Controllers;

use App\Models\Workorder;
use App\Services\WorkorderSignaturePDFService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class WorkorderSignatureController extends Controller
{
    /**
     * 显示签名页面
     */
    public function create(Workorder $workorder)
    {
        // 权限检查：工单处理人、协助人或创建人可以发起签单流程
        if (!$workorder->canBeOperatedBy(Auth::user(), 'resolve')) {
            abort(403, '您没有权限为此工单发起签单流程');
        }
        // 检查工单是否需要签单
        if (!$workorder->requires_signature) {
            return redirect()->route('workorders.show', $workorder->id)
                ->with('error', '此工单不需要签单');
        }
        
        // 检查是否已经签单
        if ($workorder->hasSignature()) {
            return redirect()->route('workorders.show', $workorder->id)
                ->with('info', '此工单已经签单');
        }
        
        // 检查工单状态
        // 允许处理中和已解决的工单进行签单
        if (!in_array($workorder->status, ['processing', 'resolved'])) {
            return redirect()->route('workorders.show', $workorder->id)
                ->with('error', '只有处理中或已解决的工单才能签单');
        }
        
        return view('workorders.signature', compact('workorder'));
    }
    
    /**
     * 生成HTML格式的处理单
     */
    public function generateHtml(Workorder $workorder)
    {
        // 权限检查：工单处理人、协助人或创建人可以查看处理单
        if (!$workorder->canBeOperatedBy(Auth::user(), 'view')) {
            abort(403, '您没有权限查看此工单处理单');
        }
        
        // 返回HTML格式的处理单视图
        return view('workorders.signature-html')->with([
            'workorder' => $workorder,
            'signatureDate' => now()->format('Y年m月d日 H:i:s')
        ]);
    }
    
    /**
     * 保存签名
     */
    public function store(Request $request, Workorder $workorder)
    {
        // 权限检查：工单处理人、协助人或创建人可以发起签单流程
        if (!$workorder->canBeOperatedBy(Auth::user(), 'resolve')) {
            abort(403, '您没有权限为此工单发起签单流程');
        }
        
        // 验证请求
        $validator = Validator::make($request->all(), [
            'signature' => 'required|string',
            'satisfaction' => 'required|integer|min:1|max:5',
            'feedback' => 'nullable|string|max:1000',
        ], [
            'signature.required' => '签名不能为空',
            'satisfaction.required' => '请选择满意度评分',
            'satisfaction.integer' => '满意度评分必须是数字',
            'satisfaction.min' => '满意度评分最低为1分',
            'satisfaction.max' => '满意度评分最高为5分',
            'feedback.max' => '反馈内容不能超过1000个字符',
        ]);
        
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }
        
        // 检查工单状态和签单要求
        if (!$workorder->requires_signature) {
            return back()->with('error', '此工单不需要签单');
        }
        
        if ($workorder->hasSignature()) {
            return back()->with('error', '此工单已经签单');
        }
        
        // 允许处理中和已解决的工单进行签单
        if (!in_array($workorder->status, ['processing', 'resolved'])) {
            return back()->with('error', '只有处理中或已解决的工单才能签单');
        }
        
        DB::beginTransaction();
        try {
            // 准备签名数据
            $signatureData = [
                'signature' => $request->input('signature'),
                'satisfaction' => $request->input('satisfaction'),
                'feedback' => $request->input('feedback'),
                'signed_at' => now()->format('Y年m月d日 H:i:s'),
            ];
            
            // 更新工单签单信息
            $workorder->update([
                'user_signature' => $signatureData['signature'],
                'user_satisfaction' => $signatureData['satisfaction'],
                'user_feedback' => $signatureData['feedback'],
                'user_signed_at' => now(),
                'is_user_signed' => true,
            ]);
            
            // 记录日志
            $satisfactionText = \App\Services\WorkorderSignaturePDFService::formatSatisfactionText($signatureData['satisfaction']);
            $logContent = "完成签单，满意度：{$satisfactionText}";
            if (!empty($signatureData['feedback'])) {
                $logContent .= "，反馈：{$signatureData['feedback']}";
            }
            $workorder->addLog('signature_completed', $logContent);
            
            // 发送通知
            $workorder->sendNotification('signature_completed');
            
            DB::commit();
            
            return redirect()->route('workorders.show', $workorder->id)
                ->with('success', '签单完成，感谢您的反馈！');
                
        } catch (\Exception $e) {
            DB::rollBack();
            
            \Log::error('工单签单失败', [
                'workorder_id' => $workorder->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);
            
            return back()->with('error', '签单失败：' . $e->getMessage());
        }
    }
    
    /**
     * 获取签单统计信息
     */
    public function getStatistics(Request $request)
    {
        // 权限检查：只有管理员可以查看统计
        if (!Auth::user()->isAdmin()) {
            return response()->json(['error' => '权限不足'], 403);
        }
        
        $startDate = $request->input('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->format('Y-m-d'));
        
        $statistics = [
            'total_signatures' => Workorder::where('is_user_signed', true)
                ->whereBetween('user_signed_at', [$startDate, $endDate])
                ->count(),
            'average_satisfaction' => Workorder::where('is_user_signed', true)
                ->whereBetween('user_signed_at', [$startDate, $endDate])
                ->avg('user_satisfaction'),
            'satisfaction_distribution' => [
                1 => Workorder::where('is_user_signed', true)
                    ->whereBetween('user_signed_at', [$startDate, $endDate])
                    ->where('user_satisfaction', 1)->count(),
                2 => Workorder::where('is_user_signed', true)
                    ->whereBetween('user_signed_at', [$startDate, $endDate])
                    ->where('user_satisfaction', 2)->count(),
                3 => Workorder::where('is_user_signed', true)
                    ->whereBetween('user_signed_at', [$startDate, $endDate])
                    ->where('user_satisfaction', 3)->count(),
                4 => Workorder::where('is_user_signed', true)
                    ->whereBetween('user_signed_at', [$startDate, $endDate])
                    ->where('user_satisfaction', 4)->count(),
                5 => Workorder::where('is_user_signed', true)
                    ->whereBetween('user_signed_at', [$startDate, $endDate])
                    ->where('user_satisfaction', 5)->count(),
            ],
        ];
        
        return response()->json($statistics);
    }
}