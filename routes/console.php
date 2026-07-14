<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// 每日凌晨 2 点自动备份系统（数据库 + 附件），保留最近 30 份
Schedule::command('backup:system')->dailyAt('02:00')->withoutOverlapping()->name('system-backup');