<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\FoodPhotoAnalyzer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FoodAnalysisController extends Controller
{
    public function __construct(
        private readonly FoodPhotoAnalyzer $analyzer,
    ) {}

    public function analyzeText(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'description' => ['required', 'string', 'max:2000'],
        ]);

        return response()->json([
            'analysis' => $this->analyzer->analyzeText($validated['description']),
        ]);
    }
}
