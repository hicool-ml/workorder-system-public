<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Workorder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * 从旧生产系统（MySQL，旧代码库）导入工单到本系统（PG）。
 *
 * 设计要点：
 *  - 幂等：以 ticket_no 为键，已存在的单自动跳过，可重复执行
 *  - 保真：created_at / 生命周期时间戳 / 满意度等全部原样保留（转库而非重录的原因）
 *  - 映射：用户按 username 匹配（缺失自动开户）；分类按名称映射（含整理期的合并表）；
 *          地址按 building/campus 名称在新地址树中查找，找不到退回 location_detail
 *  - 附件/文件本体不迁（生产 storage 单独拷贝），仅可选导入元数据
 *
 * 用法：
 *   php artisan workorders:import-mysql --old-env="C:/path/old/.env" --since=2026-08-08 --dry-run
 *   php artisan workorders:import-mysql --old-env=... --since=2026-08-08
 *
 * 连接参数也可用 --host/--port/--database/--username/--password 显式指定。
 */
class ImportWorkordersFromMysql extends Command
{
    protected $signature = 'workorders:import-mysql
        {--since= : 只导入 created_at >= 该日期的工单（如 2026-08-08）}
        {--old-env= : 旧项目 .env 文件路径（读取 DB_* 连接信息）}
        {--host=} {--port=3306} {--database=} {--username=} {--password=}
        {--with-attachments : 同时导入附件元数据（文件本体需另行拷贝到私有盘）}
        {--dry-run : 只统计不写入}';

    protected $description = '从旧 MySQL 生产库导入工单（幂等，保留原始时间戳）';

    /** 旧分类名 → 新分类名（分类整理期的合并映射，见 _bak_categories_20260816） */
    private const CATEGORY_NAME_MAP = [
        '互联网' => '上网故障',
        '网络故障' => '上网故障',          // 旧#35（多媒体下）与旧#81 顶级同名，均并入上网故障
        '咨询帐号' => '电话咨询',
        '咨询校园网' => '电话咨询',
        '咨询OA' => '电话咨询',
        '咨询邮箱' => '电话咨询',
        '咨询5G' => '电话咨询',
        '咨询VPN' => '电话咨询',
        '机房异响' => '物联网',
        '系统迁移' => '设备迁改',
        '软件支持' => '其它',              // 顶级直接挂单的兜底
    ];

    /** 旧状态 → 新状态（新状态机只有 6 态） */
    private const STATUS_MAP = [
        'verifying' => 'processing',
        'rejected' => 'closed',
    ];

    private array $stat = [
        'imported' => 0, 'skipped_existing' => 0, 'skipped_test' => 0,
        'users_created' => 0, 'cat_fallback' => 0, 'loc_unmatched' => 0,
        'logs' => 0, 'visits' => 0, 'attachments' => 0,
    ];
    private array $warnings = [];

    public function handle(): int
    {
        $pdo = $this->connect();
        $since = $this->option('since');
        $dryRun = (bool) $this->option('dry-run');

        // 幂等键预载：新库全部 ticket_no
        $existingTickets = DB::table('workorders')->pluck('ticket_no')->flip();
        $newCats = DB::table('workorder_categories_simplified')->get();
        $catByName = $newCats->keyBy('name');
        $userByName = User::pluck('id', 'username');

        $rows = $this->fetchRows($pdo, $since);
        $this->info('待处理 ' . count($rows) . ' 条（since=' . ($since ?: '全部') . '）' . ($dryRun ? ' 【DRY-RUN】' : ''));

        $bar = $this->output->createProgressBar(count($rows));
        $bar->start();

        if (!$dryRun) {
            DB::beginTransaction();
        }

        try {
            foreach ($rows as $row) {
                $bar->advance();

                if (preg_match('/^TEST/i', $row['ticket_no'])) {
                    $this->stat['skipped_test']++;
                    continue;
                }
                if ($existingTickets->has($row['ticket_no'])) {
                    $this->stat['skipped_existing']++;
                    continue;
                }

                $creatorId = $this->mapUser($row['creator_id'], $pdo, $userByName, $dryRun);
                $assigneeId = $row['assignee_id'] ? $this->mapUser($row['assignee_id'], $pdo, $userByName, $dryRun) : null;
                $categoryId = $this->mapCategory($row['category_id'], $pdo, $catByName);
                $locationId = $this->mapLocation($row);

                if (!$dryRun) {
                    $newId = DB::table('workorders')->insertGetId([
                        'ticket_prefix' => $row['ticket_prefix'] ?: substr($row['ticket_no'], 0, 1),
                        'ticket_no' => $row['ticket_no'],
                        'title' => $row['title'],
                        'description' => $row['description'],
                        'failure_description' => $row['failure_description'],
                        'type_id' => null,
                        'category_id' => $categoryId,
                        'creator_id' => $creatorId,
                        'assignee_id' => $assigneeId,
                        'department_id' => $row['department_id'],
                        'department_name' => $row['department_name'],
                        'contact_name' => $row['contact_name'],
                        'contact_phone' => $row['contact_phone'],
                        'contact_email' => $row['contact_email'],
                        'location_detail' => $row['location_detail'] ?: $row['location'],
                        'location_id' => $locationId,
                        'appointment_time_start' => $row['appointment_time_start'],
                        'appointment_time_end' => $row['appointment_time_end'],
                        'appointment_time' => $row['appointment_time'],
                        'source' => $row['source'] ?: 'other',
                        'custom_source' => $row['custom_source'],
                        'time_limit_hours' => $row['time_limit_hours'],
                        'priority' => $row['priority'] ?: 'medium',
                        'status' => self::STATUS_MAP[$row['status']] ?? $row['status'],
                        'assigned_at' => $row['assigned_at'],
                        'started_at' => $row['started_at'],
                        'resolved_at' => $row['resolved_at'],
                        'completed_at' => $row['completed_at'],
                        'closed_at' => $row['closed_at'],
                        'expected_complete_at' => $row['expected_complete_at'],
                        'processing_duration' => $row['processing_duration'],
                        'solution' => $row['solution'],
                        'remarks' => $row['remarks'],
                        'materials_usage' => $row['materials_usage'],
                        'other_reason' => $row['other_reason'],
                        'need_visit' => (bool) $row['need_visit'],
                        'is_emergency' => (bool) $row['is_emergency'],
                        'phone_assisted' => (bool) $row['phone_assisted'],
                        'requires_signature' => (bool) $row['requires_signature'],
                        'user_feedback' => $row['user_feedback'],
                        'visit_status' => $row['visit_status'],
                        'user_satisfaction' => $row['user_satisfaction'],
                        'user_signature' => $row['user_signature'],
                        'user_signed_at' => $row['user_signed_at'],
                        'is_user_signed' => (bool) $row['is_user_signed'],
                        'sms_acceptance_sent_at' => $row['sms_acceptance_sent_at'],
                        'sms_survey_sent_at' => $row['sms_survey_sent_at'],
                        'sms_satisfaction' => $row['sms_satisfaction'],
                        'sms_satisfaction_at' => $row['sms_satisfaction_at'],
                        'created_at' => $row['created_at'],
                        'updated_at' => $row['updated_at'],
                        'deleted_at' => $row['deleted_at'],
                    ]);

                    $this->importLogs($pdo, $row['id'], $newId);
                    $this->importVisits($pdo, $row['id'], $newId, $userByName);
                    if ($this->option('with-attachments')) {
                        $this->importAttachments($pdo, $row['id'], $newId);
                    }
                }

                $this->stat['imported']++;
                $existingTickets->offsetSet($row['ticket_no'], true); // 同批次内防重
            }

            if (!$dryRun) {
                DB::commit();
            }
        } catch (\Throwable $e) {
            if (!$dryRun) {
                DB::rollBack();
            }
            $bar->finish();
            $this->newLine(2);
            $this->error('导入失败已回滚：' . $e->getMessage());
            return self::FAILURE;
        }

        $bar->finish();
        $this->newLine(2);
        $this->table(['指标', '数量'], array_map(fn ($k, $v) => [$k, $v], array_keys($this->stat), $this->stat));

        if ($this->warnings) {
            $this->warn('需人工复核（' . count($this->warnings) . ' 条）：');
            foreach (array_slice($this->warnings, 0, 30) as $w) {
                $this->line('  - ' . $w);
            }
        }

        return self::SUCCESS;
    }

    private function connect(): \PDO
    {
        $host = $this->option('host');
        $port = $this->option('port');
        $db = $this->option('database');
        $user = $this->option('username');
        $pass = (string) $this->option('password');

        // 未显式指定时从旧项目 .env 读取
        if (!$host && $this->option('old-env')) {
            $env = file_get_contents($this->option('old-env'));
            $val = function (string $key, string $default) use ($env) {
                return preg_match('/^' . $key . '=(.*)$/m', $env, $m) ? trim($m[1], "\"' ") : $default;
            };
            $host = $val('DB_HOST', '127.0.0.1');
            $port = $val('DB_PORT', '3306');
            $db = $db ?: $val('DB_DATABASE', '');
            $user = $user ?: $val('DB_USERNAME', 'root');
            $pass = $pass !== '' ? $pass : $val('DB_PASSWORD', '');
        }

        if (!$host || !$db) {
            throw new \InvalidArgumentException('缺少连接信息：用 --old-env 指向旧项目 .env，或显式传 --host/--database/--username/--password');
        }

        return new \PDO(
            "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4",
            $user,
            $pass,
            [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
        );
    }

    private function fetchRows(\PDO $pdo, ?string $since): array
    {
        $sql = 'SELECT * FROM workorders';
        $params = [];
        if ($since) {
            $sql .= ' WHERE created_at >= ?';
            $params[] = $since;
        }
        $sql .= ' ORDER BY created_at';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /** 用户映射：username 匹配；缺失自动开户（普通用户、随机密码、需首次改密） */
    private function mapUser($oldUserId, \PDO $pdo, &$userByName, bool $dryRun): ?int
    {
        if (!$oldUserId) {
            return null;
        }
        static $cache = [];
        if (isset($cache[$oldUserId])) {
            return $cache[$oldUserId];
        }

        $u = $pdo->query("SELECT username, name, email, phone, employee_id, account_type FROM users WHERE id = " . (int) $oldUserId)->fetch(\PDO::FETCH_ASSOC);
        if (!$u) {
            $this->warnings[] = "旧用户 #{$oldUserId} 不存在，工单创建人置空";
            return $cache[$oldUserId] = null;
        }

        $username = $u['username'] ?: ('migrated_' . $oldUserId);
        if ($userByName->has($username)) {
            return $cache[$oldUserId] = $userByName[$username];
        }

        $newId = null;
        if (!$dryRun) {
            $newId = DB::table('users')->insertGetId([
                'name' => $u['name'] ?: $username,
                'username' => $username,
                'email' => $u['email'] ?: ($username . '@migrated.local'),
                'phone' => $u['phone'],
                'employee_id' => $u['employee_id'],
                'password' => bcrypt(Str::random(32)),
                'role' => 'user',
                'status' => 'active',
                'account_type' => $u['account_type'] ?: 'external',
                'password_changed_at' => null, // 强制首登改密
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $userByName->offsetSet($username, $newId);
        }
        $this->stat['users_created']++;

        return $cache[$oldUserId] = $newId;
    }

    /** 分类映射：名称映射表 → 新树查找 → 兜底「其它」下电话咨询 */
    private function mapCategory($oldCatId, \PDO $pdo, $catByName): ?int
    {
        if (!$oldCatId) {
            return null;
        }
        static $cache = [];
        if (isset($cache[$oldCatId])) {
            return $cache[$oldCatId];
        }

        $name = $pdo->query("SELECT name FROM workorder_categories_simplified WHERE id = " . (int) $oldCatId)->fetchColumn();
        if ($name) {
            $mapped = self::CATEGORY_NAME_MAP[$name] ?? $name;
            $cat = $catByName->first(fn ($c) => $c->name === $mapped);
            if ($cat) {
                return $cache[$oldCatId] = $cat->id;
            }
        }

        $fallback = $catByName['电话咨询'] ?? null;
        $this->stat['cat_fallback']++;
        $this->warnings[] = "旧分类「{$name}」(#{$oldCatId}) 无匹配，归入电话咨询";
        return $cache[$oldCatId] = $fallback?->id;
    }

    /** 地址映射：building 名 → campus 名 → 置空（保留 location_detail） */
    private function mapLocation(array $row): ?int
    {
        foreach (['building', 'campus'] as $field) {
            $name = trim((string) ($row[$field] ?? ''));
            if ($name === '') {
                continue;
            }
            $loc = DB::table('locations')->where('name', $name)->first();
            if ($loc) {
                return $loc->id;
            }
        }
        if (($row['building'] ?? '') || ($row['campus'] ?? '')) {
            $this->stat['loc_unmatched']++;
            $this->warnings[] = "{$row['ticket_no']} 地址「{$row['building']} {$row['campus']}」未匹配，仅保留详细地址";
        }
        return null;
    }

    private function importLogs(\PDO $pdo, int $oldWoId, int $newWoId): void
    {
        $stmt = $pdo->prepare('SELECT user_id, action, content, is_system, created_at FROM workorder_logs WHERE workorder_id = ? ORDER BY created_at');
        $stmt->execute([$oldWoId]);
        $users = User::pluck('id', 'id'); // 占位；旧 user_id 需换算，此处保守置空
        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $log) {
            // 旧日志 user_id 体系不同，无法可靠映射时置空保内容
            $newUserId = null;
            if ($log['user_id']) {
                $uname = $pdo->query("SELECT username FROM users WHERE id = " . (int) $log['user_id'])->fetchColumn();
                $newUserId = $uname ? User::where('username', $uname)->value('id') : null;
            }
            DB::table('workorder_logs')->insert([
                'workorder_id' => $newWoId,
                'user_id' => $newUserId,
                'action' => $log['action'],
                'content' => $log['content'],
                'is_system' => (bool) $log['is_system'],
                'created_at' => $log['created_at'],
                'updated_at' => $log['created_at'],
            ]);
            $this->stat['logs']++;
        }
    }

    private function importVisits(\PDO $pdo, int $oldWoId, int $newWoId): void
    {
        $stmt = $pdo->prepare('SELECT * FROM workorder_visits WHERE workorder_id = ?');
        $stmt->execute([$oldWoId]);
        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $v) {
            DB::table('workorder_visits')->insert([
                'workorder_id' => $newWoId,
                'visitor_id' => $v['visitor_id'] ?? null,
                'visit_type' => $v['visit_type'] ?? 'phone',
                'visit_status' => $v['visit_status'],
                'satisfaction_score' => $v['satisfaction_score'],
                'overall_score' => $v['overall_score'] ?? null,
                'response_speed_score' => $v['satisfaction_score'], // 旧库只有单一满意度
                'visit_notes' => $v['visit_notes'],
                'visited_at' => $v['visited_at'],
                'created_at' => $v['created_at'],
                'updated_at' => $v['updated_at'],
            ]);
            $this->stat['visits']++;
        }
    }

    private function importAttachments(\PDO $pdo, int $oldWoId, int $newWoId): void
    {
        $stmt = $pdo->prepare('SELECT * FROM workorder_attachments WHERE workorder_id = ?');
        $stmt->execute([$oldWoId]);
        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $a) {
            DB::table('workorder_attachments')->insert([
                'workorder_id' => $newWoId,
                'user_id' => $a['user_id'],
                'filename' => basename($a['file_path'] ?? '') ?: 'migrated',
                'original_name' => $a['original_name'] ?: '附件',
                'file_path' => $a['file_path'],
                'file_type' => $a['file_type'] ?? 'other',
                'file_size' => $a['file_size'] ?? 0,
                'mime_type' => $a['mime_type'],
                'description' => $a['description'],
                'type' => $a['file_type'] ?? 'other',
                'is_public' => false,
                'created_at' => $a['created_at'],
                'updated_at' => $a['updated_at'],
            ]);
            $this->stat['attachments']++;
        }
    }
}
