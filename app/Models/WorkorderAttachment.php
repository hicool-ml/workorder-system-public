<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class WorkorderAttachment extends Model
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
        'thumbnail_path',
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
            // 如果有缩略图，返回缩略图URL
            if ($this->thumbnail_path) {
                return Storage::url($this->thumbnail_path);
            }
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
        if ($this->thumbnail_path) {
            return Storage::url($this->thumbnail_path);
        }
        
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
     * 上传文件
     */
    public static function uploadFile($file, int $workorderId, string $description = null, bool $isPublic = true): self
    {
        $filename = time() . '_' . $file->getClientOriginalName();
        $originalSize = $file->getSize();
        $fileType = 'other';
        $extension = strtolower($file->getClientOriginalExtension());
        $thumbnailPath = null;
        
        // 处理图片文件 - 检查是否需要压缩
        if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'])) {
            $fileType = 'image';
            
            // 直接存储文件，不进行压缩，避免卡顿问题
            $filePath = $file->storeAs('workorder_attachments', $filename, 'public');
            $fileSize = $originalSize;
            
            // 完全禁用缩略图生成，避免卡顿问题
            $thumbnailPath = null;
        } elseif (in_array($extension, ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt'])) {
            $fileType = 'document';
            $filePath = $file->storeAs('workorder_attachments', $filename, 'public');
            $fileSize = $originalSize;
        } elseif (in_array($extension, ['mp4', 'avi', 'mov', 'wmv', 'flv', 'mkv'])) {
            $fileType = 'video';
            $filePath = $file->storeAs('workorder_attachments', $filename, 'public');
            $fileSize = $originalSize;
        } elseif (in_array($extension, ['mp3', 'wav', 'flac', 'aac', 'ogg'])) {
            $fileType = 'audio';
            $filePath = $file->storeAs('workorder_attachments', $filename, 'public');
            $fileSize = $originalSize;
        } else {
            // 其他文件类型
            $filePath = $file->storeAs('workorder_attachments', $filename, 'public');
            $fileSize = $originalSize;
        }
        
        return static::create([
            'workorder_id' => $workorderId,
            'user_id' => auth()->id(),
            'filename' => $filename,
            'original_name' => $file->getClientOriginalName(),
            'file_path' => $filePath,
            'file_type' => $fileType,
            'file_size' => $fileSize,
            'mime_type' => $file->getMimeType(),
            'description' => $description,
            'type' => $fileType,
            'is_public' => $isPublic,
            'thumbnail_path' => $thumbnailPath,
        ]);
    }

    /**
     * 生成图片缩略图
     */
    private static function generateThumbnail($file, string $filename): ?string
    {
        try {
            // 检查是否安装了GD库
            if (!extension_loaded('gd')) {
                return null;
            }
            
            $extension = strtolower($file->getClientOriginalExtension());
            if (!in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'])) {
                return null;
            }
            
            $thumbnailFilename = 'thumb_' . $filename;
            $thumbnailPath = 'workorder_attachments/thumbnails/' . $thumbnailFilename;
            
            // 创建缩略图目录
            $fullThumbnailPath = storage_path('app/public/' . $thumbnailPath);
            $thumbnailDir = dirname($fullThumbnailPath);
            if (!is_dir($thumbnailDir)) {
                mkdir($thumbnailDir, 0755, true);
            }
            
            // 获取图片信息
            $imageInfo = getimagesize($file->getPathname());
            if (!$imageInfo) {
                return null;
            }
            
            $width = $imageInfo[0];
            $height = $imageInfo[1];
            
            // 设置缩略图尺寸（最大300x300，保持比例）
            $maxSize = 300;
            if ($width > $maxSize || $height > $maxSize) {
                $ratio = min($maxSize / $width, $maxSize / $height);
                $newWidth = (int)($width * $ratio);
                $newHeight = (int)($height * $ratio);
                
                // 创建新图像
                $thumbnail = imagecreatetruecolor($newWidth, $newHeight);
                
                // 根据原始图片类型创建图像资源
                switch ($imageInfo[2]) {
                    case IMAGETYPE_JPEG:
                        $source = imagecreatefromjpeg($file->getPathname());
                        break;
                    case IMAGETYPE_PNG:
                        $source = imagecreatefrompng($file->getPathname());
                        imagealphablending($thumbnail, false);
                        imagesavealpha($thumbnail, true);
                        break;
                    case IMAGETYPE_GIF:
                        $source = imagecreatefromgif($file->getPathname());
                        break;
                    default:
                        return null;
                }
                
                // 缩放图像
                imagecopyresampled($thumbnail, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
                
                // 保存缩略图
                switch ($imageInfo[2]) {
                    case IMAGETYPE_JPEG:
                        imagejpeg($thumbnail, $fullThumbnailPath, 85);
                        break;
                    case IMAGETYPE_PNG:
                        imagepng($thumbnail, $fullThumbnailPath, 8);
                        break;
                    case IMAGETYPE_GIF:
                        imagegif($thumbnail, $fullThumbnailPath);
                        break;
                }
                
                // 释放内存
                imagedestroy($thumbnail);
                imagedestroy($source);
                
                return $thumbnailPath;
            }
            
            return null;
        } catch (\Exception $e) {
            \Log::error('生成缩略图失败: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * 压缩图片
     */
    private static function compressImage($file, string $filename): ?array
    {
        try {
            // 增加执行时间限制，避免超时
            set_time_limit(60);
            
            // 检查是否安装了GD库
            if (!extension_loaded('gd')) {
                \Log::warning('GD库未安装，无法压缩图片');
                return null;
            }
            
            $extension = strtolower($file->getClientOriginalExtension());
            if (!in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'])) {
                return null;
            }
            
            // 获取原始图片信息
            $imageInfo = getimagesize($file->getPathname());
            if (!$imageInfo) {
                return null;
            }
            
            $width = $imageInfo[0];
            $height = $imageInfo[1];
            $originalSize = $file->getSize();
            
            // 计算压缩后的尺寸（最大宽度1920，高度1080）
            $maxWidth = 1920;
            $maxHeight = 1080;
            
            // 提高压缩阈值，只有更大的图片才压缩，减少处理时间
            if ($width <= $maxWidth && $height <= $maxHeight && $originalSize <= 5 * 1024 * 1024) {
                // 图片尺寸不大且文件大小不超过5MB，不需要压缩
                return null;
            }
            
            // 计算新尺寸
            $ratio = min($maxWidth / $width, $maxHeight / $height, 1.0);
            $newWidth = (int)($width * $ratio);
            $newHeight = (int)($height * $ratio);
            
            // 限制最大处理尺寸，避免内存不足
            $maxProcessSize = 3000 * 2000; // 最大处理像素数
            if ($newWidth * $newHeight > $maxProcessSize) {
                // 如果图片太大，进一步缩小尺寸
                $processRatio = sqrt($maxProcessSize / ($width * $height));
                $newWidth = (int)($width * $processRatio);
                $newHeight = (int)($height * $processRatio);
            }
            
            // 创建新图像
            $newImage = imagecreatetruecolor($newWidth, $newHeight);
            if (!$newImage) {
                \Log::error('无法创建新图像资源，可能是内存不足');
                return null;
            }
            
            // 根据原始图片类型创建图像资源
            $source = null;
            switch ($imageInfo[2]) {
                case IMAGETYPE_JPEG:
                    $source = imagecreatefromjpeg($file->getPathname());
                    break;
                case IMAGETYPE_PNG:
                    $source = imagecreatefrompng($file->getPathname());
                    imagealphablending($newImage, false);
                    imagesavealpha($newImage, true);
                    break;
                case IMAGETYPE_GIF:
                    $source = imagecreatefromgif($file->getPathname());
                    break;
                case IMAGETYPE_BMP:
                    $source = imagecreatefrombmp($file->getPathname());
                    break;
                case IMAGETYPE_WEBP:
                    if (function_exists('imagecreatefromwebp')) {
                        $source = imagecreatefromwebp($file->getPathname());
                    }
                    break;
            }
            
            if (!$source) {
                imagedestroy($newImage);
                \Log::error('无法加载原始图像');
                return null;
            }
            
            // 缩放图像
            if (!imagecopyresampled($newImage, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height)) {
                imagedestroy($newImage);
                imagedestroy($source);
                \Log::error('图像缩放失败');
                return null;
            }
            
            // 创建压缩后的文件名
            $compressedFilename = 'compressed_' . $filename;
            $compressedPath = 'workorder_attachments/' . $compressedFilename;
            $fullCompressedPath = storage_path('app/public/' . $compressedPath);
            
            // 确保目录存在
            $dir = dirname($fullCompressedPath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            
            // 保存压缩后的图片
            $quality = 75; // JPEG质量
            $saved = false;
            
            switch ($imageInfo[2]) {
                case IMAGETYPE_JPEG:
                    $saved = imagejpeg($newImage, $fullCompressedPath, $quality);
                    break;
                case IMAGETYPE_PNG:
                    $saved = imagepng($newImage, $fullCompressedPath, 8);
                    break;
                case IMAGETYPE_GIF:
                    $saved = imagegif($newImage, $fullCompressedPath);
                    break;
                case IMAGETYPE_WEBP:
                    if (function_exists('imagewebp')) {
                        $saved = imagewebp($newImage, $fullCompressedPath, $quality);
                    }
                    break;
            }
            
            // 释放内存
            imagedestroy($newImage);
            imagedestroy($source);
            
            if (!$saved) {
                \Log::error('保存压缩图片失败');
                return null;
            }
            
            $compressedSize = filesize($fullCompressedPath);
            
            // 如果压缩后文件仍然很大，尝试进一步降低质量
            if ($compressedSize > 5 * 1024 * 1024 && $imageInfo[2] === IMAGETYPE_JPEG) {
                // 对于JPEG，可以进一步降低质量
                for ($q = 65; $q >= 50; $q -= 5) {
                    imagejpeg($newImage, $fullCompressedPath, $q);
                    $compressedSize = filesize($fullCompressedPath);
                    if ($compressedSize <= 5 * 1024 * 1024) {
                        break;
                    }
                }
            }
            
            return [
                'path' => $compressedPath,
                'filename' => $compressedFilename,
                'size' => $compressedSize,
                'original_size' => $originalSize,
                'compression_ratio' => round((1 - $compressedSize / $originalSize) * 100, 2)
            ];
            
        } catch (\Exception $e) {
            \Log::error('图片压缩失败: ' . $e->getMessage());
            return null;
        } catch (\Error $e) {
            \Log::error('图片压缩错误: ' . $e->getMessage());
            return null;
        }
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

    /**
     * 获取所有可用的文件类型
     */
    public static function getTypeOptions(): array
    {
        return [
            'image' => '图片',
            'document' => '文档',
            'video' => '视频',
            'audio' => '音频',
            'other' => '其他',
        ];
    }

    /**
     * 获取允许上传的文件扩展名
     */
    public static function getAllowedExtensions(): array
    {
        return [
            'jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', // 图片
            'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', // 文档
            'mp4', 'avi', 'mov', 'wmv', 'flv', 'mkv', // 视频
            'mp3', 'wav', 'flac', 'aac', 'ogg', // 音频
            'zip', 'rar', '7z', // 压缩文件
        ];
    }

    /**
     * 获取最大文件大小（字节）
     */
    public static function getMaxFileSize(): int
    {
        return 10 * 1024 * 1024; // 10MB
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
     * 获取文件内容预览（文本文件）
     */
    public function getTextPreview(int $lines = 50): string
    {
        if (!in_array($this->extension, ['txt', 'md'])) {
            return '';
        }
        
        try {
            $content = Storage::disk('public')->get($this->file_path);
            $linesArray = explode("\n", $content);
            
            if (count($linesArray) > $lines) {
                $previewLines = array_slice($linesArray, 0, $lines);
                return implode("\n", $previewLines) . "\n\n... (文件太长，仅显示前{$lines}行)";
            }
            
            return $content;
        } catch (\Exception $e) {
            return '无法读取文件内容';
        }
    }

    /**
     * 检查文件是否可以在线预览
     */
    public function canPreviewOnline(): bool
    {
        return in_array($this->getPreviewType(), ['image', 'text']);
    }

    /**
     * 获取适合显示的缩略图尺寸
     */
    public function getThumbnailSize(): array
    {
        if (!$this->isImage()) {
            return ['width' => 150, 'height' => 150];
        }
        
        try {
            $imagePath = storage_path('app/public/' . $this->file_path);
            if (file_exists($imagePath)) {
                $imageInfo = getimagesize($imagePath);
                if ($imageInfo) {
                    $width = $imageInfo[0];
                    $height = $imageInfo[1];
                    
                    // 如果有缩略图，返回缩略图尺寸
                    if ($this->thumbnail_path) {
                        return ['width' => 300, 'height' => 300];
                    }
                    
                    // 计算适合的显示尺寸
                    $maxSize = 150;
                    if ($width > $maxSize || $height > $maxSize) {
                        $ratio = min($maxSize / $width, $maxSize / $height);
                        return [
                            'width' => (int)($width * $ratio),
                            'height' => (int)($height * $ratio)
                        ];
                    }
                    
                    return ['width' => $width, 'height' => $height];
                }
            }
        } catch (\Exception $e) {
            // 忽略错误，返回默认尺寸
        }
        
        return ['width' => 150, 'height' => 150];
    }
}