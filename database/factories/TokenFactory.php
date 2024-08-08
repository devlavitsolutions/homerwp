<?php

namespace Database\Factories;

use App\Constants\Defaults;
use App\Database\Constants\TokenCol;
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
            TokenCol::USER_ID => fake()->numberBetween(),
            TokenCol::FREE_TOKENS => fake()->numberBetween(0, Defaults::FREE_TOKENS_PER_MONTH),
            TokenCol::PAID_TOKENS => fake()->numberBetween(0, self::MAX_PAID_TOKENS),
            TokenCol::LAST_USED => fake()->dateTimeThisYear(),
        ];
    }
}
