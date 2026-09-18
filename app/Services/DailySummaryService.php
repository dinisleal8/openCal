<?php

namespace App\Services;

use App\Enums\ExerciseSource;
use App\Models\ActivityDay;
use App\Models\ExerciseLog;
use App\Models\FoodEntry;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Aggregates a user's tracking data for a single day or a date range.
 *
 * @phpstan-type DailySummaryArray array{
 *     eaten: float,
 *     protein: float,
 *     carbs: float,
 *     fat: float,
 *     water: int,
 *     exercise: float,
 *     remaining: int,
 * }
 */
class DailySummaryService
{
    public function __construct(private readonly DailyTargetsService $dailyTargets) {}

    /**
     * Calories from the activity band for a day, reduced by the share of
     * movement already inside the goal's TDEE, so band calories and the
     * activity multiplier are never counted twice.
     */
    public function bandActivityCalories(User $user, ActivityDay $activityDay): float
    {
        if (! $user->goal?->include_band_calories) {
            return 0.0;
        }

        $tdee = $this->dailyTargets->tdee($user->goal, $user->referenceWeightKg());

        return max(0.0, (float) $activityDay->active_kcal - max(0.0, $tdee - (float) $activityDay->bmr_kcal));
    }

    /**
     * Calories from manual exercise for a day. When the band is credited for
     * the same day, Google Health entries are excluded because they were
     * already counted through the band.
     */
    public function exerciseCalories(User $user, Collection $exerciseLogs, ?ActivityDay $activityDay): float
    {
        $bandCredited = $user->goal?->include_band_calories && $activityDay !== null;

        return (float) $exerciseLogs
            ->reject(fn (ExerciseLog $log) => $bandCredited && $log->source === ExerciseSource::GoogleHealth)
            ->sum('calories_kcal');
    }

    /**
     * @param  Collection<int, FoodEntry>  $foodEntries
     * @param  Collection<int, WaterLog>  $waterLogs
     * @param  Collection<int, ExerciseLog>  $exerciseLogs
     * @return DailySummaryArray
     */
    public function summary(User $user, Collection $foodEntries, Collection $waterLogs, Collection $exerciseLogs, ?ActivityDay $activityDay): array
    {
        $targets = $this->dailyTargets->targets($user->goal, $user->referenceWeightKg());

        $eaten = (float) $foodEntries->sum('calories_kcal');
        $band = $activityDay !== null ? $this->bandActivityCalories($user, $activityDay) : 0.0;
        $exercise = $this->exerciseCalories($user, $exerciseLogs, $activityDay);

        return [
            'eaten' => round($eaten, 1),
            'protein' => round((float) $foodEntries->sum('protein_g'), 1),
            'carbs' => round((float) $foodEntries->sum('carbs_g'), 1),
            'fat' => round((float) $foodEntries->sum('fat_g'), 1),
            'water' => (int) $waterLogs->sum('amount_ml'),
            'exercise' => round($band + $exercise, 1),
            'remaining' => (int) round($targets->calorieTarget - $eaten + $band + $exercise),
        ];
    }
}
