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
            $table->string('title')->nullable()->change();
            $table->unsignedBigInteger('type_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('workorders', function (Blueprint $table) {
            $table->string('title')->nullable(false)->change();
            $table->unsignedBigInteger('type_id')->nullable(false)->change();
        });
    }
};
