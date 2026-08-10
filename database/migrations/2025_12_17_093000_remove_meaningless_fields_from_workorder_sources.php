<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('workorder_sources', function (Blueprint $table) {
            if (DB::getDriverName() !== 'sqlite') {
                // SQLite dropColumn 不需要先删索引
                $table->dropUnique('workorder_sources_code_unique');
            }
            if (Schema::hasColumn('workorder_sources', 'code')) {
                $table->dropColumn('code');
            }
            if (Schema::hasColumn('workorder_sources', 'description')) {
                $table->dropColumn('description');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('workorder_sources', function (Blueprint $table) {
            $table->string('code', 50)->nullable()->comment('来源代码');
            $table->string('description')->nullable()->comment('来源描述');
        });
    }
};