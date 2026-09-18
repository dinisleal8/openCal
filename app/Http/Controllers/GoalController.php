<?php

namespace App\Http\Controllers;

use App\Http\Requests\GoalRequest;
use App\Models\Goal;
use App\Services\DailyTargetsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class GoalController extends Controller
{
    public function __construct(private readonly DailyTargetsService $dailyTargets) {}

    /**
     * Show the goal settings page.
     */
    public function edit(Request $request): Response|RedirectResponse
    {
        $user = $request->user()->load('goal');

        if ($user->goal === null) {
            return redirect()->route('onboarding');
        }

        return Inertia::render('goal/edit', [
            'goal' => $user->goal,
            'targets' => $this->dailyTargets->targets($user->goal, $user->referenceWeightKg())->toArray(),
            'currentWeightKg' => $user->currentWeightKg(),
        ]);
    }

    /**
     * Create the user's goal (onboarding) and record the initial weight.
     */
    public function store(GoalRequest $request): RedirectResponse
    {
        $user = $request->user();

        abort_if($user->goal !== null, 409);

        $this->persistGoal($request);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Goals saved. Let\'s go!')]);

        return to_route('dashboard');
    }

    /**
     * Update the user's goal.
     */
    public function update(GoalRequest $request): RedirectResponse
    {
        abort_if($request->user()->goal === null, 404);

        $this->persistGoal($request);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Goals updated.')]);

        return to_route('goal.edit');
    }

    private function persistGoal(GoalRequest $request): Goal
    {
        $user = $request->user();
        $data = $request->validated();

        $initialWeight = Arr::pull($data, 'initial_weight_kg');
        $locale = Arr::pull($data, 'locale');

        $goal = DB::transaction(function () use ($user, $data, $initialWeight, $locale): Goal {
            if ($locale !== null) {
                $user->update(['locale' => $locale]);
            }

            if ($initialWeight !== null) {
                $user->weighIns()->firstOrCreate(
                    ['date' => today()->toDateString()],
                    ['weight_kg' => $initialWeight],
                );
            }

            $goal = $user->goal()->firstOrNew();
            $goal->fill($data)->save();

            return $goal;
        });

        return $goal;
    }
}
