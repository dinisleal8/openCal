<?php

namespace App\Http\Controllers;

use App\Http\Requests\FoodEntryRequest;
use App\Models\FoodEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class FoodEntryController extends Controller
{
    public function store(FoodEntryRequest $request): RedirectResponse
    {
        Auth::user()->foodEntries()->create([
            ...$request->validated(),
            'source' => 'manual',
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('food.saved')]);

        return back();
    }

    public function update(FoodEntryRequest $request, FoodEntry $foodEntry): RedirectResponse
    {
        Gate::authorize('update', $foodEntry);

        $foodEntry->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('food.saved')]);

        return back();
    }

    public function destroy(FoodEntry $foodEntry): RedirectResponse
    {
        Gate::authorize('delete', $foodEntry);

        $foodEntry->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('food.deleted')]);

        return back();
    }
}
