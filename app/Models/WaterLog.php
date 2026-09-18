<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\WaterLogFactory;
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
 * @property Carbon $logged_at
 * @property int $amount_ml
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['user_id', 'logged_at', 'amount_ml'])]
class WaterLog extends Model
{
    /** @use HasFactory<WaterLogFactory> */
    use HasFactory;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    #[Scope]
    protected function onDate(Builder $query, CarbonInterface $date): Builder
    {
        return $query->whereBetween('logged_at', [$date->copy()->startOfDay(), $date->copy()->endOfDay()]);
    }

    protected function casts(): array
    {
        return [
            'logged_at' => 'datetime',
            'amount_ml' => 'integer',
        ];
    }
}
