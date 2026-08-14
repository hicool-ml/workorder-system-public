<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('workorder_templates', 'fields')) {
            Schema::table('workorder_templates', function (Blueprint $table) {
                $table->json('fields')->nullable()->default('[]')->after('name');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('workorder_templates', 'fields')) {
            Schema::table('workorder_templates', function (Blueprint $table) {
                $table->dropColumn('fields');
            });
        }
    }
};
