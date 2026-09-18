<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
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
            'barcode' => fake()->ean13(),
            'name' => ucfirst(fake()->words(2, true)),
            'brand' => fake()->company(),
            'calories_kcal_per_100g' => fake()->randomFloat(1, 20, 600),
            'protein_g_per_100g' => fake()->randomFloat(1, 0, 30),
            'carbs_g_per_100g' => fake()->randomFloat(1, 0, 80),
            'fat_g_per_100g' => fake()->randomFloat(1, 0, 40),
            'source' => 'barcode',
            'meta' => null,
        ];
    }

    public function custom(): static
    {
        return $this->state(fn (array $attributes) => [
            'barcode' => null,
            'source' => 'custom',
        ]);
    }
}
