<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\WaterLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WaterLog>
 */
class WaterLogFactory extends Factory
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
            'logged_at' => now(),
            'amount_ml' => 250,
        ];
    }
}
