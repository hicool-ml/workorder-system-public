<?php

namespace App\Services;

use App\Models\Workorder;

/**
 * 工单签单 - 故障处理记录单服务
 */
class WorkorderSignaturePDFService
{
    /**
     * 满意度文本映射
     * 1=满意  2=一般  3=不满意  4=其它
     */
    public static array $satisfactionMap = [
        1 => '满意',
        2 => '一般',
        3 => '不满意',
        4 => '其它',
    ];

    public static function formatSatisfactionText(?int $value): string
    {
        if ($value === null) {
            return '未评价';
        }
        return self::$satisfactionMap[$value] ?? (string) $value;
    }

    /**
     * 回访情况文本
     */
    public static array $visitStatusMap = [
        'needed'     => '需要回访',
        'not_needed' => '不需要回访',
        'visited'    => '已回访',
    ];

    public static function formatVisitStatus(?string $value): string
    {
        if (!$value) {
            return '';
        }
        return self::$visitStatusMap[$value] ?? $value;
    }
}
