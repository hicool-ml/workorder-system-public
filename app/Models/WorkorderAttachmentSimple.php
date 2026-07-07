<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * 简化的附件模型，用于避免图片压缩和缩略图生成导致的卡顿问题
 */
class WorkorderAttachmentSimple extends Model
{
    use HasFactory;

    protected $fillable = [
        'workorder_id',
        'user_id',
        'filename',
        'original_name',
        'file_path',
        'file_type',
        'file_size',
        'mime_type',
        'description',
        'type',
        'is_public',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'is_public' => 'boolean',
    ];

    /**
     * 获取关联的工单
     */
    public function workorder(): BelongsTo
    {
        return $this->belongsTo(Workorder::class);
    }

    /**
     * 获取上传用户
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 获取文件类型文本
     */
    public function getTypeTextAttribute(): string
    {
        $types = [
            'image' => '图片',
            'document' => '文档',
            'video' => '视频',
            'audio' => '音频',
            'other' => '其他',
        ];
        
        return $types[$this->type] ?? $this->type;
    }

    /**
     * 获取格式化的文件大小
     */
    public function getFormattedFileSizeAttribute(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * 获取文件扩展名
     */
    public function getExtensionAttribute(): string
    {
        return pathinfo($this->filename, PATHINFO_EXTENSION);
    }

    /**
     * 获取附件标题（优先使用描述，否则使用原文件名）
     */
    public function getTitleAttribute(): string
    {
        return $this->description ?: $this->original_name;
    }

    /**
     * 检查是否为图片
     */
    public function isImage(): bool
    {
        return $this->type === 'image' || in_array(strtolower($this->extension), ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp']);
    }

    /**
     * 检查是否为文档
     */
    public function isDocument(): bool
    {
        return $this->type === 'document' || in_array(strtolower($this->extension), ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt']);
    }

    /**
     * 检查是否为视频
     */
    public function isVideo(): bool
    {
        return $this->type === 'video' || in_array(strtolower($this->extension), ['mp4', 'avi', 'mov', 'wmv', 'flv', 'mkv']);
    }

    /**
     * 检查是否为音频
     */
    public function isAudio(): bool
    {
        return $this->type === 'audio' || in_array(strtolower($this->extension), ['mp3', 'wav', 'flac', 'aac', 'ogg']);
    }

    /**
     * 获取文件的完整URL
     */
    public function getUrlAttribute(): string
    {
        return Storage::url($this->file_path);
    }

    /**
     * 获取文件的下载URL
     */
    public function getDownloadUrlAttribute(): string
    {
        return route('attachments.download', $this->id);
    }

    /**
     * 获取文件的预览URL
     */
    public function getPreviewUrlAttribute(): ?string
    {
        if ($this->isImage()) {
            return $this->url;
        }
        
        // 对于PDF文件，可以生成预览图（如果需要的话）
        if ($this->extension === 'pdf') {
            return null; // 可以后续添加PDF预览功能
        }
        
        // 对于文本文件，可以返回内容预览
        if (in_array($this->extension, ['txt', 'md'])) {
            return route('attachments.preview', $this->id);
        }
        
        return null;
    }

    /**
     * 获取缩略图URL
     */
    public function getThumbnailUrlAttribute(): ?string
    {
        // 如果是图片但没有缩略图，返回原图
        if ($this->isImage()) {
            return $this->url;
        }
        
        return null;
    }

    /**
     * 检查是否可以生成预览
     */
    public function canGeneratePreview(): bool
    {
        return $this->isImage() ||
               $this->extension === 'pdf' ||
               in_array($this->extension, ['txt', 'md']);
    }

    /**
     * 获取预览类型
     */
    public function getPreviewType(): string
    {
        if ($this->isImage()) {
            return 'image';
        }
        
        if ($this->extension === 'pdf') {
            return 'pdf';
        }
        
        if (in_array($this->extension, ['txt', 'md'])) {
            return 'text';
        }
        
        if ($this->isVideo()) {
            return 'video';
        }
        
        if ($this->isAudio()) {
            return 'audio';
        }
        
        return 'download';
    }

    /**
     * 检查文件是否可以预览
     */
    public function canPreview(): bool
    {
        return in_array($this->getPreviewType(), ['image', 'pdf', 'text']);
    }

    /**
     * 获取文件类型描述
     */
    public function getFileTypeDescription(): string
    {
        $descriptions = [
            'image' => '图片文件',
            'document' => '文档文件',
            'video' => '视频文件',
            'audio' => '音频文件',
            'other' => '其他文件',
        ];
        
        return $descriptions[$this->type] ?? '未知文件';
    }

    /**
     * 获取文件图标CSS类
     */
    public function getFileIcon(): string
    {
        $extension = strtolower($this->extension);
        $iconMap = [
            'pdf' => 'fas fa-file-pdf text-danger',
            'doc' => 'fas fa-file-word text-primary',
            'docx' => 'fas fa-file-word text-primary',
            'xls' => 'fas fa-file-excel text-success',
            'xlsx' => 'fas fa-file-excel text-success',
            'ppt' => 'fas fa-file-powerpoint text-warning',
            'pptx' => 'fas fa-file-powerpoint text-warning',
            'txt' => 'fas fa-file-alt text-secondary',
            'md' => 'fas fa-file-alt text-secondary',
            'zip' => 'fas fa-file-archive text-info',
            'rar' => 'fas fa-file-archive text-info',
            '7z' => 'fas fa-file-archive text-info',
            'jpg' => 'fas fa-file-image text-info',
            'jpeg' => 'fas fa-file-image text-info',
            'png' => 'fas fa-file-image text-info',
            'gif' => 'fas fa-file-image text-info',
            'bmp' => 'fas fa-file-image text-info',
            'webp' => 'fas fa-file-image text-info',
            'svg' => 'fas fa-file-image text-info',
            'mp4' => 'fas fa-file-video text-danger',
            'avi' => 'fas fa-file-video text-danger',
            'mov' => 'fas fa-file-video text-danger',
            'wmv' => 'fas fa-file-video text-danger',
            'flv' => 'fas fa-file-video text-danger',
            'mkv' => 'fas fa-file-video text-danger',
            'webm' => 'fas fa-file-video text-danger',
            'mp3' => 'fas fa-file-audio text-warning',
            'wav' => 'fas fa-file-audio text-warning',
            'flac' => 'fas fa-file-audio text-warning',
            'aac' => 'fas fa-file-audio text-warning',
            'ogg' => 'fas fa-file-audio text-warning',
            'm4a' => 'fas fa-file-audio text-warning',
        ];
        
        return $iconMap[$extension] ?? 'fas fa-file text-muted';
    }

    /**
     * 上传文件 - 简化版本，不进行压缩和缩略图生成
     */
    public static function uploadFileSimple($file, int $workorderId, string $description = null, bool $isPublic = true): self
    {
        $filename = time() . '_' . $file->getClientOriginalName();
        $originalSize = $file->getSize();
        $fileType = 'other';
        $extension = strtolower($file->getClientOriginalExtension());
        
        // 简单的文件类型判断
        if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'])) {
            $fileType = 'image';
        } elseif (in_array($extension, ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt'])) {
            $fileType = 'document';
        } elseif (in_array($extension, ['mp4', 'avi', 'mov', 'wmv', 'flv', 'mkv'])) {
            $fileType = 'video';
        } elseif (in_array($extension, ['mp3', 'wav', 'flac', 'aac', 'ogg'])) {
            $fileType = 'audio';
        }
        
        // 直接存储文件，不进行任何处理
        $filePath = $file->storeAs('workorder_attachments', $filename, 'public');
        
        return static::create([
            'workorder_id' => $workorderId,
            'user_id' => auth()->id(),
            'filename' => $filename,
            'original_name' => $file->getClientOriginalName(),
            'file_path' => $filePath,
            'file_type' => $fileType,
            'file_size' => $originalSize,
            'mime_type' => $file->getMimeType(),
            'description' => $description,
            'type' => $fileType,
            'is_public' => $isPublic,
        ]);
    }

    /**
     * 删除文件
     */
    public function deleteFile(): bool
    {
        if (Storage::disk('public')->exists($this->file_path)) {
            Storage::disk('public')->delete($this->file_path);
        }
        
        return $this->delete();
    }
}