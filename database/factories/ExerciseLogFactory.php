<?php

namespace Database\Factories;

use App\Enums\ExerciseSource;
use App\Models\ExerciseLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExerciseLog>
 */
class ExerciseLogFactory extends Factory
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
            'name' => fake()->randomElement(['Walk', 'Run', 'Cycling', 'Gym', 'Swimming']),
            'duration_min' => fake()->numberBetween(15, 90),
            'calories_kcal' => fake()->randomFloat(1, 80, 800),
            'source' => ExerciseSource::Manual,
            'meta' => null,
        ];
    }
}
