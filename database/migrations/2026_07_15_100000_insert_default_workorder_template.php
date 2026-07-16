<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * 插入系统默认工单模板
 */
return new class extends Migration
{
    public function up(): void
    {
        // 检查是否已有默认模板
        $exists = DB::table('workorder_templates')->where('name', '系统默认模板')->exists();
        if ($exists) {
            return;
        }

        // 获取第一个分类作为默认值
        $category = DB::table('workorder_categories_simplified')->first();
        $campus = DB::table('campuses')->orderBy('sort_order')->first();

        DB::table('workorder_templates')->insert([
            'name'             => '系统默认模板',
            'description'      => '系统内置的标准工单模板，可直接复制后修改使用',
            'category_id'      => $category?->id,
            'contact_name'     => '',
            'contact_phone'    => '',
            'contact_email'    => null,
            'campus_id'        => $campus?->id,
            'building'         => '',
            'location_detail'  => '',
            'time_limit_hours' => 48,
            'priority'         => 'medium',
            'source'           => 'phone',
            'department_name'  => '',
            'need_visit'       => false,
            'is_emergency'     => false,
            'phone_assisted'   => false,
            'other_reason'     => null,
            'is_active'        => true,
            'creator_id'       => DB::table('users')->value('id'),
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('workorder_templates')->where('name', '系统默认模板')->delete();
    }
};