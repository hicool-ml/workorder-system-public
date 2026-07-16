<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Workorder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class WorkorderAddLogTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * 普通用户不能给不相关的工单添加处理记录（越权防护）
     */
    public function test_regular_user_cannot_add_log_to_unrelated_workorder(): void
    {
        $user = User::factory()->regularUser()->create();
        $creator = User::factory()->engineer()->create();
        $workorder = $this->createWorkorder($creator);

        $response = $this->actingAs($user)->post("/workorders/{$workorder->id}/logs", [
            'content' => 'test comment',
        ]);

        $response->assertStatus(403);
    }

    /**
     * 工单创建者可以给自己创建的工单添加处理记录
     */
    public function test_creator_can_add_log_to_own_workorder(): void
    {
        $creator = User::factory()->engineer()->create();
        $workorder = $this->createWorkorder($creator);

        $response = $this->actingAs($creator)->post("/workorders/{$workorder->id}/logs", [
            'content' => 'test comment',
        ]);

        $response->assertStatus(302);
    }

    private function createWorkorder(User $creator): Workorder
    {
        return Workorder::factory()->create([
            'creator_id' => $creator->id,
            'assignee_id' => $creator->id,
            'type_id' => null,
            'status' => 'pending',
            'ticket_no' => 'WO' . str_pad((string) mt_rand(0, 999999), 6, '0', STR_PAD_LEFT),
        ]);
    }
}
