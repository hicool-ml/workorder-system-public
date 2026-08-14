<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('workorder_templates', 'category_main_id')) {
            Schema::table('workorder_templates', function (Blueprint $table) {
                $table->unsignedBigInteger('category_main_id')->nullable()->after('fields');
                $table->index('category_main_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('workorder_templates', 'category_main_id')) {
            Schema::table('workorder_templates', function (Blueprint $table) {
                $table->dropIndex(['category_main_id']);
                $table->dropColumn('category_main_id');
            });
        }
    }
};
