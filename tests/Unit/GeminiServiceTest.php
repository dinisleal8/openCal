<?php

use App\Services\GeminiService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

uses(TestCase::class);

function makePhotoFile(): string
{
    $path = tempnam(sys_get_temp_dir(), 'opencal_meal_');

    file_put_contents($path, 'fake-image-bytes');

    return $path;
}

test('it parses the gemini response into nutrition items', function () {
    config()->set('services.gemini.key', 'test-key');
    config()->set('services.gemini.model', 'gemini-2.5-flash');

    Http::fake([
        'generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            [
                                'text' => '[{"name":"Margherita Pizza","calories":285,"protein_g":12,"carbs_g":36,"fat_g":10,"serving_description":"1 slice"}]',
                            ],
                        ],
                    ],
                ],
            ],
        ]),
    ]);

    $path = makePhotoFile();

    $items = (new GeminiService)->analyzePhoto($path);

    expect($items)->toBeArray()->toHaveCount(1)
        ->and($items[0]['name'])->toBe('Margherita Pizza')
        ->and($items[0]['calories'])->toBe(285.0)
        ->and($items[0]['protein_g'])->toBe(12.0);

    Http::assertSent(fn ($request) => str_contains($request->url(), 'generativelanguage.googleapis.com')
        && str_contains($request->url(), 'gemini-2.5-flash'));

    expect(config('services.gemini.key'))->toBe('test-key');

    @unlink($path);
});

test('it strips markdown fences from the response', function () {
    config()->set('services.gemini.key', 'test-key');

    Http::fake([
        'generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            [
                                'text' => "```json\n[{\"name\":\"Apple\",\"calories\":95,\"protein_g\":0.5,\"carbs_g\":25,\"fat_g\":0.3,\"serving_description\":\"1 medium\"}]\n```",
                            ],
                        ],
                    ],
                ],
            ],
        ]),
    ]);

    $path = makePhotoFile();

    $items = (new GeminiService)->analyzePhoto($path);

    expect($items)->toBeArray()->toHaveCount(1)
        ->and($items[0]['name'])->toBe('Apple');

    @unlink($path);
});

test('it returns null when the api key is missing', function () {
    config()->set('services.gemini.key', null);

    expect((new GeminiService)->analyzePhoto(__FILE__))->toBeNull();
});

test('it returns null when gemini responds with an error', function () {
    config()->set('services.gemini.key', 'test-key');

    Http::fake([
        'generativelanguage.googleapis.com/*' => Http::response('rate limited', 429),
    ]);

    $path = makePhotoFile();

    expect((new GeminiService)->analyzePhoto($path))->toBeNull();

    @unlink($path);
});
