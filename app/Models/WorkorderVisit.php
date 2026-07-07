<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkorderVisit extends Model
{
    use HasFactory;

    protected $fillable = [
        'workorder_id',
        'visitor_id',
        'visit_method',
        'visit_time',
        'visit_content',
        'feedback',
        'satisfaction_score',
        'response_speed_score',
        'service_quality_score',
        'professional_score',
        'overall_score',
        'suggestions',
        'status',
        'fail_reason',
        'need_follow_up',
        'follow_up_note',
    ];

    protected $casts = [
        'visit_time' => 'datetime',
        'satisfaction_score' => 'integer',
        'response_speed_score' => 'integer',
        'service_quality_score' => 'integer',
        'professional_score' => 'integer',
        'overall_score' => 'integer',
        'need_follow_up' => 'boolean',
    ];

    /**
     * 获取关联的工单
     */
    public function workorder(): BelongsTo
    {
        return $this->belongsTo(Workorder::class);
    }

    /**
     * 获取回访人
     */
    public function visitor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'visitor_id');
    }

    /**
     * 获取回访方式文本
     */
    public function getVisitMethodTextAttribute(): string
    {
        $methods = [
            'phone' => '电话',
            'sms' => '短信',
            'email' => '邮件',
            'online' => '在线',
            'scene' => '现场',
        ];
        
        return $methods[$this->visit_method] ?? $this->visit_method;
    }

    /**
     * 获取状态文本
     */
    public function getStatusTextAttribute(): string
    {
        $statuses = [
            'pending' => '待回访',
            'completed' => '已完成',
            'failed' => '回访失败',
            'skipped' => '跳过回访',
        ];
        
        return $statuses[$this->status] ?? $this->status;
    }

    /**
     * 获取平均满意度分数
     */
    public function getAverageScoreAttribute(): ?float
    {
        $scores = [
            $this->satisfaction_score,
            $this->response_speed_score,
            $this->service_quality_score,
            $this->professional_score,
            $this->overall_score,
        ];
        
        $validScores = array_filter($scores, function($score) {
            return $score !== null && $score > 0;
        });
        
        if (empty($validScores)) {
            return null;
        }
        
        return round(array_sum($validScores) / count($validScores), 2);
    }

    /**
     * 获取满意度等级
     */
    public function getSatisfactionLevelAttribute(): string
    {
        if (!$this->average_score) {
            return '未评价';
        }
        
        if ($this->average_score >= 4.5) {
            return '非常满意';
        } elseif ($this->average_score >= 3.5) {
            return '满意';
        } elseif ($this->average_score >= 2.5) {
            return '一般';
        } elseif ($this->average_score >= 1.5) {
            return '不满意';
        } else {
            return '非常不满意';
        }
    }

    /**
     * 获取满意度颜色
     */
    public function getSatisfactionColorAttribute(): string
    {
        if (!$this->average_score) {
            return '#999999';
        }
        
        if ($this->average_score >= 4.5) {
            return '#28a745'; // 绿色
        } elseif ($this->average_score >= 3.5) {
            return '#17a2b8'; // 青色
        } elseif ($this->average_score >= 2.5) {
            return '#ffc107'; // 黄色
        } elseif ($this->average_score >= 1.5) {
            return '#fd7e14'; // 橙色
        } else {
            return '#dc3545'; // 红色
        }
    }

    /**
     * 检查是否已完成回访
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * 检查是否回访失败
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * 检查是否需要跟进
     */
    public function needsFollowUp(): bool
    {
        return $this->need_follow_up || $this->isFailed();
    }

    /**
     * 完成回访
     */
    public function complete(array $data): bool
    {
        $this->update([
            'status' => 'completed',
            'feedback' => $data['feedback'] ?? null,
            'satisfaction_score' => $data['satisfaction_score'] ?? null,
            'response_speed_score' => $data['satisfaction_score'] ?? null, // 使用satisfaction_score作为响应速度评分
            'service_quality_score' => $data['service_quality_score'] ?? null,
            'professional_score' => $data['professional_score'] ?? null,
            'overall_score' => $data['overall_score'] ?? null,
            'suggestions' => $data['suggestions'] ?? null,
            'need_follow_up' => $data['need_follow_up'] ?? false,
            'follow_up_note' => $data['follow_up_note'] ?? null,
        ]);
        
        return true;
    }

    /**
     * 标记回访失败
     */
    public function fail(string $reason): bool
    {
        $this->update([
            'status' => 'failed',
            'fail_reason' => $reason,
        ]);
        
        return true;
    }

    /**
     * 跳过回访
     */
    public function skip(string $reason = null): bool
    {
        $this->update([
            'status' => 'skipped',
            'fail_reason' => $reason,
        ]);
        
        return true;
    }

    /**
     * 获取所有可用的回访方式
     */
    public static function getVisitMethodOptions(): array
    {
        return [
            'phone' => '电话',
            'sms' => '短信',
            'email' => '邮件',
            'online' => '在线',
            'scene' => '现场',
        ];
    }

    /**
     * 获取所有可用的状态
     */
    public static function getStatusOptions(): array
    {
        return [
            'pending' => '待回访',
            'completed' => '已完成',
            'failed' => '回访失败',
            'skipped' => '跳过回访',
        ];
    }

    /**
     * 获取评分选项
     */
    public static function getScoreOptions(): array
    {
        return [
            5 => '5分 - 非常满意',
            4 => '4分 - 满意',
            3 => '3分 - 一般',
            2 => '2分 - 不满意',
            1 => '1分 - 非常不满意',
        ];
    }
}