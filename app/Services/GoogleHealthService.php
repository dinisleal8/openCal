<?php

namespace App\Services;

use App\Models\GoogleHealthAccount;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleHealthService
{
    private const BASE_URL = 'https://www.googleapis.com/fitness/v1/users/me';

    private const SCOPES = [
        'openid',
        'https://www.googleapis.com/auth/fitness.activity.read',
        'https://www.googleapis.com/auth/fitness.body.read',
    ];

    private const DATA_SOURCES = [
        'steps' => 'com.google.step_count.delta',
        'active_calories' => 'com.google.calories.expended',
        'weight' => 'com.google.weight',
    ];

    private readonly string $clientId;

    private readonly string $clientSecret;

    private readonly string $redirectUri;

    public function __construct(
        string $clientId = '',
        string $clientSecret = '',
        string $redirectUri = '',
    ) {
        $this->clientId = $clientId ?: (string) config('services.google_health.client_id', '');
        $this->clientSecret = $clientSecret ?: (string) config('services.google_health.client_secret', '');
        $this->redirectUri = $redirectUri ?: (string) config('services.google_health.redirect', '');
    }

    /**
     * Generate the Google OAuth authorization URL.
     */
    public function getAuthorizationUrl(string $state = ''): string
    {
        $params = http_build_query([
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'response_type' => 'code',
            'scope' => implode(' ', self::SCOPES),
            'access_type' => 'offline',
            'prompt' => 'consent',
            'state' => $state,
        ]);

        return "https://accounts.google.com/o/oauth2/v2/auth?{$params}";
    }

    /**
     * Exchange authorization code for tokens and store/update the account.
     */
    public function exchangeCode(string $code, User $user): GoogleHealthAccount
    {
        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'code' => $code,
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri' => $this->redirectUri,
            'grant_type' => 'authorization_code',
        ]);

        if ($response->failed()) {
            throw new \RuntimeException('Failed to exchange authorization code: '.$response->body());
        }

        $data = $response->json();

        $googleUserId = $this->fetchGoogleUserId($data['access_token']);

        return GoogleHealthAccount::updateOrCreate(
            ['user_id' => $user->id],
            [
                'google_user_id' => $googleUserId,
                'access_token' => $data['access_token'],
                'refresh_token' => $data['refresh_token'] ?? null,
                'expires_at' => Carbon::now()->addSeconds($data['expires_in'] ?? 3600),
                'scope' => $data['scope'] ?? implode(' ', self::SCOPES),
            ]
        );
    }

    /**
     * Ensure the access token is valid, refreshing if necessary.
     */
    public function ensureValidToken(GoogleHealthAccount $account): string
    {
        if ($account->expires_at?->isFuture()) {
            return $account->access_token;
        }

        if (! $account->refresh_token) {
            throw new \RuntimeException('No refresh token available. Please re-authorize.');
        }

        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'refresh_token' => $account->refresh_token,
            'grant_type' => 'refresh_token',
        ]);

        if ($response->failed()) {
            throw new \RuntimeException('Failed to refresh token: '.$response->body());
        }

        $data = $response->json();

        $account->update([
            'access_token' => $data['access_token'],
            'expires_at' => Carbon::now()->addSeconds($data['expires_in'] ?? 3600),
        ]);

        return $data['access_token'];
    }

    /**
     * Fetch steps, active calories, and weight for a given date.
     *
     * @return array{steps: int, active_kcal: float, weight_kg: float|null}
     */
    public function fetchDayData(GoogleHealthAccount $account, CarbonInterface $date): array
    {
        $token = $this->ensureValidToken($account);

        $startOfDay = $date->copy()->startOfDay();
        $endOfDay = $date->copy()->endOfDay();

        $startNs = $startOfDay->timestamp * 1_000_000_000;
        $endNs = $endOfDay->timestamp * 1_000_000_000;

        $steps = $this->fetchDataSource($token, self::DATA_SOURCES['steps'], $startNs, $endNs);
        $activeCalories = $this->fetchDataSource($token, self::DATA_SOURCES['active_calories'], $startNs, $endNs);
        $weight = $this->fetchDataSource($token, self::DATA_SOURCES['weight'], $startNs, $endNs);

        return [
            'steps' => $this->aggregateSteps($steps),
            'active_kcal' => $this->aggregateCalories($activeCalories),
            'weight_kg' => $this->aggregateWeight($weight),
        ];
    }

    /**
     * Revoke the Google OAuth token and delete the account record.
     */
    public function disconnect(GoogleHealthAccount $account): void
    {
        $token = $this->ensureValidToken($account);

        Http::post('https://oauth2.googleapis.com/revoke', [
            'token' => $token,
        ]);

        $account->delete();
    }

    /**
     * Fetch a specific data source dataset from Google Health API.
     *
     * @return array<int, array{int, float}>
     */
    private function fetchDataSource(string $token, string $dataSourceId, string $startNs, string $endNs): array
    {
        $response = Http::withToken($token)
            ->post(self::BASE_URL."/dataSources/{$dataSourceId}/datasets/{$startNs}-{$endNs}", [
                'dataSourceId' => "raw:{$dataSourceId}",
                'maxEndTimeNs' => $endNs,
                'minStartTimeNs' => $startNs,
            ]);

        if ($response->failed()) {
            Log::warning('Google Health API fetch failed', [
                'data_source' => $dataSourceId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [];
        }

        $data = $response->json();
        $points = $data['point'] ?? [];

        $results = [];
        foreach ($points as $point) {
            $values = $point['value'] ?? [];
            foreach ($values as $value) {
                if (isset($value['fpVal'])) {
                    $results[] = [
                        (int) ($point['startTimeNanos'] ?? 0),
                        (float) $value['fpVal'],
                    ];
                } elseif (isset($value['intVal'])) {
                    $results[] = [
                        (int) ($point['startTimeNanos'] ?? 0),
                        (float) $value['intVal'],
                    ];
                }
            }
        }

        return $results;
    }

    /**
     * Aggregate step count values for the day.
     */
    private function aggregateSteps(array $points): int
    {
        return (int) array_sum(array_column($points, 1));
    }

    /**
     * Aggregate calories expended, subtracting BMR to get active calories.
     * Google returns total calories; we use a simple BMR estimate.
     */
    private function aggregateCalories(array $points): float
    {
        $total = array_sum(array_column($points, 1));

        // Subtract estimated BMR (rough 1 kcal/min baseline for active calories)
        $hours = count($points) > 0 ? count($points) / 2.0 : 0;

        return round(max(0.0, $total - ($hours * 60)), 1);
    }

    /**
     * Get the most recent weight reading for the day.
     */
    private function aggregateWeight(array $points): ?float
    {
        if ($points === []) {
            return null;
        }

        // Take the latest reading
        usort($points, fn ($a, $b) => $b[0] <=> $a[0]);

        return round($points[0][1], 2);
    }

    /**
     * Fetch the Google user ID from the userinfo endpoint.
     */
    private function fetchGoogleUserId(string $accessToken): string
    {
        $response = Http::withToken($accessToken)
            ->get('https://www.googleapis.com/oauth2/v3/userinfo');

        if ($response->failed()) {
            throw new \RuntimeException('Failed to fetch Google user info.');
        }

        return $response->json('sub', 'unknown');
    }
}
