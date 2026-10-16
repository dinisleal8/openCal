<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ReminderSettingsRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NotificationSettingsController extends Controller
{
    /**
     * Show the reminder notification settings page.
     */
    public function edit(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('settings/notifications', [
            'settings' => [
                'meal_reminders_enabled' => (bool) $user->meal_reminders_enabled,
                'meal_reminder_time' => $user->meal_reminder_time,
                'water_reminders_enabled' => (bool) $user->water_reminders_enabled,
                'water_reminder_time' => $user->water_reminder_time,
                'weigh_in_reminders_enabled' => (bool) $user->weigh_in_reminders_enabled,
                'weigh_in_reminder_time' => $user->weigh_in_reminder_time,
            ],
        ]);
    }

    /**
     * Update the reminder notification settings.
     */
    public function update(ReminderSettingsRequest $request): RedirectResponse
    {
        $request->user()->update($request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('notifications.saved'),
        ]);

        return back();
    }
}
