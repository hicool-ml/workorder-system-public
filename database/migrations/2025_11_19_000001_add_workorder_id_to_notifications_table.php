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
        Schema::table('notifications', function (Blueprint $table) {
            // 只有当字段不存在时才添加
            if (!Schema::hasColumn('notifications', 'workorder_id')) {
                $table->foreignId('workorder_id')->nullable()->after('user_id')->constrained()->onDelete('cascade');
            }
            
            // 只有当索引不存在时才添加
            if (!Schema::hasIndex('notifications', 'notifications_workorder_id_type_index')) {
                $table->index(['workorder_id', 'type']);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            // 只有当外键存在时才删除
            if (Schema::hasColumn('notifications', 'workorder_id')) {
                $table->dropForeign(['workorder_id']);
                $table->dropColumn('workorder_id');
            }
            
            // 只有当索引存在时才删除
            if (Schema::hasIndex('notifications', 'notifications_workorder_id_type_index')) {
                $table->dropIndex(['workorder_id', 'type']);
            }
        });
    }
};