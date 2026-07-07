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
        Schema::table('workorder_attachments', function (Blueprint $table) {
            if (!Schema::hasColumn('workorder_attachments', 'thumbnail_path')) {
                $table->string('thumbnail_path')->nullable()->after('is_public')->comment('缩略图路径');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('workorder_attachments', function (Blueprint $table) {
            if (Schema::hasColumn('workorder_attachments', 'thumbnail_path')) {
                $table->dropColumn('thumbnail_path');
            }
        });
    }
};