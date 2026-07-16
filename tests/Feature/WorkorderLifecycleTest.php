<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Workorder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class WorkorderLifecycleTest extends TestCase
{
    use DatabaseTransactions;

    private function createWorkorder(string $status = 'pending'): Workorder
    {
        return Workorder::factory()->create([
            'creator_id' => User::factory()->engineer()->create()->id,
            'assignee_id' => null,
            'type_id' => null,
            'status' => $status,
            'ticket_no' => 'WO' . str_pad((string) mt_rand(0, 999999), 6, '0', STR_PAD_LEFT),
            'started_at' => null,
            'resolved_at' => null,
            'closed_at' => null,
            'assigned_at' => null,
        ]);
    }

    private function createEngineer(): User
    {
        return User::factory()->engineer()->create();
    }

    /**
     * 待处理工单可以被分配
     */
    public function test_pending_workorder_can_be_assigned(): void
    {
        $workorder = $this->createWorkorder('pending');
        $engineer = $this->createEngineer();

        $this->assertTrue($workorder->canBeAssigned());
        $result = $workorder->assign($engineer->id, null, User::factory()->workorderManager()->create()->id);

        $this->assertTrue($result);
        $this->assertEquals('assigned', $workorder->fresh()->status);
        $this->assertEquals($engineer->id, $workorder->fresh()->assignee_id);
    }

    /**
     * 已分配工单可以开始处理
     */
    public function test_assigned_workorder_can_start(): void
    {
        $engineer = $this->createEngineer();
        $workorder = $this->createWorkorder('assigned');
        $workorder->update(['assignee_id' => $engineer->id]);

        $this->assertTrue($workorder->canBeStarted());
        $result = $workorder->start($engineer->id);

        $this->assertTrue($result);
        $this->assertEquals('processing', $workorder->fresh()->status);
        $this->assertNotNull($workorder->fresh()->started_at);
    }

    /**
     * 处理中工单可以解决
     */
    public function test_processing_workorder_can_resolve(): void
    {
        $engineer = $this->createEngineer();
        $workorder = $this->createWorkorder('processing');
        $workorder->update(['assignee_id' => $engineer->id, 'started_at' => now()]);

        $this->assertTrue($workorder->canBeResolved());
        $result = $workorder->resolve('修复完成，更换了配件', $engineer->id);

        $this->assertTrue($result);
        $this->assertEquals('resolved', $workorder->fresh()->status);
        $this->assertEquals('修复完成，更换了配件', $workorder->fresh()->solution);
    }

    /**
     * 非法状态转换应被拒绝
     */
    public function test_cannot_start_pending_workorder_without_assignment(): void
    {
        $workorder = $this->createWorkorder('pending');

        $this->assertFalse($workorder->canBeStarted());
    }

    /**
     * 已关闭工单不能再次操作
     */
    public function test_closed_workorder_cannot_be_operated(): void
    {
        $workorder = $this->createWorkorder('closed');
        $workorder->update(['closed_at' => now()]);

        $this->assertFalse($workorder->canBeAssigned());
        $this->assertFalse($workorder->canBeStarted());
        $this->assertFalse($workorder->canBeResolved());
    }
}
