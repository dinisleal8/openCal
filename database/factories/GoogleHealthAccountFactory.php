<?php

namespace Database\Factories;

use App\Models\GoogleHealthAccount;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GoogleHealthAccount>
 */
class GoogleHealthAccountFactory extends Factory
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
            'google_user_id' => fake()->uuid(),
            'access_token' => 'test-access-token',
            'refresh_token' => 'test-refresh-token',
            'expires_at' => now()->addHour(),
            'scope' => 'https://www.googleapis.com/auth/googlehealth.activity_and_fitness.readonly',
            'last_synced_at' => null,
            'last_sync_error' => null,
        ];
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->subMinutes(10),
        ]);
    }
}
