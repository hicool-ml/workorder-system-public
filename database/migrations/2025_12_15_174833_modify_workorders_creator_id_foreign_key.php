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
        if (DB::getDriverName() !== 'sqlite') {
            Schema::table('workorders', function (Blueprint $table) {
                $table->dropForeign('workorders_creator_id_foreign');
                $table->foreign('creator_id')
                      ->references('id')
                      ->on('users')
                      ->onDelete('set null')
                      ->name('workorders_creator_id_foreign');
            });
        }

        // SQLite 修改 creator_id 允许为 NULL
        Schema::table('workorders', function (Blueprint $table) {
            $table->unsignedBigInteger('creator_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            Schema::table('workorders', function (Blueprint $table) {
                $table->dropForeign('workorders_creator_id_foreign');
                $table->foreign('creator_id')
                      ->references('id')
                      ->on('users')
                      ->onDelete('restrict')
                      ->name('workorders_creator_id_foreign');
            });
        }

        Schema::table('workorders', function (Blueprint $table) {
            $table->unsignedBigInteger('creator_id')->nullable(false)->change();
        });
    }
};