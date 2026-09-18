<?php

namespace App\Services;

use App\Enums\GoalType;
use App\Enums\Sex;
use App\Models\Goal;

class DailyTargetsService
{
    /**
     * Energy equivalent of one kilogram of body weight change.
     */
    public const KCAL_PER_KG = 7700.0;

    public const MIN_CALORIES_MALE = 1500;

    public const MIN_CALORIES_FEMALE = 1200;

    public const MAX_SURPLUS_KCAL = 1000.0;

    public const KCAL_PER_G_PROTEIN = 4.0;

    public const KCAL_PER_G_CARBS = 4.0;

    public const KCAL_PER_G_FAT = 9.0;

    /**
     * Basal metabolic rate using the Mifflin-St Jeor equation.
     */
    public function bmr(Goal $goal, float $weightKg): float
    {
        $base = (10 * $weightKg) + (6.25 * $goal->height_cm) - (5 * $goal->age());

        return $goal->sex === Sex::Male ? $base + 5 : $base - 161;
    }

    /**
     * Total daily energy expenditure (BMR x activity multiplier).
     */
    public function tdee(Goal $goal, float $weightKg): float
    {
        return $this->bmr($goal, $weightKg) * $goal->activity_level->multiplier();
    }

    /**
     * Daily calorie and macro targets derived from the goal.
     */
    public function targets(Goal $goal, float $weightKg): DailyTargets
    {
        $tdee = $this->tdee($goal, $weightKg);

        $dailyDelta = match ($goal->goal_type) {
            GoalType::Lose => -((float) $goal->weekly_rate_kg * self::KCAL_PER_KG / 7),
            GoalType::Gain => (float) $goal->weekly_rate_kg * self::KCAL_PER_KG / 7,
            GoalType::Maintain => 0.0,
        };

        $minCalories = $goal->sex === Sex::Male ? self::MIN_CALORIES_MALE : self::MIN_CALORIES_FEMALE;

        $calorieTarget = (int) round(max(
            (float) $minCalories,
            min($tdee + $dailyDelta, $tdee + self::MAX_SURPLUS_KCAL),
        ));

        return new DailyTargets(
            bmr: (int) round($this->bmr($goal, $weightKg)),
            tdee: (int) round($tdee),
            calorieTarget: $calorieTarget,
            proteinG: (int) round($calorieTarget * $goal->protein_pct / 100 / self::KCAL_PER_G_PROTEIN),
            carbsG: (int) round($calorieTarget * $goal->carbs_pct / 100 / self::KCAL_PER_G_CARBS),
            fatG: (int) round($calorieTarget * $goal->fat_pct / 100 / self::KCAL_PER_G_FAT),
            waterGoalMl: $goal->water_goal_ml,
        );
    }
}
