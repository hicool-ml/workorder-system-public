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
        Schema::table('departments', function (Blueprint $table) {
            // 鍒犻櫎涓婄骇閮ㄩ棬鐩稿叧瀛楁
            $table->dropIndex(['parent_id', 'status']);
            $table->dropColumn('parent_id');
            $table->dropColumn('level');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->unsignedBigInteger('parent_id')->nullable()->comment('涓婄骇閮ㄩ棬ID');
            $table->integer('level')->default(1)->comment('閮ㄩ棬灞傜骇');
            
            $table->foreign('parent_id')->references('id')->on('departments')->onDelete('cascade');
            $table->index(['parent_id', 'status']);
        });
    }
};
