<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name'              => fake()->name(),
            'email'             => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password'          => static::$password ??= Hash::make('password'),
            'role'              => fake()->randomElement([
                'pipeline_manager',
                'designer',
                'printing_manager',
                'sewing_manager',
            ]),
            'is_active'         => true,
            'remember_token'    => Str::random(10),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn () => ['email_verified_at' => null]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    public function pipelineManager(): static
    {
        return $this->state(fn () => ['role' => 'pipeline_manager']);
    }

    public function designer(): static
    {
        return $this->state(fn () => ['role' => 'designer']);
    }

    public function printingManager(): static
    {
        return $this->state(fn () => ['role' => 'printing_manager']);
    }

    public function sewingManager(): static
    {
        return $this->state(fn () => ['role' => 'sewing_manager']);
    }
}
