<?php

use App\Enums\ActivityLevel;
use App\Enums\GoalType;
use App\Enums\Sex;
use App\Models\Goal;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('has a belongsTo user relationship', function () {
    $goal = Goal::factory()->create();

    expect($goal->user)->toBeInstanceOf(User::class)
        ->and($goal->user->id)->toBe($goal->user_id);
});

it('returns the correct age from birth_date', function () {
    $goal = Goal::factory()->make([
        'birth_date' => now()->subYears(25)->toDateString(),
    ]);

    expect($goal->age())->toBe(25);
});

it('casts sex to Sex enum', function () {
    $goal = Goal::factory()->make(['sex' => Sex::Male]);

    expect($goal->sex)->toBeInstanceOf(Sex::class)
        ->and($goal->sex)->toBe(Sex::Male);
});

it('casts activity_level to ActivityLevel enum', function () {
    $goal = Goal::factory()->make(['activity_level' => ActivityLevel::VeryActive]);

    expect($goal->activity_level)->toBeInstanceOf(ActivityLevel::class)
        ->and($goal->activity_level)->toBe(ActivityLevel::VeryActive);
});

it('casts goal_type to GoalType enum', function () {
    $goal = Goal::factory()->make(['goal_type' => GoalType::Gain]);

    expect($goal->goal_type)->toBeInstanceOf(GoalType::class)
        ->and($goal->goal_type)->toBe(GoalType::Gain);
});

it('casts birth_date to Carbon instance', function () {
    $goal = Goal::factory()->make();

    expect($goal->birth_date)->toBeInstanceOf(CarbonImmutable::class);
});

it('casts weekly_rate_kg to decimal', function () {
    $goal = Goal::factory()->make(['weekly_rate_kg' => '0.75']);

    expect($goal->weekly_rate_kg)->toBe('0.75');
});

it('casts include_band_calories to boolean', function () {
    $goal = Goal::factory()->make(['include_band_calories' => true]);

    expect($goal->include_band_calories)->toBeTrue();

    $goal = Goal::factory()->make(['include_band_calories' => false]);

    expect($goal->include_band_calories)->toBeFalse();
});

it('has fillable attributes', function () {
    $goal = new Goal;

    expect($goal->getFillable())->toContain(
        'user_id',
        'sex',
        'birth_date',
        'height_cm',
        'activity_level',
        'goal_type',
        'weekly_rate_kg',
        'protein_pct',
        'carbs_pct',
        'fat_pct',
    );
});

it('can be created with factory', function () {
    $goal = Goal::factory()->create();

    expect($goal->id)->toBeInt()
        ->and($goal->sex)->toBeInstanceOf(Sex::class)
        ->and($goal->activity_level)->toBeInstanceOf(ActivityLevel::class)
        ->and($goal->goal_type)->toBeInstanceOf(GoalType::class)
        ->and($goal->height_cm)->toBeInt()
        ->and($goal->water_goal_ml)->toBeInt();
});

it('can create female goal via factory state', function () {
    $goal = Goal::factory()->female()->create();

    expect($goal->sex)->toBe(Sex::Female);
});

it('can create maintain goal via factory state', function () {
    $goal = Goal::factory()->maintain()->create();

    expect($goal->goal_type)->toBe(GoalType::Maintain);
});

it('can create gain goal via factory state', function () {
    $goal = Goal::factory()->gain()->create();

    expect($goal->goal_type)->toBe(GoalType::Gain);
});
