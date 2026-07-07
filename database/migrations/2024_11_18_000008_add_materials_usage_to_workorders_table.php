<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('workorders', function (Blueprint $table) {
            $table->text('materials_usage')->nullable()->after('remarks')->comment('备件耗材使用情况');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('workorders', function (Blueprint $table) {
            $table->dropColumn('materials_usage');
        });
    }
};