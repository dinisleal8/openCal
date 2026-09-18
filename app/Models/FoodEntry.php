<?php

namespace App\Models;

use App\Enums\FoodSource;
use App\Enums\MealType;
use Carbon\CarbonInterface;
use Database\Factories\FoodEntryFactory;
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
 * @property int|null $photo_id
 * @property Carbon $date
 * @property MealType $meal_type
 * @property string $name
 * @property string|null $serving_description
 * @property string $calories_kcal
 * @property string $protein_g
 * @property string $carbs_g
 * @property string $fat_g
 * @property FoodSource $source
 * @property string|null $barcode
 * @property array<string, mixed>|null $meta
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'user_id',
    'photo_id',
    'date',
    'meal_type',
    'name',
    'serving_description',
    'calories_kcal',
    'protein_g',
    'carbs_g',
    'fat_g',
    'source',
    'barcode',
    'meta',
])]
class FoodEntry extends Model
{
    /** @use HasFactory<FoodEntryFactory> */
    use HasFactory;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function photo(): BelongsTo
    {
        return $this->belongsTo(Photo::class);
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
            'meal_type' => MealType::class,
            'calories_kcal' => 'decimal:1',
            'protein_g' => 'decimal:1',
            'carbs_g' => 'decimal:1',
            'fat_g' => 'decimal:1',
            'source' => FoodSource::class,
            'meta' => 'array',
        ];
    }
}
