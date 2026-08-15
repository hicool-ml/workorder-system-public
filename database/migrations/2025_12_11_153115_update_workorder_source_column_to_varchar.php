<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * workorders.source 从 ENUM 改 VARCHAR（支持动态来源）。
     * PG 不支持 ->change() 的内联 check 语法，走原生 ALTER。
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE workorders DROP CONSTRAINT IF EXISTS workorders_source_check");
            DB::statement("ALTER TABLE workorders ALTER COLUMN source TYPE varchar(50)");
            return;
        }

        Schema::table('workorders', function (Blueprint $table) {
            $table->string('source', 50)->nullable()->change();
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE workorders DROP CONSTRAINT IF EXISTS workorders_source_check");
            DB::statement("ALTER TABLE workorders ALTER COLUMN source TYPE varchar(10)");
            DB::statement("ALTER TABLE workorders ADD CONSTRAINT workorders_source_check CHECK (source IN ('phone','web','email','scene','other'))");
            return;
        }

        Schema::table('workorders', function (Blueprint $table) {
            $table->enum('source', ['phone', 'web', 'email', 'scene', 'other'])->nullable()->change();
        });
    }
};
