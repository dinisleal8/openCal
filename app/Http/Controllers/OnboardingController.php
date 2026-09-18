<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OnboardingController extends Controller
{
    /**
     * Show the onboarding wizard for users without a goal yet.
     */
    public function create(Request $request): Response|RedirectResponse
    {
        if ($request->user()->goal !== null) {
            return redirect()->route('dashboard');
        }

        return Inertia::render('onboarding');
    }
}
