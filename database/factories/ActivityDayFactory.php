<?php

namespace Database\Factories;

use App\Models\ActivityDay;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ActivityDay>
 */
class ActivityDayFactory extends Factory
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
            'steps' => fake()->numberBetween(2000, 15000),
            'active_kcal' => fake()->randomFloat(1, 100, 900),
            'bmr_kcal' => fake()->randomFloat(1, 1200, 1800),
            'distance_m' => fake()->numberBetween(1000, 12000),
            'synced_at' => now(),
        ];
    }
}
