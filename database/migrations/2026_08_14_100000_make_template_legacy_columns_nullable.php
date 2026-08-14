<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * workorder_templates 表旧列改为 nullable：
 * 新模板系统使用 fields JSON，不再写入 description / category_id 等固定列。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workorder_templates', function (Blueprint $table) {
            $table->text('description')->nullable()->default(null)->change();
            $table->unsignedBigInteger('category_id')->nullable()->default(null)->change();
            $table->string('priority', 20)->nullable()->default(null)->change();
            $table->string('source', 50)->nullable()->default(null)->change();
        });
    }

    public function down(): void
    {
        // 不可逆（旧数据可能已有 null）
    }
};
