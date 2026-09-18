<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
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

        $prompt = <<<'PROMPT'
Analyze this food photo. For each distinct food item visible, provide:
- name: the food name
- calories: estimated calories (number)
- protein_g: estimated protein in grams (number)
- carbs_g: estimated carbohydrates in grams (number)
- fat_g: estimated fat in grams (number)
- serving_description: a brief description of the estimated serving size

Return ONLY a valid JSON array of objects with these exact keys. Do not include markdown, backticks, or any text outside the JSON array. If you cannot identify any food, return an empty array [].
PROMPT;

        try {
            $response = Http::timeout(30)
                ->post("{$baseUrl}/models/{$model}:generateContent?key={$apiKey}", [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt],
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
                        'maxOutputTokens' => 2048,
                    ],
                ]);

            if (! $response->successful()) {
                Log::error('Gemini API request failed.', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            $body = $response->json();
            $text = $body['candidates'][0]['content']['parts'][0]['text'] ?? null;

            if ($text === null) {
                Log::warning('Gemini returned no text content.', ['response' => $body]);

                return null;
            }

            $text = trim($text);
            $text = preg_replace('/^```json\s*/i', '', $text);
            $text = preg_replace('/```\s*$/', '', $text);

            $items = json_decode($text, true, 512, JSON_THROW_ON_ERROR);

            if (! is_array($items)) {
                return null;
            }

            return array_map(function (array $item): array {
                return [
                    'name' => $item['name'] ?? 'Unknown',
                    'calories' => (float) ($item['calories'] ?? 0),
                    'protein_g' => (float) ($item['protein_g'] ?? 0),
                    'carbs_g' => (float) ($item['carbs_g'] ?? 0),
                    'fat_g' => (float) ($item['fat_g'] ?? 0),
                    'serving_description' => $item['serving_description'] ?? '',
                ];
            }, $items);
        } catch (\Throwable $e) {
            Log::error('Gemini analysis failed.', [
                'message' => $e->getMessage(),
                'file' => $filePath,
            ]);

            return null;
        }
    }
}
