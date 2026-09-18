<?php

namespace App\Http\Controllers;

use App\Http\Requests\WeighInRequest;
use App\Models\WeighIn;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class WeighInController extends Controller
{
    public function store(WeighInRequest $request): RedirectResponse
    {
        $date = $request->validated('date');
        $weightKg = $request->validated('weight_kg');

        $weighIn = Auth::user()->weighIns()
            ->whereDate('date', $date)
            ->first();

        if ($weighIn) {
            $weighIn->update(['weight_kg' => $weightKg]);
        } else {
            Auth::user()->weighIns()->create([
                'date' => $date,
                'weight_kg' => $weightKg,
            ]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('weight.saved')]);

        return back();
    }

    public function destroy(WeighIn $weighIn): RedirectResponse
    {
        Gate::authorize('delete', $weighIn);

        $weighIn->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('weight.deleted')]);

        return back();
    }
}
