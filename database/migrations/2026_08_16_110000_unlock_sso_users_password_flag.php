<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * 解锁存量 SSO 用户：CAS/OIDC 账号的本地密码是建号时的随机值，
     * 用户无从知晓；password_changed_at 为 null 会被 ForcePasswordChange
     * 中间件拦截并要求输入 current_password 改密 → 永久锁死。
     * SSO 用户身份由 IdP 管理，本地密码不参与认证，直接视为已过改密节点。
     */
    public function up(): void
    {
        DB::table('users')
            ->whereIn('account_type', ['cas', 'oidc', 'wechat'])
            ->whereNull('password_changed_at')
            ->update(['password_changed_at' => now()]);
    }

    public function down(): void
    {
        // 不回滚：回滚会重新锁死 SSO 用户
    }
};
