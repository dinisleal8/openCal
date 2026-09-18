<?php

use App\Models\Goal;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $user->goal()->create(Goal::factory()->raw(['user_id' => $user->id]));
    $this->actingAs($user);

    $response = $this->get(route('dashboard', ['date' => now()->toDateString()]));
    $response->assertOk();
});

test('dashboard defaults to today when no date is provided', function () {
    $user = User::factory()->create();
    $user->goal()->create(Goal::factory()->raw(['user_id' => $user->id]));
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});
