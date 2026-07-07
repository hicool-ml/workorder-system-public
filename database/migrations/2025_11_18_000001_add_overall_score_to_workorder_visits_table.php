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
        Schema::table('workorder_visits', function (Blueprint $table) {
            $table->integer('overall_score')->nullable()->comment('总体满意度评分（1-5分）')->after('professional_score');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('workorder_visits', function (Blueprint $table) {
            $table->dropColumn('overall_score');
        });
    }
};