<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Workorder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class WorkorderBatchOperationTest extends TestCase
{
    use DatabaseTransactions;

    private function actingAsEngineer(): User
    {
        $engineer = User::factory()->engineer()->create();
        $this->actingAs($engineer);
        return $engineer;
    }

    private function createAssignedWorkorder(User $assignee): Workorder
    {
        $creator = User::factory()->engineer()->create();

        return Workorder::factory()->create([
            'assignee_id' => $assignee->id,
            'creator_id' => $creator->id,
            'type_id' => null,
            'status' => 'assigned',
            'ticket_no' => 'WO' . str_pad((string) mt_rand(0, 999999), 6, '0', STR_PAD_LEFT),
        ]);
    }

    /**
     * batchStart 不应因传入不存在的工单 ID 而 500 崩溃
     */
    public function test_batch_start_does_not_crash_on_nonexistent_workorder_id(): void
    {
        $this->actingAsEngineer();

        $response = $this->post('/workorders/batch/start', [
            'workorder_ids' => '999999999',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }

    /**
     * batchComplete 不应因传入不存在的工单 ID 而 500 崩溃
     */
    public function test_batch_complete_does_not_crash_on_nonexistent_workorder_id(): void
    {
        $this->actingAsEngineer();

        $response = $this->post('/workorders/batch/complete', [
            'workorder_ids' => '999999999',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }

    /**
     * batchStart 正常处理已分配给当前工程师的工单
     */
    public function test_batch_start_succeeds_for_assigned_workorder(): void
    {
        $engineer = $this->actingAsEngineer();
        $workorder = $this->createAssignedWorkorder($engineer);

        $response = $this->post('/workorders/batch/start', [
            'workorder_ids' => (string) $workorder->id,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }
    /**
     * batchResolve 普通用户无权限调用，应返回 403
     */
    public function test_batch_resolve_requires_permission(): void
    {
        $regularUser = User::factory()->regularUser()->create();

        $response = $this->actingAs($regularUser)->post('/workorders/batch/resolve', [
            'workorder_ids' => '1',
            'solution_type' => 'common',
            'solution' => 'test solution',
        ]);

        $response->assertStatus(403);
    }

    /**
     * batchComplete 普通用户无权限调用，应返回 403
     */
    public function test_batch_complete_requires_permission(): void
    {
        $regularUser = User::factory()->regularUser()->create();

        $response = $this->actingAs($regularUser)->post('/workorders/batch/complete', [
            'workorder_ids' => '1',
        ]);

        $response->assertStatus(403);
    }
}
