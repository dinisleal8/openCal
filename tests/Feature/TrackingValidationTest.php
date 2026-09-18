<?php

use App\Models\User;

test('food entry validation: missing required fields returns 422', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->postJson(route('food.store'), []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['date', 'meal_type', 'name', 'calories_kcal', 'protein_g', 'carbs_g', 'fat_g']);
});

test('food entry validation: invalid calorie values rejected', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->postJson(route('food.store'), [
            'date' => now()->toDateString(),
            'meal_type' => 'lunch',
            'name' => 'Test Food',
            'calories_kcal' => -100,
            'protein_g' => 10,
            'carbs_g' => 20,
            'fat_g' => 5,
        ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors('calories_kcal');

    $response = $this->actingAs($user)
        ->postJson(route('food.store'), [
            'date' => now()->toDateString(),
            'meal_type' => 'lunch',
            'name' => 'Test Food',
            'calories_kcal' => 6000,
            'protein_g' => 10,
            'carbs_g' => 20,
            'fat_g' => 5,
        ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors('calories_kcal');
});

test('water log validation: missing amount returns 422', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->postJson(route('water.store'), [
            'date' => now()->toDateString(),
        ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors('amount_ml');
});

test('water log validation: amount out of range rejected', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->postJson(route('water.store'), [
            'date' => now()->toDateString(),
            'amount_ml' => 0,
        ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors('amount_ml');

    $response = $this->actingAs($user)
        ->postJson(route('water.store'), [
            'date' => now()->toDateString(),
            'amount_ml' => 3000,
        ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors('amount_ml');
});

test('weight entry validation: missing weight returns 422', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->postJson(route('weight.store'), [
            'date' => now()->toDateString(),
        ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors('weight_kg');
});

test('weight entry validation: weight out of range rejected', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->postJson(route('weight.store'), [
            'date' => now()->toDateString(),
            'weight_kg' => 20,
        ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors('weight_kg');
});

test('exercise entry validation: missing required fields returns 422', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->postJson(route('exercise.store'), []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['date', 'name', 'calories_kcal']);
});

test('exercise entry validation: invalid calorie values rejected', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->postJson(route('exercise.store'), [
            'date' => now()->toDateString(),
            'name' => 'Running',
            'calories_kcal' => -50,
        ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors('calories_kcal');
});

test('food entry with valid data is accepted', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->postJson(route('food.store'), [
            'date' => now()->toDateString(),
            'meal_type' => 'lunch',
            'name' => 'Grilled Chicken Salad',
            'calories_kcal' => 350,
            'protein_g' => 30,
            'carbs_g' => 15,
            'fat_g' => 18,
        ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('food_entries', [
        'user_id' => $user->id,
        'name' => 'Grilled Chicken Salad',
    ]);
});

test('saving weight twice for the same day updates the existing entry', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('weight.store'), [
            'date' => now()->toDateString(),
            'weight_kg' => 80,
        ])
        ->assertRedirect();

    $this->actingAs($user)
        ->post(route('weight.store'), [
            'date' => now()->toDateString(),
            'weight_kg' => 79,
        ])
        ->assertRedirect();

    expect($user->weighIns()->count())->toBe(1)
        ->and((float) $user->weighIns()->first()->weight_kg)->toBe(79.0);
});
