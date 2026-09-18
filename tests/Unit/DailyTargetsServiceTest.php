<?php

use App\Enums\ActivityLevel;
use App\Enums\GoalType;
use App\Enums\Sex;
use App\Models\Goal;
use App\Services\DailyTargets;
use App\Services\DailyTargetsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->service = new DailyTargetsService;
});

function makeServiceGoal(array $overrides = []): Goal
{
    $defaults = [
        'sex' => Sex::Male,
        'birth_date' => now()->subYears(30)->toDateString(),
        'height_cm' => 180,
        'activity_level' => ActivityLevel::Moderate,
        'goal_type' => GoalType::Maintain,
        'weekly_rate_kg' => '0.50',
        'target_weight_kg' => '75.00',
        'water_goal_ml' => 2500,
        'protein_pct' => 30,
        'carbs_pct' => 40,
        'fat_pct' => 30,
        'include_band_calories' => false,
    ];

    return Goal::make(array_merge($defaults, $overrides));
}

it('calculates BMR for males using Mifflin-St Jeor', function () {
    // 30yo male, 180cm, 80kg
    // (10 * 80) + (6.25 * 180) - (5 * 30) + 5 = 800 + 1125 - 150 + 5 = 1780
    $goal = makeServiceGoal(['height_cm' => 180]);

    expect($this->service->bmr($goal, 80))->toBe(1780.0);
});

it('calculates BMR for females using Mifflin-St Jeor', function () {
    // 30yo female, 165cm, 60kg
    // (10 * 60) + (6.25 * 165) - (5 * 30) - 161 = 600 + 1031.25 - 150 - 161 = 1320.25
    $goal = makeServiceGoal([
        'sex' => Sex::Female,
        'height_cm' => 165,
    ]);

    expect($this->service->bmr($goal, 60))->toBe(1320.25);
});

it('calculates TDEE with sedentary multiplier', function () {
    $goal = makeServiceGoal([
        'height_cm' => 180,
        'activity_level' => ActivityLevel::Sedentary,
    ]);

    // BMR = 1780, sedentary multiplier = 1.2
    expect($this->service->tdee($goal, 80))->toBe(1780.0 * 1.2);
});

it('calculates TDEE with very active multiplier', function () {
    $goal = makeServiceGoal([
        'height_cm' => 180,
        'activity_level' => ActivityLevel::VeryActive,
    ]);

    // BMR = 1780, very active multiplier = 1.9
    expect($this->service->tdee($goal, 80))->toBe(1780.0 * 1.9);
});

it('returns correct TDEE for each activity level', function () {
    $bmr = 1780.0;

    $goal = makeServiceGoal(['height_cm' => 180, 'activity_level' => ActivityLevel::Sedentary]);
    expect($this->service->tdee($goal, 80))->toBe($bmr * 1.2);

    $goal = makeServiceGoal(['height_cm' => 180, 'activity_level' => ActivityLevel::Light]);
    expect($this->service->tdee($goal, 80))->toBe($bmr * 1.375);

    $goal = makeServiceGoal(['height_cm' => 180, 'activity_level' => ActivityLevel::Moderate]);
    expect($this->service->tdee($goal, 80))->toBe($bmr * 1.55);

    $goal = makeServiceGoal(['height_cm' => 180, 'activity_level' => ActivityLevel::Active]);
    expect($this->service->tdee($goal, 80))->toBe($bmr * 1.725);

    $goal = makeServiceGoal(['height_cm' => 180, 'activity_level' => ActivityLevel::VeryActive]);
    expect($this->service->tdee($goal, 80))->toBe($bmr * 1.9);
});

it('computes daily targets for a weight loss goal', function () {
    $goal = makeServiceGoal([
        'height_cm' => 180,
        'activity_level' => ActivityLevel::Sedentary,
        'goal_type' => GoalType::Lose,
        'weekly_rate_kg' => '0.50',
    ]);

    $targets = $this->service->targets($goal, 80);

    expect($targets)->toBeInstanceOf(DailyTargets::class);

    $bmr = 1780.0;
    $tdee = $bmr * 1.2; // 2136
    $dailyDelta = -(0.5 * 7700 / 7); // -550
    $calorieTarget = (int) round(max(1500, min($tdee + $dailyDelta, $tdee + 1000)));

    expect($targets->bmr)->toBe((int) round($bmr))
        ->and($targets->tdee)->toBe((int) round($tdee))
        ->and($targets->calorieTarget)->toBe($calorieTarget)
        ->and($targets->waterGoalMl)->toBe(2500);
});

it('computes macro grams from calorie target and percentages', function () {
    $goal = makeServiceGoal([
        'height_cm' => 180,
        'activity_level' => ActivityLevel::Moderate,
        'goal_type' => GoalType::Maintain,
        'protein_pct' => 30,
        'carbs_pct' => 40,
        'fat_pct' => 30,
    ]);

    $targets = $this->service->targets($goal, 80);

    $expectedProtein = (int) round($targets->calorieTarget * 30 / 100 / 4);
    $expectedCarbs = (int) round($targets->calorieTarget * 40 / 100 / 4);
    $expectedFat = (int) round($targets->calorieTarget * 30 / 100 / 9);

    expect($targets->proteinG)->toBe($expectedProtein)
        ->and($targets->carbsG)->toBe($expectedCarbs)
        ->and($targets->fatG)->toBe($expectedFat);
});

it('clips calorie target to minimum for males', function () {
    $goal = makeServiceGoal([
        'height_cm' => 170,
        'activity_level' => ActivityLevel::Sedentary,
        'goal_type' => GoalType::Lose,
        'weekly_rate_kg' => '1.00',
    ]);

    $targets = $this->service->targets($goal, 45);

    expect($targets->calorieTarget)->toBeGreaterThanOrEqual(1500);
});

it('clips calorie target to minimum for females', function () {
    $goal = makeServiceGoal([
        'sex' => Sex::Female,
        'height_cm' => 155,
        'activity_level' => ActivityLevel::Sedentary,
        'goal_type' => GoalType::Lose,
        'weekly_rate_kg' => '1.00',
    ]);

    $targets = $this->service->targets($goal, 40);

    expect($targets->calorieTarget)->toBeGreaterThanOrEqual(1200);
});

it('caps surplus at max for weight gain goals', function () {
    $goal = makeServiceGoal([
        'height_cm' => 180,
        'activity_level' => ActivityLevel::Moderate,
        'goal_type' => GoalType::Gain,
        'weekly_rate_kg' => '1.00',
    ]);

    $targets = $this->service->targets($goal, 80);

    $tdee = $this->service->tdee($goal, 80);

    expect($targets->calorieTarget)->toBeLessThanOrEqual((int) round($tdee + 1000));
});

it('returns TDEE as calorie target for maintenance goals', function () {
    $goal = makeServiceGoal([
        'height_cm' => 180,
        'activity_level' => ActivityLevel::Moderate,
        'goal_type' => GoalType::Maintain,
    ]);

    $targets = $this->service->targets($goal, 80);

    $tdee = $this->service->tdee($goal, 80);

    expect($targets->calorieTarget)->toBe((int) round($tdee));
});

it('handles very low weight', function () {
    $goal = makeServiceGoal([
        'height_cm' => 170,
        'activity_level' => ActivityLevel::Light,
        'goal_type' => GoalType::Maintain,
    ]);

    $targets = $this->service->targets($goal, 35);

    expect($targets->bmr)->toBeGreaterThan(0)
        ->and($targets->tdee)->toBeGreaterThan(0)
        ->and($targets->calorieTarget)->toBeGreaterThanOrEqual(1500);
});

it('handles very high weight', function () {
    $goal = makeServiceGoal([
        'height_cm' => 195,
        'activity_level' => ActivityLevel::Active,
        'goal_type' => GoalType::Lose,
        'weekly_rate_kg' => '0.75',
    ]);

    $targets = $this->service->targets($goal, 180);

    expect($targets->bmr)->toBeGreaterThan(0)
        ->and($targets->tdee)->toBeGreaterThan($targets->bmr)
        ->and($targets->calorieTarget)->toBeGreaterThan(0);
});

it('handles extreme activity levels', function () {
    $goal = makeServiceGoal([
        'sex' => Sex::Female,
        'height_cm' => 165,
        'activity_level' => ActivityLevel::VeryActive,
        'goal_type' => GoalType::Maintain,
    ]);

    $targets = $this->service->targets($goal, 65);

    $tdee = $this->service->tdee($goal, 65);

    expect($targets->tdee)->toBe((int) round($tdee))
        ->and($targets->calorieTarget)->toBe((int) round($tdee));
});

it('computes targets for female with different macro split', function () {
    $goal = makeServiceGoal([
        'sex' => Sex::Female,
        'height_cm' => 160,
        'activity_level' => ActivityLevel::Light,
        'goal_type' => GoalType::Lose,
        'weekly_rate_kg' => '0.30',
        'protein_pct' => 35,
        'carbs_pct' => 35,
        'fat_pct' => 30,
    ]);

    $targets = $this->service->targets($goal, 58);

    expect($targets->proteinG)->toBeGreaterThan(0)
        ->and($targets->carbsG)->toBeGreaterThan(0)
        ->and($targets->fatG)->toBeGreaterThan(0);

    $expectedProtein = (int) round($targets->calorieTarget * 35 / 100 / 4);
    $expectedCarbs = (int) round($targets->calorieTarget * 35 / 100 / 4);
    $expectedFat = (int) round($targets->calorieTarget * 30 / 100 / 9);

    expect($targets->proteinG)->toBe($expectedProtein)
        ->and($targets->carbsG)->toBe($expectedCarbs)
        ->and($targets->fatG)->toBe($expectedFat);
});

it('rounds all target values to integers', function () {
    $goal = makeServiceGoal([
        'height_cm' => 175,
        'activity_level' => ActivityLevel::Light,
        'goal_type' => GoalType::Lose,
        'weekly_rate_kg' => '0.40',
    ]);

    $targets = $this->service->targets($goal, 82);

    expect($targets->bmr)->toBeInt()
        ->and($targets->tdee)->toBeInt()
        ->and($targets->calorieTarget)->toBeInt()
        ->and($targets->proteinG)->toBeInt()
        ->and($targets->carbsG)->toBeInt()
        ->and($targets->fatG)->toBeInt();
});
