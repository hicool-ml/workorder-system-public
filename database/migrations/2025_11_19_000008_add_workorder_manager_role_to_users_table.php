<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // 更新role字段，添加workorder_manager选项
            $table->enum('role', ['admin', 'workorder_manager', 'engineer', 'user'])->default('user')->change()->comment('角色：管理员、工单管理员、工程师、普通用户');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // 恢复原有的role字段定义
            $table->enum('role', ['admin', 'engineer', 'user'])->default('user')->change()->comment('角色：管理员、工程师、普通用户');
        });
    }
};