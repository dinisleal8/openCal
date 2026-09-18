<?php

use App\Http\Controllers\Api\PhotoAnalysisController;
use App\Http\Controllers\GoalController;
use App\Http\Controllers\OnboardingController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('onboarding', [OnboardingController::class, 'create'])->name('onboarding');

    Route::post('goal', [GoalController::class, 'store'])->name('goal.store');
    Route::get('goal/edit', [GoalController::class, 'edit'])->name('goal.edit');
    Route::put('goal', [GoalController::class, 'update'])->name('goal.update');

    Route::post('api/photo/analyze', [PhotoAnalysisController::class, 'analyze'])->name('api.photo.analyze');

    Route::put('locale/{locale}', function (Request $request, string $locale) {
        abort_unless(in_array($locale, config('opencal.locales'), true), 404);

        $request->user()->update(['locale' => $locale]);

        return back();
    })->name('locale.update');
});

require __DIR__.'/tracking.php';
require __DIR__.'/settings.php';
