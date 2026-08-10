<?php

namespace Database\Factories;

use App\Models\WorkorderType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WorkorderType>
 */
class WorkorderTypeFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = WorkorderType::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true),
            'code' => fake()->unique()->regexify('[A-Z]{3}[0-9]{2}'),
            'description' => fake()->optional()->sentence(),
            'icon' => fake()->optional()->randomElement(['fas fa-desktop', 'fas fa-printer', 'fas fa-network-wired', 'fas fa-projector']),
            'color' => fake()->optional()->randomElement(['#007bff', '#28a745', '#ffc107', '#dc3545', '#6f42c1']),
            'parent_id' => null,
            'level' => 1,
            'source' => fake()->optional()->randomElement(['电话', '网络', '现场']),
           'subcategory' => fake()->optional()->randomElement(['机房', '多媒体教室', '专项']),
            'subcategory' => fake()->optional()->randomElement(['终端设备', '外设', '网络设备']),
            'default_priority' => fake()->numberBetween(1, 3),
            'default_hours' => fake()->numberBetween(1, 72),
            'status' => fake()->randomElement(['active', 'inactive']),
            'sort_order' => fake()->numberBetween(0, 100),
        ];
    }
}
