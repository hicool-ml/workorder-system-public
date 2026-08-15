<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PDO;

/**
 * 上线迁移演练工具：在 workorder_rehearsal 库从零完整走一遍上线流程。
 *
 * 流程：
 *   1. 空库 migrate（--fresh 时重建）
 *   2. 从当前主库复制基础数据（分类/用户/地址树/系统设置）
 *   3. 从旧 MySQL 生产库全量导单（幂等 ticket_no）
 *   4. 跑 categories:reorganize 验证整理规则
 *   5. 对账报告（数量/时间戳抽验）
 *
 * 与另两个工具组成上线工具链：
 *   - migrate:rehearsal      本命令（端到端演练）
 *   - workorders:import-mysql  正式导入（生产快照 → 主库）
 *   - categories:reorganize    分类整理（幂等）
 *
 * 用法：
 *   php artisan migrate:rehearsal --old-env="C:/old/.env" [--fresh]
 */
class RehearseMigration extends Command
{
    protected $signature = 'migrate:rehearsal
        {--old-env= : 旧项目 .env 路径（MySQL 连接信息）}
        {--host=} {--port=3306} {--database=} {--username=} {--password=}
        {--fresh : 重建演练库（drop 所有表重新迁移）}';

    protected $description = '上线迁移演练：空库全流程（迁移+基础数据+导单+整理+对账）';

    /** 基础数据表，按依赖顺序 */
    private const BASE_TABLES = [
        'location_levels',
        'campuses',
        'locations',
        'departments',
        'users',
        'workorder_categories_simplified',
        'workorder_sources',
        'system_settings',
    ];

    /** 旧分类名 → 新分类名（与 import-mysql 保持一致） */
    private const CAT_MAP = [
        '互联网' => '上网故障', '网络故障' => '上网故障',
        '咨询帐号' => '电话咨询', '咨询校园网' => '电话咨询', '咨询OA' => '电话咨询',
        '咨询邮箱' => '电话咨询', '咨询5G' => '电话咨询', '咨询VPN' => '电话咨询',
        '机房异响' => '物联网', '系统迁移' => '设备迁改',
    ];

    private const STATUS_MAP = ['verifying' => 'processing', 'rejected' => 'closed'];

    public function handle(): int
    {
        $rehearsal = DB::connection('pgsql_rehearsal');
        $main = DB::connection();

        // ===== 0. 可选重建 =====
        if ($this->option('fresh')) {
            $this->info('重建演练库...');
            $rehearsal->statement('DROP SCHEMA public CASCADE');
            $rehearsal->statement('CREATE SCHEMA public');
            $this->call('migrate', ['--force' => true, '--database' => 'pgsql_rehearsal']);
        }

        // ===== 1. 基础数据 =====
        $this->info('[1/5] 复制基础数据');
        foreach (self::BASE_TABLES as $t) {
            $rows = $main->table($t)->get()->map(fn ($r) => (array) $r)->all();
            $count = count($rows);
            if ($count === 0) {
                continue;
            }
            $hasData = $rehearsal->table($t)->exists();
            if (!$hasData) {
                $this->convertMysqlCompat($rehearsal, $t, $rows);
                $rehearsal->table($t)->insert($rows);
                $maxId = $rehearsal->table($t)->max('id');
                if ($maxId) {
                    $rehearsal->statement("SELECT setval(pg_get_serial_sequence('{$t}', 'id'), {$maxId})");
                }
            }
            $this->line("  {$t}: {$count} 行");
        }
        // 演练库关闭一切外发通知
        $rehearsal->table('system_settings')->whereIn('key', ['wecom_webhook_enabled', 'wecom_app_enabled', 'sms_enabled'])->update(['value' => '0']);
        $this->line('  企微/短信通知已关闭（防演练误发）');

        // ===== 2. 导入工单 =====
        $this->info('[2/5] 从旧 MySQL 导入工单');
        $pdo = $this->connectMysql();
        $imported = $this->importWorkorders($pdo, $rehearsal);

        // ===== 3. 分类整理 =====
        $this->info('[3/5] 分类整理');
        $this->call('categories:reorganize');

        // ===== 4. 对账 =====
        $this->info('[4/5] 对账');
        $mysqlTotal = (int) $pdo->query("SELECT COUNT(*) FROM workorders WHERE ticket_no NOT LIKE 'TEST%'")->fetchColumn();
        $rehTotal = $rehearsal->table('workorders')->count();
        $this->table(['指标', '演练库', '旧库'], [
            ['工单总数', $rehTotal, $mysqlTotal],
            ['工单日志', $rehearsal->table('workorder_logs')->count(), $pdo->query('SELECT COUNT(*) FROM workorder_logs')->fetchColumn()],
        ]);

        // 时间戳抽验
        $sample = $rehearsal->table('workorders')->orderByDesc('id')->limit(5)->get(['ticket_no', 'created_at']);
        $srcIdx = [];
        foreach ($pdo->query("SELECT ticket_no, created_at FROM workorders ORDER BY id DESC LIMIT 20") as $r) {
            $srcIdx[$r['ticket_no']] = $r['created_at'];
        }
        $ok = 0;
        foreach ($sample as $s) {
            if (isset($srcIdx[$s->ticket_no]) && substr($srcIdx[$s->ticket_no], 0, 19) === substr($s->created_at, 0, 19)) $ok++;
        }
        $this->line("时间戳抽验：{$ok}/5 精确一致");

        $this->info('[5/5] 演练完成 ✔');
        $this->newLine();
        $this->line('正式上线时执行顺序：');
        $this->line('  1. php artisan workorders:import-mysql --old-env=<生产.env> --dry-run  # 对账');
        $this->line('  2. php artisan workorders:import-mysql --old-env=<生产.env>          # 导入');
        $this->line('  3. php artisan categories:reorganize                               # 整理');

        return self::SUCCESS;
    }

    private function connectMysql(): PDO
    {
        $host = $this->option('host');
        if (!$host && $this->option('old-env')) {
            $env = file_get_contents($this->option('old-env'));
            $val = fn (string $k, string $d) => preg_match('/^' . $k . '=(.*)$/m', $env, $m) ? trim($m[1], "\"' ") : $d;
            $host = $val('DB_HOST', '127.0.0.1');
            $port = $val('DB_PORT', '3306');
            $db = $this->option('database') ?: $val('DB_DATABASE', '');
            $user = $this->option('username') ?: $val('DB_USERNAME', 'root');
            $pass = (string) $this->option('password') !== '' ? $this->option('password') : $val('DB_PASSWORD', '');
        } else {
            $port = $this->option('port');
            $db = $this->option('database');
            $user = $this->option('username');
            $pass = (string) $this->option('password');
        }
        if (!$host || !$db) {
            throw new \InvalidArgumentException('需要 --old-env 或显式 --host/--database/--username/--password');
        }
        return new PDO("mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    }

    /** MySQL → PG 兼容转换：空串转 null（NOT NULL 列给唯一占位） */
    private function convertMysqlCompat($rehearsal, string $table, array &$rows): void
    {
        $notNullCols = collect($rehearsal->select(
            "SELECT column_name FROM information_schema.columns
             WHERE table_name = ? AND is_nullable = 'NO' AND data_type IN ('character varying','character','text')",
            [$table]
        ))->pluck('column_name')->all();

        foreach ($rows as &$r) {
            foreach ($r as $k => $v) {
                if ($v === '') {
                    $r[$k] = in_array($k, $notNullCols, true) ? 'MIGRATED_' . $r['id'] : null;
                }
            }
        }
    }

    private function importWorkorders(PDO $pdo, $rehearsal): int
    {
        $existing = $rehearsal->table('workorders')->pluck('ticket_no')->flip();
        $catByName = $rehearsal->table('workorder_categories_simplified')->get()->keyBy('name');
        $userByName = $rehearsal->table('users')->pluck('id', 'username');
        $locCache = [];

        $userCache = [];
        $mapUser = function ($oldId) use ($pdo, $userByName, &$userCache) {
            if (!$oldId) return null;
            if (isset($userCache[$oldId])) return $userCache[$oldId];
            $u = $pdo->query("SELECT username FROM users WHERE id = " . (int) $oldId)->fetchColumn();
            return $userCache[$oldId] = ($u ? ($userByName[$u] ?? null) : null);
        };

        $imported = 0;
        $stmt = $pdo->query("SELECT * FROM workorders ORDER BY created_at");
        $bar = $this->output->createProgressBar($stmt->rowCount());
        $bar->start();

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $bar->advance();
            if (preg_match('/^TEST/i', $row['ticket_no'])) continue;
            if ($existing->has($row['ticket_no'])) continue;

            $categoryId = null;
            if ($row['category_id']) {
                $name = $pdo->query("SELECT name FROM workorder_categories_simplified WHERE id = " . (int) $row['category_id'])->fetchColumn();
                if ($name) {
                    $cat = $catByName->first(fn ($c) => $c->name === (self::CAT_MAP[$name] ?? $name));
                    $categoryId = $cat->id ?? null;
                }
            }
            $categoryId ??= $catByName['电话咨询']?->id;

            $locationId = null;
            foreach (['building', 'campus'] as $f) {
                $n = trim((string) ($row[$f] ?? ''));
                if ($n === '') continue;
                if (!isset($locCache[$n])) $locCache[$n] = $rehearsal->table('locations')->where('name', $n)->value('id');
                if ($locCache[$n]) { $locationId = $locCache[$n]; break; }
            }

            $newId = $rehearsal->table('workorders')->insertGetId($this->workorderPayload($row, $categoryId, $mapUser($row['creator_id']), $mapUser($row['assignee_id']), $locationId));
            $imported++;
            $existing->offsetSet($row['ticket_no'], true);

            $ls = $pdo->prepare('SELECT user_id, action, content, is_system, created_at FROM workorder_logs WHERE workorder_id = ?');
            $ls->execute([$row['id']]);
            foreach ($ls->fetchAll(PDO::FETCH_ASSOC) as $log) {
                $rehearsal->table('workorder_logs')->insert([
                    'workorder_id' => $newId,
                    'user_id' => $mapUser($log['user_id']),
                    'action' => $log['action'],
                    'content' => $log['content'],
                    'is_system' => (bool) $log['is_system'],
                    'created_at' => $log['created_at'],
                    'updated_at' => $log['created_at'],
                ]);
            }
        }
        $bar->finish();
        $this->newLine();
        $this->line("  导入 {$imported} 单");
        return $imported;
    }

    private function workorderPayload(array $r, ?int $cat, ?int $creator, ?int $assignee, ?int $loc): array
    {
        return [
            'ticket_prefix' => $r['ticket_prefix'] ?: substr($r['ticket_no'], 0, 1),
            'ticket_no' => $r['ticket_no'],
            'title' => $r['title'], 'description' => $r['description'],
            'failure_description' => $r['failure_description'],
            'type_id' => null, 'category_id' => $cat,
            'creator_id' => $creator, 'assignee_id' => $assignee,
            'department_id' => $r['department_id'], 'department_name' => $r['department_name'],
            'contact_name' => $r['contact_name'], 'contact_phone' => $r['contact_phone'],
            'contact_email' => $r['contact_email'],
            'location_detail' => $r['location_detail'] ?: $r['location'],
            'location_id' => $loc,
            'appointment_time_start' => $r['appointment_time_start'],
            'appointment_time_end' => $r['appointment_time_end'],
            'appointment_time' => $r['appointment_time'],
            'source' => $r['source'] ?: 'other', 'custom_source' => $r['custom_source'],
            'time_limit_hours' => $r['time_limit_hours'],
            'priority' => $r['priority'] ?: 'medium',
            'status' => self::STATUS_MAP[$r['status']] ?? $r['status'],
            'assigned_at' => $r['assigned_at'], 'started_at' => $r['started_at'],
            'resolved_at' => $r['resolved_at'], 'completed_at' => $r['completed_at'],
            'closed_at' => $r['closed_at'], 'expected_complete_at' => $r['expected_complete_at'],
            'processing_duration' => $r['processing_duration'],
            'solution' => $r['solution'], 'remarks' => $r['remarks'],
            'materials_usage' => $r['materials_usage'], 'other_reason' => $r['other_reason'],
            'need_visit' => (bool) $r['need_visit'], 'is_emergency' => (bool) $r['is_emergency'],
            'phone_assisted' => (bool) $r['phone_assisted'],
            'requires_signature' => (bool) $r['requires_signature'],
            'user_feedback' => $r['user_feedback'], 'visit_status' => $r['visit_status'],
            'user_satisfaction' => $r['user_satisfaction'],
            'user_signature' => $r['user_signature'], 'user_signed_at' => $r['user_signed_at'],
            'is_user_signed' => (bool) $r['is_user_signed'],
            'sms_acceptance_sent_at' => $r['sms_acceptance_sent_at'],
            'sms_survey_sent_at' => $r['sms_survey_sent_at'],
            'sms_satisfaction' => $r['sms_satisfaction'], 'sms_satisfaction_at' => $r['sms_satisfaction_at'],
            'created_at' => $r['created_at'], 'updated_at' => $r['updated_at'],
            'deleted_at' => $r['deleted_at'],
        ];
    }
}
