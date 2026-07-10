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
        Schema::create('deleted_users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('original_user_id')->unique()->comment('原用户ID');
            $table->string('name')->comment('用户姓名');
            $table->string('email')->comment('邮箱');
            $table->string('username')->nullable()->comment('用户名');
            $table->string('phone')->nullable()->comment('电话');
            $table->string('employee_id')->nullable()->comment('员工编号');
            $table->unsignedBigInteger('department_id')->nullable()->comment('部门ID');
            $table->string('role')->comment('角色');
            $table->string('status')->comment('状态');
            $table->string('location')->nullable()->comment('位置');
            $table->text('remarks')->nullable()->comment('备注');
            $table->string('account_type')->nullable()->comment('账户类型');
            $table->text('delete_reason')->nullable()->comment('删除原因');
            $table->unsignedBigInteger('deleted_by')->nullable()->comment('删除操作人');
            $table->timestamp('deleted_at')->useCurrent()->comment('删除时间');
            $table->timestamps();
            
            // 索引
            $table->index('original_user_id');
            $table->index('deleted_at');
            $table->index('deleted_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deleted_users');
    }
};
