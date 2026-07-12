<?php

namespace App\Http\Controllers;

use App\Models\WorkorderAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttachmentController extends Controller
{
    /**
     * 下载附件
     */
    public function download(WorkorderAttachment $attachment): StreamedResponse
    {
        // 权限检查：只有工单的创建者、分配的处理人、管理员可以下载附件
        if (!auth()->user()->isAdmin() && 
            $attachment->workorder->creator_id !== auth()->id() && 
            $attachment->workorder->assignee_id !== auth()->id()) {
            abort(403, '您没有权限下载此附件');
        }
        
        // 检查文件是否存在
        if (!Storage::disk('public')->exists($attachment->file_path)) {
            abort(404, '文件不存在');
        }
        
        return Storage::disk('public')->download($attachment->file_path, $attachment->original_name);
    }
    
    /**
     * 预览附件（支持图片、PDF和文本文件）
     */
    public function preview(WorkorderAttachment $attachment)
    {
        // 权限检查
        if (!auth()->user()->isAdmin() && 
            $attachment->workorder->creator_id !== auth()->id() && 
            $attachment->workorder->assignee_id !== auth()->id()) {
            abort(403, '您没有权限预览此附件');
        }
        
        // 检查文件是否存在
        if (!Storage::disk('public')->exists($attachment->file_path)) {
            abort(404, '文件不存在');
        }
        
        // 检查是否可以预览
        if (!$attachment->canPreview()) {
            abort(403, '此文件类型不支持预览');
        }
        
        $mimeType = $attachment->mime_type ?: Storage::disk('public')->mimeType($attachment->file_path);
        
        return Storage::disk('public')->response($attachment->file_path, null, [
            'Content-Type' => $mimeType,
           'Content-Disposition' => 'inline',
           'Cache-Control' => 'no-cache, must-revalidate',
        ]);
    }
    
    /**
     * 获取附件信息（用于预览）
     */
    public function info(WorkorderAttachment $attachment)
    {
        // 权限检查：只有工单的创建者、分配的处理人或管理员可以查看附件信息
        if (!auth()->user()->isAdmin() &&
            $attachment->workorder->creator_id !== auth()->id() &&
            $attachment->workorder->assignee_id !== auth()->id()) {
            abort(403, '您没有权限查看此附件信息');
        }
        
        $response = [
            'id' => $attachment->id,
            'original_name' => $attachment->original_name,
            'file_size' => $attachment->file_size,
            'formatted_file_size' => $attachment->formatted_file_size,
            'mime_type' => $attachment->mime_type,
            'file_type' => $attachment->type,
            'file_type_description' => $attachment->file_type_description,
            'extension' => $attachment->extension,
            'is_image' => $attachment->isImage(),
            'is_document' => $attachment->isDocument(),
            'is_video' => $attachment->isVideo(),
            'is_audio' => $attachment->isAudio(),
            'can_preview' => $attachment->canPreview(),
            'preview_type' => $attachment->preview_type,
            'preview_url' => $attachment->canPreview() ? route('attachments.preview', $attachment) : null,
            'download_url' => route('attachments.download', $attachment),
            'uploaded_at' => $attachment->created_at->format('Y-m-d H:i:s'),
            'uploader' => $attachment->user ? $attachment->user->name : '未知用户',
            'description' => $attachment->description,
        ];
        
        // 如果是文本文件，读取内容
        if (in_array($attachment->extension, ['txt', 'md'])) {
            try {
                $content = Storage::disk('public')->get($attachment->file_path);
                // 限制内容长度，避免过大的文本文件
                if (strlen($content) > 50000) {
                    $content = substr($content, 0, 50000) . "\n\n... (内容过长，已截断，请下载完整文件查看)";
                }
                $response['content'] = $content;
            } catch (\Exception $e) {
                $response['content'] = '无法读取文件内容';
            }
        }
        
        return response()->json($response);
    }
    
    /**
     * 删除附件
     */
    public function destroy(WorkorderAttachment $attachment)
    {
        // 权限检查：只有工单创建者、分配的处理人或管理员可以删除附件
        if (!auth()->user()->isAdmin() && 
            $attachment->workorder->creator_id !== auth()->id() && 
            $attachment->workorder->assignee_id !== auth()->id()) {
            abort(403, '您没有权限删除此附件');
        }
        
        // 只有未分配或处理中的工单可以删除附件
        if (!in_array($attachment->workorder->status, ['pending', 'processing'])) {
            return back()->with('error', '当前工单状态不允许删除附件');
        }
        
        if ($attachment->deleteFile()) {
            // 记录日志
            $attachment->workorder->addLog('attachment_deleted', '删除附件：' . $attachment->original_name);
            
            return back()->with('success', '附件删除成功');
        }
        
        return back()->with('error', '附件删除失败');
    }
}
