<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * parent_id 转 bigint + 外键。
     * PG 的 ->change() 缺 USING 子句无法自动转类型，走原生 ALTER。
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            // 清洗脏数据（历史遗留的非数字 parent_id）
            DB::statement("UPDATE workorder_categories_simplified SET parent_id = NULL WHERE parent_id !~ '^[0-9]+$'");
            DB::statement("ALTER TABLE workorder_categories_simplified ALTER COLUMN parent_id TYPE bigint USING parent_id::bigint");
            DB::statement("ALTER TABLE workorder_categories_simplified DROP CONSTRAINT IF EXISTS wocs_parent_fk");
            DB::statement("ALTER TABLE workorder_categories_simplified ADD CONSTRAINT wocs_parent_fk FOREIGN KEY (parent_id) REFERENCES workorder_categories_simplified(id) ON DELETE CASCADE");
            return;
        }

        Schema::table('workorder_categories_simplified', function (Blueprint $table) {
            $table->unsignedBigInteger('parent_id')->nullable()->change();
            $table->foreign('parent_id')->references('id')->on('workorder_categories_simplified')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE workorder_categories_simplified DROP CONSTRAINT IF EXISTS wocs_parent_fk");
            DB::statement("ALTER TABLE workorder_categories_simplified ALTER COLUMN parent_id TYPE varchar(255) USING parent_id::varchar");
            return;
        }

        Schema::table('workorder_categories_simplified', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->string('parent_id')->nullable()->change();
        });
    }
};
