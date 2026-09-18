<?php

namespace App\Services;

/**
 * Computed daily nutrition targets for a user.
 *
 * @phpstan-type DailyTargetsArray array{
 *     bmr: int,
 *     tdee: int,
 *     calorie_target: int,
 *     protein_g: int,
 *     carbs_g: int,
 *     fat_g: int,
 *     water_goal_ml: int,
 * }
 */
readonly class DailyTargets
{
    public function __construct(
        public int $bmr,
        public int $tdee,
        public int $calorieTarget,
        public int $proteinG,
        public int $carbsG,
        public int $fatG,
        public int $waterGoalMl,
    ) {}

    /**
     * @return DailyTargetsArray
     */
    public function toArray(): array
    {
        return [
            'bmr' => $this->bmr,
            'tdee' => $this->tdee,
            'calorie_target' => $this->calorieTarget,
            'protein_g' => $this->proteinG,
            'carbs_g' => $this->carbsG,
            'fat_g' => $this->fatG,
            'water_goal_ml' => $this->waterGoalMl,
        ];
    }
}
