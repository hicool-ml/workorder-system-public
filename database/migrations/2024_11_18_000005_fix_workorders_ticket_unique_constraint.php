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
            // 删除现有的唯一约束
            $table->dropUnique('workorders_ticket_no_unique');
            
            // 添加复合唯一约束
            $table->unique(['ticket_prefix', 'ticket_no'], 'workorders_ticket_prefix_ticket_no_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('workorders', function (Blueprint $table) {
            // 删除复合唯一约束
            $table->dropUnique('workorders_ticket_prefix_ticket_no_unique');
            
            // 恢复原来的唯一约束
            $table->unique('ticket_no', 'workorders_ticket_no_unique');
        });
    }
};