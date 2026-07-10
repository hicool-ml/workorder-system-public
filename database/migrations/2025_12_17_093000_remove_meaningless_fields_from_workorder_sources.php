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
        Schema::table('workorder_sources', function (Blueprint $table) {
            // 删除无意义的代码和描述字段
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
            // 恢复代码和描述字段（如果需要回滚）
            $table->string('code', 50)->nullable()->comment('来源代码，用于程序识别');
            $table->string('description')->nullable()->comment('来源描述');
        });
    }
};