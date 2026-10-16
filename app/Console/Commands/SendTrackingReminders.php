<?php

namespace App\Console\Commands;

use App\Enums\ReminderType;
use App\Models\FoodEntry;
use App\Models\Goal;
use App\Models\User;
use App\Models\WaterLog;
use App\Notifications\TrackingReminder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class SendTrackingReminders extends Command
{
    /**
     * @var string
     */
    protected $signature = 'opencal:send-reminders';

    /**
     * @var string
     */
    protected $description = 'Send tracking reminders (meals, water, weigh-in) to users';

    public function handle(): int
    {
        $now = now();
        $time = $now->format('H:i');
        $sent = 0;

        foreach (ReminderType::cases() as $type) {
            $users = User::query()
                ->where($type->enabledColumn(), true)
                ->where($type->timeColumn(), $time)
                ->get();

            foreach ($users as $user) {
                if (! $this->shouldSend($type, $user)) {
                    continue;
                }

                $key = "reminder:{$type->value}:{$user->id}:{$now->toDateString()}";

                if (! Cache::add($key, true, $now->endOfDay())) {
                    continue;
                }

                $user->notify(
                    (new TrackingReminder($type))->locale($user->locale),
                );

                $sent++;
            }
        }

        $this->info("Sent {$sent} reminder(s).");

        return self::SUCCESS;
    }

    /**
     * Determine whether the user still needs this reminder.
     */
    protected function shouldSend(ReminderType $type, User $user): bool
    {
        $today = now();

        return match ($type) {
            ReminderType::Meal => ! FoodEntry::query()
                ->where('user_id', $user->id)
                ->whereDate('date', $today->toDateString())
                ->exists(),
            ReminderType::Water => $this->needsWater($user),
            ReminderType::WeighIn => ! $user->weighIns()
                ->whereDate('date', $today->toDateString())
                ->exists(),
        };
    }

    /**
     * The user still needs water when below their daily goal.
     */
    protected function needsWater(User $user): bool
    {
        $goal = Goal::query()
            ->where('user_id', $user->id)
            ->value('water_goal_ml');

        if ($goal === null || (int) $goal <= 0) {
            return false;
        }

        $logged = (int) WaterLog::query()
            ->where('user_id', $user->id)
            ->whereBetween('logged_at', [now()->startOfDay(), now()->endOfDay()])
            ->sum('amount_ml');

        return $logged < (int) $goal;
    }
}
