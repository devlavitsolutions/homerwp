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
            Persist::EMAIL => fake()->unique()->safeEmail(),
            Persist::PASSWORD => static::$password ??= Generators::encryptPassword('password'),
            Persist::REMEMBER_TOKEN => Str::random(10),
            Persist::LICENSE_KEY => fake()->uuid(),
            Persist::TOKENS_COUNT => fake()->number(0, 100),
            Persist::IS_ADMIN => fake()->boolean(),
            Persist::IS_DISABLED => fake()->boolean(),
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
