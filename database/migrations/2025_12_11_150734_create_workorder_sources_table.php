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
        Schema::create('workorder_sources', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique()->comment('来源名称');
            $table->string('code', 50)->unique()->comment('来源代码，用于程序识别');
            $table->string('description')->nullable()->comment('来源描述');
            $table->boolean('is_active')->default(true)->comment('是否启用');
            $table->integer('sort_order')->default(0)->comment('排序顺序');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workorder_sources');
    }
};
