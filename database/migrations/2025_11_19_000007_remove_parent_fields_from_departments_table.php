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
        Schema::table('departments', function (Blueprint $table) {
            if (DB::getDriverName() !== 'sqlite') {
                $table->dropForeign(['parent_id']);
            }
            $table->dropIndex(['parent_id', 'status']);
            $table->dropColumn(['parent_id', 'level']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->unsignedBigInteger('parent_id')->nullable()->comment('上级部门ID');
            $table->integer('level')->default(1)->comment('部门层级');
            $table->index(['parent_id', 'status']);

            if (DB::getDriverName() !== 'sqlite') {
                $table->foreign('parent_id')->references('id')->on('departments')->onDelete('cascade');
            }
        });
    }
};