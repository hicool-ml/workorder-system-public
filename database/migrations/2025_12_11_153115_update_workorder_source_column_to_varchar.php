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
            // 将source字段从ENUM改为VARCHAR，以支持动态来源
            $table->string('source', 50)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('workorders', function (Blueprint $table) {
            // 回滚：将source字段改回ENUM
            $table->enum('source', ['phone', 'web', 'email', 'scene', 'other'])->nullable()->change();
        });
    }
};
