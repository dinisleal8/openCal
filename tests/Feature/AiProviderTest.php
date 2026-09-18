<?php

use App\Services\FoodPhotoAnalyzer;
use App\Services\GeminiService;
use App\Services\OpenAiCompatibleService;

test('it resolves the gemini analyzer by default', function () {
    config()->set('opencal.ai.provider', 'gemini');

    expect(app(FoodPhotoAnalyzer::class))->toBeInstanceOf(GeminiService::class);
});

test('it resolves the opencode analyzer when configured', function () {
    config()->set('opencal.ai.provider', 'opencode');

    expect(app(FoodPhotoAnalyzer::class))->toBeInstanceOf(OpenAiCompatibleService::class);
});
