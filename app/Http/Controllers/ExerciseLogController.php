<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExerciseLogRequest;
use App\Models\ExerciseLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class ExerciseLogController extends Controller
{
    public function store(ExerciseLogRequest $request): RedirectResponse
    {
        Auth::user()->exerciseLogs()->create([
            ...$request->validated(),
            'source' => 'manual',
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('exercise.saved')]);

        return back();
    }

    public function update(ExerciseLogRequest $request, ExerciseLog $exerciseLog): RedirectResponse
    {
        Gate::authorize('update', $exerciseLog);

        $exerciseLog->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('exercise.saved')]);

        return back();
    }

    public function destroy(ExerciseLog $exerciseLog): RedirectResponse
    {
        Gate::authorize('delete', $exerciseLog);

        $exerciseLog->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('exercise.deleted')]);

        return back();
    }
}
