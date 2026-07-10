<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkorderSignatureDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'workorder_id',
        'filename',
        'file_path',
        'file_type',
        'file_size',
        'md5_hash',
    ];

    protected $casts = [
        'file_size' => 'integer',
    ];

    /**
     * 获取关联的工单
     */
    public function workorder(): BelongsTo
    {
        return $this->belongsTo(Workorder::class);
    }

    /**
     * 获取格式化的文件大小
     */
    public function getFormattedFileSizeAttribute(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * 获取文件URL
     */
    public function getUrlAttribute(): string
    {
        return route('workorders.signature.document.download', $this->id);
    }

    /**
     * 获取下载URL
     */
    public function getDownloadUrlAttribute(): string
    {
        return route('workorders.signature.document.download', $this->id);
    }

    /**
     * 获取预览URL
     */
    public function getPreviewUrlAttribute(): string
    {
        return route('workorders.signature.document.preview', $this->id);
    }

    /**
     * 检查是否为PDF文件
     */
    public function isPdf(): bool
    {
        return $this->file_type === 'application/pdf';
    }

    /**
     * 检查是否为图片文件
     */
    public function isImage(): bool
    {
        return str_starts_with($this->file_type, 'image/');
    }

    /**
     * 获取文件图标
     */
    public function getFileIconAttribute(): string
    {
        if ($this->isPdf()) {
            return 'fas fa-file-pdf text-danger';
        }
        
        if ($this->isImage()) {
            return 'fas fa-file-image text-success';
        }
        
        return 'fas fa-file text-secondary';
    }

    /**
     * 创建签单文档记录
     */
    public static function createSignatureDocument(
        int $workorderId,
        string $filename,
        string $filePath,
        string $fileType = 'application/pdf',
        int $fileSize = 0,
        ?string $md5Hash = null
    ): self {
        return self::create([
            'workorder_id' => $workorderId,
            'filename' => $filename,
            'file_path' => $filePath,
            'file_type' => $fileType,
            'file_size' => $fileSize,
            'md5_hash' => $md5Hash,
        ]);
    }

    /**
     * 删除文件和记录
     */
    public function deleteWithFile(): bool
    {
        try {
            // 删除物理文件
            Storage::disk('public')->delete($this->file_path);
            
            // 删除数据库记录
            return $this->delete();
        } catch (\Exception $e) {
            \Log::error('删除签单文档失败', [
                'document_id' => $this->id,
                'file_path' => $this->file_path,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }

    /**
     * 生成唯一的文件名
     */
    public static function generateUniqueFilename(string $originalName, string $extension = 'pdf'): string
    {
        $timestamp = now()->format('YmdHis');
        $random = mt_rand(1000, 9999);
        $baseName = pathinfo($originalName, PATHINFO_FILENAME);
        
        // 限制基础名称长度
        $baseName = mb_substr($baseName, 0, 20, 'UTF-8');
        
        return "{$baseName}_{$timestamp}_{$random}.{$extension}";
    }

    /**
     * 获取存储路径
     */
    public static function getStoragePath(): string
    {
        return "signatures";
    }
    
    /**
     * 获取完整的存储路径
     */
    public static function getFullStoragePath(string $filename = null): string
    {
        $storagePath = self::getStoragePath();
        $fullPath = public_path("storage/app/public/{$storagePath}");
        
        // 确保目录存在
        if (!is_dir($fullPath)) {
            mkdir($fullPath, 0777, true);
        }
        
        return $filename ? "{$fullPath}/{$filename}" : $fullPath;
    }
}