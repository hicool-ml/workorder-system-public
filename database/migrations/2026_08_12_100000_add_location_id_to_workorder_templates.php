<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * workorder_templates 表加入 location_id 列，替代已废弃的 campus_id/building。
 * 旧列保留不动，不影响历史模板数据。
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('workorder_templates', 'location_id')) {
            Schema::table('workorder_templates', function (Blueprint $table) {
                $table->unsignedBigInteger('location_id')->nullable()->after('contact_email');
                $table->foreign('location_id')->references('id')->on('locations')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('workorder_templates', 'location_id')) {
            Schema::table('workorder_templates', function (Blueprint $table) {
                $table->dropForeign(['location_id']);
                $table->dropColumn('location_id');
            });
        }
    }
};
