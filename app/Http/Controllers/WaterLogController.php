<?php

namespace App\Http\Controllers;

use App\Http\Requests\WaterLogRequest;
use App\Models\WaterLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class WaterLogController extends Controller
{
    public function store(WaterLogRequest $request): RedirectResponse
    {
        $date = Carbon::createFromFormat('Y-m-d', $request->validated('date'));

        Auth::user()->waterLogs()->create([
            'logged_at' => $date->copy()->setTimeFromTimeString(now()->format('H:i:s')),
            'amount_ml' => $request->validated('amount_ml'),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('water.saved')]);

        return back();
    }

    public function destroy(WaterLog $waterLog): RedirectResponse
    {
        Gate::authorize('delete', $waterLog);

        $waterLog->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('water.deleted')]);

        return back();
    }
}
