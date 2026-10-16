<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'locale' => app()->getLocale(),
            'auth' => [
                'user' => $request->user(),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'notifications' => fn (): ?array => $this->notifications($request),
            'push' => [
                'enabled' => filled(config('webpush.vapid.public_key')),
                'publicKey' => config('webpush.vapid.public_key'),
            ],
        ];
    }

    /**
     * Recent notifications and unread count for the authenticated user.
     *
     * @return array{unread_count: int, items: array<int, array<string, mixed>>}|null
     */
    protected function notifications(Request $request): ?array
    {
        $user = $request->user();

        if ($user === null) {
            return null;
        }

        return [
            'unread_count' => $user->unreadNotifications()->count(),
            'items' => $user->notifications()
                ->latest()
                ->limit(15)
                ->get()
                ->map(fn ($notification): array => [
                    'id' => $notification->id,
                    'type' => $notification->data['type'] ?? null,
                    'title' => $notification->data['title'] ?? '',
                    'body' => $notification->data['body'] ?? '',
                    'url' => $notification->data['url'] ?? '/',
                    'read_at' => $notification->read_at?->toIso8601String(),
                    'created_at' => $notification->created_at?->toIso8601String(),
                    'created_at_diff' => $notification->created_at?->diffForHumans(),
                ])
                ->all(),
        ];
    }
}
