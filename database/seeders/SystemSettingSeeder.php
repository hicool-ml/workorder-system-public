<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\SystemSetting;

class SystemSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaultSettings = [
            [
                'key' => 'registration_enabled',
                'value' => '0',
                'type' => 'boolean',
                'description' => '是否开放用户注册',
                'is_public' => true,
            ],
            [
                'key' => 'default_user_role',
                'value' => 'user',
                'type' => 'string',
                'description' => '新注册用户的默认角色',
                'is_public' => false,
            ],
            [
                'key' => 'system_name',
                'value' => '校园网工单系统',
                'type' => 'string',
                'description' => '系统名称',
                'is_public' => true,
            ],
            [
                'key' => 'require_email_verification',
                'value' => '0',
                'type' => 'boolean',
                'description' => '是否需要邮箱验证',
                'is_public' => true,
            ],
        ];

        foreach ($defaultSettings as $setting) {
            SystemSetting::firstOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
