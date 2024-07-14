<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'username' => fake()->unique()->userName(),
            'first_name' => fake()->unique()->firstName(),
            'last_name' => fake()->unique()->lastName(),
        ];
    }

    public function banned(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
