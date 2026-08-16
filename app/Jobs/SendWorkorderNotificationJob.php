<?php

namespace App\Jobs;

use App\Models\Workorder;
use App\Services\Notification\NotificationDispatcher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * 异步发送工单事件通知。
 *
 * 把站内信批量插入与短信/企微/钉钉/飞书的 HTTP 调用移出用户请求，
 * 避免创建工单等操作被外部网络超时拖慢。
 * 仅当 NOTIFY_QUEUE=true 时由 Workorder::sendNotification 派发（需运行 queue:work）。
 */
class SendWorkorderNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;

    public function __construct(
        public Workorder $workorder,
        public string $event,
    ) {
    }

    public function handle(): void
    {
        // SerializesModels 反序列化后工单可能已被删除；同时重新从 DB 加载——
        // 入队时的快照可能缺失 sms_acceptance_sent_at 等防重标记，用快照会导致重复发送
        $workorder = $this->workorder?->id
            ? \App\Models\Workorder::find($this->workorder->id)
            : null;

        if (!$workorder) {
            Log::info('通知任务跳过：工单已不存在', [
                'event' => $this->event,
            ]);
            return;
        }

        app(NotificationDispatcher::class)->dispatch($workorder, $this->event);
    }

    public function failed(\Throwable $e): void
    {
        Log::error('工单通知队列任务最终失败', [
            'workorder_id' => $this->workorder?->id,
            'event' => $this->event,
            'error' => $e->getMessage(),
        ]);
    }
}
