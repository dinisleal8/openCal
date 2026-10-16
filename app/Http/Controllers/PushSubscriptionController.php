<?php

namespace App\Http\Controllers;

use App\Http\Requests\PushSubscriptionDeleteRequest;
use App\Http\Requests\PushSubscriptionRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class PushSubscriptionController extends Controller
{
    /**
     * Register (or refresh) a browser push subscription.
     */
    public function store(PushSubscriptionRequest $request): RedirectResponse
    {
        $request->user()->updatePushSubscription(
            $request->validated('endpoint'),
            $request->validated('keys.pwh'),
            $request->validated('keys.auth'),
            $request->validated('content_encoding'),
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('notifications.pushEnabled'),
        ]);

        return back();
    }

    /**
     * Remove a browser push subscription.
     */
    public function destroy(PushSubscriptionDeleteRequest $request): RedirectResponse
    {
        $request->user()->deletePushSubscription(
            $request->validated('endpoint'),
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('notifications.pushDisabled'),
        ]);

        return back();
    }
}
