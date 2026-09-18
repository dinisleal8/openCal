<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExerciseLogController;
use App\Http\Controllers\FoodEntryController;
use App\Http\Controllers\HistoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\WaterLogController;
use App\Http\Controllers\WeighInController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::post('food', [FoodEntryController::class, 'store'])->name('food.store');
    Route::post('food/bulk', [FoodEntryController::class, 'bulkStore'])->name('food.bulk');
    Route::put('food/{foodEntry}', [FoodEntryController::class, 'update'])->name('food.update');
    Route::delete('food/{foodEntry}', [FoodEntryController::class, 'destroy'])->name('food.destroy');

    Route::post('water', [WaterLogController::class, 'store'])->name('water.store');
    Route::delete('water/{waterLog}', [WaterLogController::class, 'destroy'])->name('water.destroy');

    Route::post('weight', [WeighInController::class, 'store'])->name('weight.store');
    Route::delete('weight/{weighIn}', [WeighInController::class, 'destroy'])->name('weight.destroy');

    Route::post('exercise', [ExerciseLogController::class, 'store'])->name('exercise.store');
    Route::put('exercise/{exerciseLog}', [ExerciseLogController::class, 'update'])->name('exercise.update');
    Route::delete('exercise/{exerciseLog}', [ExerciseLogController::class, 'destroy'])->name('exercise.destroy');

    Route::get('history', [HistoryController::class, 'index'])->name('history');

    Route::get('products/barcode/{barcode}', [ProductController::class, 'barcode'])->name('products.barcode');
    Route::resource('products', ProductController::class)->except(['create', 'edit']);
});
