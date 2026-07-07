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
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique()->comment('设置键名');
            $table->text('value')->nullable()->comment('设置值');
            $table->string('type')->default('string')->comment('数据类型：string, boolean, integer, json');
            $table->string('description')->nullable()->comment('设置描述');
            $table->boolean('is_public')->default(false)->comment('是否为公开设置（前端可访问）');
            $table->timestamps();
            
            $table->index('key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};
