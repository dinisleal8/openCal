<?php

use App\Models\GoogleHealthAccount;
use App\Models\User;
use App\Services\GoogleHealthService;
use Illuminate\Support\Facades\Session;

test('authenticated user can initiate oauth redirect', function () {
    $mockService = Mockery::mock(GoogleHealthService::class);
    $mockService->shouldReceive('getAuthorizationUrl')
        ->once()
        ->andReturn('https://accounts.google.com/o/oauth2/v2/auth?client_id=test');
    $this->app->instance(GoogleHealthService::class, $mockService);

    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->get(route('google-health.redirect'));

    $response->assertRedirect('https://accounts.google.com/o/oauth2/v2/auth?client_id=test');
});

test('unauthenticated user cannot access google health routes', function () {
    $this->get(route('google-health.redirect'))->assertRedirect(route('login'));

    $this->get(route('google-health.callback', ['code' => 'test', 'state' => 'test']))
        ->assertRedirect(route('login'));

    $this->post(route('google-health.sync'))->assertRedirect(route('login'));

    $this->delete(route('google-health.disconnect'))->assertRedirect(route('login'));
});

test('callback creates google health account record', function () {
    $user = User::factory()->create();

    $account = GoogleHealthAccount::factory()->create([
        'user_id' => $user->id,
    ]);

    $mockService = Mockery::mock(GoogleHealthService::class);

    $mockService->shouldReceive('exchangeCode')
        ->once()
        ->andReturn($account);

    $mockService->shouldReceive('fetchDayData')
        ->once()
        ->andReturn([
            'steps' => 0,
            'active_kcal' => 0.0,
            'weight_kg' => null,
        ]);

    $this->app->instance(GoogleHealthService::class, $mockService);

    Session::put('google_health_state', 'valid-state-token');

    $response = $this->actingAs($user)
        ->get(route('google-health.callback', [
            'code' => 'auth-code-123',
            'state' => 'valid-state-token',
        ]));

    $response->assertRedirect(route('profile.edit'));
});

test('callback rejects invalid state parameter', function () {
    $user = User::factory()->create();

    Session::put('google_health_state', 'correct-state');

    $response = $this->actingAs($user)
        ->get(route('google-health.callback', [
            'code' => 'auth-code-123',
            'state' => 'wrong-state',
        ]));

    $response->assertRedirect()
        ->assertSessionHasErrors('google_health');
});

test('sync fetches data from google health api', function () {
    $account = GoogleHealthAccount::factory()->create([
        'expires_at' => now()->addHour(),
    ]);

    $mockService = Mockery::mock(GoogleHealthService::class);
    $mockService->shouldReceive('fetchDayData')
        ->once()
        ->andReturn([
            'steps' => 8500,
            'active_kcal' => 350.5,
            'weight_kg' => 75.5,
        ]);
    $this->app->instance(GoogleHealthService::class, $mockService);

    $response = $this->actingAs($account->user)
        ->post(route('google-health.sync'));

    $response->assertRedirect();

    $activityDay = $account->user->activityDays()
        ->whereDate('date', now()->toDateString())
        ->first();

    expect($activityDay)->not->toBeNull()
        ->and($activityDay->steps)->toBe(8500)
        ->and((float) $activityDay->active_kcal)->toBe(350.5);
});

test('sync fails gracefully when google health is not connected', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('google-health.sync'));

    $response->assertRedirect();
});

test('disconnect removes the google health account record', function () {
    $account = GoogleHealthAccount::factory()->create();

    $mockService = Mockery::mock(GoogleHealthService::class);
    $mockService->shouldReceive('disconnect')
        ->once()
        ->with(Mockery::type(GoogleHealthAccount::class))
        ->andReturnUsing(fn (GoogleHealthAccount $account) => $account->delete());
    $this->app->instance(GoogleHealthService::class, $mockService);

    $userId = $account->user_id;

    $response = $this->actingAs($account->user)
        ->delete(route('google-health.disconnect'));

    $response->assertRedirect(route('profile.edit'));

    $this->assertDatabaseMissing('google_health_accounts', [
        'user_id' => $userId,
    ]);
});

test('disconnect fails gracefully when account does not exist', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->delete(route('google-health.disconnect'));

    $response->assertRedirect();
});
