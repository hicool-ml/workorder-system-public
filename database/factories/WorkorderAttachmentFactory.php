<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Workorder;
use App\Models\WorkorderAttachment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WorkorderAttachment>
 */
class WorkorderAttachmentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = WorkorderAttachment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $fileTypes = ['image', 'document', 'video', 'audio', 'other'];
        $fileType = fake()->randomElement($fileTypes);
        
        return [
            'workorder_id' => Workorder::factory(),
            'user_id' => User::factory(),
            'filename' => fake()->uuid() . '.' . fake()->fileExtension(),
            'original_name' => fake()->word() . '.' . fake()->fileExtension(),
            'file_path' => 'workorder_attachments/' . fake()->uuid() . '.' . fake()->fileExtension(),
            'file_type' => $fileType,
            'file_size' => fake()->numberBetween(1024, 10485760), // 1KB to 10MB
            'mime_type' => fake()->mimeType(),
            'description' => fake()->optional()->sentence(),
            'type' => $fileType,
            'is_public' => fake()->boolean(),
            'thumbnail_path' => $fileType === 'image' ? 'workorder_attachments/thumbnails/thumb_' . fake()->uuid() . '.jpg' : null,
        ];
    }
}