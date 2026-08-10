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
        // 注意：code / manager_name / manager_phone / location 以及 timestamps 仍被
        // DepartmentController（unique 校验、搜索）、Department 模型与 seeder 广泛使用，
        // 删除这些列会导致部门增删改查全部失效并破坏 Eloquent 时间戳。故该迁移改为空操作。
        return;

       Schema::table('departments', function (Blueprint $table) {
           // 删除无意义字段
           $table->dropColumn('code'); // 部门编码
            $table->dropColumn('manager_name'); // 负责人
            $table->dropColumn('manager_phone'); // 联系电话
            $table->dropColumn('location'); // 办公地点
            $table->dropColumn('created_at'); // 创建时间
            $table->dropColumn('updated_at'); // 更新时间
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            // 恢复字段
            $table->string('code')->unique()->after('name')->comment('部门编码');
            $table->string('manager_name')->nullable()->after('parent_id')->comment('部门负责人');
            $table->string('manager_phone')->nullable()->after('manager_name')->comment('负责人电话');
            $table->string('location')->nullable()->after('manager_phone')->comment('办公地点');
            $table->timestamps();
        });
    }
};
