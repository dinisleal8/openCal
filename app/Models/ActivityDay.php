<?php

namespace App\Models;

use Database\Factories\ActivityDayFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property Carbon $date
 * @property int $steps
 * @property string $active_kcal
 * @property string $bmr_kcal
 * @property int $distance_m
 * @property Carbon|null $synced_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['user_id', 'date', 'steps', 'active_kcal', 'bmr_kcal', 'distance_m', 'synced_at'])]
class ActivityDay extends Model
{
    /** @use HasFactory<ActivityDayFactory> */
    use HasFactory;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'steps' => 'integer',
            'active_kcal' => 'decimal:1',
            'bmr_kcal' => 'decimal:1',
            'distance_m' => 'integer',
            'synced_at' => 'datetime',
        ];
    }
}
