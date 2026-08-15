<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * users.role 加入 workorder_manager。
     * PG 不支持 ->change() 的内联 check 语法，走原生 ALTER。
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            $roles = "'admin','workorder_manager','engineer','user'";
            DB::statement("ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_check");
            DB::statement("ALTER TABLE users ALTER COLUMN role TYPE varchar(20)");
            DB::statement("ALTER TABLE users ADD CONSTRAINT users_role_check CHECK (role IN ({$roles}))");
            DB::statement("COMMENT ON COLUMN users.role IS '角色：管理员、工单管理员、工程师、普通用户'");
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['admin', 'workorder_manager', 'engineer', 'user'])->default('user')->change()->comment('角色：管理员、工单管理员、工程师、普通用户');
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            $roles = "'admin','engineer','user'";
            DB::statement("ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_check");
            DB::statement("ALTER TABLE users ALTER COLUMN role TYPE varchar(20)");
            DB::statement("ALTER TABLE users ADD CONSTRAINT users_role_check CHECK (role IN ({$roles}))");
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['admin', 'engineer', 'user'])->default('user')->change()->comment('角色：管理员、工单管理员、工程师、普通用户');
        });
    }
};
