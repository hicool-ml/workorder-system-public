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
        Schema::create('workorder_attachments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('workorder_id')->comment('工单ID');
            $table->unsignedBigInteger('user_id')->comment('上传人ID');
            $table->string('filename')->comment('文件名');
            $table->string('original_name')->comment('原始文件名');
            $table->string('file_path')->comment('文件路径');
            $table->string('file_type')->comment('文件类型');
            $table->bigInteger('file_size')->comment('文件大小（字节）');
            $table->string('mime_type')->nullable()->comment('MIME类型');
            $table->text('description')->nullable()->comment('文件描述');
            $table->enum('type', ['image', 'document', 'video', 'audio', 'other'])->default('other')->comment('附件类型');
            $table->boolean('is_public')->default(true)->comment('是否公开（用户可见）');
            
            $table->timestamps();
            
            // 外键约束
            $table->foreign('workorder_id')->references('id')->on('workorders')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('restrict');
            
            // 索引
            $table->index(['workorder_id', 'type']);
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workorder_attachments');
    }
};