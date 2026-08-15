<?php

namespace App\Http\Controllers;

use App\Models\Workorder;
use App\Services\WorkorderSignaturePDFService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class WorkorderSignatureController extends Controller
{
    /**
     * 显示签单页面（故障处理记录单）
     */
    public function create(Workorder $workorder)
    {
        if (!$workorder->canBeOperatedBy(Auth::user(), 'resolve')) {
            abort(403, '您没有权限为此工单发起签单流程');
        }

        if (!$workorder->requires_signature) {
            return redirect()->route('workorders.show', $workorder->id)
                ->with('error', '此工单不需要签单');
        }

        if ($workorder->hasSignature()) {
            return redirect()->route('workorders.show', $workorder->id)
                ->with('info', '此工单已经签单');
        }

        // 允许处理中和已解决的工单进行签单
        if (!in_array($workorder->status, ['processing', 'resolved'])) {
            return redirect()->route('workorders.show', $workorder->id)
                ->with('error', '只有处理中或已解决的工单才能签单');
        }

        return view('workorders.signature', compact('workorder'));
    }

    /**
     * 生成 HTML 格式的处理记录单（只读 / 打印）
     */
    public function generateHtml(Workorder $workorder)
    {
        if (!$workorder->canBeOperatedBy(Auth::user(), 'view')) {
            abort(403, '您没有权限查看此工单处理单');
        }

        return view('workorders.signature-html')->with([
            'workorder' => $workorder,
        ]);
    }

    /**
     * 保存签名（故障处理记录单提交）
     */
    public function store(Request $request, Workorder $workorder)
    {
        if (!$workorder->canBeOperatedBy(Auth::user(), 'resolve')) {
            abort(403, '您没有权限为此工单发起签单流程');
        }

        $validator = Validator::make($request->all(), [
            'signature'         => 'required|string',
            'satisfaction'      => 'required|integer|min:1|max:4',
            'satisfaction_other'=> 'nullable|string|max:200',
            'visit_status'      => 'nullable|string|in:needed,not_needed,visited',
            'feedback'          => 'required|string|max:1000',
        ], [
            'signature.required'        => '签名不能为空',
            'satisfaction.required'     => '请选择满意度',
            'satisfaction.integer'      => '满意度必须是数字',
            'satisfaction.min'          => '满意度值无效',
            'satisfaction.max'          => '满意度值无效',
            'feedback.required'         => '请填写意见和建议',
            'feedback.max'              => '反馈内容不能超过1000个字符',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        if (!$workorder->requires_signature) {
            return back()->with('error', '此工单不需要签单');
        }

        if ($workorder->hasSignature()) {
            return back()->with('error', '此工单已经签单');
        }

        if (!in_array($workorder->status, ['processing', 'resolved'])) {
            return back()->with('error', '只有处理中或已解决的工单才能签单');
        }

        DB::beginTransaction();
        try {
            // 满意度为"其它"时，将用户填写内容附加到反馈中
            $feedbackText = $request->input('feedback');
            if ((int)$request->input('satisfaction') === 4 && $request->filled('satisfaction_other')) {
                $feedbackText = '【' . $request->input('satisfaction_other') . '】' . $feedbackText;
            }

            $updateData = [
                'user_signature'    => $request->input('signature'),
                'user_satisfaction' => $request->input('satisfaction'),
                'user_feedback'     => $feedbackText,
                'visit_status'      => $request->input('visit_status'),
                'user_signed_at'    => now(),
                'is_user_signed'    => true,
            ];

            // 签单字段已从 Workorder::$fillable 移除（防 mass assignment 伪造签名），
            // 这里通过 forceFill 在受信控制器路径写入；上方已做权限 + 状态 + 验证检查
            $workorder->forceFill($updateData)->save();

            // 记录日志
            $satText = WorkorderSignaturePDFService::formatSatisfactionText((int)$request->input('satisfaction'));
            $logContent = '完成签单（故障处理记录单），满意度：' . $satText;
            if ($request->filled('visit_status')) {
                $logContent .= '，回访情况：' . WorkorderSignaturePDFService::formatVisitStatus($request->input('visit_status'));
            }
            if (!empty($feedbackText)) {
                $logContent .= '，意见：' . mb_substr($feedbackText, 0, 80);
            }
           $workorder->addLog('signature_completed', $logContent);

            // 将故障处理记录单保存为工单附件（快照）
            $this->saveRecordFormAsAttachment($workorder);

           DB::commit();

           return redirect()->route('workorders.show', $workorder->id)
               ->with('success', '签单完成，感谢您的反馈！');

        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('工单签单失败', [
                'workorder_id' => $workorder->id,
                'user_id'      => Auth::id(),
                'error'        => $e->getMessage()
            ]);

            return back()->with('error', '签单失败：' . $e->getMessage());
       }
    }

    /**
     * 将已签字的故障处理记录单渲染为独立 HTML 文件并保存为工单附件
     */
    private function saveRecordFormAsAttachment(Workorder $workorder): void
    {
        try {
            // 渲染打印用的 HTML 快照（自包含完整 CSS + 签名图片）
            $html = view('workorders.signature-html', compact('workorder'))->render();

            // 写入私有 attachments 盘（不暴露 /storage 直链，读取走鉴权路由）
            $filename  = 'record_' . $workorder->ticket_no . '_' . \Illuminate\Support\Str::uuid()->toString() . '.html';
            $directory = 'workorder_attachments';
            $filePath  = $directory . '/' . $filename;

            if (!\Illuminate\Support\Facades\Storage::disk('attachments')->exists($directory)) {
                \Illuminate\Support\Facades\Storage::disk('attachments')->makeDirectory($directory);
            }

            \Illuminate\Support\Facades\Storage::disk('attachments')->put($filePath, $html);
            $fileSize = \Illuminate\Support\Facades\Storage::disk('attachments')->size($filePath);

            \App\Models\WorkorderAttachment::create([
                'workorder_id'   => $workorder->id,
                'user_id'        => Auth::id(),
                'filename'       => $filename,
                'original_name'  => '故障处理记录单_' . $workorder->ticket_no . '.html',
                'file_path'      => $filePath,
                'file_type'      => 'document',
                'file_size'      => $fileSize,
                'mime_type'      => 'text/html',
                'description'    => '故障处理记录单（签单快照）',
                'type'           => 'document',
                'is_public'      => true,
                'thumbnail_path' => null,
            ]);
        } catch (\Exception $e) {
            // 附件保存失败不应阻断签单流程
            \Log::error('故障处理记录单附件保存失败', [
                'workorder_id' => $workorder->id,
                'error'        => $e->getMessage(),
            ]);
        }
    }

    /**
     * 获取签单统计信息
     */
    public function getStatistics(Request $request)
    {
        if (!Auth::user()->isAdmin()) {
            return response()->json(['error' => '权限不足'], 403);
        }

        $startDate = $request->input('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate   = $request->input('end_date', now()->format('Y-m-d'));

        $baseQuery = Workorder::where('is_user_signed', true)
            ->whereBetween('user_signed_at', [$startDate, $endDate]);

        $statistics = [
            'total_signatures'     => (clone $baseQuery)->count(),
            'average_satisfaction' => (clone $baseQuery)->avg('user_satisfaction'),
            'satisfaction_distribution' => [],
        ];

        // 新满意度体系：1=满意 2=一般 3=不满意 4=其它
        foreach ([1, 2, 3, 4] as $sat) {
            $statistics['satisfaction_distribution'][$sat] = (clone $baseQuery)
                ->where('user_satisfaction', $sat)->count();
        }

        return response()->json($statistics);
    }
}
