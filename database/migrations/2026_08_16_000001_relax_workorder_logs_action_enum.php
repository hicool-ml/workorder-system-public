<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * workorder_logs.action 从 ENUM 放开为 varchar。
     *
     * 背景：建表迁移的枚举（created/assigned/.../comment）早已落后于实际使用——
     * 系统运行期写入的 action 还包括 materials_updated / phone_assisted / completed /
     * collaboration_* / attachment_* / rolled_back / status_fixed / status_reset /
     * signature_completed 等 10+ 种。主库当年手工改过，空库重跑会因 check 约束
     * 拒绝这些值（PG）；MySQL 侧虽可写入但改枚举同样繁琐。日志表 action 本就是
     * 开放词汇（展示用），放开为 varchar(50) 最合理。
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE workorder_logs DROP CONSTRAINT IF EXISTS workorder_logs_action_check');
            DB::statement('ALTER TABLE workorder_logs ALTER COLUMN action TYPE varchar(50)');
            return;
        }
        DB::statement("ALTER TABLE workorder_logs MODIFY action VARCHAR(50) NOT NULL");
    }

    public function down(): void
    {
        // 不回滚收紧：历史数据含枚举外的 action，恢复约束会失败
    }
};
