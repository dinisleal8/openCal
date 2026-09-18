<?php

use App\Models\User;

test('authenticated users can switch to a supported locale', function () {
    $user = User::factory()->create(['locale' => 'en']);

    $response = $this->actingAs($user)->put(route('locale.update', 'pt'));

    $response->assertRedirect();
    expect($user->fresh()->locale)->toBe('pt');
});

test('switching to an unsupported locale fails', function () {
    $user = User::factory()->create(['locale' => 'en']);

    $this->actingAs($user)->put(route('locale.update', 'de'))->assertNotFound();

    expect($user->fresh()->locale)->toBe('en');
});

test('the request locale follows the user locale', function () {
    $user = User::factory()->create(['locale' => 'pt']);

    $this->actingAs($user)->get(route('dashboard'));

    expect(app()->getLocale())->toBe('pt');
});

test('guests cannot switch locale', function () {
    $this->put(route('locale.update', 'pt'))->assertRedirect(route('login'));
});
