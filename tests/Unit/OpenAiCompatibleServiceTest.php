<?php

use App\Services\OpenAiCompatibleService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

uses(TestCase::class);

function makeOpenCodePhotoFile(): string
{
    $path = tempnam(sys_get_temp_dir(), 'opencal_ai_');

    file_put_contents($path, 'fake-image-bytes');

    return $path;
}

test('it sends the image and parses an openai-compatible response', function () {
    config()->set('services.opencode.key', 'opencode-test-key');
    config()->set('services.opencode.model', 'deepseek-v4-flash-vision-exp');
    config()->set('services.opencode.base_url', 'https://opencode.ai/zen/go/v1');
    config()->set('services.opencode.max_tokens', 8192);

    Http::fake([
        'opencode.ai/zen/go/v1/chat/completions' => Http::response([
            'choices' => [
                [
                    'message' => [
                        'content' => '[{"name":"Sushi","calories":320,"protein_g":12,"carbs_g":48,"fat_g":8,"serving_description":"6 pieces"}]',
                    ],
                ],
            ],
        ]),
    ]);

    $path = makeOpenCodePhotoFile();

    $items = (new OpenAiCompatibleService)->analyzePhoto($path);

    expect($items)->toBeArray()->toHaveCount(1)
        ->and($items[0]['name'])->toBe('Sushi')
        ->and($items[0]['calories'])->toBe(320.0);

    Http::assertSent(function ($request) {
        $body = $request->data();

        return $request->url() === 'https://opencode.ai/zen/go/v1/chat/completions'
            && $request->hasHeader('Authorization', 'Bearer opencode-test-key')
            && $request->hasHeader('x-opencode-session')
            && str_contains($request->header('User-Agent')[0] ?? '', 'openCal/1.0')
            && $body['model'] === 'deepseek-v4-flash-vision-exp'
            && $body['max_tokens'] === 8192
            && $body['messages'][0]['content'][1]['type'] === 'image_url'
            && str_starts_with($body['messages'][0]['content'][1]['image_url']['url'], 'data:')
            && str_contains($body['messages'][0]['content'][1]['image_url']['url'], 'base64,');
    });

    @unlink($path);
});

test('it handles content returned as an array of parts', function () {
    config()->set('services.opencode.key', 'opencode-test-key');

    Http::fake([
        'opencode.ai/*' => Http::response([
            'choices' => [
                [
                    'message' => [
                        'content' => [
                            ['type' => 'text', 'text' => '[{"name":"Apple","calories":95}]'],
                        ],
                    ],
                ],
            ],
        ]),
    ]);

    $path = makeOpenCodePhotoFile();

    $items = (new OpenAiCompatibleService)->analyzePhoto($path);

    expect($items)->toBeArray()->toHaveCount(1)
        ->and($items[0]['name'])->toBe('Apple');

    @unlink($path);
});

test('it returns null when the api key is missing', function () {
    config()->set('services.opencode.key', null);

    expect((new OpenAiCompatibleService)->analyzePhoto(__FILE__))->toBeNull();
});

test('it returns null when the provider responds with an error', function () {
    config()->set('services.opencode.key', 'opencode-test-key');

    Http::fake([
        'opencode.ai/*' => Http::response('unauthorized', 401),
    ]);

    $path = makeOpenCodePhotoFile();

    expect((new OpenAiCompatibleService)->analyzePhoto($path))->toBeNull();

    @unlink($path);
});
