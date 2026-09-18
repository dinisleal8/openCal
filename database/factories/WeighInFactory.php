<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\WeighIn;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WeighIn>
 */
class WeighInFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'date' => now()->toDateString(),
            'weight_kg' => fake()->randomFloat(2, 55, 110),
        ];
    }
}
