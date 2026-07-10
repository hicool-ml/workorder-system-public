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
            $table->boolean('requires_signature')->default(false)->after('phone_assisted')->comment('是否需要签单');
            $table->text('user_feedback')->nullable()->after('requires_signature')->comment('用户反馈');
            $table->integer('user_satisfaction')->nullable()->after('user_feedback')->comment('用户满意度评分(1-5)');
            $table->text('user_signature')->nullable()->after('user_satisfaction')->comment('用户签名数据');
            $table->timestamp('user_signed_at')->nullable()->after('user_signature')->comment('用户签单时间');
            $table->boolean('is_user_signed')->default(false)->after('user_signed_at')->comment('是否已签单');
        });

        // 创建工单签单文档表
        Schema::create('workorder_signature_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workorder_id')->constrained()->cascadeOnDelete()->comment('工单ID');
            $table->string('filename')->comment('文件名');
            $table->string('file_path')->comment('文件路径');
            $table->string('file_type', 50)->default('application/pdf')->comment('文件类型');
            $table->integer('file_size')->comment('文件大小(字节)');
            $table->string('md5_hash', 32)->nullable()->comment('文件MD5哈希值');
            $table->timestamps();
            
            $table->index(['workorder_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('workorders', function (Blueprint $table) {
            $table->dropColumn([
                'requires_signature',
                'user_feedback',
                'user_satisfaction',
                'user_signature',
                'user_signed_at',
                'is_user_signed'
            ]);
        });

        Schema::dropIfExists('workorder_signature_documents');
    }
};