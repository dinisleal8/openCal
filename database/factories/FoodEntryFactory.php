<?php

namespace Database\Factories;

use App\Enums\FoodSource;
use App\Enums\MealType;
use App\Models\FoodEntry;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FoodEntry>
 */
class FoodEntryFactory extends Factory
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
            'photo_id' => null,
            'date' => now()->toDateString(),
            'meal_type' => fake()->randomElement(MealType::cases()),
            'name' => ucfirst(fake()->words(2, true)),
            'serving_description' => null,
            'calories_kcal' => fake()->randomFloat(1, 80, 900),
            'protein_g' => fake()->randomFloat(1, 2, 50),
            'carbs_g' => fake()->randomFloat(1, 5, 120),
            'fat_g' => fake()->randomFloat(1, 1, 40),
            'source' => FoodSource::Manual,
            'barcode' => null,
            'meta' => null,
        ];
    }

    public function fromAiPhoto(): static
    {
        return $this->state(fn (array $attributes) => [
            'source' => FoodSource::AiPhoto,
            'photo_id' => PhotoFactory::new(),
            'meta' => ['ai' => true, 'confidence' => 0.9],
        ]);
    }

    public function fromBarcode(): static
    {
        return $this->state(fn (array $attributes) => [
            'source' => FoodSource::Barcode,
            'barcode' => fake()->ean13(),
        ]);
    }
}
