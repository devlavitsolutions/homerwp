<?php

namespace Database\Factories;

use App\Constants\Persist;
use App\Helpers\Generators;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            Persist::FREE_TOKENS => fake()->numberBetween(0, 4),
            Persist::PAID_TOKENS => fake()->numberBetween(0, 100),
            Persist::LAST_USED => fake()->dateTime(),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => []);
    }
}
