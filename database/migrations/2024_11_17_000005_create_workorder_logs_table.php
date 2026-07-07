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
        Schema::create('workorder_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('workorder_id')->comment('工单ID');
            $table->unsignedBigInteger('user_id')->comment('操作人ID');
            $table->enum('action', [
                'created',      // 创建工单
                'assigned',     // 分配工单
                'accepted',     // 接单
                'started',      // 开始处理
                'paused',       // 暂停处理
                'resumed',      // 恢复处理
                'transferred',  // 转派
                'resolved',     // 已解决
                'rejected',     // 拒绝处理
                'closed',       // 关闭工单
                'reopened',     // 重新打开
                'comment'       // 添加备注
            ])->comment('操作类型');
            
            $table->text('content')->nullable()->comment('操作内容/备注');
            $table->text('old_value')->nullable()->comment('原值（用于状态变更）');
            $table->text('new_value')->nullable()->comment('新值（用于状态变更）');
            $table->datetime('processing_time')->nullable()->comment('处理耗时（分钟）');
            $table->boolean('is_system')->default(false)->comment('是否系统自动操作');
            
            $table->timestamps();
            
            // 外键约束
            $table->foreign('workorder_id')->references('id')->on('workorders')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('restrict');
            
            // 索引
            $table->index(['workorder_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index(['action', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workorder_logs');
    }
};