<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 给工单模板表补充 campus_id 列。
 *
 * 建表迁移(2025_11_18_000002)只建了旧的 campus(varchar) 列，
 * 新版 Model/表单/insert_default_workorder_template 迁移均使用 campus_id，
 * 该列在历史中从未通过迁移建立，属于 schema 漂移。此迁移补齐该列。
 */
return new class extends Migration
{
    public function up(): void
    {
 if (!Schema::hasColumn('workorder_templates', 'campus_id')) {
 Schema::table('workorder_templates', function (Blueprint $table) {
 $table->unsignedBigInteger('campus_id')->nullable()->after('campus')->comment('校区ID');
 $table->index('campus_id');
 });
 }
    }

    public function down(): void
    {
 if (Schema::hasColumn('workorder_templates', 'campus_id')) {
 Schema::table('workorder_templates', function (Blueprint $table) {
 $table->dropIndex(['campus_id']);
 $table->dropColumn('campus_id');
 });
 }
    }
};
