<?php

use App\Http\Controllers\GoogleHealthController;
use App\Http\Controllers\Settings\IntegrationsController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\SecurityController;
use App\Http\Controllers\Settings\UserManagementController;
use Illuminate\Auth\Middleware\RequirePassword;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', '/settings/profile');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::get('settings/google-health/redirect', [GoogleHealthController::class, 'redirect'])->name('google-health.redirect');
    Route::get('settings/google-health/callback', [GoogleHealthController::class, 'callback'])->name('google-health.callback');
    Route::post('settings/google-health/sync', [GoogleHealthController::class, 'sync'])->name('google-health.sync');
    Route::delete('settings/google-health/disconnect', [GoogleHealthController::class, 'disconnect'])->name('google-health.disconnect');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('settings/security', [SecurityController::class, 'edit'])
        ->middleware(RequirePassword::class)
        ->name('security.edit');

    Route::put('settings/password', [SecurityController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('user-password.update');

    Route::inertia('settings/appearance', 'settings/appearance')->name('appearance.edit');

    Route::get('settings/users', [UserManagementController::class, 'index'])->name('users.index');
    Route::post('settings/users', [UserManagementController::class, 'store'])->name('users.store');
    Route::delete('settings/users/{user}', [UserManagementController::class, 'destroy'])->name('users.destroy');

    Route::get('settings/integrations', [IntegrationsController::class, 'index'])->name('integrations.index');
});

Route::get('.well-known/passkey-endpoints', function () {
    return response()->json([
        'enroll' => route('security.edit'),
        'manage' => route('security.edit'),
    ]);
})->name('well-known.passkeys');
