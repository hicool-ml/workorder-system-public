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
        Schema::create('campuses', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->comment('校区名称');
            $table->string('code', 50)->unique()->comment('校区代码');
            $table->text('description')->nullable()->comment('校区描述');
            $table->string('address')->nullable()->comment('校区地址');
            $table->string('contact_phone')->nullable()->comment('联系电话');
            $table->string('contact_person')->nullable()->comment('联系人');
            $table->integer('sort_order')->default(0)->comment('排序顺序');
            $table->string('status', 20)->default('active')->comment('状态');
            $table->timestamps();
            
            $table->index('status');
            $table->index('sort_order');
        });

       // 插入默认校区数据
        // 通用工单系统默认园区数据（可在管理后台自行增删）
        DB::table('campuses')->insert([
            [
                'name' => '总部园区',
                'code' => 'hq',
                'description' => '总部办公园区',
                'sort_order' => 1,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campuses');
    }
};
