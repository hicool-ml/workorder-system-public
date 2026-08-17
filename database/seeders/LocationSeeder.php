<?php

namespace Database\Seeders;

use App\Models\Location;
use Illuminate\Database\Seeder;

/**
 * 地址 Seeder：全新部署不再预置占位地址（省份/城市/区县/街道/门牌号，
 * 以及总部园区/A 楼/101 室等示例），让用户在「地址管理 → 基础地址」页
 * 通过「创建项目地址」按钮初始化真实地址。
 */
class LocationSeeder extends Seeder
{
    public function run(): void
    {
        // 仅清空地址表，保持干净的初始状态（基础地址页将显示「创建项目地址」引导）
        Location::query()->delete();
    }
}
