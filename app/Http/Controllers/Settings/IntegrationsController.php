<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class IntegrationsController extends Controller
{
    public function index(Request $request): Response
    {
        $account = $request->user()->googleHealthAccount;

        return Inertia::render('settings/integrations', [
            'googleHealthConnected' => $account !== null,
            'googleHealthLastSync' => $account?->last_synced_at?->diffForHumans(),
            'googleHealthError' => $account?->last_sync_error,
        ]);
    }
}
