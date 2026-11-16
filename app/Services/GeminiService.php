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

        return $this->analyze([
            ['text' => FoodAnalysis::PROMPT],
            [
                'inline_data' => [
                    'mime_type' => $mimeType,
                    'data' => $imageData,
                ],
            ],
        ]);
    }

    public function analyzeText(string $description): ?array
    {
        return $this->analyze([
            ['text' => FoodAnalysis::TEXT_PROMPT."\n\nDescription: ".$description],
        ]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $parts
     * @return array<int, array{name: string, calories: float, protein_g: float, carbs_g: float, fat_g: float, serving_description: string}>|null
     */
    private function analyze(array $parts): ?array
    {
        $apiKey = config('services.gemini.key');

        if (empty($apiKey)) {
            Log::warning('Gemini API key is not configured.');

            return null;
        }

        $model = config('services.gemini.model', 'gemini-3.5-flash-lite');
        $baseUrl = config('services.gemini.base_url', 'https://generativelanguage.googleapis.com/v1beta');

        try {
            $response = Http::timeout((int) config('services.gemini.timeout', 120))
                ->post("{$baseUrl}/models/{$model}:generateContent?key={$apiKey}", [
                    'contents' => [
                        [
                            'parts' => $parts,
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
                    'model' => $model,
                    'body' => $response->body(),
                ]);

                return null;
            }

            $json = $response->json();
            $candidate = is_array($json) ? ($json['candidates'][0] ?? []) : [];
            $finishReason = $candidate['finishReason'] ?? null;

            // Gemini 3 may return thought parts alongside (or before) the answer,
            // so every non-thought part has to be considered instead of only the first.
            $text = collect($candidate['content']['parts'] ?? [])
                ->reject(fn ($part) => ! empty($part['thought']))
                ->pluck('text')
                ->filter(fn ($chunk) => is_string($chunk) && trim($chunk) !== '')
                ->implode("\n");

            if (trim($text) === '') {
                Log::warning('Gemini returned no text content.', [
                    'finish_reason' => $finishReason,
                    'block_reason' => $json['promptFeedback']['blockReason'] ?? null,
                    'model' => $model,
                    'response' => $json,
                ]);

                return null;
            }

            $parsed = FoodAnalysis::parse($text);

            if ($parsed === null) {
                Log::warning('Gemini returned unparseable content.', [
                    'finish_reason' => $finishReason,
                    'text' => mb_substr($text, 0, 1000),
                ]);
            }

            return $parsed;
        } catch (\Throwable $e) {
            Log::error('Gemini analysis failed.', [
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
