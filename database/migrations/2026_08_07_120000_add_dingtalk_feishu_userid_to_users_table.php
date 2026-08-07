<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 为用户表新增钉钉 userid、飞书 user_id 两个可空字段，
     * 用于 @ 提醒（参照 wecom_userid）。纯增量，不动存量数据。
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('dingtalk_userid', 100)->nullable()->after('wecom_userid');
            $table->string('feishu_user_id', 100)->nullable()->after('dingtalk_userid');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['dingtalk_userid', 'feishu_user_id']);
        });
    }
};
