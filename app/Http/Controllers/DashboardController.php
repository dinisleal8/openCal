<?php

namespace App\Http\Controllers;

use App\Services\DailySummaryService;
use App\Services\DailyTargetsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DailyTargetsService $dailyTargets,
        private readonly DailySummaryService $dailySummary,
    ) {}

    /**
     * Show the "Today" hub, or send new users through onboarding first.
     */
    public function index(Request $request): Response|RedirectResponse
    {
        $user = $request->user()->load('goal');

        if ($user->goal === null) {
            return redirect()->route('onboarding');
        }

        $dateStr = $request->input('date', now()->toDateString());

        $validated = $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $date = Carbon::createFromFormat('Y-m-d', $validated['date'] ?? $dateStr)->startOfDay();

        $foodEntries = $user->foodEntries()->onDate($date)->orderBy('created_at')->with('photo')->get();
        $waterLogs = $user->waterLogs()->onDate($date)->orderBy('logged_at')->get();
        $exerciseLogs = $user->exerciseLogs()->onDate($date)->orderBy('created_at')->get();
        $activityDay = $user->activityDays()->whereDate('date', $date->toDateString())->first();
        $targets = $this->dailyTargets->targets($user->goal, $user->referenceWeightKg());

        $frequentFoods = $user->foodEntries()
            ->select('name', 'calories_kcal', 'protein_g', 'carbs_g', 'fat_g', 'serving_description')
            ->selectRaw('COUNT(*) as times_logged')
            ->groupBy('name', 'calories_kcal', 'protein_g', 'carbs_g', 'fat_g', 'serving_description')
            ->orderByDesc('times_logged')
            ->limit(5)
            ->get();

        return Inertia::render('dashboard', [
            'date' => $date->toDateString(),
            'goal' => $user->goal,
            'targets' => $targets->toArray(),
            'currentWeightKg' => $user->currentWeightKg(),
            'foodEntries' => $foodEntries,
            'waterLogs' => $waterLogs,
            'exerciseLogs' => $exerciseLogs,
            'weightHistory' => $user->weighIns()
                ->whereDate('date', '<=', $date->toDateString())
                ->orderBy('date')
                ->limit(30)
                ->get(['id', 'date', 'weight_kg']),
            'activityDay' => $activityDay,
            'frequentFoods' => $frequentFoods,
            'summary' => $this->dailySummary->summary($user, $foodEntries, $waterLogs, $exerciseLogs, $activityDay),
        ]);
    }
}
