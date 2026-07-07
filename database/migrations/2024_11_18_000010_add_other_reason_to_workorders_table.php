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
            // 添加其他部门原因字段
            if (!Schema::hasColumn('workorders', 'other_reason')) {
                $table->text('other_reason')->nullable()->after('materials_usage')->comment('其他部门原因');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('workorders', function (Blueprint $table) {
            $table->dropColumn('other_reason');
        });
    }
};