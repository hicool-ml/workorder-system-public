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
        Schema::table('workorder_types', function (Blueprint $table) {
            // 添加用户选择权限字段（决定哪些角色可以创建此类型工单）
            if (!Schema::hasColumn('workorder_types', 'allowed_roles')) {
                $table->json('allowed_roles')->nullable()->comment('允许创建此类型工单的角色');
            }
            
            // 添加索引
            $table->index(['status', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('workorder_types', function (Blueprint $table) {
            $table->dropIndex(['status', 'sort_order']);
            
            if (Schema::hasColumn('workorder_types', 'allowed_roles')) {
                $table->dropColumn('allowed_roles');
            }
        });
    }
};