<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use NotificationChannels\WebPush\HasPushSubscriptions;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property string $locale
 * @property bool $is_owner
 * @property bool $meal_reminders_enabled
 * @property string $meal_reminder_time
 * @property bool $water_reminders_enabled
 * @property string $water_reminder_time
 * @property bool $weigh_in_reminders_enabled
 * @property string $weigh_in_reminder_time
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'email', 'password', 'locale', 'meal_reminders_enabled', 'meal_reminder_time', 'water_reminders_enabled', 'water_reminder_time', 'weigh_in_reminders_enabled', 'weigh_in_reminder_time'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasPushSubscriptions, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    public function goal(): HasOne
    {
        return $this->hasOne(Goal::class);
    }

    public function foodEntries(): HasMany
    {
        return $this->hasMany(FoodEntry::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(Photo::class);
    }

    public function weighIns(): HasMany
    {
        return $this->hasMany(WeighIn::class);
    }

    public function waterLogs(): HasMany
    {
        return $this->hasMany(WaterLog::class);
    }

    public function exerciseLogs(): HasMany
    {
        return $this->hasMany(ExerciseLog::class);
    }

    public function activityDays(): HasMany
    {
        return $this->hasMany(ActivityDay::class);
    }

    public function googleHealthAccount(): HasOne
    {
        return $this->hasOne(GoogleHealthAccount::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function latestWeighIn(): ?WeighIn
    {
        return $this->weighIns()->latest('date')->latest('id')->first();
    }

    public function currentWeightKg(): ?float
    {
        $latest = $this->latestWeighIn();

        return $latest !== null ? (float) $latest->weight_kg : null;
    }

    /**
     * Weight used for calorie calculations: latest weigh-in, falling back
     * to the goal target weight, then to a neutral default.
     */
    public function referenceWeightKg(): float
    {
        if (($current = $this->currentWeightKg()) !== null) {
            return $current;
        }

        return $this->goal?->target_weight_kg !== null
            ? (float) $this->goal->target_weight_kg
            : 70.0;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'is_owner' => 'boolean',
            'meal_reminders_enabled' => 'boolean',
            'water_reminders_enabled' => 'boolean',
            'weigh_in_reminders_enabled' => 'boolean',
        ];
    }
}
