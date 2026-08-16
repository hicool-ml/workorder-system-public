<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

/**
 * System backup command.
 *
 * Backs up:
 *  1. Database (prefers the native dump tool for the active driver; falls back
 *     to a pure-PHP export when the binary is unavailable).
 *  2. User attachments under storage/app/public.
 *
 * Usage:
 *   php artisan backup:system              # run once now
 *   php artisan backup:system --keep=14    # keep only the latest 14 backups
 *
 * Schedule suggestion:
 *   $schedule->command('backup:system')->dailyAt('02:00');
 */
class BackupSystem extends Command
{
    protected $signature = 'backup:system {--keep=30 : number of backups to keep}';
    protected $description = 'Backup database and attachments to storage/backups';

    public function handle(): int
    {
        $disk = Storage::disk('local');
        $stamp = now()->format('Ymd_His');
        $backupDir = "backups/{$stamp}";
        $disk->makeDirectory($backupDir);
        // Explicit chmod: PHP mkdir is affected by the process umask (a scheduler
        // process commonly runs with umask 0077, which would turn 0775 into 0700
        // and prevent the web process from reading the backup directory).
        @chmod(storage_path('app/private/' . $backupDir), 0775);

        $this->info("Starting backup: {$backupDir}");

        $dbOk = $this->backupDatabase($disk, $backupDir);
        $this->backupAttachments($disk, $backupDir, $stamp);
        $this->pruneOldBackups($disk);

        if ($dbOk) {
            $this->info("Backup complete: {$backupDir}");
            return self::SUCCESS;
        }

        $this->error('Database backup failed; check the logs.');
        return self::FAILURE;
    }

    /**
     * Backup the database using the best available tool for the active driver.
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
            $this->warn('mysqldump not available, falling back to pure-PHP export (slower).');
        } elseif ($driver === 'pgsql') {
            $dumped = $this->tryPgDump($disk, $backupDir);
            if ($dumped !== null) {
                return $dumped;
            }
            $this->warn('pg_dump not available, falling back to pure-PHP export (slower).');
        }

        return $this->phpSqlDump($disk, $backupDir);
    }

    /**
     * Try to back up MySQL via the mysqldump CLI.
     */
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

        // 密码经 MYSQL_PWD 环境变量传递：不出现在命令行/进程列表（ps 可见），
        // 也避免特殊字符破坏 shell 拼接（此前 -p{$pass} 未转义双重风险）
        $cmd = sprintf(
            '%s --host=%s --port=%s -u %s --single-transaction --quick --no-tablespaces %s > %s 2>&1',
            escapeshellarg(explode("\n", $mysqldump)[0]),
            escapeshellarg($host),
            escapeshellarg((string) $port),
            escapeshellarg($user),
            escapeshellarg($dbName),
            escapeshellarg($tmpSql)
        );

        $proc = proc_open($cmd, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, null, ['MYSQL_PWD' => $pass]);
        if (!is_resource($proc)) {
            return null;
        }
        fclose($pipes[1]);
        stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        $code = proc_close($proc);

        if ($code === 0 && File::exists($tmpSql) && filesize($tmpSql) > 0) {
            $this->info('Database backed up via mysqldump.');
            return true;
        }

        if (File::exists($tmpSql)) {
            File::delete($tmpSql);
        }
        return null;
    }

    /**
     * Try to back up PostgreSQL via the pg_dump CLI.
     */
    private function tryPgDump($disk, string $backupDir): ?bool
    {
        $pgDump = pg_bin_path('pg_dump');
        if ($pgDump === '') {
            return null;
        }

        $cfg = config('database.connections.pgsql');
        $host = $cfg['host'] ?? '127.0.0.1';
        $port = $cfg['port'] ?? 5432;
        $user = $cfg['username'] ?? 'postgres';
        $pass = $cfg['password'] ?? '';
        $dbName = $cfg['database'] ?? 'laravel';

        $tmpSql = $disk->path("{$backupDir}/database.sql");
        File::ensureDirectoryExists(dirname($tmpSql));

        // Pass the password through the environment so it never shows up on
        // the command line or in the process list.
        $prevPass = getenv('PGPASSWORD');
        putenv('PGPASSWORD=' . $pass);

        $cmd = sprintf(
            '%s --host=%s --port=%s --username=%s --no-password --format=plain --no-owner --no-privileges --no-tablespaces %s > %s 2>&1',
            escapeshellarg(explode("\n", $pgDump)[0]),
            escapeshellarg($host),
            escapeshellarg((string) $port),
            escapeshellarg($user),
            escapeshellarg($dbName),
            escapeshellarg($tmpSql)
        );

        exec($cmd, $output, $code);

        putenv('PGPASSWORD=' . ($prevPass !== false ? $prevPass : ''));

        if ($code === 0 && File::exists($tmpSql) && filesize($tmpSql) > 0) {
            $this->info('Database backed up via pg_dump.');
            return true;
        }

        if (File::exists($tmpSql)) {
            File::delete($tmpSql);
        }
        return null;
    }

    /**
     * Pure-PHP table-by-table SQL export (used when the native dump binary is
     * unavailable). The output targets the active driver so it can be restored
     * on the same engine.
     */
    private function phpSqlDump($disk, string $backupDir): bool
    {
        $driver = DB::getDriverName();

        // Enumerate tables in a driver-aware way.
        $tables = [];
        if ($driver === 'mysql') {
            $rows = DB::select('SHOW TABLES');
            $key = 'Tables_in_' . config('database.connections.mysql.database');
            foreach ($rows as $r) {
                $tables[] = is_object($r) ? ($r->$key ?? null) : $r;
            }
        } elseif ($driver === 'pgsql') {
            $names = DB::table('information_schema.tables')
                ->where('table_schema', 'public')
                ->where('table_type', 'BASE TABLE')
                ->orderBy('table_name')
                ->pluck('table_name');
            foreach ($names as $n) {
                $tables[] = $n;
            }
        } else {
            $rows = DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
            foreach ($rows as $r) {
                $tables[] = $r->name ?? null;
            }
        }

        // Identifier quoting matches each engine's expectations on restore.
        $q = $driver === 'mysql' ? '`' : '"';
        $wrapIdent = fn (string $n) => $driver === 'sqlite' ? $n : "{$q}{$n}{$q}";

        $sql = "-- Workorder system database backup\n";
        $sql .= "-- Generated at: " . now() . "\n";
        $sql .= "-- Pure-PHP export\n\n";

        // Disable FK checks per driver so the dump restores cleanly.
        if ($driver === 'mysql') {
            $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";
        } elseif ($driver === 'pgsql') {
            $sql .= "SET session_replication_role = 'replica';\n\n";
        } else {
            $sql .= "PRAGMA foreign_keys = OFF;\n\n";
        }

        $count = 0;

        foreach ($tables as $table) {
            if (!$table) {
                continue;
            }

            $sql .= "-- ----------------------------\n";
            $sql .= "-- Table: {$table}\n";
            $sql .= "-- ----------------------------\n";

            // Emit the schema where we can.
            if ($driver === 'mysql') {
                $createRow = DB::select("SHOW CREATE TABLE `{$table}`");
                if (!empty($createRow)) {
                    $createCol = array_key_exists('Create Table', (array) $createRow[0])
                        ? 'Create Table'
                        : (array_keys((array) $createRow[0])[1] ?? null);
                    if ($createCol) {
                        $sql .= $createRow[0]->$createCol . ";\n\n";
                    }
                }
            } elseif ($driver === 'pgsql') {
                // PostgreSQL has no SHOW CREATE TABLE; faithfully reconstructing
                // sequences, constraints, and indexes is out of reach for the
                // pure-PHP path. Install pg_dump for full-schema backups.
                $sql .= "-- Schema omitted in pure-PHP fallback; use pg_dump for full schema.\n";
            } else {
                $row = DB::select("SELECT sql FROM sqlite_master WHERE type='table' AND name=?", [$table]);
                if (!empty($row) && !empty($row[0]->sql)) {
                    $sql .= $row[0]->sql . ";\n\n";
                }
            }

            // Data rows.
            $rows = DB::table($table)->get();
            if ($rows->isNotEmpty()) {
                $sql .= "-- Data: {$table}\n";
                $columns = array_keys((array) $rows->first());
                foreach ($rows as $row) {
                    $vals = [];
                    foreach ($columns as $col) {
                        $v = $row->$col ?? null;
                        $vals[] = $v === null ? 'NULL' : "'" . str_replace(["\\", "'"], ["\\\\", "''"], (string) $v) . "'";
                    }
                    $colList = implode(', ', array_map($wrapIdent, $columns));
                    $sql .= "INSERT INTO {$wrapIdent($table)} ({$colList}) VALUES (" . implode(',', $vals) . ");\n";
                }
                $sql .= "\n";
            }
            $count++;
        }

        // Re-enable FK checks.
        if ($driver === 'mysql') {
            $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";
        } elseif ($driver === 'pgsql') {
            $sql .= "SET session_replication_role = 'origin';\n";
        } else {
            $sql .= "PRAGMA foreign_keys = ON;\n";
        }

        $disk->put("{$backupDir}/database.sql", $sql);
        $this->info("Database backed up via pure-PHP export ({$count} tables).");
        return true;
    }

    /**
     * Zip the attachments directory.
     * v3.1 起附件存私有盘 storage/app/attachments；旧版残留仍在 storage/app/public。
     * 两处都打包（条目名均为相对路径，如 workorder_attachments/xxx），恢复端写入私有盘。
     */
    private function backupAttachments($disk, string $backupDir, string $stamp): void
    {
        $sources = [
            storage_path('app/attachments'),   // 现行私有盘（主）
            storage_path('app/public'),        // 旧版公开盘（历史附件兼容）
        ];

        $zipPath = $disk->path("{$backupDir}/attachments.zip");
        File::ensureDirectoryExists(dirname($zipPath));

        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            $this->warn('Attachment zip failed; skipping.');
            return;
        }

        $fileCount = 0;
        foreach ($sources as $sourceDir) {
            if (!File::exists($sourceDir)) {
                continue;
            }
            $realBase = realpath($sourceDir);
            if (!$realBase) {
                continue;
            }
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($sourceDir, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::LEAVES_ONLY
            );
            foreach ($files as $file) {
                if (!$file->isDir()) {
                    $real = $file->getRealPath();
                    $relative = ltrim(str_replace(DIRECTORY_SEPARATOR, '/', substr($real, strlen($realBase))), '/');
                    if ($relative === '') {
                        continue;
                    }
                    // 私有盘条目保持原相对名；旧盘条目去掉 public/ 前缀语义由 realpath 基准天然处理
                    $zip->addFile($real, $relative);
                    $fileCount++;
                }
            }
        }

        $zip->close();
        if ($fileCount > 0) {
            $this->info("Attachments backed up ({$fileCount} files).");
        } else {
            $this->warn('No attachments found to back up.');
        }
    }

    /**
     * Remove old backups beyond the keep limit.
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
            $this->line("Pruned old backup: {$dirs[$i]}");
        }
    }
}
