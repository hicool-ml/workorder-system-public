<?php

namespace App\Console\Commands;

use App\Models\WorkorderAttachment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * 把存量工单附件从 public 磁盘迁移到私有 attachments 磁盘。
 *
 * 背景：附件原先存 storage/app/public（经 /storage 符号链接公开可达），
 * 绕过了 AttachmentController 的权限检查。迁移后读取统一走鉴权路由。
 *
 * 用法：php artisan attachments:migrate-to-private
 * 可重复执行（已迁移的自动跳过）。
 */
class MigrateAttachmentsToPrivate extends Command
{
    protected $signature = 'attachments:migrate-to-private {--purge-public : 迁移完成后删除 public 盘上的 workorder_attachments 目录}';

    protected $description = '把存量工单附件从 public 磁盘迁移到私有 attachments 磁盘';

    public function handle(): int
    {
        $public = Storage::disk('public');
        $private = Storage::disk('attachments');

        if (!$public->exists('workorder_attachments')) {
            $this->info('public 盘上没有 workorder_attachments 目录，无需迁移。');
            return self::SUCCESS;
        }

        $files = $public->allFiles('workorder_attachments');
        $moved = 0;
        $skipped = 0;
        $failed = 0;

        // 签单快照同时修 DB 记录（原名含 ticket_no+time 可枚举，换 UUID）
        $recordSnapshots = WorkorderAttachment::where('mime_type', 'text/html')->get()->keyBy('file_path');

        foreach ($files as $path) {
            if ($private->exists($path)) {
                $skipped++;
                continue;
            }

            try {
                $stream = $public->readStream($path);
                $private->writeStream($path, $stream);
                if (is_resource($stream)) {
                    fclose($stream);
                }

                // 签单 HTML 快照：文件名换成 UUID 并更新 DB 记录
                if ($recordSnapshots->has($path)) {
                    $record = $recordSnapshots->get($path);
                    $newName = 'record_' . str_replace('/', '_', $record->workorder_id) . '_' . \Illuminate\Support\Str::uuid()->toString() . '.html';
                    $newPath = 'workorder_attachments/' . $newName;
                    $private->move($path, $newPath);
                    $record->update([
                        'filename' => $newName,
                        'file_path' => $newPath,
                    ]);
                }

                $moved++;
            } catch (\Throwable $e) {
                $failed++;
                $this->warn("迁移失败 {$path}: {$e->getMessage()}");
            }
        }

        $this->info("迁移完成：{$moved} 个文件已移入私有盘，{$skipped} 个跳过，{$failed} 个失败。");

        if ($this->option('purge-public') && $failed === 0) {
            $public->deleteDirectory('workorder_attachments');
            $this->info('已删除 public 盘上的 workorder_attachments 目录（旧直链从此失效）。');
        } else {
            $this->line('提示：确认功能正常后执行 --purge-public 删除 public 盘旧文件，彻底关闭直链访问。');
        }

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }
}
