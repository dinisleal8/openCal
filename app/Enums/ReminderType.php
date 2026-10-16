<?php

namespace App\Enums;

enum ReminderType: string
{
    case Meal = 'meal';
    case Water = 'water';
    case WeighIn = 'weigh_in';

    /**
     * The users column that enables this reminder.
     */
    public function enabledColumn(): string
    {
        return match ($this) {
            self::Meal => 'meal_reminders_enabled',
            self::Water => 'water_reminders_enabled',
            self::WeighIn => 'weigh_in_reminders_enabled',
        };
    }

    /**
     * The users column that stores the reminder time (HH:MM).
     */
    public function timeColumn(): string
    {
        return match ($this) {
            self::Meal => 'meal_reminder_time',
            self::Water => 'water_reminder_time',
            self::WeighIn => 'weigh_in_reminder_time',
        };
    }

    /**
     * Translation key for the notification title.
     */
    public function titleKey(): string
    {
        return "notifications.reminders.{$this->value}.title";
    }

    /**
     * Translation key for the notification body.
     */
    public function bodyKey(): string
    {
        return "notifications.reminders.{$this->value}.body";
    }

    /**
     * Destination opened when the notification is tapped.
     */
    public function url(): string
    {
        return route('dashboard', absolute: false);
    }
}
