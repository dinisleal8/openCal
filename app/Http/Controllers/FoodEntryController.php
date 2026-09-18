<?php

namespace App\Http\Controllers;

use App\Http\Requests\BulkFoodEntryRequest;
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

    /**
     * Store several food entries at once, typically the items detected in a photo.
     */
    public function bulkStore(BulkFoodEntryRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $user = Auth::user();
        $photoId = $validated['photo_id'] ?? null;
        $source = $photoId ? 'ai_photo' : 'manual';

        $entries = array_map(fn (array $item): array => [
            'user_id' => $user->id,
            'date' => $validated['date'],
            'meal_type' => $validated['meal_type'],
            'photo_id' => $photoId,
            'source' => $source,
            ...$item,
        ], $validated['items']);

        $user->foodEntries()->createMany($entries);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('food.savedMany', ['count' => count($entries)]),
        ]);

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
