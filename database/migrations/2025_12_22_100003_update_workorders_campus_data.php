<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * 注意：本迁移早于 2025_12_22_100004（添加 campus_id 列），因此这里需要先确保
     * campus_id 列存在，再回填历史数据，避免在全新部署时因列不存在而失败。
     */
    public function up(): void
    {
        if (!Schema::hasColumn('workorders', 'campus_id')) {
            Schema::table('workorders', function (Blueprint $table) {
                $table->unsignedBigInteger('campus_id')->nullable()->after('campus')->comment('校区ID');
            });
        }

        // 将已有工单的 campus 文本映射到 campus_id（一次性数据迁移）。
        $campusMapping = [
            'old_campus' => 1,    // 老校区
            'new_campus' => 2,    // 新校区
            'asean_campus' => 3,  // 东盟校区
        ];

        $keys = array_keys($campusMapping);

        DB::table('workorders')
            ->whereNotNull('campus')
            ->whereIn('campus', $keys)
            ->update([
                'campus_id' => DB::raw(sprintf(
                    "CASE campus %s ELSE NULL END",
                    implode(' ', array_map(function ($key) use ($campusMapping) {
                        return sprintf("WHEN '%s' THEN %d", $key, $campusMapping[$key]);
                    }, $keys))
                )),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('workorders')
            ->whereNotNull('campus_id')
            ->update(['campus_id' => null]);
    }
};
