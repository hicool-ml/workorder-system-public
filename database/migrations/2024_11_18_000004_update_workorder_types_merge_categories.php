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
            // 添加工单来源选项
            $table->json('source_options')->nullable()->after('color');
            // 添加默认工单编号前缀
            $table->string('default_ticket_prefix', 5)->default('WO')->after('source_options');
            // 添加是否允许用户选择
            $table->boolean('allow_user_select')->default(true)->after('default_ticket_prefix');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('workorder_types', function (Blueprint $table) {
            $table->dropColumn(['source_options', 'default_ticket_prefix', 'allow_user_select']);
        });
    }
};