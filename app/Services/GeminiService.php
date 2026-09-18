<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService implements FoodPhotoAnalyzer
{
    /**
     * Analyze a food photo using Google Gemini and return structured nutrition data.
     *
     * @return array<int, array{name: string, calories: float, protein_g: float, carbs_g: float, fat_g: float, serving_description: string}>|null
     */
    public function analyzePhoto(string $filePath): ?array
    {
        $apiKey = config('services.gemini.key');

        if (empty($apiKey)) {
            Log::warning('Gemini API key is not configured.');

            return null;
        }

        if (! file_exists($filePath)) {
            Log::warning('Photo file not found for Gemini analysis.', ['path' => $filePath]);

            return null;
        }

        $contents = file_get_contents($filePath);

        if ($contents === false) {
            Log::warning('Failed to read photo file for Gemini analysis.', ['path' => $filePath]);

            return null;
        }

        $imageData = base64_encode($contents);
        $mimeType = mime_content_type($filePath) ?: 'image/jpeg';
        $model = config('services.gemini.model', 'gemini-2.5-flash');
        $baseUrl = config('services.gemini.base_url', 'https://generativelanguage.googleapis.com/v1beta');

        try {
            $response = Http::timeout((int) config('services.gemini.timeout', 120))
                ->post("{$baseUrl}/models/{$model}:generateContent?key={$apiKey}", [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => FoodAnalysis::PROMPT],
                                [
                                    'inline_data' => [
                                        'mime_type' => $mimeType,
                                        'data' => $imageData,
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'generationConfig' => [
                        'temperature' => 0.1,
                        'maxOutputTokens' => (int) config('services.gemini.max_tokens', 8192),
                    ],
                ]);

            if (! $response->successful()) {
                Log::error('Gemini API request failed.', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            $text = $response->json('candidates.0.content.parts.0.text');

            if ($text === null) {
                Log::warning('Gemini returned no text content.', ['response' => $response->json()]);

                return null;
            }

            $parsed = FoodAnalysis::parse($text);

            if ($parsed === null) {
                Log::warning('Gemini returned unparseable content.', [
                    'text' => mb_substr($text, 0, 1000),
                ]);
            }

            return $parsed;
        } catch (\Throwable $e) {
            Log::error('Gemini analysis failed.', [
                'message' => $e->getMessage(),
                'file' => $filePath,
            ]);

            return null;
        }
    }
}
