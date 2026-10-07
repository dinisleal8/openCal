<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class InstanceController extends Controller
{
    /**
     * Public endpoint that lets a client validate a self-hosted instance URL
     * and read basic branding/version info before authenticating.
     */
    public function show(): JsonResponse
    {
        return response()->json([
            'name' => config('app.name'),
            'version' => config('opencal.version'),
        ]);
    }
}
