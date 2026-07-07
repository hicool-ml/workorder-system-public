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
        Schema::create('workorder_visits', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('workorder_id')->comment('工单ID');
            $table->unsignedBigInteger('visitor_id')->comment('回访人ID');
            
            // 回访信息
            $table->enum('visit_method', ['phone', 'sms', 'email', 'online', 'scene'])->default('phone')->comment('回访方式');
            $table->datetime('visit_time')->comment('回访时间');
            $table->text('visit_content')->nullable()->comment('回访内容');
            $table->text('feedback')->nullable()->comment('用户反馈');
            
            // 满意度评价
            $table->integer('satisfaction_score')->nullable()->comment('满意度评分（1-5分）');
            $table->integer('response_speed_score')->nullable()->comment('响应速度评分（1-5分）');
            $table->integer('service_quality_score')->nullable()->comment('服务质量评分（1-5分）');
            $table->integer('professional_score')->nullable()->comment('专业水平评分（1-5分）');
            $table->text('suggestions')->nullable()->comment('改进建议');
            
            // 回访状态
            $table->enum('status', ['pending', 'completed', 'failed', 'skipped'])->default('pending')->comment('回访状态');
            $table->text('fail_reason')->nullable()->comment('回访失败原因');
            $table->boolean('need_follow_up')->default(false)->comment('是否需要后续跟进');
            $table->text('follow_up_note')->nullable()->comment('跟进说明');
            
            $table->timestamps();
            
            // 外键约束
            $table->foreign('workorder_id')->references('id')->on('workorders')->onDelete('cascade');
            $table->foreign('visitor_id')->references('id')->on('users')->onDelete('restrict');
            
            // 索引
            $table->index(['workorder_id']);
            $table->index(['visitor_id', 'visit_time']);
            $table->index(['status', 'visit_time']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workorder_visits');
    }
};