<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 获取校区映射关系
        $campusMapping = [
            'old_campus' => 1,  // 老校区
            'new_campus' => 2,  // 新校区
            'asean_campus' => 3, // 东盟校区
        ];
        
        // 更新工单表中的校区数据
        DB::table('workorders')
            ->whereNotNull('campus')
            ->where(function ($query) use ($campusMapping) {
                $query->whereIn('campus', array_keys($campusMapping));
            })
            ->update([
                'campus_id' => DB::raw('CASE campus 
                    WHEN "old_campus" THEN ' . $campusMapping['old_campus'] . '
                    WHEN "new_campus" THEN ' . $campusMapping['new_campus'] . '
                    WHEN "asean_campus" THEN ' . $campusMapping['asean_campus'] . '
                    ELSE NULL
                END')
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 回滚操作：将campus_id设置回NULL
        DB::table('workorders')
            ->whereNotNull('campus_id')
            ->update(['campus_id' => null]);
    }
};