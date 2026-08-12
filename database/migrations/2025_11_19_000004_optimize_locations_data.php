<?php

use Illuminate\Database\Migrations\Migration;

/**
 * 历史遗留 migration：原部署中曾用于批量重写地点名称。
 *
 * 该迁移在通用化工单系统中已不适用（地点名称与具体单位绑定），
 * up()/down() 均为 no-op，仅保留 stub 以维持 migrations 表的执行记录。
 * 通用化部署不应假设任何具体地点命名。
 */
return new class extends Migration
{
    public function up(): void
    {
        // no-op（通用化部署无需此步）
    }

    public function down(): void
    {
        // no-op
    }
};

