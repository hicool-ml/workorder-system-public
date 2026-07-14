<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

/**
 * 系统备份命令
 *
 * 备份内容包括：
 *  1. 数据库（优先 mysqldump，不可用时回退纯 PHP SQL 导出）
 *  2. storage/app/public 下的用户附件（图片、签名、PDF 等）
 *
 * 用法：
 *   php artisan backup:system              # 立即执行一次备份
 *   php artisan backup:system --keep=14    # 仅保留最近 14 份（默认 30）
 *
 * 建议在调度中每日执行：
 *   $schedule->command('backup:system')->dailyAt('02:00');
 */
class BackupSystem extends Command
{
    protected $signature = 'backup:system {--keep=30 : 保留的备份份数}';
    protected $description = '备份数据库与附件到 storage/backups';

    public function handle(): int
    {
        $disk = Storage::disk('local');
        $stamp = now()->format('Ymd_His');
        $backupDir = "backups/{$stamp}";
        $disk->makeDirectory($backupDir);

        $this->info("开始备份：{$backupDir}");

        $dbOk = $this->backupDatabase($disk, $backupDir);
        $this->backupAttachments($disk, $backupDir, $stamp);
        $this->pruneOldBackups($disk);

        if ($dbOk) {
            $this->info("备份完成：{$backupDir}");
            return self::SUCCESS;
        }

        $this->error('数据库备份失败，请检查日志');
        return self::FAILURE;
    }

    /**
     * 备份数据库：优先 mysqldump，失败则用纯 PHP 导出
     */
    private function backupDatabase($disk, string $backupDir): bool
    {
        $driver = config('database.default');
        $dbName = config("database.connections.{$driver}.database");

        if ($driver === 'mysql') {
            $dumped = $this->tryMysqlDump($disk, $backupDir, $dbName);
            if ($dumped !== null) {
                return $dumped;
            }
            $this->warn('mysqldump 不可用，回退到纯 PHP 导出（速度较慢）');
        }

        return $this->phpSqlDump($disk, $backupDir);
    }

    private function tryMysqlDump($disk, string $backupDir, string $dbName): ?bool
    {
        $mysqldump = trim((string) shell_exec('where mysqldump 2>NUL') ?? '');
        if ($mysqldump === '' || stripos($mysqldump, 'INFO') !== false) {
            return null;
        }

        $cfg = config('database.connections.mysql');
        $host = $cfg['host'] ?? '127.0.0.1';
        $port = $cfg['port'] ?? 3306;
        $user = $cfg['username'] ?? 'root';
        $pass = $cfg['password'] ?? '';

        $tmpSql = $disk->path("{$backupDir}/database.sql");
        File::ensureDirectoryExists(dirname($tmpSql));

        $passPart = $pass !== '' ? '-p' . escapeshellarg($pass) : '';
        $cmd = sprintf(
            '%s --host=%s --port=%s -u %s %s --single-transaction --quick --no-tablespaces %s > %s 2>&1',
            escapeshellarg(explode("\n", $mysqldump)[0]),
            escapeshellarg($host),
            escapeshellarg((string) $port),
            escapeshellarg($user),
            $passPart,
            escapeshellarg($dbName),
            escapeshellarg($tmpSql)
        );

        exec($cmd, $output, $code);

        if ($code === 0 && File::exists($tmpSql) && filesize($tmpSql) > 0) {
            $this->info('数据库已通过 mysqldump 备份');
            return true;
        }

        if (File::exists($tmpSql)) {
            File::delete($tmpSql);
        }
        return null;
    }

    /**
     * 纯 PHP 逐表导出为 SQL（无需外部二进制）
     */
    private function phpSqlDump($disk, string $backupDir): bool
    {
        try {
            $tables = DB::select('SHOW TABLES');
            $key = 'Tables_in_' . config('database.connections.mysql.database');
        } catch (\Throwable $e) {
            $tables = array_map(
                fn($r) => (object) ['name' => $r->name],
                DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'")
            );
            $key = 'name';
        }

        $sql = "-- 工单系统数据库备份\n-- 生成时间: " . now() . "\n-- 纯 PHP 导出\n\nSET FOREIGN_KEY_CHECKS=0;\n\n";
        $count = 0;

        foreach ($tables as $t) {
            $table = is_object($t) ? ($t->$key ?? $t->name ?? null) : $t;
            if (!$table) {
                continue;
            }

            $sql .= "-- ----------------------------\n-- 表结构 {$table}\n-- ----------------------------\n";
            $sql .= "DROP TABLE IF EXISTS `{$table}`;\n";

            $createRow = DB::select("SHOW CREATE TABLE `{$table}`");
            if (!empty($createRow)) {
                $createCol = array_key_exists('Create Table', (array) $createRow[0])
                    ? 'Create Table'
                    : array_keys((array) $createRow[0])[1] ?? null;
                if ($createCol) {
                    $sql .= $createRow[0]->$createCol . ";\n\n";
                }
            }

            $rows = DB::table($table)->get();
            if ($rows->isNotEmpty()) {
                $sql .= "-- 数据 {$table}\n";
                $columns = array_keys((array) $rows->first());
                foreach ($rows as $row) {
                    $vals = [];
                    foreach ($columns as $col) {
                        $v = $row->$col ?? null;
                        $vals[] = $v === null ? 'NULL' : "'" . str_replace(["\\", "'"], ["\\\\", "''"], (string) $v) . "'";
                    }
                    $sql .= "INSERT INTO `{$table}` (`" . implode('`,`', $columns) . "`) VALUES (" . implode(',', $vals) . ");\n";
                }
                $sql .= "\n";
            }
            $count++;
        }

        $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";

        $disk->put("{$backupDir}/database.sql", $sql);
        $this->info("数据库已通过 PHP 导出备份（{$count} 张表）");
        return true;
    }

    /**
     * 打包附件目录为 zip
     */
    private function backupAttachments($disk, string $backupDir, string $stamp): void
    {
        $publicPath = storage_path('app/public');
        if (!File::exists($publicPath)) {
            $this->warn('附件目录不存在，跳过附件备份');
            return;
        }

        $zipPath = $disk->path("{$backupDir}/attachments.zip");
        File::ensureDirectoryExists(dirname($zipPath));

        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true) {
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($publicPath, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::LEAVES_ONLY
            );
            $fileCount = 0;
            foreach ($files as $file) {
                if (!$file->isDir()) {
                    $relative = 'public/' . ltrim(str_replace($publicPath, '', $file->getRealPath()), DIRECTORY_SEPARATOR);
                    $zip->addFile($file->getRealPath(), $relative);
                    $fileCount++;
                }
            }
            $zip->close();
            $this->info("附件已备份（{$fileCount} 个文件）");
        } else {
            $this->warn('附件 zip 打包失败，跳过');
        }
    }

    /**
     * 清理超出保留份数的旧备份
     */
    private function pruneOldBackups($disk): void
    {
        $keep = (int) $this->option('keep');
        $dirs = $disk->directories('backups');
        sort($dirs);

        $excess = count($dirs) - $keep;
        if ($excess <= 0) {
            return;
        }

        for ($i = 0; $i < $excess; $i++) {
            $disk->deleteDirectory($dirs[$i]);
            $this->line("已清理旧备份：{$dirs[$i]}");
        }
    }
}