<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\WorkorderAttachment;
use Illuminate\Support\Facades\Storage;

class CompressImageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $attachmentId;
    protected $originalPath;
    protected $filename;

    /**
     * 创建新的任务实例
     */
    public function __construct($attachmentId, $originalPath, $filename)
    {
        $this->attachmentId = $attachmentId;
        $this->originalPath = $originalPath;
        $this->filename = $filename;
    }

    /**
     * 执行任务
     */
    public function handle(): void
    {
        try {
            // 获取附件记录
            $attachment = WorkorderAttachment::find($this->attachmentId);
            if (!$attachment) {
                \Log::error('找不到附件记录: ' . $this->attachmentId);
                return;
            }

            // 检查原始文件是否存在
            if (!Storage::disk('public')->exists($this->originalPath)) {
                \Log::error('原始文件不存在: ' . $this->originalPath);
                return;
            }

            // 获取原始文件信息
            $originalPath = storage_path('app/public/' . $this->originalPath);
            $imageInfo = getimagesize($originalPath);
            if (!$imageInfo) {
                \Log::error('无法获取图片信息: ' . $this->originalPath);
                return;
            }

            $width = $imageInfo[0];
            $height = $imageInfo[1];
            $originalSize = filesize($originalPath);

            // 计算压缩后的尺寸
            $maxWidth = 1920;
            $maxHeight = 1080;
            
            if ($width <= $maxWidth && $height <= $maxHeight) {
                // 图片尺寸不大，不需要压缩
                return;
            }

            // 计算新尺寸
            $ratio = min($maxWidth / $width, $maxHeight / $height, 1.0);
            $newWidth = (int)($width * $ratio);
            $newHeight = (int)($height * $ratio);

            // 创建新图像
            $newImage = imagecreatetruecolor($newWidth, $newHeight);
            if (!$newImage) {
                \Log::error('无法创建新图像资源');
                return;
            }

            // 根据原始图片类型创建图像资源
            $source = null;
            switch ($imageInfo[2]) {
                case IMAGETYPE_JPEG:
                    $source = imagecreatefromjpeg($originalPath);
                    break;
                case IMAGETYPE_PNG:
                    $source = imagecreatefrompng($originalPath);
                    imagealphablending($newImage, false);
                    imagesavealpha($newImage, true);
                    break;
                case IMAGETYPE_GIF:
                    $source = imagecreatefromgif($originalPath);
                    break;
                case IMAGETYPE_BMP:
                    $source = imagecreatefrombmp($originalPath);
                    break;
                case IMAGETYPE_WEBP:
                    if (function_exists('imagecreatefromwebp')) {
                        $source = imagecreatefromwebp($originalPath);
                    }
                    break;
            }

            if (!$source) {
                imagedestroy($newImage);
                \Log::error('无法加载原始图像');
                return;
            }

            // 缩放图像
            if (!imagecopyresampled($newImage, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height)) {
                imagedestroy($newImage);
                imagedestroy($source);
                \Log::error('图像缩放失败');
                return;
            }

            // 创建压缩后的文件名
            $compressedFilename = 'compressed_' . $this->filename;
            $compressedPath = 'workorder_attachments/' . $compressedFilename;
            $fullCompressedPath = storage_path('app/public/' . $compressedPath);

            // 确保目录存在
            $dir = dirname($fullCompressedPath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            // 保存压缩后的图片
            $quality = 75;
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
                return;
            }

            $compressedSize = filesize($fullCompressedPath);

            // 更新附件记录
            $attachment->update([
                'file_path' => $compressedPath,
                'file_size' => $compressedSize,
                'filename' => $compressedFilename,
            ]);

            // 删除原始文件
            Storage::disk('public')->delete($this->originalPath);

            \Log::info('图片压缩完成: ' . $this->filename . 
                      ', 原始大小: ' . round($originalSize / 1024 / 1024, 2) . 'MB' . 
                      ', 压缩后: ' . round($compressedSize / 1024 / 1024, 2) . 'MB' .
                      ', 压缩率: ' . round((1 - $compressedSize / $originalSize) * 100, 2) . '%');

        } catch (\Exception $e) {
            \Log::error('图片压缩任务失败: ' . $e->getMessage());
        }
    }
}