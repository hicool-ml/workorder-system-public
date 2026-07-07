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
        Schema::table('workorders', function (Blueprint $table) {
            // 检查字段是否存在，不存在则添加
            if (!Schema::hasColumn('workorders', 'time_limit_hours')) {
                $table->integer('time_limit_hours')->nullable()->after('source');
            }
            // 检查字段是否存在，不存在则添加
            if (!Schema::hasColumn('workorders', 'ticket_prefix')) {
                $table->string('ticket_prefix', 5)->default('WO')->after('id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('workorders', function (Blueprint $table) {
            $table->dropColumn(['appointment_time', 'source', 'time_limit_hours', 'ticket_prefix']);
            $table->datetime('created_at')->nullable(false)->change();
        });
    }
};