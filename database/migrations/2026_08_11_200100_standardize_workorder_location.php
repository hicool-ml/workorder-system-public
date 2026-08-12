<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 工单地址字段标准化迁移：
 *
 * 1. 数据迁移：把 workorders 现有 building 字段（数字 id / 文本楼名 混存）的值
 *    同步到标准 location_id 列。规则：
 *      a) location_id 已有值 → 跳过
 *      b) building 为数字 → 写入 location_id
 *      c) building 为文本楼名 → 沿 campus_id 找校区节点（locations.level=6 或
 *         通过 locations.campus_id 反查），在其下匹配同名 building 子节点；
 *         匹配不上则在校区节点下创建"未分类"节点收容。
 *      d) 既无 campus_id 又无 building → location_id 保持 NULL。
 *
 * 2. 删除冗余列：campus(text) / campus_id(FK) / building(text) / location(text)
 *    工单地址此后只通过 location_id + location_detail 描述。
 *
 * 3. 删除 Workorder 上的旧外键索引（campus_id）。
 *
 * 注：迁移完成后，workorder.campus_name / building_name 改为沿 location_id 父链解析。
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->migrateWorkorderLocations();
        $this->dropLegacyColumns();
    }

    public function down(): void
    {
        // 无法安全恢复原始 building 文本，down 只重建列结构
        if (! Schema::hasColumn('workorders', 'campus')) {
            Schema::table('workorders', function (Blueprint $table) {
                $table->string('campus', 100)->nullable()->after('location_id');
            });
        }
        if (! Schema::hasColumn('workorders', 'campus_id')) {
            Schema::table('workorders', function (Blueprint $table) {
                $table->unsignedBigInteger('campus_id')->nullable()->after('campus');
                $table->foreign('campus_id')->references('id')->on('campuses')->nullOnDelete();
            });
        }
        if (! Schema::hasColumn('workorders', 'building')) {
            Schema::table('workorders', function (Blueprint $table) {
                $table->string('building')->nullable()->after('campus_id');
            });
        }
        if (! Schema::hasColumn('workorders', 'location')) {
            Schema::table('workorders', function (Blueprint $table) {
                $table->string('location')->nullable()->after('building');
            });
        }
    }

    private function migrateWorkorderLocations(): void
    {
        $campusLevelId = DB::table('location_levels')->where('code', 'campus')->value('id');
        $buildingLevelId = DB::table('location_levels')->where('code', 'building')->value('id');

        // 拉所有 location_id 为空但 building 有值的行，PHP 端分类（保证跨数据库兼容）
        if (! Schema::hasColumn('workorders', 'building')) {
            return;
        }

        $rows = DB::table('workorders')
            ->whereNull('location_id')
            ->whereNotNull('building')
            ->where('building', '!=', '')
            ->get(['id', 'campus_id', 'building']);

        foreach ($rows as $row) {
            $building = (string) $row->building;
            $locationId = null;

            if (ctype_digit($building)) {
                // 数字 id：直接写入
                $locationId = (int) $building;
            } elseif ($campusLevelId && $buildingLevelId) {
                // 文本楼名：找/建未分类节点收容
                $locationId = $this->resolveOrCreateTextNode(
                    (int) ($row->campus_id ?? 0),
                    $building,
                    $campusLevelId,
                    $buildingLevelId
                );
            }

            if ($locationId) {
                DB::table('workorders')->where('id', $row->id)->update([
                    'location_id' => $locationId,
                ]);
            }
        }
    }

    /**
     * 按 campus_id 找校区节点（locations.campus_id 外键），再在其下匹配同名 building。
     * 找不到则在校区节点下创建"未分类"节点，把文本楼名作为它的子节点。
     * 没有 campus_id 时，在前缀根下找/创建"未分类"校区节点。
     */
    private function resolveOrCreateTextNode(int $campusId, string $buildingName, int $campusLevelId, int $buildingLevelId): ?int
    {
        // 找到校区节点
        $campusNode = null;
        if ($campusId) {
            $campusNode = DB::table('locations')
                ->where('level_id', $campusLevelId)
                ->where('campus_id', $campusId)
                ->first();
        }

        // 没有 campus_id 关联 → 尝试通过 workorder.campus 文本字段反查 campuses 表
        if (! $campusNode && $campusId) {
            // 兜底：直接找 level=6 的根节点中的"未分类"
            $campusNode = $this->ensureUncategorizedCampus($campusLevelId);
        } elseif (! $campusNode) {
            $campusNode = $this->ensureUncategorizedCampus($campusLevelId);
        }

        if (! $campusNode) {
            return null;
        }

        // 在校区节点下找同名 building
        $existing = DB::table('locations')
            ->where('parent_id', $campusNode->id)
            ->where('level_id', $buildingLevelId)
            ->where('name', $buildingName)
            ->first();

        if ($existing) {
            return $existing->id;
        }

        // 在校区下建"未分类"building 节点，把楼名作为子节点
        $uncategorized = DB::table('locations')
            ->where('parent_id', $campusNode->id)
            ->where('level_id', $buildingLevelId)
            ->where('name', '未分类')
            ->first();

        if (! $uncategorized) {
            $uncategorizedId = DB::table('locations')->insertGetId([
                'name' => '未分类',
                'code' => null,
                'parent_id' => $campusNode->id,
                'level_id' => $buildingLevelId,
                'campus_id' => $campusNode->campus_id ?? null,
                'sort_order' => 9999,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $uncategorizedId = $uncategorized->id;
        }

        // 在"未分类"下创建该文本楼名节点
        // 先去重：可能多条工单指向同一个文本楼名
        $leaf = DB::table('locations')
            ->where('parent_id', $uncategorizedId)
            ->where('name', $buildingName)
            ->first();

        if ($leaf) {
            return $leaf->id;
        }

        return DB::table('locations')->insertGetId([
            'name' => $buildingName,
            'code' => null,
            'parent_id' => $uncategorizedId,
            'level_id' => $buildingLevelId,
            'campus_id' => $campusNode->campus_id ?? null,
            'sort_order' => 0,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function ensureUncategorizedCampus(int $campusLevelId): ?object
    {
        // 取地址前缀根
        $prefixId = DB::table('system_settings')->where('key', 'address_prefix_location_id')->value('value');
        $prefixId = $prefixId ? (int) $prefixId : null;

        $uncategorized = DB::table('locations')
            ->when($prefixId, fn ($q) => $q->where('parent_id', $prefixId))
            ->where('level_id', $campusLevelId)
            ->where('name', '未分类校区')
            ->first();

        if ($uncategorized) {
            return $uncategorized;
        }

        if (! $prefixId) {
            return null; // 没有前缀根则放弃
        }

        $id = DB::table('locations')->insertGetId([
            'name' => '未分类校区',
            'code' => null,
            'parent_id' => $prefixId,
            'level_id' => $campusLevelId,
            'campus_id' => null,
            'sort_order' => 9999,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::table('locations')->where('id', $id)->first();
    }

    private function dropLegacyColumns(): void
    {
        // PG 在事务中某条 SQL 失败后整个事务会被 abort，try/catch 抓不住。
        // 因此先查清楚实际存在的外键/索引名，再用原始 SQL drop，避免触发异常。
        $driver = DB::getDriverName();

        // 1. 收集 campus_id 相关的外键约束名（如果有）
        $fkNames = [];
        if ($driver === 'pgsql') {
            $fkNames = DB::table('pg_constraint')
                ->where('contype', 'f')
                ->where('conrelid', DB::raw("'workorders'::regclass"))
                ->pluck('conname')
                ->filter(fn ($n) => str_contains($n, 'campus'))
                ->all();
        } elseif ($driver === 'mysql') {
            $rows = DB::select("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_NAME = 'workorders' AND COLUMN_NAME = 'campus_id' AND REFERENCED_TABLE_NAME IS NOT NULL");
            $fkNames = array_map(fn ($r) => $r->CONSTRAINT_NAME, $rows);
        }

        // 2. 逐个 drop 外键（每个独立 try，避免一个失败影响其它）
        foreach ($fkNames as $fkName) {
            try {
                DB::statement("ALTER TABLE workorders DROP CONSTRAINT {$fkName}");
            } catch (\Throwable $e) {
                // 单独 savepoint 也行，这里忽略；但若事务已 abort 需 rollback
                $this->safeRollback();
            }
        }

        // 3. 收集涉及 campus/building/location（非 location_id）的索引名
        // 注意：索引定义中 campus_id 是一个 word 字符（下划线），正则 \b 不会断在这里，
        // 因此不能用 \bcampus\b，而要用更直接的 strpos 检查
        $indexNames = [];
        if ($driver === 'pgsql') {
            // PG：通过 pg_indexes 拿索引定义 sql，匹配列名
            $rows = DB::table('pg_indexes')->where('tablename', 'workorders')->get(['indexname', 'indexdef']);
            foreach ($rows as $r) {
                $def = strtolower($r->indexdef);
                if (strpos($def, 'campus') !== false || strpos($def, 'building') !== false
                    || (strpos($def, 'location') !== false && strpos($def, 'location_id') === false)) {
                    $indexNames[] = $r->indexname;
                }
            }
        } elseif ($driver === 'mysql') {
            $rows = DB::select("SELECT INDEX_NAME, COLUMN_NAME FROM information_schema.statistics WHERE TABLE_NAME = 'workorders'");
            foreach ($rows as $r) {
                $col = strtolower($r->COLUMN_NAME);
                if ($col === 'campus' || $col === 'campus_id' || $col === 'building' || $col === 'location') {
                    $indexNames[] = $r->INDEX_NAME;
                }
            }
            $indexNames = array_unique($indexNames);
        } else {
            // SQLite / 其它：用 sqlite_master 查所有手动创建的索引
            $rows = DB::select("SELECT name, sql FROM sqlite_master WHERE type = 'index' AND tbl_name = 'workorders' AND sql IS NOT NULL");
            foreach ($rows as $r) {
                $def = strtolower($r->sql);
                // campus / campus_id / building / location（非 location_id）
                if (strpos($def, 'campus') !== false
                    || strpos($def, 'building') !== false
                    || (strpos($def, 'location') !== false && strpos($def, 'location_id') === false)) {
                    $indexNames[] = $r->name;
                }
            }
        }

        foreach ($indexNames as $idxName) {
            try {
                // PG / SQLite 用 DROP INDEX，MySQL 用 ALTER TABLE DROP INDEX
                if ($driver === 'mysql') {
                    DB::statement("ALTER TABLE workorders DROP INDEX `{$idxName}`");
                } else {
                    DB::statement("DROP INDEX IF EXISTS \"{$idxName}\"");
                }
            } catch (\Throwable $e) {
                $this->safeRollback();
            }
        }

        // 4. drop 4 个旧列
        Schema::table('workorders', function (Blueprint $table) {
            foreach (['campus', 'campus_id', 'building', 'location'] as $col) {
                if (Schema::hasColumn('workorders', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }

    /**
     * 如果当前 PDO 事务被 abort（PG），手动 rollback 一次让事务恢复可用状态
     */
    private function safeRollback(): void
    {
        try {
            DB::connection()->getPdo()->rollBack();
        } catch (\Throwable $ignored) {
            // 没有活跃事务，忽略
        }
    }
};
