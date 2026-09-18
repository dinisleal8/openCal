<?php

use App\Enums\ActivityLevel;
use App\Enums\GoalType;
use App\Enums\Sex;
use App\Models\Goal;
use App\Models\User;
use App\Services\DailyTargetsService;
use Illuminate\Support\Carbon;

beforeEach(function () {
    Carbon::setTestNow('2026-01-01 12:00:00');
});

afterEach(function () {
    Carbon::setTestNow();
});

function makeGoal(array $attributes = []): Goal
{
    return Goal::factory()->create(array_merge([
        'sex' => Sex::Male,
        'birth_date' => '1996-01-01',
        'height_cm' => 180,
        'activity_level' => ActivityLevel::Moderate,
        'goal_type' => GoalType::Lose,
        'weekly_rate_kg' => 0.50,
        'protein_pct' => 30,
        'carbs_pct' => 40,
        'fat_pct' => 30,
        'water_goal_ml' => 2500,
    ], $attributes));
}

test('bmr uses mifflin-st jeor for men', function () {
    $goal = makeGoal();
    $service = new DailyTargetsService;

    // 10*80 + 6.25*180 - 5*30 + 5 = 1780
    expect($service->bmr($goal, 80.0))->toBe(1780.0);
});

test('bmr uses mifflin-st jeor for women', function () {
    $goal = makeGoal(['sex' => Sex::Female, 'height_cm' => 165]);
    $service = new DailyTargetsService;

    // 10*60 + 6.25*165 - 5*30 - 161 = 1320.25
    expect($service->bmr($goal, 60.0))->toBe(1320.25);
});

test('tdee multiplies bmr by the activity level factor', function () {
    $goal = makeGoal(['activity_level' => ActivityLevel::Sedentary]);
    $service = new DailyTargetsService;

    expect($service->tdee($goal, 80.0))->toBe(1780.0 * 1.2);
});

test('targets apply the weekly rate deficit when losing weight', function () {
    $goal = makeGoal();
    $service = new DailyTargetsService;

    $targets = $service->targets($goal, 80.0);

    // TDEE 2759 - 550 (0.5 kg/week) = 2209
    expect($targets->tdee)->toBe(2759)
        ->and($targets->calorieTarget)->toBe(2209)
        ->and($targets->proteinG)->toBe(166)
        ->and($targets->carbsG)->toBe(221)
        ->and($targets->fatG)->toBe(74)
        ->and($targets->waterGoalMl)->toBe(2500);
});

test('targets equal tdee when maintaining weight', function () {
    $goal = makeGoal(['goal_type' => GoalType::Maintain]);
    $service = new DailyTargetsService;

    expect($service->targets($goal, 80.0)->calorieTarget)->toBe(2759);
});

test('targets apply the weekly rate surplus when gaining weight', function () {
    $goal = makeGoal(['goal_type' => GoalType::Gain, 'weekly_rate_kg' => 0.50]);
    $service = new DailyTargetsService;

    // TDEE 2759 + 550 = 3309
    expect($service->targets($goal, 80.0)->calorieTarget)->toBe(3309);
});

test('calorie target never drops below the safety minimum', function () {
    $goal = makeGoal([
        'sex' => Sex::Female,
        'height_cm' => 165,
        'activity_level' => ActivityLevel::Sedentary,
        'weekly_rate_kg' => 1.00,
    ]);
    $service = new DailyTargetsService;

    // TDEE ~1584 - 1100 = 484 -> clamped to 1200 (female minimum)
    expect($service->targets($goal, 60.0)->calorieTarget)
        ->toBe(DailyTargetsService::MIN_CALORIES_FEMALE);
});

test('surplus is capped at the maximum daily surplus', function () {
    $goal = makeGoal(['goal_type' => GoalType::Gain, 'weekly_rate_kg' => 2.00]);
    $service = new DailyTargetsService;

    $targets = $service->targets($goal, 80.0);

    // 2 kg/week would be +2200/day, capped at +1000
    expect($targets->calorieTarget)->toBe($targets->tdee + 1000);
});

test('user current weight comes from the latest weigh-in', function () {
    $user = User::factory()->create();
    $user->weighIns()->createMany([
        ['date' => '2025-12-01', 'weight_kg' => 82.5],
        ['date' => '2026-01-01', 'weight_kg' => 80.1],
        ['date' => '2025-12-15', 'weight_kg' => 81.3],
    ]);

    expect($user->currentWeightKg())->toBe(80.1);
});

test('user current weight is null without weigh-ins', function () {
    expect(User::factory()->create()->currentWeightKg())->toBeNull();
});
