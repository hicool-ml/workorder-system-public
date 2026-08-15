<?php

namespace App\Models\Concerns;

/**
 * 工单度量指标：处理/响应时长、效率评价、超时判定
 */
trait HasWorkorderMetrics
{
    /**
     * 获取处理时长（分钟）
     * 处理时长 = 工单解决时间 - 工单创建时间
     */
    public function getProcessingDurationAttribute(): ?int
    {
        if (!$this->resolved_at) {
            return null;
        }

        // 处理时长 = 解决时间 - 创建时间
        return $this->created_at->diffInMinutes($this->resolved_at);
    }

    /**
     * 获取响应时长（分钟）
     * 响应时长 = 接单时间 - 工单创建时间
     */
    public function getResponseDurationAttribute(): ?int
    {
        if (!$this->assigned_at) {
            return null;
        }

        return $this->created_at->diffInMinutes($this->assigned_at);
    }

    /**
     * 获取格式化的处理时长
     */
    public function getFormattedProcessingDurationAttribute(): string
    {
        $duration = $this->processing_duration;
        if ($duration === null) {
            return '未解决';
        }

        return $this->formatDuration($duration);
    }

    /**
     * 获取格式化的响应时长
     */
    public function getFormattedResponseDurationAttribute(): string
    {
        $duration = $this->response_duration;
        if ($duration === null) {
            return '未接单';
        }

        return $this->formatDuration($duration);
    }

    /**
     * 格式化时长显示
     */
    private function formatDuration(int $minutes): string
    {
        if ($minutes < 1) {
            return '不到1分钟';
        } elseif ($minutes < 60) {
            return $minutes . '分钟';
        } elseif ($minutes < 1440) { // 24小时 = 1440分钟
            $hours = (int)($minutes / 60);
            $remainingMinutes = $minutes % 60;

            if ($remainingMinutes > 0) {
                return $hours . '小时' . $remainingMinutes . '分钟';
            }
            return $hours . '小时';
        } else {
            $days = (int)($minutes / 1440);
            $remainingMinutes = $minutes % 1440;
            $hours = (int)($remainingMinutes / 60);
            $remainingMinutes = $remainingMinutes % 60;

            $result = $days . '天';
            if ($hours > 0) {
                $result .= $hours . '小时';
            }
            if ($remainingMinutes > 0) {
                $result .= $remainingMinutes . '分钟';
            }
            return $result;
        }
    }

    /**
     * 获取处理时长（小时）
     */
    public function getProcessingDurationInHoursAttribute(): ?float
    {
        $minutes = $this->processing_duration;
        return $minutes !== null ? round($minutes / 60, 2) : null;
    }

    /**
     * 获取响应时长（小时）
     */
    public function getResponseDurationInHoursAttribute(): ?float
    {
        $minutes = $this->response_duration;
        return $minutes !== null ? round($minutes / 60, 2) : null;
    }

    /**
     * 检查响应时长是否超时（超过2小时）
     */
    public function isResponseOverdue(): bool
    {
        $responseMinutes = $this->response_duration;
        return $responseMinutes !== null && $responseMinutes > 120; // 2小时 = 120分钟
    }

    /**
     * 检查处理时长是否超时（超过24小时）
     */
    public function isProcessingOverdue(): bool
    {
        $processingMinutes = $this->processing_duration;
        return $processingMinutes !== null && $processingMinutes > 1440; // 24小时 = 1440分钟
    }

    /**
     * 获取响应效率评价
     */
    public function getResponseEfficiencyAttribute(): string
    {
        $minutes = $this->response_duration;
        if ($minutes === null) {
            return '未响应';
        }

        if ($minutes <= 30) {
            return '快速响应';
        } elseif ($minutes <= 60) {
            return '正常响应';
        } elseif ($minutes <= 120) {
            return '较慢响应';
        } else {
            return '响应超时';
        }
    }

    /**
     * 获取处理效率评价
     */
    public function getProcessingEfficiencyAttribute(): string
    {
        $minutes = $this->processing_duration;
        if ($minutes === null) {
            return '未完成';
        }

        if ($minutes <= 240) { // 4小时
            return '高效处理';
        } elseif ($minutes <= 480) { // 8小时
            return '正常处理';
        } elseif ($minutes <= 1440) { // 24小时
            return '较慢处理';
        } else {
            return '处理超时';
        }
    }

    /**
     * 获取响应效率颜色
     */
    public function getResponseEfficiencyColorAttribute(): string
    {
        $efficiency = $this->response_efficiency;
        switch ($efficiency) {
            case '快速响应':
                return 'success';
            case '正常响应':
                return 'info';
            case '较慢响应':
                return 'warning';
            case '响应超时':
                return 'danger';
            default:
                return 'secondary';
        }
    }

    /**
     * 获取处理效率颜色
     */
    public function getProcessingEfficiencyColorAttribute(): string
    {
        $efficiency = $this->processing_efficiency;
        switch ($efficiency) {
            case '高效处理':
                return 'success';
            case '正常处理':
                return 'info';
            case '较慢处理':
                return 'warning';
            case '处理超时':
                return 'danger';
            default:
                return 'secondary';
        }
    }

    /**
     * 获取创建历时（格式化）
     * 返回格式化的时间差，如：刚刚、5分钟前、2小时前、3天5小时前、2天5小时30分钟前等
     */
    public function getCreatedDurationAttribute(): string
    {
        // Phone-assisted workorders are resolved instantly on creation.
        if ($this->phone_assisted && $this->resolved_at) {
            return '电话解决';
        }

        // Finished workorders use their final end time; only in-progress ones keep ticking.
        $endTime = $this->closed_at ?? $this->completed_at ?? $this->resolved_at ?? now();
        $diff = (int)$this->created_at->diffInMinutes($endTime);

        if ($diff < 1) {
            return '刚刚';
        } elseif ($diff < 60) {
            return $diff . '分';
        } elseif ($diff < 1440) { // 24小时 = 1440分钟
            $hours = (int)($diff / 60);
            $minutes = $diff % 60;
            if ($minutes > 0) {
                return $hours . '时' . $minutes . '分';
            }
            return $hours . '时';
        } else {
            $days = (int)($diff / 1440);
            $remainingMinutes = $diff % 1440;
            $hours = (int)($remainingMinutes / 60);
            $minutes = $remainingMinutes % 60;

            $result = $days . '天';
            if ($hours > 0) {
                $result .= $hours . '时';
            }
            if ($minutes > 0) {
                $result .= $minutes . '分';
            }
            return $result . '前';
        }
    }

    /**
     * 获取超时判定基准时间：
     * 已完结工单按实际结束时间（closed/completed/resolved），未完结按 now()。
     */
    public function getOverdueCheckTime(): ?\Carbon\Carbon
    {
        return $this->closed_at ?? $this->completed_at ?? $this->resolved_at ?? now();
    }

    public function isOverdue(): bool
    {
        // Finished workorders are judged by their actual end time, not now().
        $checkTime = $this->getOverdueCheckTime();

        // 获取超时计算的基准时间
        $baseTime = $this->getOverdueBaseTime();
        if (!$baseTime) {
            return false;
        }

        // 计算预期完成时间
        $expectedCompleteTime = $this->calculateExpectedCompleteTime($baseTime);
        if (!$expectedCompleteTime) {
            return false;
        }

        return $checkTime->isAfter($expectedCompleteTime);
    }

    /**
     * 获取超时计算的起始时间
     * 如果有预约时间，使用预约开始时间；否则使用创建时间
     */
    public function getOverdueStartTime(): \Carbon\Carbon
    {
        // 优先使用预约开始时间
        if ($this->appointment_time_start) {
            return $this->appointment_time_start;
        }

        // 如果没有预约开始时间，使用创建时间
        return $this->created_at;
    }

    /**
     * 获取超时计算的基准时间
     * 用于确定超时的起始点
     */
    public function getOverdueBaseTime(): ?\Carbon\Carbon
    {
        // 如果是预约工单且当前时间在预约开始时间之后，使用预约开始时间
        if ($this->appointment_time_start && now()->isAfter($this->appointment_time_start)) {
            return $this->appointment_time_start;
        }

        // 如果是预约工单但当前时间还未到预约开始时间，不计算超时
        if ($this->appointment_time_start && now()->isBefore($this->appointment_time_start)) {
            return null;
        }

        // 非预约工单使用创建时间
        return $this->created_at;
    }

    /**
     * 计算预期完成时间
     * 根据工单类型和优先级计算
     */
    public function calculateExpectedCompleteTime(?\Carbon\Carbon $baseTime = null): ?\Carbon\Carbon
    {
        $baseTime = $baseTime ?: $this->getOverdueBaseTime();
        if (!$baseTime) {
            return null;
        }

        // 如果已有明确的预期完成时间，直接使用
        if ($this->expected_complete_at) {
            return $this->expected_complete_at;
        }

        // 根据优先级计算默认预期完成时间
        $hoursToAdd = 24; // 默认24小时

        switch ($this->priority) {
            case 'high':
                $hoursToAdd = 4; // 高优先级4小时
                break;
            case 'medium':
                $hoursToAdd = 8; // 中优先级8小时
                break;
            case 'low':
                $hoursToAdd = 24; // 低优先级24小时
                break;
        }

        // 紧急工单时间减半
        if ($this->is_emergency) {
            $hoursToAdd = $hoursToAdd / 2;
        }

        return $baseTime->copy()->addHours($hoursToAdd);
    }

    /**
     * 获取超时级别
     * 返回值：normal（正常）、warning（1小时）、danger（4小时）、critical（8小时以上）
     * 按小时计算：浅绿色：1小时以内、浅黄色：1小时、浅橙色：4小时、浅红色：8小时
     */
    public function getOverdueLevel(): string
    {
        if (!$this->isOverdue()) {
            return 'normal';
        }

        $baseTime = $this->getOverdueBaseTime();
        if (!$baseTime) {
            return 'normal';
        }

        $expectedTime = $this->calculateExpectedCompleteTime($baseTime);
        if (!$expectedTime) {
            return 'normal';
        }

        $checkTime = $this->getOverdueCheckTime();
        $overdueMinutes = $expectedTime->diffInMinutes($checkTime);

        if ($overdueMinutes <= 60) {
            return 'warning'; // 1小时以内 - 黄色警告
        } elseif ($overdueMinutes <= 240) { // 4小时 = 240分钟
            return 'danger'; // 1-4小时 - 橙色危险
        } elseif ($overdueMinutes <= 480) { // 8小时 = 480分钟
            return 'critical'; // 4-8小时 - 红色严重
        } else {
            return 'critical'; // 8小时以上 - 红色严重
        }
    }
}
