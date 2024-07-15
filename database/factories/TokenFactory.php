<?php

namespace Database\Factories;

use App\Constants\Defaults;
use App\Constants\Persist;
use Illuminate\Database\Eloquent\Factories\Factory;

class TokenFactory extends Factory
{
    private const MAX_PAID_TOKENS = 32000;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            Persist::USER_ID => fake()->numberBetween(),
            Persist::FREE_TOKENS => fake()->numberBetween(0, Defaults::FREE_TOKENS_PER_MONTH),
            Persist::PAID_TOKENS => fake()->numberBetween(0, self::MAX_PAID_TOKENS),
            Persist::LAST_USED => fake()->dateTimeThisYear(),
        ];
    }
}
