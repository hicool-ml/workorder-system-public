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
        Schema::create('workorders', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_no')->unique()->comment('工单编号');
            $table->string('title')->nullable()->comment('工单标题');
            $table->text('description')->comment('问题描述');
            $table->unsignedBigInteger('type_id')->nullable()->comment('工单类型ID');
            $table->unsignedBigInteger('creator_id')->comment('创建人ID');
            $table->unsignedBigInteger('assignee_id')->nullable()->comment('处理人ID');
            $table->unsignedBigInteger('department_id')->nullable()->comment('部门ID');
            
            // 联系信息
            $table->string('contact_name')->comment('联系人姓名');
            $table->string('contact_phone')->comment('联系电话');
            $table->string('contact_email')->nullable()->comment('联系邮箱');
            $table->string('location')->comment('故障地点');
            $table->text('location_detail')->nullable()->comment('详细地址');
            
            // 工单属性
            $table->enum('source', ['phone', 'web', 'email', 'scene', 'other'])->default('web')->comment('工单来源');
            $table->enum('priority', ['high', 'medium', 'low'])->default('medium')->comment('优先级');
            $table->enum('status', [
                'pending',    // 待处理
                'assigned',   // 已分配
                'processing', // 处理中
                'resolved',   // 已解决
                'verifying',  // 待验证
                'closed',     // 已关闭
                'rejected'    // 已拒绝
            ])->default('pending')->comment('工单状态');
            
            // 时间管理
            $table->datetime('assigned_at')->nullable()->comment('分配时间');
            $table->datetime('started_at')->nullable()->comment('开始处理时间');
            $table->datetime('resolved_at')->nullable()->comment('解决时间');
            $table->datetime('closed_at')->nullable()->comment('关闭时间');
            $table->datetime('expected_complete_at')->nullable()->comment('预计完成时间');
            
            // 处理信息
            $table->text('solution')->nullable()->comment('解决方案');
            $table->text('remarks')->nullable()->comment('备注');
            $table->boolean('need_visit')->default(false)->comment('是否需要回访');
            $table->boolean('is_emergency')->default(false)->comment('是否紧急');
            
            $table->timestamps();
            $table->softDeletes();
            
            // 外键约束
            $table->foreign('type_id')->references('id')->on('workorder_types')->onDelete('restrict');
            $table->foreign('creator_id')->references('id')->on('users')->onDelete('restrict');
            $table->foreign('assignee_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('department_id')->references('id')->on('departments')->onDelete('set null');
            
            // 索引
            $table->index(['ticket_no']);
            $table->index(['status', 'priority']);
            $table->index(['creator_id', 'created_at']);
            $table->index(['assignee_id', 'status']);
            $table->index(['type_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workorders');
    }
};