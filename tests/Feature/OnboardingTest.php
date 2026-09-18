<?php

use App\Models\Goal;
use App\Models\User;

test('guests are redirected away from onboarding', function () {
    $this->get(route('onboarding'))->assertRedirect(route('login'));
});

test('users without a goal see the onboarding wizard', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('onboarding'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('onboarding'));
});

test('users with a goal are redirected from onboarding to the dashboard', function () {
    $user = User::factory()->create();
    Goal::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->get(route('onboarding'))
        ->assertRedirect(route('dashboard'));
});

test('the dashboard redirects to onboarding when the user has no goal', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('onboarding'));
});

test('the dashboard renders with computed targets for users with a goal', function () {
    $user = User::factory()->create();
    Goal::factory()->create(['user_id' => $user->id]);
    $user->weighIns()->create(['date' => today()->toDateString(), 'weight_kg' => 85.0]);

    $this->actingAs($user)
        ->get(route('dashboard', ['date' => now()->toDateString()]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('dashboard')
            ->has('targets')
            ->where('targets.calorie_target', fn ($value) => $value > 0)
            ->has('goal'));
});
