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
            $table->enum('status', ['pending', 'assigned', 'processing', 'resolved', 'completed', 'verifying', 'closed', 'rejected'])->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('workorders', function (Blueprint $table) {
            $table->enum('status', ['pending', 'assigned', 'processing', 'resolved', 'verifying', 'closed', 'rejected'])->change();
        });
    }
};