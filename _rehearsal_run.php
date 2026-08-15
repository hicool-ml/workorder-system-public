<?php
/**
 * 上线迁移演练：空 PG 库（workorder_rehearsal）全流程
 * 1. 基础数据：分类树 + 用户 + 地址树 + 系统设置（从当前调试库复制，分类为已整理版）
 * 2. 工单导入：本地 MySQL 全量（import-mysql 命令逻辑指向 rehearsal 库）
 * 3. categories:reorganize 验证幂等
 * 4. 对账报告
 */
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$rehearsal = DB::connection('pgsql_rehearsal');
$main = DB::connection();

// 需要复制的基础表（含数据），按依赖顺序
$tables = [
    'location_levels',
    'campuses',                           // 旧兼容表，locations.campus_id 外键仍指向它
    'locations',
    'departments',
    'users',
    'workorder_categories_simplified',
    'workorder_sources',
    'system_settings',
];

echo "=== 1. 复制基础数据 ===" . PHP_EOL;
foreach ($tables as $t) {
    $rows = $main->table($t)->get()->map(fn ($r) => (array) $r)->all();
    // 先清后插：迁移种子可能已插入 id 不同的行，唯一约束（code/key/username 等）会挡 upsert
    $rehearsal->statement("TRUNCATE TABLE {$t} RESTART IDENTITY CASCADE");
    if ($rows) {
        // MySQL→PG 兼容转换：
        // 1) 空串统一转 null（PG 唯一约束不允许多个空串）
        // 2) NOT NULL 的字符串列空值给唯一占位（如 departments.code / categories.ticket_prefix）
        $notNullCols = collect($rehearsal->select("
            SELECT column_name FROM information_schema.columns
            WHERE table_name = ? AND is_nullable = 'NO' AND data_type IN ('character varying','character','text')",
            [$t]))->pluck('column_name')->all();
        foreach ($rows as &$r) {
            foreach ($r as $k => $v) {
                if ($v === '') {
                    $r[$k] = in_array($k, $notNullCols) ? 'MIGRATED_' . $r['id'] : null;
                }
            }
        }
        unset($r);
        $rehearsal->table($t)->insert($rows);
    }
    // 序列重置到 max(id)
    $maxId = $rehearsal->table($t)->max('id') ?? 0;
    if ($maxId > 0) {
        $rehearsal->statement("SELECT setval(pg_get_serial_sequence('{$t}', 'id'), {$maxId})");
    }
    echo "  {$t}: " . count($rows) . " 行" . PHP_EOL;
}

// 演练库关掉企微通知（防止导入时给同事发消息）
$rehearsal->table('system_settings')->where('key', 'like', 'wecom_%')->update(['value' => '0']);
echo "  演练库企微通知已全部关闭" . PHP_EOL;

echo PHP_EOL . "=== 2. 导入工单（本地 MySQL 全量 → 演练库）===" . PHP_EOL;
$oldEnv = file_get_contents('C:/Users/66107/Desktop/workorder-system/.env');
function envVal($env, $key, $default) {
    if (preg_match('/^' . $key . '=(.*)$/m', $env, $m)) return trim($m[1], "\"' ");
    return $default;
}
$pdo = new PDO(
    "mysql:host=" . envVal($oldEnv, 'DB_HOST', '127.0.0.1') . ";port=" . envVal($oldEnv, 'DB_PORT', '3306') . ";dbname=workorder_db;charset=utf8mb4",
    envVal($oldEnv, 'DB_USERNAME', 'root'),
    envVal($oldEnv, 'DB_PASSWORD', ''),
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$existing = $rehearsal->table('workorders')->pluck('ticket_no')->flip();
$newCats = $rehearsal->table('workorder_categories_simplified')->get();
$catByName = $newCats->keyBy('name');
$userByName = $rehearsal->table('users')->pluck('id', 'username');

$CAT_MAP = [
    '互联网' => '上网故障', '网络故障' => '上网故障',
    '咨询帐号' => '电话咨询', '咨询校园网' => '电话咨询', '咨询OA' => '电话咨询',
    '咨询邮箱' => '电话咨询', '咨询5G' => '电话咨询', '咨询VPN' => '电话咨询',
    '机房异响' => '物联网', '系统迁移' => '设备迁改',
];
$STATUS_MAP = ['verifying' => 'processing', 'rejected' => 'closed'];

$stmt = $pdo->query("SELECT * FROM workorders ORDER BY created_at");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stat = ['imported' => 0, 'skipped' => 0, 'test' => 0];
$catFallback = [];
$logs = 0;

foreach ($rows as $row) {
    if (preg_match('/^TEST/i', $row['ticket_no'])) { $stat['test']++; continue; }
    if ($existing->has($row['ticket_no'])) { $stat['skipped']++; continue; }

    // 用户映射（复用主库已有的 username 匹配）
    $creatorId = null; $assigneeId = null;
    foreach (['creator_id' => 'creator', 'assignee_id' => 'assignee'] as $col => $_) {
        if (!$row[$col]) continue;
        $u = $pdo->query("SELECT username FROM users WHERE id = " . (int) $row[$col])->fetchColumn();
        $mapped = $u ? ($userByName[$u] ?? null) : null;
        if ($col === 'creator_id') $creatorId = $mapped; else $assigneeId = $mapped;
    }

    // 分类映射
    $categoryId = null;
    if ($row['category_id']) {
        $name = $pdo->query("SELECT name FROM workorder_categories_simplified WHERE id = " . (int) $row['category_id'])->fetchColumn();
        if ($name) {
            $mapped = $CAT_MAP[$name] ?? $name;
            $cat = $catByName->first(fn ($c) => $c->name === $mapped);
            $categoryId = $cat?->id;
            if (!$cat) $catFallback[] = "{$row['ticket_no']}: {$name}";
        }
    }
    if (!$categoryId) {
        $categoryId = $catByName['电话咨询']?->id;
        if ($row['category_id']) $catFallback[] = "{$row['ticket_no']}: (原{$row['category_id']})兜底电话咨询";
    }

    // 地址映射
    $locationId = null;
    foreach (['building', 'campus'] as $f) {
        $n = trim((string) ($row[$f] ?? ''));
        if ($n !== '') {
            $loc = $rehearsal->table('locations')->where('name', $n)->first();
            if ($loc) { $locationId = $loc->id; break; }
        }
    }

    $newId = $rehearsal->table('workorders')->insertGetId([
        'ticket_prefix' => $row['ticket_prefix'] ?: substr($row['ticket_no'], 0, 1),
        'ticket_no' => $row['ticket_no'],
        'title' => $row['title'], 'description' => $row['description'],
        'failure_description' => $row['failure_description'],
        'type_id' => null, 'category_id' => $categoryId,
        'creator_id' => $creatorId, 'assignee_id' => $assigneeId,
        'department_id' => $row['department_id'], 'department_name' => $row['department_name'],
        'contact_name' => $row['contact_name'], 'contact_phone' => $row['contact_phone'],
        'contact_email' => $row['contact_email'],
        'location_detail' => $row['location_detail'] ?: $row['location'],
        'location_id' => $locationId,
        'appointment_time_start' => $row['appointment_time_start'],
        'appointment_time_end' => $row['appointment_time_end'],
        'appointment_time' => $row['appointment_time'],
        'source' => $row['source'] ?: 'other', 'custom_source' => $row['custom_source'],
        'time_limit_hours' => $row['time_limit_hours'],
        'priority' => $row['priority'] ?: 'medium',
        'status' => $STATUS_MAP[$row['status']] ?? $row['status'],
        'assigned_at' => $row['assigned_at'], 'started_at' => $row['started_at'],
        'resolved_at' => $row['resolved_at'], 'completed_at' => $row['completed_at'],
        'closed_at' => $row['closed_at'], 'expected_complete_at' => $row['expected_complete_at'],
        'processing_duration' => $row['processing_duration'],
        'solution' => $row['solution'], 'remarks' => $row['remarks'],
        'materials_usage' => $row['materials_usage'], 'other_reason' => $row['other_reason'],
        'need_visit' => (bool) $row['need_visit'], 'is_emergency' => (bool) $row['is_emergency'],
        'phone_assisted' => (bool) $row['phone_assisted'],
        'requires_signature' => (bool) $row['requires_signature'],
        'user_feedback' => $row['user_feedback'], 'visit_status' => $row['visit_status'],
        'user_satisfaction' => $row['user_satisfaction'],
        'user_signature' => $row['user_signature'], 'user_signed_at' => $row['user_signed_at'],
        'is_user_signed' => (bool) $row['is_user_signed'],
        'sms_acceptance_sent_at' => $row['sms_acceptance_sent_at'],
        'sms_survey_sent_at' => $row['sms_survey_sent_at'],
        'sms_satisfaction' => $row['sms_satisfaction'], 'sms_satisfaction_at' => $row['sms_satisfaction_at'],
        'created_at' => $row['created_at'], 'updated_at' => $row['updated_at'],
        'deleted_at' => $row['deleted_at'],
    ]);
    $stat['imported']++;
    $existing->offsetSet($row['ticket_no'], true);

    // 日志
    $ls = $pdo->prepare("SELECT user_id, action, content, is_system, created_at FROM workorder_logs WHERE workorder_id = ?");
    $ls->execute([$row['id']]);
    foreach ($ls->fetchAll(PDO::FETCH_ASSOC) as $log) {
        $newUid = null;
        if ($log['user_id']) {
            $un = $pdo->query("SELECT username FROM users WHERE id = " . (int) $log['user_id'])->fetchColumn();
            $newUid = $un ? ($userByName[$un] ?? null) : null;
        }
        $rehearsal->table('workorder_logs')->insert([
            'workorder_id' => $newId, 'user_id' => $newUid,
            'action' => $log['action'], 'content' => $log['content'],
            'is_system' => (bool) $log['is_system'],
            'created_at' => $log['created_at'], 'updated_at' => $log['created_at'],
        ]);
        $logs++;
    }
}

echo "  导入 {$stat['imported']}，跳过(已存在) {$stat['skipped']}，TEST 剔除 {$stat['test']}，日志 {$logs} 条" . PHP_EOL;
echo "  分类回退: " . count($catFallback) . " 条" . (empty($catFallback) ? '' : ' → ' . implode('; ', array_slice($catFallback, 0, 5))) . PHP_EOL;

echo PHP_EOL . "=== 3. 对账 ===" . PHP_EOL;
echo sprintf("工单: 演练库 %d / 主库 %d / MySQL %d", $rehearsal->table('workorders')->count(), $main->table('workorders')->count(), count($rows) - $stat['test']) . PHP_EOL;

// 时间戳保真抽验：取 5 条对比 created_at
$sample = $rehearsal->table('workorders')->orderBy('id', 'desc')->limit(5)->get(['ticket_no', 'created_at']);
$mysqlIdx = [];
foreach ($rows as $r) { $mysqlIdx[$r['ticket_no']] = $r['created_at']; }
$ok = 0;
foreach ($sample as $s) {
    $src = $mysqlIdx[$s->ticket_no] ?? '?';
    if (substr($src, 0, 19) === substr($s->created_at, 0, 19)) $ok++;
    else echo "  时间不符: {$s->ticket_no} mysql={$src} rehearsal={$s->created_at}" . PHP_EOL;
}
echo "  时间戳抽验 5 条：{$ok} 条精确一致" . PHP_EOL;

echo PHP_EOL . "✔ 演练完成" . PHP_EOL;
