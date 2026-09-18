<?php

use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Http;

function productPayload(array $overrides = []): array
{
    return array_merge([
        'barcode' => '5901234123457',
        'name' => 'Test Product',
        'brand' => 'Test Brand',
        'calories_kcal_per_100g' => 250.0,
        'protein_g_per_100g' => 10.0,
        'carbs_g_per_100g' => 30.0,
        'fat_g_per_100g' => 12.0,
        'serving_description' => '100g',
    ], $overrides);
}

test('owner can create a product', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson(route('products.store'), productPayload());

    $response->assertCreated()->assertJsonFragment([
        'barcode' => '5901234123457',
        'name' => 'Test Product',
    ]);
    $this->assertDatabaseHas('products', [
        'user_id' => $user->id,
        'barcode' => '5901234123457',
        'name' => 'Test Product',
    ]);
});

test('owner can list products', function () {
    $user = User::factory()->create();
    Product::factory()->count(3)->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->getJson(route('products.index'));

    $response->assertOk()->assertJsonCount(3, 'data');
});

test('owner can view a single product', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->getJson(route('products.show', $product));

    $response->assertOk()->assertJsonFragment([
        'id' => $product->id,
        'name' => $product->name,
    ]);
});

test('owner can update a product', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->putJson(route('products.update', $product), productPayload([
        'name' => 'Updated Product',
    ]));

    $response->assertOk()->assertJsonFragment(['name' => 'Updated Product']);
    expect($product->fresh()->name)->toBe('Updated Product');
});

test('owner can delete a product', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->deleteJson(route('products.destroy', $product));

    $response->assertOk();
    $this->assertDatabaseMissing('products', ['id' => $product->id]);
});

test('non-owner cannot view another users product', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $product = Product::factory()->create(['user_id' => $owner->id]);

    $response = $this->actingAs($other)->getJson(route('products.show', $product));

    $response->assertForbidden();
});

test('non-owner cannot update another users product', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $product = Product::factory()->create(['user_id' => $owner->id]);

    $response = $this->actingAs($other)->putJson(route('products.update', $product), productPayload([
        'name' => 'Hacked',
    ]));

    $response->assertForbidden();
});

test('non-owner cannot delete another users product', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $product = Product::factory()->create(['user_id' => $owner->id]);

    $response = $this->actingAs($other)->deleteJson(route('products.destroy', $product));

    $response->assertForbidden();
    $this->assertDatabaseHas('products', ['id' => $product->id]);
});

test('owner can look up a barcode', function () {
    $user = User::factory()->create();

    Http::fake([
        'world.openfoodfacts.org/api/v2/product/5901234123457.json' => Http::response([
            'status' => 1,
            'product' => [
                'product_name' => 'Coca-Cola',
                'brands' => 'Coca-Cola',
                'serving_size' => '330ml',
                'nutriments' => [
                    'energy-kcal_100g' => 42.0,
                    'proteins_100g' => 0.0,
                    'carbohydrates_100g' => 10.6,
                    'fat_100g' => 0.0,
                ],
            ],
        ]),
    ]);

    $response = $this->actingAs($user)->getJson(route('products.barcode', '5901234123457'));

    $response->assertOk()->assertJson([
        'name' => 'Coca-Cola',
        'brand' => 'Coca-Cola',
        'barcode' => '5901234123457',
        'calories_kcal_per_100g' => 42.0,
        'protein_g_per_100g' => 0.0,
        'carbs_g_per_100g' => 10.6,
        'fat_g_per_100g' => 0.0,
        'serving_description' => '330ml',
    ]);

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'world.openfoodfacts.org');
    });
});

test('barcode lookup returns 404 for unknown barcode', function () {
    $user = User::factory()->create();

    Http::fake([
        'world.openfoodfacts.org/api/v2/product/0000000000000.json' => Http::response([
            'status' => 0,
        ], 200),
    ]);

    $response = $this->actingAs($user)->getJson(route('products.barcode', '0000000000000'));

    $response->assertNotFound()->assertJson([
        'message' => 'Product not found for barcode: 0000000000000',
    ]);
});

test('unauthenticated user cannot access products', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['user_id' => $user->id]);

    $this->getJson(route('products.index'))->assertUnauthorized();
    $this->getJson(route('products.show', $product))->assertUnauthorized();
    $this->postJson(route('products.store'), productPayload())->assertUnauthorized();
});
