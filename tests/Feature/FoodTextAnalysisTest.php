<?php

use App\Models\User;
use App\Services\FoodPhotoAnalyzer;

test('authenticated user can analyze a text description', function () {
    $mock = Mockery::mock(FoodPhotoAnalyzer::class);
    $mock->shouldReceive('analyzeText')->once()->with('scrambled eggs')->andReturn([
        [
            'name' => 'Scrambled Eggs',
            'calories' => 180,
            'protein_g' => 12.0,
            'carbs_g' => 1.0,
            'fat_g' => 14.0,
            'serving_description' => '2 eggs',
        ],
    ]);
    $this->app->instance(FoodPhotoAnalyzer::class, $mock);

    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->postJson(route('api.food.analyze-text'), [
            'description' => 'scrambled eggs',
        ]);

    $response->assertOk()
        ->assertJsonStructure([
            'analysis' => [
                '*' => ['name', 'calories', 'protein_g', 'carbs_g', 'fat_g', 'serving_description'],
            ],
        ]);
});

test('text analysis returns null when the analyzer fails', function () {
    $mock = Mockery::mock(FoodPhotoAnalyzer::class);
    $mock->shouldReceive('analyzeText')->once()->andReturn(null);
    $this->app->instance(FoodPhotoAnalyzer::class, $mock);

    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->postJson(route('api.food.analyze-text'), [
            'description' => 'scrambled eggs',
        ]);

    $response->assertOk()->assertJson([
        'analysis' => null,
    ]);
});

test('text analysis requires a description', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson(route('api.food.analyze-text'), [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('description');
});

test('unauthenticated user cannot analyze text', function () {
    $this->postJson(route('api.food.analyze-text'), [
        'description' => 'scrambled eggs',
    ])->assertUnauthorized();
});
