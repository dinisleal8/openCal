<?php

use App\Models\User;
use Illuminate\Support\Facades\Gate;

test('the seeder creates the owner account', function () {
    $this->artisan('db:seed')->assertSuccessful();

    $owner = User::where('email', config('opencal.owner.email'))->first();

    expect($owner)->not->toBeNull()
        ->and($owner->is_owner)->toBeTrue()
        ->and($owner->email_verified_at)->not->toBeNull();
});

test('seeding twice does not duplicate the owner', function () {
    $this->artisan('db:seed')->assertSuccessful();
    $this->artisan('db:seed')->assertSuccessful();

    expect(User::where('is_owner', true)->count())->toBe(1);
});

test('public registration is disabled', function () {
    $this->get('/register')->assertNotFound();
    $this->post('/register', [
        'name' => 'Sneaky User',
        'email' => 'sneaky@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertNotFound();

    expect(User::where('email', 'sneaky@example.com')->exists())->toBeFalse();
});

test('only the owner can manage users', function () {
    $owner = User::factory()->owner()->create();
    $member = User::factory()->create();

    expect(Gate::forUser($owner)->allows('manage-users'))->toBeTrue()
        ->and(Gate::forUser($member)->allows('manage-users'))->toBeFalse();
});
