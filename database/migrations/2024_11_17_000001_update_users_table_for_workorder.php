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
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'phone')) {
                $table->string('phone')->nullable()->after('email')->comment('联系电话');
            }
            
            if (!Schema::hasColumn('users', 'employee_id')) {
                $table->string('employee_id')->nullable()->after('phone')->comment('员工编号');
            }
            
            if (!Schema::hasColumn('users', 'department_id')) {
                $table->unsignedBigInteger('department_id')->nullable()->after('employee_id')->comment('部门ID');
                // $table->foreign('department_id')->references('id')->on('departments')->onDelete('set null');
            }
            
            if (!Schema::hasColumn('users', 'role')) {
                $table->enum('role', ['admin', 'engineer', 'user'])->default('user')->after('department_id')->comment('角色：管理员、工程师、普通用户');
            }
            
            if (!Schema::hasColumn('users', 'status')) {
                $table->enum('status', ['active', 'inactive'])->default('active')->after('role')->comment('状态');
            }
            
            if (!Schema::hasColumn('users', 'location')) {
                $table->string('location')->nullable()->after('status')->comment('办公地点');
            }
            
            if (!Schema::hasColumn('users', 'remarks')) {
                $table->text('remarks')->nullable()->after('location')->comment('备注');
            }
            
            $table->index(['role', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
            $table->dropIndex(['role', 'status']);
            $table->dropColumn([
                'phone', 
                'employee_id', 
                'department_id', 
                'role', 
                'status', 
                'location', 
                'remarks'
            ]);
        });
    }
};