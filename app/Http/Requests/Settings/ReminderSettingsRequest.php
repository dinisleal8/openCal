<?php

namespace App\Http\Requests\Settings;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ReminderSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'meal_reminders_enabled' => ['required', 'boolean'],
            'meal_reminder_time' => ['required', 'date_format:H:i'],
            'water_reminders_enabled' => ['required', 'boolean'],
            'water_reminder_time' => ['required', 'date_format:H:i'],
            'weigh_in_reminders_enabled' => ['required', 'boolean'],
            'weigh_in_reminder_time' => ['required', 'date_format:H:i'],
        ];
    }
}
