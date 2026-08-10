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
        // 幂等保护：campus_id 可能已由 2025_12_22_100003 数据迁移先行创建
        if (Schema::hasColumn('workorders', 'campus_id')) {
            return;
        }

       Schema::table('workorders', function (Blueprint $table) {
            $table->unsignedBigInteger('campus_id')->nullable()->after('campus')->comment('校区ID');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('workorders', function (Blueprint $table) {
            $table->dropColumn('campus_id');
        });
    }
};
