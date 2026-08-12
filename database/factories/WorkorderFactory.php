<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Workorder;
use App\Models\WorkorderType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Workorder>
 */
class WorkorderFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Workorder::class;

    /**
     * Define the model's default state.
     *
     * 注意：Laravel Factory 内部已用 Model::unguarded() 包裹创建过程，
     * 即便 started_at / resolved_at / closed_at 不在 Workorder::$fillable 中，
     * 工厂仍可正确写入这些生命周期时间戳字段。
     *
     * 工单地址字段已统一为 location_id（指向 locations 树节点），
     * 原 campus / campus_id / building / location 文本列已 drop。
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ticket_no' => 'TEST' . fake()->unique()->numerify('####'),
            'title' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'type_id' => WorkorderType::factory(),
            'creator_id' => User::factory(),
            'assignee_id' => User::factory(),
            'department_id' => null,
            'contact_name' => fake()->name(),
            'contact_phone' => fake()->phoneNumber(),
            'contact_email' => fake()->optional()->email(),
            'location_id' => null, // 测试场景按需指定树节点 id
            'location_detail' => fake()->optional()->sentence(),
            'source' => fake()->randomElement(['phone', 'web', 'email', 'scene', 'other']),
            'priority' => fake()->randomElement(['high', 'medium', 'low']),
            'status' => fake()->randomElement(['pending', 'assigned', 'processing', 'resolved', 'closed']),
            'assigned_at' => fake()->optional()->dateTime(),
            'started_at' => fake()->optional()->dateTime(),
            'resolved_at' => fake()->optional()->dateTime(),
            'closed_at' => fake()->optional()->dateTime(),
            'expected_complete_at' => fake()->optional()->dateTime(),
            'solution' => fake()->optional()->paragraph(),
            'remarks' => fake()->optional()->sentence(),
            'need_visit' => fake()->boolean(),
            'is_emergency' => fake()->boolean(),
        ];
    }
}