<?php

namespace Database\Factories;

use App\Enums\ActivityLevel;
use App\Enums\GoalType;
use App\Enums\Sex;
use App\Models\Goal;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Goal>
 */
class GoalFactory extends Factory
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
            'sex' => Sex::Male,
            'birth_date' => now()->subYears(30)->toDateString(),
            'height_cm' => fake()->numberBetween(155, 195),
            'activity_level' => ActivityLevel::Moderate,
            'goal_type' => GoalType::Lose,
            'weekly_rate_kg' => 0.50,
            'target_weight_kg' => 75.00,
            'water_goal_ml' => 2500,
            'protein_pct' => 30,
            'carbs_pct' => 40,
            'fat_pct' => 30,
            'include_band_calories' => false,
        ];
    }

    public function maintain(): static
    {
        return $this->state(fn (array $attributes) => [
            'goal_type' => GoalType::Maintain,
        ]);
    }

    public function gain(): static
    {
        return $this->state(fn (array $attributes) => [
            'goal_type' => GoalType::Gain,
        ]);
    }

    public function female(): static
    {
        return $this->state(fn (array $attributes) => [
            'sex' => Sex::Female,
        ]);
    }
}
