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
            'location' => fake()->address(),
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