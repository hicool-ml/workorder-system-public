<?php

namespace App\Http\Controllers;

use App\Models\WorkorderAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttachmentController extends Controller
{
    /**
     * 附件所在磁盘：新文件在私有 attachments 盘；迁移期旧文件仍可能位于 public 盘
     */
    private function diskFor(WorkorderAttachment $attachment): string
    {
        if (Storage::disk('attachments')->exists($attachment->file_path)) {
            return 'attachments';
        }
        if (Storage::disk('public')->exists($attachment->file_path)) {
            return 'public';
        }
        return 'attachments';
    }

    /**
     * 下载附件
     */
    public function download(WorkorderAttachment $attachment): StreamedResponse
    {
        // 权限检查：统一使用 canViewWorkorder（覆盖管理员/工单管理员/创建者/处理人/协作工程师）
        if (!auth()->user()->canViewWorkorder($attachment->workorder)) {
            abort(403, '您没有权限下载此附件');
        }

        $disk = $this->diskFor($attachment);
        if (!Storage::disk($disk)->exists($attachment->file_path)) {
            abort(404, '文件不存在');
        }

        return Storage::disk($disk)->download($attachment->file_path, $attachment->original_name);
    }

    /**
     * 预览附件（支持图片、PDF和文本文件）
     */
    public function preview(WorkorderAttachment $attachment)
    {
        // 权限检查：统一使用 canViewWorkorder
        if (!auth()->user()->canViewWorkorder($attachment->workorder)) {
            abort(403, '您没有权限预览此附件');
        }

        $disk = $this->diskFor($attachment);
        if (!Storage::disk($disk)->exists($attachment->file_path)) {
            abort(404, '文件不存在');
        }

        // 检查是否可以预览
        if (!$attachment->canPreview()) {
            abort(403, '此文件类型不支持预览');
        }

        $mimeType = $attachment->mime_type ?: Storage::disk($disk)->mimeType($attachment->file_path);

        // 纵深防御：文本类强制 text/plain，HTML 快照强制下载（防内联执行）
        $headers = [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline',
            'Cache-Control' => 'no-cache, must-revalidate',
            'X-Content-Type-Options' => 'nosniff',
        ];
        if (in_array($attachment->extension, ['txt', 'md'], true)) {
            $headers['Content-Type'] = 'text/plain; charset=UTF-8';
        } elseif ($attachment->extension === 'html' || str_contains($mimeType, 'text/html')) {
            $headers['Content-Type'] = 'text/plain; charset=UTF-8';
        }

        return Storage::disk($disk)->response($attachment->file_path, null, $headers);
    }

    /**
     * 获取附件信息（用于预览）
     */
    public function info(WorkorderAttachment $attachment)
    {
        // 权限检查：统一使用 canViewWorkorder（与下载/预览一致）
        if (!auth()->user()->canViewWorkorder($attachment->workorder)) {
            abort(403, '您没有权限查看此附件信息');
        }

        $disk = $this->diskFor($attachment);
        
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
                $content = Storage::disk($disk)->get($attachment->file_path);
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
     * 删除附件：仅上传者本人或管理员/工单管理员；resolved 后一律禁删（证据保全）
     */
    public function destroy(WorkorderAttachment $attachment)
    {
        $user = auth()->user();

        $isUploader = $attachment->user_id === $user->id;
        $isManager = $user->isAdmin() || $user->role === 'workorder_manager';

        if (!$isUploader && !$isManager) {
            abort(403, '只有上传者本人或管理员可以删除附件');
        }

        // 已完结/已解决的工单不允许删除附件（证据保全），已关闭同样禁删
        if (in_array($attachment->workorder->status, ['resolved', 'completed', 'closed'])) {
            return back()->with('error', '工单已解决/完结，不允许删除附件');
        }

        if ($attachment->deleteFile()) {
            // 记录日志
            $attachment->workorder->addLog('attachment_deleted', '删除附件：' . $attachment->original_name);

            return back()->with('success', '附件删除成功');
        }

        return back()->with('error', '附件删除失败');
    }
}
