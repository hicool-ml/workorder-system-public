<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 工单号序号表：原子自增，消除生成器并发竞态（PG 空结果集不加行锁）与序号回绕
        Schema::create('ticket_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('prefix', 20);
            $table->date('date');
            $table->unsignedInteger('seq')->default(0);
            $table->timestamps();

            $table->unique(['prefix', 'date']);
        });

        // ticket_no 唯一索引兜底（若历史数据已有重复则跳过，由运维清理）
        $dupCount = 0;
        try {
            $dupCount = DB::table('workorders')
                ->select('ticket_no')
                ->groupBy('ticket_no')
                ->havingRaw('COUNT(*) > 1')
                ->count();
        } catch (\Throwable $e) {
            // 表不存在（新装环境）等情况忽略
        }

        if ($dupCount === 0) {
            Schema::table('workorders', function (Blueprint $table) {
                $table->unique('ticket_no');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_sequences');
        // PG/MySQL 语法差异通过 Laravel 抽象处理
        Schema::table('workorders', function (Blueprint $table) {
            $table->dropUnique(['ticket_no']);
        });
    }
};
