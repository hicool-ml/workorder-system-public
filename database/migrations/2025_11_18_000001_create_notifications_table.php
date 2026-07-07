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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('workorder_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('type', 50); // 通知类型
            $table->string('title', 200); // 通知标题
            $table->text('content'); // 通知内容
            $table->json('data')->nullable(); // 额外数据（JSON格式）
            $table->boolean('is_read')->default(false); // 是否已读
            $table->timestamp('read_at')->nullable(); // 阅读时间
            $table->boolean('is_important')->default(false); // 是否重要通知
            $table->timestamps();
            
            // 索引
            $table->index(['user_id', 'is_read']);
            $table->index(['user_id', 'type']);
            $table->index(['user_id', 'created_at']);
            $table->index(['workorder_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};