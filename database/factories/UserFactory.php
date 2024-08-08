<?php

namespace Database\Factories;

use App\Database\Constants\UserCol;
use App\Helpers\Generators;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Database\Models\User>
 */
class UserFactory extends Factory
{
    private const DEFAULT_PASSWORD = 'password';

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
            UserCol::EMAIL => fake()->unique()->safeEmail(),
            UserCol::PASSWORD => static::$password ??= Generators::encryptPassword(self::DEFAULT_PASSWORD),
            UserCol::REMEMBER_TOKEN => fake()->uuid(),
            UserCol::LICENSE_KEY => fake()->uuid(),
            UserCol::IS_ADMIN => fake()->boolean(),
            UserCol::IS_DISABLED => fake()->boolean(),
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
