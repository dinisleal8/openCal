<?php

namespace App\Http\Controllers;

use App\Services\DailySummaryService;
use App\Services\DailyTargetsService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HistoryController extends Controller
{
    public function __construct(
        private readonly DailyTargetsService $dailyTargets,
        private readonly DailySummaryService $dailySummary,
    ) {}

    /**
     * Show the 7 or 30 day history page.
     */
    public function index(Request $request): Response
    {
        $user = $request->user()->load('goal');

        $validated = $request->validate([
            'days' => ['nullable', 'integer', 'in:7,30'],
        ]);

        $days = (int) ($validated['days'] ?? 7);
        $today = today()->startOfDay();
        $from = $today->copy()->subDays($days - 1);

        $foodByDate = $user->foodEntries()
            ->betweenDates($from, $today)
            ->get(['id', 'user_id', 'date', 'calories_kcal'])
            ->groupBy(fn ($entry) => $entry->date->toDateString());

        $waterByDate = $user->waterLogs()
            ->whereBetween('logged_at', [$from->copy()->startOfDay(), $today->copy()->endOfDay()])
            ->get(['id', 'user_id', 'logged_at', 'amount_ml'])
            ->groupBy(fn ($log) => $log->logged_at->toDateString());

        $exerciseByDate = $user->exerciseLogs()
            ->betweenDates($from, $today)
            ->get(['id', 'user_id', 'date', 'calories_kcal', 'source'])
            ->groupBy(fn ($log) => $log->date->toDateString());

        $activityByDate = $user->activityDays()
            ->whereBetween('date', [$from->toDateString(), $today->toDateString()])
            ->get()
            ->keyBy(fn ($day) => $day->date->toDateString());

        $weighIns = $user->weighIns()
            ->whereDate('date', '<=', $today->toDateString())
            ->orderBy('date')
            ->limit(30)
            ->get(['id', 'date', 'weight_kg']);

        $calorieTarget = $this->dailyTargets->targets($user->goal, $user->referenceWeightKg())->calorieTarget;

        $rows = [];

        foreach (range(0, $days - 1) as $offset) {
            $day = $from->copy()->addDays($offset);
            $key = $day->toDateString();

            $foodEntries = $foodByDate->get($day->toDateString(), collect());
            $exerciseLogs = $exerciseByDate->get($key, collect());
            $activityDay = $activityByDate->get($key);
            $summary = $this->dailySummary->summary(
                $user,
                $foodEntries,
                $waterByDate->get($key, collect()),
                $exerciseLogs,
                $activityDay,
            );

            $rows[] = [
                'date' => $key,
                'calories' => $summary['eaten'],
                'target' => $calorieTarget,
                'logged' => $foodEntries->isNotEmpty(),
                'water' => $summary['water'],
                'exercise' => $summary['exercise'],
            ];
        }

        $loggedRows = array_values(array_filter($rows, fn (array $row) => $row['logged']));

        return Inertia::render('history', [
            'days' => $rows,
            'dayCount' => $days,
            'weightHistory' => $weighIns,
            'averages' => [
                'calories' => $loggedRows === []
                    ? 0.0
                    : round(array_sum(array_column($loggedRows, 'calories')) / count($loggedRows), 1),
                'water' => round(array_sum(array_column($rows, 'water')) / $days, 0),
                'exercise' => round(array_sum(array_column($rows, 'exercise')) / $days, 1),
            ],
        ]);
    }
}
