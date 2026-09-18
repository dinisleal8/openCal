<?php

namespace App\Services;

class FoodAnalysis
{
    public const PROMPT = <<<'PROMPT'
Analyze this food photo. For each distinct food item visible, provide:
- name: the food name
- calories: estimated calories (number)
- protein_g: estimated protein in grams (number)
- carbs_g: estimated carbohydrates in grams (number)
- fat_g: estimated fat in grams (number)
- serving_description: a brief description of the estimated serving size

Return ONLY a valid JSON array of objects with these exact keys. Do not include markdown, backticks, or any text outside the JSON array. If you cannot identify any food, return an empty array [].
PROMPT;

    /**
     * Parse a model response into normalized nutrition items.
     *
     * Tolerates markdown fences, surrounding prose, a single item object, and
     * an {"items": [...]} wrapper.
     *
     * @return array<int, array{name: string, calories: float, protein_g: float, carbs_g: float, fat_g: float, serving_description: string}>|null
     */
    public static function parse(?string $text): ?array
    {
        if ($text === null) {
            return null;
        }

        $text = trim($text);
        $text = (string) preg_replace('/^```(?:json)?\s*/i', '', $text);
        $text = (string) preg_replace('/```\s*$/', '', $text);
        $text = trim($text);

        if ($text === '') {
            return null;
        }

        $decoded = self::decode($text);

        if ($decoded === null) {
            $decoded = self::extractJson($text);
        }

        if (! is_array($decoded)) {
            return null;
        }

        if (isset($decoded['items']) && is_array($decoded['items'])) {
            $decoded = $decoded['items'];
        } elseif (isset($decoded['name'])) {
            $decoded = [$decoded];
        }

        if ($decoded === []) {
            return [];
        }

        $items = array_values(array_filter($decoded, is_array(...)));

        if ($items === []) {
            return null;
        }

        return array_map(static function (array $item): array {
            return [
                'name' => $item['name'] ?? 'Unknown',
                'calories' => (float) ($item['calories'] ?? 0),
                'protein_g' => (float) ($item['protein_g'] ?? 0),
                'carbs_g' => (float) ($item['carbs_g'] ?? 0),
                'fat_g' => (float) ($item['fat_g'] ?? 0),
                'serving_description' => $item['serving_description'] ?? '',
            ];
        }, $items);
    }

    /**
     * @return array<mixed>|null
     */
    private static function decode(string $text): ?array
    {
        try {
            $decoded = json_decode($text, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * @return array<mixed>|null
     */
    private static function extractJson(string $text): ?array
    {
        if (preg_match('/\[.*\]/s', $text, $matches) === 1) {
            $decoded = self::decode($matches[0]);

            if ($decoded !== null) {
                return $decoded;
            }
        }

        if (preg_match('/\{.*\}/s', $text, $matches) === 1) {
            return self::decode($matches[0]);
        }

        return null;
    }
}
