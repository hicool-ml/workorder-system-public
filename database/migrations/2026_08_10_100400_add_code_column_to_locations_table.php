<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('locations', 'code')) {
            Schema::table('locations', function (Blueprint $table) {
                $table->string('code', 50)->nullable()->after('name')->comment('地址编码');
                $table->index('code');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('locations', 'code')) {
            Schema::table('locations', function (Blueprint $table) {
                $table->dropIndex(['code']);
                $table->dropColumn('code');
            });
        }
    }
};
