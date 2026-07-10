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