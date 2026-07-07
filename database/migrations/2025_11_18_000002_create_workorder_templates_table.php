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
        Schema::create('workorder_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name', 200); // 模板名称
            $table->text('description'); // 工单描述模板
            $table->foreignId('category_id')->nullable()->constrained('workorder_categories_simplified')->nullOnDelete(); // 工单分类
            $table->string('contact_name', 100)->nullable(); // 联系人姓名
            $table->string('contact_phone', 20)->nullable(); // 联系人电话
            $table->string('contact_email', 100)->nullable(); // 联系人邮箱
            $table->string('campus', 50)->nullable(); // 校区
            $table->string('building', 200)->nullable(); // 楼栋
            $table->text('location_detail')->nullable(); // 位置详情
            $table->integer('time_limit_hours')->nullable(); // 时限（小时）
            $table->string('priority', 20)->default('medium'); // 优先级
            $table->string('source', 20)->default('web'); // 来源
            $table->string('department_name', 100)->nullable(); // 部门名称
            $table->boolean('need_visit')->default(false); // 是否需要回访
            $table->boolean('is_emergency')->default(false); // 是否紧急
            $table->boolean('phone_assisted')->default(false); // 电话协助
            $table->text('other_reason')->nullable(); // 其他原因
            $table->boolean('is_active')->default(true); // 是否启用
            $table->foreignId('creator_id')->constrained('users')->onDelete('cascade'); // 创建人
            $table->timestamps();
            
            // 索引
            $table->index(['is_active', 'name']);
            $table->index('creator_id');
            $table->index('category_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workorder_templates');
    }
};