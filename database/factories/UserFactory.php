<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'username' => fake()->unique()->userName(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'role' => 'user',
            'status' => 'active',
            'password_changed_at' => now(),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'admin',
            'status' => 'active',
        ]);
    }

    public function workorderManager(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'workorder_manager',
            'status' => 'active',
        ]);
    }

    public function engineer(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'engineer',
            'status' => 'active',
        ]);
    }

    public function regularUser(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'user',
            'status' => 'active',
        ]);
    }
}
