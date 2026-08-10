<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 为用户表新增 oidc_sub 字段，用于关联 OIDC 统一身份认证的用户标识。
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('oidc_sub', 255)->nullable()->after('feishu_user_id');
            $table->index('oidc_sub');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['oidc_sub']);
            $table->dropColumn('oidc_sub');
        });
    }
};
