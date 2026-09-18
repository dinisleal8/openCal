<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Analyzes food photos through any OpenAI-compatible chat completions API.
 *
 * Defaults target OpenCode Zen / Go, but the base URL, key and model are
 * configurable, so it also works with OpenAI, OpenRouter, a local Ollama
 * server, and similar providers.
 */
class OpenAiCompatibleService implements FoodPhotoAnalyzer
{
    /**
     * OpenCode asks clients to identify themselves with their own user agent
     * instead of a generic SDK name, and to send a session id per conversation.
     */
    private const USER_AGENT = 'openCal/1.0 (self-hosted calorie tracker)';

    /**
     * @return array<int, array{name: string, calories: float, protein_g: float, carbs_g: float, fat_g: float, serving_description: string}>|null
     */
    public function analyzePhoto(string $filePath): ?array
    {
        $apiKey = config('services.opencode.key');

        if (empty($apiKey)) {
            Log::warning('OpenCode / OpenAI-compatible API key is not configured.');

            return null;
        }

        if (! file_exists($filePath)) {
            Log::warning('Photo file not found for AI analysis.', ['path' => $filePath]);

            return null;
        }

        $contents = file_get_contents($filePath);

        if ($contents === false) {
            Log::warning('Failed to read photo file for AI analysis.', ['path' => $filePath]);

            return null;
        }

        $imageData = base64_encode($contents);
        $mimeType = mime_content_type($filePath) ?: 'image/jpeg';
        $model = config('services.opencode.model', 'deepseek-v4-flash-vision-exp');
        $baseUrl = rtrim((string) config('services.opencode.base_url', 'https://opencode.ai/zen/go/v1'), '/');
        $maxTokens = (int) config('services.opencode.max_tokens', 8192);
        $timeout = (int) config('services.opencode.timeout', 120);

        try {
            $response = Http::timeout($timeout)
                ->withToken($apiKey)
                ->withUserAgent(self::USER_AGENT)
                ->withHeaders(['x-opencode-session' => (string) Str::uuid()])
                ->acceptJson()
                ->post("{$baseUrl}/chat/completions", [
                    'model' => $model,
                    'temperature' => 0.1,
                    'max_tokens' => $maxTokens,
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => [
                                ['type' => 'text', 'text' => FoodAnalysis::PROMPT],
                                [
                                    'type' => 'image_url',
                                    'image_url' => [
                                        'url' => "data:{$mimeType};base64,{$imageData}",
                                    ],
                                ],
                            ],
                        ],
                    ],
                ]);

            if (! $response->successful()) {
                Log::error('OpenAI-compatible API request failed.', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            $text = $this->extractText($response->json('choices.0.message.content'));

            if ($text === null) {
                Log::warning('AI provider returned no text content.', ['response' => $response->json()]);

                return null;
            }

            $parsed = FoodAnalysis::parse($text);

            if ($parsed === null) {
                Log::warning('AI provider returned unparseable content.', [
                    'model' => $model,
                    'text' => mb_substr($text, 0, 1000),
                ]);
            }

            return $parsed;
        } catch (\Throwable $e) {
            Log::error('AI photo analysis failed.', [
                'message' => $e->getMessage(),
                'file' => $filePath,
            ]);

            return null;
        }
    }

    /**
     * Normalize the message content, which may be a string or an array of parts.
     */
    private function extractText(mixed $content): ?string
    {
        if (is_string($content)) {
            return $content;
        }

        if (! is_array($content)) {
            return null;
        }

        $text = '';

        foreach ($content as $part) {
            if (is_array($part)) {
                $text .= $part['text'] ?? '';
            } elseif (is_string($part)) {
                $text .= $part;
            }
        }

        return $text === '' ? null : $text;
    }
}
