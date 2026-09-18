<?php

use App\Models\ExerciseLog;
use App\Models\FoodEntry;
use App\Models\Goal;
use App\Models\User;
use App\Models\WaterLog;
use App\Models\WeighIn;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $this->get(route('history'))->assertRedirect(route('login'));
});

test('authenticated users can view their history', function () {
    $user = User::factory()->create();
    $user->goal()->create(Goal::factory()->raw(['user_id' => $user->id]));

    FoodEntry::factory()->create(['user_id' => $user->id, 'date' => today()->toDateString()]);
    ExerciseLog::factory()->create(['user_id' => $user->id, 'date' => today()->toDateString()]);
    WaterLog::factory()->create(['user_id' => $user->id, 'logged_at' => now()]);
    WeighIn::factory()->create(['user_id' => $user->id, 'date' => today()->toDateString()]);

    $this->actingAs($user)
        ->get(route('history'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('history')
            ->where('dayCount', 7)
            ->has('days', 7)
            ->has('weightHistory')
        );
});

test('history can display the last 30 days', function () {
    $user = User::factory()->create();
    $user->goal()->create(Goal::factory()->raw(['user_id' => $user->id]));

    $this->actingAs($user)
        ->get(route('history', ['days' => 30]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('history')
            ->where('dayCount', 30)
            ->has('days', 30)
        );
});
