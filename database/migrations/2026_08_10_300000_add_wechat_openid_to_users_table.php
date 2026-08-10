<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 为用户表新增 wechat_openid 字段，用于关联微信公众号 OAuth 的微信用户标识（openid）。
     * openid 按 appid 唯一，同一用户换绑定/解绑时会覆盖。
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('wechat_openid', 128)->nullable()->after('oidc_sub')->unique();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['wechat_openid']);
            $table->dropColumn('wechat_openid');
        });
    }
};
