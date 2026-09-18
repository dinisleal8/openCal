<?php

use App\Models\Goal;
use App\Models\User;

function goalPayload(array $overrides = []): array
{
    return array_merge([
        'sex' => 'male',
        'birth_date' => '1990-01-01',
        'height_cm' => 180,
        'activity_level' => 'moderate',
        'goal_type' => 'lose',
        'weekly_rate_kg' => 0.5,
        'target_weight_kg' => 78,
        'water_goal_ml' => 2500,
        'protein_pct' => 30,
        'carbs_pct' => 40,
        'fat_pct' => 30,
        'include_band_calories' => false,
        'initial_weight_kg' => 85,
    ], $overrides);
}

test('onboarding creates the goal and the initial weigh-in', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('goal.store'), goalPayload());

    $response->assertRedirect(route('dashboard'));

    $goal = $user->fresh()->goal;
    expect($goal)->not->toBeNull()
        ->and($goal->height_cm)->toBe(180)
        ->and($goal->goal_type->value)->toBe('lose')
        ->and((float) $goal->weekly_rate_kg)->toBe(0.5)
        ->and($user->fresh()->currentWeightKg())->toBe(85.0);
});

test('onboarding can also set the locale', function () {
    $user = User::factory()->create(['locale' => 'en']);

    $this->actingAs($user)->post(route('goal.store'), goalPayload(['locale' => 'pt']));

    expect($user->fresh()->locale)->toBe('pt');
});

test('an unsupported locale is rejected', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('goal.store'), goalPayload(['locale' => 'de']))
        ->assertSessionHasErrors('locale');
});

test('macros must add up to one hundred percent', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('goal.store'), goalPayload(['fat_pct' => 40]))
        ->assertSessionHasErrors('protein_pct');
});

test('weekly rate is required when the goal is not maintenance', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('goal.store'), goalPayload(['weekly_rate_kg' => null]))
        ->assertSessionHasErrors('weekly_rate_kg');
});

test('weekly rate is forced to zero for maintenance goals', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('goal.store'), goalPayload([
        'goal_type' => 'maintain',
        'weekly_rate_kg' => null,
    ]));

    expect((float) $user->fresh()->goal->weekly_rate_kg)->toBe(0.0);
});

test('initial weight is required when creating the goal', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('goal.store'), goalPayload(['initial_weight_kg' => null]))
        ->assertSessionHasErrors('initial_weight_kg');
});

test('users younger than thirteen are rejected', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('goal.store'), goalPayload(['birth_date' => now()->subYears(10)->toDateString()]))
        ->assertSessionHasErrors('birth_date');
});

test('a second goal cannot be created', function () {
    $user = User::factory()->create();
    Goal::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->post(route('goal.store'), goalPayload())
        ->assertStatus(409);
});

test('the goal can be updated', function () {
    $user = User::factory()->create();
    Goal::factory()->create(['user_id' => $user->id, 'water_goal_ml' => 2000]);

    $response = $this->actingAs($user)->put(route('goal.update'), goalPayload([
        'water_goal_ml' => 3000,
        'goal_type' => 'gain',
    ]));

    $response->assertRedirect(route('goal.edit'));
    expect($user->fresh()->goal->water_goal_ml)->toBe(3000)
        ->and($user->fresh()->goal->goal_type->value)->toBe('gain');
});

test('updating without an existing goal fails', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->put(route('goal.update'), goalPayload(['initial_weight_kg' => null]))
        ->assertNotFound();
});

test('the goal settings page renders with targets', function () {
    $user = User::factory()->create();
    Goal::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->get(route('goal.edit'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('goal/edit')
            ->has('goal')
            ->has('targets'));
});

test('the goal settings page redirects to onboarding without a goal', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('goal.edit'))
        ->assertRedirect(route('onboarding'));
});

test('goal validation rejects an invalid activity level', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('goal.store'), goalPayload(['activity_level' => 'extreme']))
        ->assertSessionHasErrors('activity_level');
});
