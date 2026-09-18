<?php

use App\Models\Photo;
use App\Models\User;
use App\Services\FoodPhotoAnalyzer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('authenticated user can upload a photo and get analysis', function () {
    Storage::fake('local');

    $mockGemini = Mockery::mock(FoodPhotoAnalyzer::class);
    $mockGemini->shouldReceive('analyzePhoto')->once()->andReturn([
        [
            'name' => 'Grilled Chicken',
            'calories' => 250,
            'protein_g' => 35.0,
            'carbs_g' => 0.0,
            'fat_g' => 12.0,
            'serving_description' => '1 breast (150g)',
        ],
    ]);
    $this->app->instance(FoodPhotoAnalyzer::class, $mockGemini);

    $user = User::factory()->create();

    $photo = UploadedFile::fake()->image('meal.jpg', 800, 600)->size(100);

    $response = $this->actingAs($user)
        ->postJson(route('api.photo.analyze'), [
            'photo' => $photo,
        ]);

    $response->assertOk()
        ->assertJsonStructure([
            'photo_id',
            'analysis' => [
                '*' => ['name', 'calories', 'protein_g', 'carbs_g', 'fat_g', 'serving_description'],
            ],
        ]);

    $this->assertDatabaseHas('photos', [
        'user_id' => $user->id,
        'mime' => 'image/jpeg',
    ]);
});

test('unauthenticated user cannot access the photo analysis endpoint', function () {
    $photo = UploadedFile::fake()->image('meal.jpg', 800, 600)->size(100);

    $response = $this->postJson(route('api.photo.analyze'), [
        'photo' => $photo,
    ]);

    $response->assertUnauthorized();
});

test('invalid file type is rejected', function () {
    Storage::fake('local');

    $user = User::factory()->create();

    $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

    $response = $this->actingAs($user)
        ->postJson(route('api.photo.analyze'), [
            'photo' => $file,
        ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors('photo');
});

test('missing photo field returns validation error', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->postJson(route('api.photo.analyze'), []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors('photo');
});

test('gemini service failure returns appropriate error', function () {
    Storage::fake('local');

    $mockGemini = Mockery::mock(FoodPhotoAnalyzer::class);
    $mockGemini->shouldReceive('analyzePhoto')->once()->andReturn(null);
    $this->app->instance(FoodPhotoAnalyzer::class, $mockGemini);

    $user = User::factory()->create();

    $photo = UploadedFile::fake()->image('meal.jpg', 800, 600)->size(100);

    $response = $this->actingAs($user)
        ->postJson(route('api.photo.analyze'), [
            'photo' => $photo,
        ]);

    $response->assertOk()
        ->assertJson([
            'analysis' => null,
        ]);

    $this->assertDatabaseHas('photos', [
        'user_id' => $user->id,
    ]);
});

test('owner can stream their stored photo', function () {
    Storage::fake('local');

    $user = User::factory()->create();

    $photo = Photo::factory()->create([
        'user_id' => $user->id,
        'path' => 'photos/'.$user->id.'/meal.jpg',
        'mime' => 'image/jpeg',
    ]);

    Storage::disk('local')->put($photo->path, 'fake-image-bytes');

    $this->actingAs($user)
        ->get(route('photos.show', $photo))
        ->assertOk()
        ->assertHeader('Content-Type', 'image/jpeg');
});

test('non-owner cannot stream another users photo', function () {
    Storage::fake('local');

    $owner = User::factory()->create();
    $other = User::factory()->create();

    $photo = Photo::factory()->create([
        'user_id' => $owner->id,
        'path' => 'photos/'.$owner->id.'/meal.jpg',
    ]);

    Storage::disk('local')->put($photo->path, 'fake-image-bytes');

    $this->actingAs($other)
        ->get(route('photos.show', $photo))
        ->assertForbidden();
});
