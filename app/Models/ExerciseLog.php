<?php

namespace App\Models;

use App\Enums\ExerciseSource;
use Carbon\CarbonInterface;
use Database\Factories\ExerciseLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property Carbon $date
 * @property string $name
 * @property int|null $duration_min
 * @property string $calories_kcal
 * @property ExerciseSource $source
 * @property array<string, mixed>|null $meta
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['user_id', 'date', 'name', 'duration_min', 'calories_kcal', 'source', 'meta'])]
class ExerciseLog extends Model
{
    /** @use HasFactory<ExerciseLogFactory> */
    use HasFactory;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    #[Scope]
    protected function onDate(Builder $query, CarbonInterface $date): Builder
    {
        return $query->whereDate('date', $date->toDateString());
    }

    #[Scope]
    protected function betweenDates(Builder $query, CarbonInterface $from, CarbonInterface $to): Builder
    {
        return $query->whereBetween('date', [$from->toDateString(), $to->toDateString()]);
    }

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'duration_min' => 'integer',
            'calories_kcal' => 'decimal:1',
            'source' => ExerciseSource::class,
            'meta' => 'array',
        ];
    }
}
