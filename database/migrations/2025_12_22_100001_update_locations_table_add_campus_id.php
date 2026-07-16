<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->unsignedBigInteger('campus_id')->nullable()->after('id')->comment('校区ID');
            $table->index('campus_id');
        });

        if (DB::getDriverName() === 'sqlite') {
            $campuses = DB::table('campuses')->pluck('id', 'code');
            foreach ($campuses as $code => $id) {
                DB::table('locations')->where('campus', $code)->update(['campus_id' => $id]);
            }
        } else {
            DB::statement('UPDATE locations l JOIN campuses c ON l.campus = c.code SET l.campus_id = c.id WHERE l.campus IS NOT NULL AND l.campus != ""');
        }

        if (DB::getDriverName() !== 'sqlite') {
            Schema::table('locations', function (Blueprint $table) {
                $table->foreign('campus_id')->references('id')->on('campuses')->onDelete('cascade');
            });
        }

        // SQLite 下必须先删引用 campus 的复合索引，再删列
        if (DB::getDriverName() === 'sqlite') {
            DB::statement('DROP INDEX IF EXISTS locations_campus_building_type_index');
        }
        Schema::table('locations', function (Blueprint $table) {
            $table->dropColumn('campus');
        });
    }

    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->string('campus')->nullable()->comment('校区');
        });

        DB::table('locations')->whereNotNull('campus_id')->update(['campus' => DB::raw('campus_id')]);

        if (DB::getDriverName() !== 'sqlite') {
            Schema::table('locations', function (Blueprint $table) {
                $table->dropForeign(['campus_id']);
            });
        }
        Schema::table('locations', function (Blueprint $table) {
            $table->dropIndex(['campus_id']);
            $table->dropColumn('campus_id');
        });
    }
};