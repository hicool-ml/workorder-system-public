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
        Schema::create('workorder_sources', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->comment('来源名称');
            $table->string('code', 30)->unique()->comment('来源代码');
            $table->string('description', 200)->nullable()->comment('来源描述');
            $table->integer('sort_order')->default(0)->comment('排序');
            $table->string('status', 20)->default('active')->comment('状态：active/inactive');
            $table->timestamps();
        });

        // 插入默认来源数据
        $sources = [
            ['name' => '电话', 'code' => 'phone', 'sort_order' => 1],
            ['name' => '网络', 'code' => 'web', 'sort_order' => 2],
            ['name' => '现场', 'code' => 'scene', 'sort_order' => 3],
            ['name' => '邮件', 'code' => 'email', 'sort_order' => 4],
            ['name' => '其他', 'code' => 'other', 'sort_order' => 5],
        ];

        foreach ($sources as $source) {
            \DB::table('workorder_sources')->insert(array_merge($source, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workorder_sources');
    }
};
