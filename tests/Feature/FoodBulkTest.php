<?php

use App\Models\FoodEntry;
use App\Models\Photo;
use App\Models\User;

test('guests cannot bulk store food entries', function () {
    $this->post(route('food.bulk'), [])->assertRedirect(route('login'));
});

test('a user can add several detected items at once', function () {
    $user = User::factory()->create();
    $photo = Photo::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->post(route('food.bulk'), [
        'date' => now()->toDateString(),
        'meal_type' => 'lunch',
        'photo_id' => $photo->id,
        'items' => [
            [
                'name' => 'White rice',
                'serving_description' => '1/2 cup',
                'calories_kcal' => 130,
                'protein_g' => 2.7,
                'carbs_g' => 28,
                'fat_g' => 0.3,
            ],
            [
                'name' => 'Chicken thigh',
                'serving_description' => '1 piece',
                'calories_kcal' => 280,
                'protein_g' => 23,
                'carbs_g' => 0,
                'fat_g' => 20,
            ],
        ],
    ]);

    $response->assertRedirect();

    expect(FoodEntry::where('user_id', $user->id)->count())->toBe(2);

    $this->assertDatabaseHas('food_entries', [
        'user_id' => $user->id,
        'name' => 'White rice',
        'meal_type' => 'lunch',
        'photo_id' => $photo->id,
        'source' => 'ai_photo',
    ]);
});

test('bulk storing without a photo is marked as manual', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('food.bulk'), [
        'date' => now()->toDateString(),
        'meal_type' => 'dinner',
        'items' => [
            [
                'name' => 'Soup',
                'calories_kcal' => 90,
                'protein_g' => 4,
                'carbs_g' => 12,
                'fat_g' => 3,
            ],
        ],
    ])->assertRedirect();

    $this->assertDatabaseHas('food_entries', [
        'user_id' => $user->id,
        'name' => 'Soup',
        'photo_id' => null,
        'source' => 'manual',
    ]);
});

test('bulk store requires at least one item', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('food.bulk'), [
        'date' => now()->toDateString(),
        'meal_type' => 'lunch',
        'items' => [],
    ])->assertSessionHasErrors('items');
});
