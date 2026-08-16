<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * SSO 账号类型与邮箱可空化。
     *
     * 背景（首登即败的两个 blocker）：
     * 1. CasAuthController/OidcAuthController 写入 account_type='cas'/'oidc'，
     *    但枚举只有 staff/student/external —— 新用户创建被吞、老用户更新直接 500；
     * 2. users.email NOT NULL + unique —— 校园 CAS 常不回传邮箱，
     *    建号必败；多人共享邮箱撞 unique 后续全部登录失败。
     */
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            DB::statement("ALTER TABLE users DROP CONSTRAINT IF EXISTS users_account_type_check");
            DB::statement("ALTER TABLE users ALTER COLUMN account_type TYPE varchar(20)");
            DB::statement("ALTER TABLE users ADD CONSTRAINT users_account_type_check CHECK (account_type IN ('staff','student','external','cas','oidc','wechat'))");
            // email 可空（历史数据可能有占位邮箱，不动数据）
            DB::statement("ALTER TABLE users ALTER COLUMN email DROP NOT NULL");
            return;
        }

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY account_type ENUM('staff','student','external','cas','oidc','wechat') DEFAULT 'staff'");
            DB::statement("ALTER TABLE users MODIFY email VARCHAR(100) NULL");
            return;
        }

        // sqlite（测试环境）：account_type 建表本就是约束宽松的检查，email 可空
        // sqlite 不支持 ALTER COLUMN，测试库建表语句需本身兼容——测试环境通常重建，跳过
    }

    public function down(): void
    {
        // 不回滚：收窄会破坏已存在的 SSO 账号
    }
};
