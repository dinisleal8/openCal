<?php

use App\Enums\ReminderType;
use App\Models\FoodEntry;
use App\Models\Goal;
use App\Models\User;
use App\Models\WaterLog;
use App\Notifications\TrackingReminder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Cache::flush();
});

test('sends a meal reminder when nothing was logged today', function () {
    Notification::fake();

    $user = User::factory()->create([
        'meal_reminders_enabled' => true,
        'meal_reminder_time' => now()->format('H:i'),
    ]);

    $this->artisan('opencal:send-reminders')->assertSuccessful();

    Notification::assertSentTo(
        $user,
        TrackingReminder::class,
        fn (TrackingReminder $notification) => $notification->type === ReminderType::Meal,
    );
});

test('does not send a meal reminder when food was logged today', function () {
    Notification::fake();

    $user = User::factory()->create([
        'meal_reminders_enabled' => true,
        'meal_reminder_time' => now()->format('H:i'),
    ]);
    FoodEntry::factory()->create(['user_id' => $user->id]);

    $this->artisan('opencal:send-reminders')->assertSuccessful();

    Notification::assertNothingSentTo($user);
});

test('does not send reminders that are disabled', function () {
    Notification::fake();

    $user = User::factory()->create([
        'meal_reminders_enabled' => false,
        'meal_reminder_time' => now()->format('H:i'),
    ]);

    $this->artisan('opencal:send-reminders')->assertSuccessful();

    Notification::assertNothingSentTo($user);
});

test('does not send reminders at a different time', function () {
    Notification::fake();

    $user = User::factory()->create([
        'meal_reminders_enabled' => true,
        'meal_reminder_time' => now()->addMinutes(5)->format('H:i'),
    ]);

    $this->artisan('opencal:send-reminders')->assertSuccessful();

    Notification::assertNothingSentTo($user);
});

test('sends a water reminder when below the daily goal', function () {
    Notification::fake();

    $user = User::factory()->create([
        'water_reminders_enabled' => true,
        'water_reminder_time' => now()->format('H:i'),
    ]);
    Goal::factory()->create(['user_id' => $user->id, 'water_goal_ml' => 2500]);
    WaterLog::factory()->create(['user_id' => $user->id, 'amount_ml' => 500]);

    $this->artisan('opencal:send-reminders')->assertSuccessful();

    Notification::assertSentTo(
        $user,
        TrackingReminder::class,
        fn (TrackingReminder $notification) => $notification->type === ReminderType::Water,
    );
});

test('does not send a water reminder when the goal is met', function () {
    Notification::fake();

    $user = User::factory()->create([
        'water_reminders_enabled' => true,
        'water_reminder_time' => now()->format('H:i'),
    ]);
    Goal::factory()->create(['user_id' => $user->id, 'water_goal_ml' => 2500]);
    WaterLog::factory()->create(['user_id' => $user->id, 'amount_ml' => 2500]);

    $this->artisan('opencal:send-reminders')->assertSuccessful();

    Notification::assertNothingSentTo($user);
});

test('sends a weigh-in reminder when there is no weigh-in today', function () {
    Notification::fake();

    $user = User::factory()->create([
        'weigh_in_reminders_enabled' => true,
        'weigh_in_reminder_time' => now()->format('H:i'),
    ]);

    $this->artisan('opencal:send-reminders')->assertSuccessful();

    Notification::assertSentTo(
        $user,
        TrackingReminder::class,
        fn (TrackingReminder $notification) => $notification->type === ReminderType::WeighIn,
    );
});

test('does not send the same reminder twice in one day', function () {
    Notification::fake();

    $user = User::factory()->create([
        'weigh_in_reminders_enabled' => true,
        'weigh_in_reminder_time' => now()->format('H:i'),
    ]);

    $this->artisan('opencal:send-reminders')->assertSuccessful();
    $this->artisan('opencal:send-reminders')->assertSuccessful();

    Notification::assertSentToTimes($user, TrackingReminder::class, 1);
});

test('stores the reminder in the database channel', function () {
    $user = User::factory()->create([
        'weigh_in_reminders_enabled' => true,
        'weigh_in_reminder_time' => now()->format('H:i'),
    ]);

    $this->artisan('opencal:send-reminders')->assertSuccessful();

    expect($user->notifications()->count())->toBe(1);
    expect($user->notifications()->first()->data['type'])->toBe('weigh_in');
});

test('user can mark all notifications as read', function () {
    $user = User::factory()->create();
    $user->notify(new TrackingReminder(ReminderType::Water));
    $user->notify(new TrackingReminder(ReminderType::Meal));

    expect($user->unreadNotifications()->count())->toBe(2);

    $this->actingAs($user)
        ->post(route('notifications.readAll'))
        ->assertRedirect();

    expect($user->unreadNotifications()->count())->toBe(0);
});

test('user can mark a single notification as read', function () {
    $user = User::factory()->create();
    $user->notify(new TrackingReminder(ReminderType::Water));
    $notification = $user->notifications()->firstOrFail();

    $this->actingAs($user)
        ->post(route('notifications.read', $notification->id))
        ->assertRedirect();

    expect($notification->fresh()->read_at)->not->toBeNull();
});

test('user can delete a notification', function () {
    $user = User::factory()->create();
    $user->notify(new TrackingReminder(ReminderType::Meal));
    $notification = $user->notifications()->firstOrFail();

    $this->actingAs($user)
        ->delete(route('notifications.destroy', $notification->id))
        ->assertRedirect();

    expect($user->notifications()->count())->toBe(0);
});

test('user can register a push subscription', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('pushSubscriptions.store'), [
        'endpoint' => 'https://push.example.com/abc',
        'keys' => ['pwh' => 'p256dh-key', 'auth' => 'auth-token'],
        'content_encoding' => 'aes128gcm',
    ])->assertRedirect();

    expect($user->pushSubscriptions()->count())->toBe(1);
    $this->assertDatabaseHas('push_subscriptions', [
        'subscribable_id' => $user->id,
        'endpoint' => 'https://push.example.com/abc',
    ]);
});

test('user can remove a push subscription', function () {
    $user = User::factory()->create();
    $user->updatePushSubscription(
        'https://push.example.com/abc',
        'p256dh-key',
        'auth-token',
        'aes128gcm',
    );

    $this->actingAs($user)
        ->delete(route('pushSubscriptions.destroy'), [
            'endpoint' => 'https://push.example.com/abc',
        ])
        ->assertRedirect();

    expect($user->pushSubscriptions()->count())->toBe(0);
});

test('unauthenticated users cannot access notifications', function () {
    $this->get(route('notifications.edit'))->assertRedirect(route('login'));
    $this->post(route('notifications.readAll'))->assertRedirect(route('login'));
});
