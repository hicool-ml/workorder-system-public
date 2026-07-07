<?php

namespace App\Helpers;

use Carbon\Carbon;

class DateHelper
{
    /**
     * 获取中文格式的时间差
     */
    public static function diffForHumansCN($datetime): string
    {
        $now = now();
        $diff = $now->diffInMinutes($datetime);
        
        if ($diff < 1) {
            return '刚刚';
        } elseif ($diff < 60) {
            return $diff . '分钟前';
        } elseif ($diff < 1440) { // 24小时
            $hours = floor($diff / 60);
            return $hours . '小时前';
        } elseif ($diff < 10080) { // 7天
            $days = floor($diff / 1440);
            return $days . '天前';
        } else {
            return $datetime->format('m-d H:i');
        }
    }
}