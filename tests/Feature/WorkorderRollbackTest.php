<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Workorder;
use App\Models\WorkorderCollaboration;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class WorkorderRollbackTest extends TestCase
{
    use DatabaseTransactions;

    private function createWorkorder(string $status = 'pending', array $overrides = []): Workorder
    {
        return Workorder::factory()->create(array_merge([
            'creator_id' => User::factory()->engineer()->create()->id,
            'assignee_id' => null,
            'type_id' => null,
            'status' => $status,
            'ticket_no' => 'WO' . str_pad((string) mt_rand(0, 999999), 6, '0', STR_PAD_LEFT),
            'started_at' => null,
            'resolved_at' => null,
            'closed_at' => null,
            'assigned_at' => null,
        ], $overrides));
    }

    private function createManager(): User
    {
        return User::factory()->workorderManager()->create();
    }

    private function createEngineer(): User
    {
        return User::factory()->engineer()->create();
    }

    public function test_manager_can_rollback_to_pending_clearing_assignee_and_collaborations(): void
    {
        $manager = $this->createManager();
        $assignee = $this->createEngineer();
        $collaborator = $this->createEngineer();

        // 工单处于处理中，有处理人和待处理的协作邀请（对应工单 #1994 的场景）
        $workorder = $this->createWorkorder('processing', [
            'assignee_id' => $assignee->id,
            'assigned_at' => now(),
            'started_at' => now(),
            'solution' => '临时方案',
            'resolved_at' => now(),
        ]);

        WorkorderCollaboration::create([
            'workorder_id' => $workorder->id,
            'inviter_id' => $assignee->id,
            'collaborator_id' => $collaborator->id,
            'status' => 'pending',
        ]);

        $this->assertNotEmpty($workorder->getRollbackOptions());
        $this->assertTrue($workorder->canRollbackTo('pending'));

        $result = $workorder->rollback('pending', '协作未接受，需重新分配', $manager->id);

        $this->assertTrue($result);

        $fresh = $workorder->fresh();
        $this->assertEquals('pending', $fresh->status);
        $this->assertNull($fresh->assignee_id);
        $this->assertNull($fresh->assigned_at);
        $this->assertNull($fresh->started_at);
        $this->assertNull($fresh->resolved_at);
        $this->assertNull($fresh->solution);

        // 协作邀请被清除
        $this->assertEquals(0, $fresh->collaborations()->count());

        // 审计日志已写入
        $log = $fresh->logs()->where('action', 'rolled_back')->first();
        $this->assertNotNull($log);
        $this->assertEquals('processing', $log->old_value);
        $this->assertEquals('pending', $log->new_value);
        $this->assertEquals($manager->id, $log->user_id);
        $this->assertStringContainsString('清理协作邀请', $log->content);
    }

    public function test_rollback_to_assigned_keeps_assignee_but_clears_started_at(): void
    {
        $manager = $this->createManager();
        $assignee = $this->createEngineer();

        $workorder = $this->createWorkorder('processing', [
            'assignee_id' => $assignee->id,
            'assigned_at' => now(),
            'started_at' => now(),
            'resolved_at' => now(),
            'solution' => '已修复',
        ]);

        $workorder->rollback('assigned', null, $manager->id);

        $fresh = $workorder->fresh();
        $this->assertEquals('assigned', $fresh->status);
        $this->assertEquals($assignee->id, $fresh->assignee_id);
        $this->assertNotNull($fresh->assigned_at);
        $this->assertNull($fresh->started_at);
        $this->assertNull($fresh->resolved_at);
    }

    public function test_pending_workorder_has_no_rollback_options(): void
    {
        $workorder = $this->createWorkorder('pending');

        $this->assertEmpty($workorder->getRollbackOptions());
        $this->assertFalse($workorder->canRollbackTo('pending'));
        $this->assertFalse($workorder->rollback('pending'));
    }

    public function test_cannot_rollback_forward_to_a_later_node(): void
    {
        $manager = $this->createManager();
        $workorder = $this->createWorkorder('assigned', [
            'assignee_id' => $this->createEngineer()->id,
            'assigned_at' => now(),
        ]);

        // 不能"回滚"到更靠后的处理中状态
        $this->assertFalse($workorder->canRollbackTo('processing'));
        $this->assertFalse($workorder->canRollbackTo('resolved'));
    }

    public function test_controller_rejects_non_manager(): void
    {
        $engineer = $this->createEngineer();
        $assignee = $this->createEngineer();
        $workorder = $this->createWorkorder('processing', [
            'assignee_id' => $assignee->id,
            'assigned_at' => now(),
            'started_at' => now(),
        ]);

        $response = $this->actingAs($engineer)
            ->post(route('workorders.rollback', $workorder->id), [
                'target_status' => 'pending',
                'reason' => '测试',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertEquals('processing', $workorder->fresh()->status);
    }

    public function test_controller_rejects_invalid_target_node(): void
    {
        $manager = $this->createManager();
        $workorder = $this->createWorkorder('processing', [
            'assignee_id' => $this->createEngineer()->id,
            'assigned_at' => now(),
            'started_at' => now(),
        ]);

        // completed 不是合法回滚目标
        $response = $this->actingAs($manager)
            ->post(route('workorders.rollback', $workorder->id), [
                'target_status' => 'completed',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertEquals('processing', $workorder->fresh()->status);
    }

    public function test_manager_can_rollback_via_http(): void
    {
        $manager = $this->createManager();
        $assignee = $this->createEngineer();

        $workorder = $this->createWorkorder('processing', [
            'assignee_id' => $assignee->id,
            'assigned_at' => now(),
            'started_at' => now(),
        ]);

        $response = $this->actingAs($manager)
            ->post(route('workorders.rollback', $workorder->id), [
                'target_status' => 'pending',
                'reason' => '需要重新分配',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $fresh = $workorder->fresh();
        $this->assertEquals('pending', $fresh->status);
        $this->assertNull($fresh->assignee_id);
    }
}