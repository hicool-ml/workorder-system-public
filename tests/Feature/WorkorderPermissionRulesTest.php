<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Workorder;
use App\Models\WorkorderCollaboration;
use App\Services\WorkorderPermissionService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * 校验权限规则（见需求说明）：
 *  3. 工程师可见所有未完结工单；操作仅限相关工单。
 *  4. 协作工程师须接受邀请后方可操作。
 *  5. 负责人可在对方接受前取消邀请，接受后不可取消。
 */
class WorkorderPermissionRulesTest extends TestCase
{
    use DatabaseTransactions;

    private function engineer(): User
    {
        return User::factory()->create(['role' => 'engineer', 'status' => 'active']);
    }

    public function test_engineer_can_view_all_unfinished_workorders_but_not_closed_unrelated(): void
    {
        $engineer = $this->engineer();

        foreach (['pending', 'assigned', 'processing', 'resolved'] as $status) {
            $wo = Workorder::factory()->create([
                'status' => $status,
                'creator_id' => User::factory()->create(['role' => 'user'])->id,
                'assignee_id' => User::factory()->create(['role' => 'engineer'])->id,
            ]);
            $this->assertTrue($engineer->canViewWorkorder($wo), "工程师应可见 {$status} 工单");
        }

        // 与工程师无关的已完结工单不可见
        $closed = Workorder::factory()->create([
            'status' => 'completed',
            'creator_id' => User::factory()->create(['role' => 'user'])->id,
            'assignee_id' => User::factory()->create(['role' => 'engineer'])->id,
        ]);
        $this->assertFalse($engineer->canViewWorkorder($closed), '工程师不应可见无关的已完结工单');
    }

    public function test_engineer_scope_includes_all_unfinished(): void
    {
        $engineer = $this->engineer();

        $ids = collect(['pending', 'assigned', 'processing', 'resolved'])
            ->map(function ($s) {
                return Workorder::factory()->create([
                    'status' => $s,
                    'creator_id' => User::factory()->create(['role' => 'user'])->id,
                    'assignee_id' => User::factory()->create(['role' => 'engineer'])->id,
                ])->id;
            });

        $visible = $engineer->getWorkorderQueryScope()->whereIn('id', $ids)->pluck('id');
        $this->assertEqualsCanonicalizing($ids->all(), $visible->all());
    }

    public function test_pending_collaborator_cannot_operate_until_accepted(): void
    {
        $assignee = $this->engineer();
        $collaborator = $this->engineer();

        $wo = Workorder::factory()->create([
            'status' => 'processing',
            'creator_id' => $assignee->id,
            'assignee_id' => $assignee->id,
        ]);

        WorkorderCollaboration::create([
            'workorder_id' => $wo->id,
            'inviter_id' => $assignee->id,
            'collaborator_id' => $collaborator->id,
            'status' => 'pending',
        ]);

        $this->actingAs($collaborator);
        $this->assertFalse(WorkorderPermissionService::canStartWorkorder($wo), '待接受协作者不应能开始处理');
        $this->assertFalse(WorkorderPermissionService::canUploadAttachment($wo), '待接受协作者不应能上传附件');

        // 接受后即可操作
        $wo->collaborations()->where('collaborator_id', $collaborator->id)->update(['status' => 'accepted']);
        $this->assertTrue(WorkorderPermissionService::canStartWorkorder($wo));
        $this->assertTrue(WorkorderPermissionService::canUploadAttachment($wo));
    }

    public function test_invitation_can_be_cancelled_before_acceptance_but_not_after(): void
    {
        $assignee = $this->engineer();
        $collaborator = $this->engineer();

        $wo = Workorder::factory()->create([
            'status' => 'processing',
            'creator_id' => $assignee->id,
            'assignee_id' => $assignee->id,
        ]);

        $this->actingAs($assignee);
        $this->assertTrue($wo->inviteCollaborator($collaborator->id, '测试'), '应能成功邀请');

        $invitation = $wo->fresh()->collaborations()->where('collaborator_id', $collaborator->id)->first();
        $this->assertNotNull($invitation);
        $this->assertTrue($invitation->canBeCancelledBy($assignee), '待接受时负责人应可取消');
        $this->assertTrue($invitation->cancel($assignee), '取消应成功');
        $this->assertNull($wo->fresh()->collaborations()->where('collaborator_id', $collaborator->id)->first(), '取消后邀请应已删除');

        // 重新邀请并由对方接受
        $wo->fresh()->inviteCollaborator($collaborator->id);
        $accepted = $wo->fresh()->collaborations()->where('collaborator_id', $collaborator->id)->first();
        $accepted->update(['status' => 'accepted']);
        $this->assertFalse($accepted->fresh()->canBeCancelledBy($assignee), '对方接受后负责人不可取消');
    }
}
