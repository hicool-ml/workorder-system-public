<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

/**
 * 数据备份与恢复（仅管理员）。
 *
 * 复用 BackupSystem 命令生成的目录结构：storage/app/private/backups/{stamp}/
 *   - database.sql    数据库导出
 *   - attachments.zip 用户附件（可选）
 *
 * 用户上传的 .zip 备份解压后存放于 backups/uploaded/{stamp}/。
 */
class BackupController extends Controller
{
    private const DISK_NAME = 'local';
    private const ROOT = 'backups';

    /**
     * 列出所有备份
     */
    public function index()
    {
        $disk = Storage::disk(self::DISK_NAME);
        $items = [];

        foreach ((array) $disk->directories(self::ROOT) as $dir) {
            $name = basename($dir);
            if ($name === 'uploaded') {
                continue;
            }
            $items[] = $this->describeBackup($disk, $dir, $name, false);
        }

        foreach ((array) $disk->directories(self::ROOT . '/uploaded') as $dir) {
            $name = basename($dir);
            $items[] = $this->describeBackup($disk, $dir, $name, true);
        }

        usort($items, fn($a, $b) => strcmp($b['name'], $a['name']));
        return response()->json(['success' => true, 'data' => $items]);
    }

    /**
     * 立即创建一份新备份（调用 BackupSystem 命令）
     */
    public function create(Request $request)
    {
        @set_time_limit(0);
        @ini_set('memory_limit', '512M');
        try {
            $exit = Artisan::call('backup:system');
            $output = Artisan::output();
            if ($exit !== 0) {
                return $this->jsonFail('备份失败：' . $this->cleanOutput($output));
            }
            $disk = Storage::disk(self::DISK_NAME);
            $latest = collect($disk->directories(self::ROOT))
                ->filter(fn($d) => basename($d) !== 'uploaded')
                ->sortDesc()
                ->first();
            return response()->json([
                'success' => true,
                'message' => '备份已创建',
                'backup' => $latest ? basename($latest) : null,
            ]);
        } catch (\Throwable $e) {
            return $this->jsonFail('备份失败：' . $e->getMessage());
        }
    }

    /**
     * 下载整个备份目录为 zip
     */
    public function download(string $name)
    {
        if (!$this->validName($name)) {
            return $this->jsonFail('无效的备份名称', 400);
        }
        $disk = Storage::disk(self::DISK_NAME);
        $dir = $this->locateDir($disk, $name);
        if (!$dir || !$disk->exists($dir)) {
            return $this->jsonFail('备份不存在', 404);
        }

        $absRoot = $disk->path($dir);
        $tmpZip = tempnam(sys_get_temp_dir(), 'bk_') . '.zip';

        $zip = new ZipArchive();
        if ($zip->open($tmpZip, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            @unlink($tmpZip);
            return $this->jsonFail('打包失败', 500);
        }
        $this->addFolderToZip($zip, $absRoot, '');
        $zip->close();

        $filename = "backup-{$name}.zip";
        return response()->download($tmpZip, $filename, [
            'Content-Type' => 'application/zip',
        ])->deleteFileAfterSend(true);
    }

    /**
     * 删除一份备份
     */
    public function destroy(string $name)
    {
        if (!$this->validName($name)) {
            return $this->jsonFail('无效的备份名称', 400);
        }
        $disk = Storage::disk(self::DISK_NAME);
        $dir = $this->locateDir($disk, $name);
        if (!$dir || !$disk->exists($dir)) {
            return $this->jsonFail('备份不存在', 404);
        }
        $disk->deleteDirectory($dir);
        return response()->json(['success' => true, 'message' => '备份已删除']);
    }

    /**
     * 上传一份备份 zip，解压到 backups/uploaded/{stamp}/
     */
    public function upload(Request $request)
    {
        @set_time_limit(0);
        @ini_set('memory_limit', '512M');
        $request->validate([
            'file' => 'required|file|mimes:zip|max:204800',
        ]);

        $uploaded = $request->file('file');
        $tmpPath = $uploaded->getRealPath();

        $probe = new ZipArchive();
        if ($probe->open($tmpPath) !== true) {
            return $this->jsonFail('无法打开 zip 文件', 400);
        }

        $hasSql = false;
        for ($i = 0; $i < $probe->numFiles; $i++) {
            $entry = $probe->getNameIndex($i);
            if (Str::endsWith($entry, 'database.sql')) {
                $hasSql = true;
                break;
            }
        }
        $probe->close();

        if (!$hasSql) {
            return $this->jsonFail('备份文件缺少 database.sql，无法用于恢复', 422);
        }

        $disk = Storage::disk(self::DISK_NAME);
        $stamp = now()->format('Ymd_His') . '_upload';
        $destDir = self::ROOT . '/uploaded/' . $stamp;
        $disk->makeDirectory($destDir);
        // 同 BackupSystem：规避 umask 导致的 0700，保证组 www-data 可读
        @chmod($disk->path($destDir), 0775);
        $absDest = $disk->path($destDir);

        $zip = new ZipArchive();
        $zip->open($tmpPath);
        $zip->extractTo($absDest);
        $zip->close();
        // 解压出的文件默认权限可能不含组读，统一修正为目录 0775 / 文件 0664
        $this->fixPermissions($absDest);

        $this->flattenIfNested($absDest);

        return response()->json([
            'success' => true,
            'message' => '备份已上传',
            'backup' => $stamp,
        ]);
    }

    /**
     * 从备份恢复：先自动备份当前状态，再执行 SQL 与附件
     */
    public function restore(Request $request, string $name)
    {
        @set_time_limit(0);
        @ini_set('memory_limit', '1024M');
        if (!$this->validName($name)) {
            return $this->jsonFail('无效的备份名称', 400);
        }

        $confirm = $request->boolean('confirm');
        if (!$confirm) {
            return $this->jsonFail('请确认恢复操作', 422);
        }

        $disk = Storage::disk(self::DISK_NAME);
        $dir = $this->locateDir($disk, $name);
        if (!$dir || !$disk->exists($dir)) {
            return $this->jsonFail('备份不存在', 404);
        }

        $sqlPath = $disk->path($dir . '/database.sql');
        if (!File::exists($sqlPath)) {
            return $this->jsonFail('备份缺少 database.sql', 422);
        }

        // 1. 先自动备份当前数据，保证可回滚
        try {
            Artisan::call('backup:system');
        } catch (\Throwable $e) {
            return $this->jsonFail('恢复前的自动备份失败，已中止：' . $e->getMessage());
        }

        $driver = config('database.default');
        $dbName = config("database.connections.{$driver}.database");

        // 2. 恢复数据库
        try {
            if ($driver === 'mysql') {
                $ok = $this->restoreMysql($sqlPath, $dbName);
            } else {
                $ok = $this->restoreViaPdo($sqlPath);
            }
            if (!$ok) {
                return $this->jsonFail('数据库恢复失败，请检查日志');
            }
        } catch (\Throwable $e) {
            return $this->jsonFail('数据库恢复异常：' . $e->getMessage());
        }

        // 3. 恢复附件（若存在）
        $attachmentsZip = $disk->path($dir . '/attachments.zip');
        if (File::exists($attachmentsZip)) {
            $this->restoreAttachments($attachmentsZip);
        }

        return response()->json([
            'success' => true,
            'message' => '恢复完成，恢复前已自动备份当前状态以便回滚',
        ]);
    }

    // ---------- 私有辅助 ----------

    private function describeBackup($disk, string $dir, string $name, bool $uploaded): array
    {
        $files = $disk->files($dir);
        $size = 0;
        $hasSql = false;
        $hasAttachments = false;
        $mtime = null;
        foreach ($files as $f) {
            $size += $disk->size($f);
            $base = basename($f);
            $hasSql = $hasSql || $base === 'database.sql';
            $hasAttachments = $hasAttachments || $base === 'attachments.zip';
            $ts = $disk->lastModified($f);
            if ($ts && (!$mtime || $ts > $mtime)) {
                $mtime = $ts;
            }
        }
        return [
            'name' => $name,
            'uploaded' => $uploaded,
            'size' => $size,
            'size_human' => $this->humanSize($size),
            'has_sql' => $hasSql,
            'has_attachments' => $hasAttachments,
            'created_at' => $mtime ? date('Y-m-d H:i:s', $mtime) : null,
        ];
    }

    private function locateDir($disk, string $name): ?string
    {
        $candidates = [
            self::ROOT . '/' . $name,
            self::ROOT . '/uploaded/' . $name,
        ];
        foreach ($candidates as $c) {
            if ($disk->exists($c)) {
                return $c;
            }
        }
        return null;
    }

    private function validName(string $name): bool
    {
        return (bool) preg_match('/^[A-Za-z0-9_]+$/', $name);
    }

    private function addFolderToZip(ZipArchive $zip, string $absPath, string $relative): void
    {
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($absPath, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );
        foreach ($files as $file) {
            if (!$file->isDir()) {
                $local = $relative . $file->getFilename();
                $zip->addFile($file->getRealPath(), $local);
            }
        }
    }

    /**
     * 统一修正解压目录的权限：目录 0775、文件 0664，保证 www-data 组可读写。
     * 规避 umask / Windows zip 默认权限导致 Web 进程读取失败。
     */
    private function fixPermissions(string $absPath): void
    {
        if (!is_dir($absPath)) {
            return;
        }
        try {
            $it = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($absPath, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST
            );
            foreach ($it as $item) {
                if ($item->isDir()) {
                    @chmod($item->getRealPath(), 0775);
                } else {
                    @chmod($item->getRealPath(), 0664);
                }
            }
            @chmod($absPath, 0775);
        } catch (\Throwable $e) {
            // 权限修正失败不应阻断恢复流程，仅记录
            \Log::warning('fixPermissions 失败: ' . $e->getMessage());
        }
    }

    private function flattenIfNested(string $absDest): void
    {
        $subs = glob($absDest . '/*', GLOB_ONLYDIR);
        if (count($subs) === 1) {
            $inner = $subs[0];
            $hasSqlInRoot = file_exists($absDest . '/database.sql');
            if (!$hasSqlInRoot && file_exists($inner . '/database.sql')) {
                foreach (glob($inner . '/*') as $f) {
                    @rename($f, $absDest . '/' . basename($f));
                }
                @rmdir($inner);
            }
        }
    }

    private function restoreMysql(string $sqlPath, string $dbName): bool
    {
        $cfg = config('database.connections.mysql');
        $host = $cfg['host'] ?? '127.0.0.1';
        $port = $cfg['port'] ?? 3306;
        $user = $cfg['username'] ?? 'root';
        $pass = $cfg['password'] ?? '';

        $mysql = trim((string) shell_exec('where mysql 2>NUL') ?? '');
        if ($mysql === '' || stripos($mysql, 'INFO') !== false || stripos($mysql, 'could not find') !== false) {
            return $this->restoreViaPdo($sqlPath);
        }

        $passPart = $pass !== '' ? '-p' . $pass : '';
        $cmd = sprintf(
            '%s --host=%s --port=%s -u %s %s --default-character-set=utf8mb4 %s < %s 2>&1',
            escapeshellarg(explode("\n", $mysql)[0]),
            escapeshellarg($host),
            escapeshellarg((string) $port),
            escapeshellarg($user),
            $passPart,
            escapeshellarg($dbName),
            escapeshellarg($sqlPath)
        );
        exec($cmd, $output, $code);
        return $code === 0;
    }

    /**
     * 纯 PDO 执行 SQL 文件：按 ";" 切分语句逐条执行。
     */
    private function restoreViaPdo(string $sqlPath): bool
    {
        @set_time_limit(0);
        @ini_set('memory_limit', '1024M');
        $sql = file_get_contents($sqlPath);
        if ($sql === false) {
            return false;
        }
        $sql = preg_replace('/^\s*--.*$/m', '', $sql);
        $sql = preg_replace('/^\s*\/\*.*?\*\//ms', '', $sql);

        $statements = [];
        $buf = '';
        $inString = false;
        $quote = '';
        $len = strlen($sql);
        for ($i = 0; $i < $len; $i++) {
            $ch = $sql[$i];
            $prev = $i > 0 ? $sql[$i - 1] : '';
            if (!$inString && ($ch === "'" || $ch === '"')) {
                $inString = true;
                $quote = $ch;
            } elseif ($inString && $ch === $quote && $prev !== '\\') {
                $inString = false;
                $quote = '';
            }
            $buf .= $ch;
            if (!$inString && $ch === ';') {
                $stmt = trim($buf);
                if ($stmt !== '' && $stmt !== ';') {
                    $statements[] = $stmt;
                }
                $buf = '';
            }
        }
        $tail = trim($buf);
        if ($tail !== '') {
            $statements[] = $tail;
        }

        DB::unprepared('SET FOREIGN_KEY_CHECKS=0');
        foreach ($statements as $stmt) {
            $up = strtoupper(ltrim($stmt));
            if (Str::startsWith($up, 'SET FOREIGN_KEY_CHECKS')) {
                continue;
            }
            try {
                DB::unprepared($stmt);
            } catch (\Throwable $e) {
                \Log::warning('备份恢复语句失败：' . $e->getMessage() . ' | ' . substr($stmt, 0, 200));
            }
        }
        DB::unprepared('SET FOREIGN_KEY_CHECKS=1');
        return true;
    }

    private function restoreAttachments(string $zipPath): void
    {
        $publicRoot = storage_path('app/public');
        File::ensureDirectoryExists($publicRoot);

        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            return;
        }

        // 兼容新旧两种打包格式：
        //   新格式：条目路径是相对 public 的相对路径（如 workorder-attachments/xxx.jpg）
        //   旧格式：条目路径带 public/ 前缀，或残留绝对路径（C:\... 或 C:/...）
        // 统一规整为相对 public 的路径后逐个写入，避免把脏目录结构带回 storage/app。
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (substr($name, -1) === '/') {
                continue; // 目录条目跳过
            }

            $normalized = str_replace('\\', '/', $name);
            // 去掉可能残留的绝对路径前缀（旧 bug 遗留）
            if (preg_match('#(?:^|/)(storage/app/)?public(/.+)$#', $normalized, $m)) {
                $normalized = ltrim($m[2], '/');
            } elseif (strpos($normalized, 'public/') === 0) {
                $normalized = substr($normalized, strlen('public/'));
            } elseif (preg_match('#^[A-Za-z]:/#', $normalized)) {
                // 形如 C:/Users/.../storage/app/public/xxx，取 public 之后的部分
                if (($cut = strpos($normalized, '/public/')) !== false) {
                    $normalized = substr($normalized, $cut + strlen('/public/'));
                }
            }

            $normalized = ltrim($normalized, '/');
            if ($normalized === '') {
                continue;
            }

            $stream = $zip->getStream($name);
            if ($stream === false) {
                continue;
            }
            $dest = $publicRoot . '/' . $normalized;
            if (!is_dir(dirname($dest))) {
                @mkdir(dirname($dest), 0775, true);
            }
            $out = fopen($dest, 'wb');
            if ($out) {
                stream_copy_to_stream($stream, $out);
                fclose($out);
            }
            fclose($stream);
        }
        $zip->close();
    }

    private function humanSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        $size = (float) $bytes;
        while ($size >= 1024 && $i < count($units) - 1) {
            $size /= 1024;
            $i++;
        }
        return round($size, $i === 0 ? 0 : 1) . ' ' . $units[$i];
    }

    private function cleanOutput(string $output): string
    {
        return trim(strip_tags($output));
    }

    private function jsonFail(string $message, int $status = 500)
    {
        return response()->json(['success' => false, 'message' => $message], $status);
    }
}
