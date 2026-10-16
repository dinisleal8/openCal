<?php

use App\Models\User;

test('notification settings page is displayed', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('notifications.edit'))
        ->assertOk();
});

test('reminder settings can be updated', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->put(route('notifications.update'), [
            'meal_reminders_enabled' => true,
            'meal_reminder_time' => '08:30',
            'water_reminders_enabled' => true,
            'water_reminder_time' => '10:15',
            'weigh_in_reminders_enabled' => false,
            'weigh_in_reminder_time' => '07:00',
        ])
        ->assertRedirect();

    $user->refresh();

    expect($user->meal_reminders_enabled)->toBeTrue();
    expect($user->meal_reminder_time)->toBe('08:30');
    expect($user->water_reminders_enabled)->toBeTrue();
    expect($user->water_reminder_time)->toBe('10:15');
    expect($user->weigh_in_reminders_enabled)->toBeFalse();
    expect($user->weigh_in_reminder_time)->toBe('07:00');
});

test('reminder times must be valid', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('notifications.edit'))
        ->put(route('notifications.update'), [
            'meal_reminders_enabled' => true,
            'meal_reminder_time' => '25:99',
            'water_reminders_enabled' => false,
            'water_reminder_time' => '15:00',
            'weigh_in_reminders_enabled' => false,
            'weigh_in_reminder_time' => '08:00',
        ])
        ->assertSessionHasErrors('meal_reminder_time');
});
