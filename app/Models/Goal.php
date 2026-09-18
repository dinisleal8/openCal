<?php

namespace App\Models;

use App\Enums\ActivityLevel;
use App\Enums\GoalType;
use App\Enums\Sex;
use Database\Factories\GoalFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property Sex $sex
 * @property Carbon $birth_date
 * @property int $height_cm
 * @property ActivityLevel $activity_level
 * @property GoalType $goal_type
 * @property string $weekly_rate_kg
 * @property string|null $target_weight_kg
 * @property int $water_goal_ml
 * @property int $protein_pct
 * @property int $carbs_pct
 * @property int $fat_pct
 * @property bool $include_band_calories
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'user_id',
    'sex',
    'birth_date',
    'height_cm',
    'activity_level',
    'goal_type',
    'weekly_rate_kg',
    'target_weight_kg',
    'water_goal_ml',
    'protein_pct',
    'carbs_pct',
    'fat_pct',
    'include_band_calories',
])]
class Goal extends Model
{
    /** @use HasFactory<GoalFactory> */
    use HasFactory;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function age(): int
    {
        return $this->birth_date->age;
    }

    protected function casts(): array
    {
        return [
            'sex' => Sex::class,
            'birth_date' => 'date',
            'height_cm' => 'integer',
            'activity_level' => ActivityLevel::class,
            'goal_type' => GoalType::class,
            'weekly_rate_kg' => 'decimal:2',
            'target_weight_kg' => 'decimal:2',
            'water_goal_ml' => 'integer',
            'protein_pct' => 'integer',
            'carbs_pct' => 'integer',
            'fat_pct' => 'integer',
            'include_band_calories' => 'boolean',
        ];
    }
}
