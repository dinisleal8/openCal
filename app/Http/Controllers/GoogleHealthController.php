<?php

namespace App\Http\Controllers;

use App\Models\ActivityDay;
use App\Models\GoogleHealthAccount;
use App\Services\GoogleHealthService;
use Carbon\CarbonInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class GoogleHealthController extends Controller
{
    public function __construct(
        private readonly GoogleHealthService $googleHealth,
    ) {}

    /**
     * Generate the OAuth authorization URL and redirect the user to Google.
     */
    public function redirect(): RedirectResponse
    {
        $state = csrf_token();

        session(['google_health_state' => $state]);

        $url = $this->googleHealth->getAuthorizationUrl($state);

        return redirect($url);
    }

    /**
     * Handle the OAuth callback, store tokens, and sync today's data.
     */
    public function callback(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => 'required|string',
            'state' => 'required|string',
        ]);

        if ($request->state !== session('google_health_state')) {
            return back()->withErrors(['google_health' => 'Invalid state parameter. Please try again.']);
        }

        session()->forget('google_health_state');

        try {
            $account = $this->googleHealth->exchangeCode($request->code, $request->user());

            $this->syncDay($account, Carbon::today());

            Inertia::flash('toast', ['type' => 'success', 'message' => 'Google Health connected and synced.']);

            return redirect()->route('profile.edit');
        } catch (\Throwable $e) {
            Log::error('Google Health OAuth callback failed', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            Inertia::flash('toast', ['type' => 'error', 'message' => 'Failed to connect Google Health: '.$e->getMessage()]);

            return redirect()->route('profile.edit');
        }
    }

    /**
     * Manually sync data for today or a specific date.
     */
    public function sync(Request $request): RedirectResponse
    {
        $request->validate([
            'date' => 'nullable|date|before_or_equal:today',
        ]);

        $account = $request->user()->googleHealthAccount;

        if (! $account || ! $account->isConnected()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Google Health is not connected.']);

            return back();
        }

        try {
            $date = $request->date ? Carbon::parse($request->date) : Carbon::today();

            $this->syncDay($account, $date);

            Inertia::flash('toast', ['type' => 'success', 'message' => "Synced data for {$date->toDateString()}."]);

            return back();
        } catch (\Throwable $e) {
            Log::error('Google Health sync failed', [
                'user_id' => $request->user()->id,
                'date' => $request->date,
                'error' => $e->getMessage(),
            ]);

            Inertia::flash('toast', ['type' => 'error', 'message' => 'Sync failed: '.$e->getMessage()]);

            return back();
        }
    }

    /**
     * Revoke the Google OAuth token and disconnect the account.
     */
    public function disconnect(Request $request): RedirectResponse
    {
        $account = $request->user()->googleHealthAccount;

        if (! $account) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Google Health is not connected.']);

            return back();
        }

        try {
            $this->googleHealth->disconnect($account);

            Inertia::flash('toast', ['type' => 'success', 'message' => 'Google Health disconnected.']);
        } catch (\Throwable $e) {
            Log::error('Google Health disconnect failed', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            // Delete locally even if revocation fails
            $account->delete();

            Inertia::flash('toast', ['type' => 'success', 'message' => 'Google Health disconnected.']);
        }

        return redirect()->route('profile.edit');
    }

    /**
     * Sync a single day's data from Google Health and update the database.
     */
    private function syncDay(GoogleHealthAccount $account, CarbonInterface $date): void
    {
        $data = $this->googleHealth->fetchDayData($account, $date);

        $user = $account->user;

        // Update or create ActivityDay
        $activityAttributes = [
            'steps' => $data['steps'],
            'active_kcal' => $data['active_kcal'],
            'distance_m' => (int) ($data['steps'] * 0.762),
            'synced_at' => now(),
        ];

        $activityDay = ActivityDay::where('user_id', $user->id)
            ->whereDate('date', $date->toDateString())
            ->first();

        if ($activityDay) {
            $activityDay->update($activityAttributes);
        } else {
            ActivityDay::create([
                'user_id' => $user->id,
                'date' => $date->toDateString(),
                ...$activityAttributes,
            ]);
        }

        // Update weight if available
        if ($data['weight_kg'] !== null) {
            $existingWeight = $user->weighIns()
                ->whereDate('date', $date->toDateString())
                ->latest('id')
                ->first();

            if ($existingWeight) {
                $existingWeight->update(['weight_kg' => $data['weight_kg']]);
            } else {
                $user->weighIns()->create([
                    'date' => $date->toDateString(),
                    'weight_kg' => $data['weight_kg'],
                ]);
            }
        }

        $account->update([
            'last_synced_at' => now(),
            'last_sync_error' => null,
        ]);
    }
}
