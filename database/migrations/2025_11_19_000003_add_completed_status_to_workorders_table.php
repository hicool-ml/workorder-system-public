<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 扩展工单状态枚举（加入 completed）。
     * MySQL 走 ->change()；PG 不支持该内联 check 语法，改用原生 ALTER。
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            $statuses = "'pending','assigned','processing','resolved','completed','verifying','closed','rejected'";
            // 删除旧 check 约束（若存在）再改类型，兼容任何历史版本
            DB::statement("ALTER TABLE workorders DROP CONSTRAINT IF EXISTS workorders_status_check");
            DB::statement("ALTER TABLE workorders ALTER COLUMN status TYPE varchar(20)");
            DB::statement("ALTER TABLE workorders ADD CONSTRAINT workorders_status_check CHECK (status IN ({$statuses}))");
            return;
        }

        Schema::table('workorders', function (Blueprint $table) {
            $table->enum('status', ['pending', 'assigned', 'processing', 'resolved', 'completed', 'verifying', 'closed', 'rejected'])->change();
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            $statuses = "'pending','assigned','processing','resolved','verifying','closed','rejected'";
            DB::statement("ALTER TABLE workorders DROP CONSTRAINT IF EXISTS workorders_status_check");
            DB::statement("ALTER TABLE workorders ALTER COLUMN status TYPE varchar(20)");
            DB::statement("ALTER TABLE workorders ADD CONSTRAINT workorders_status_check CHECK (status IN ({$statuses}))");
            return;
        }

        Schema::table('workorders', function (Blueprint $table) {
            $table->enum('status', ['pending', 'assigned', 'processing', 'resolved', 'verifying', 'closed', 'rejected'])->change();
        });
    }
};
