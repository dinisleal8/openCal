<?php

namespace App\Services;

interface FoodPhotoAnalyzer
{
    /**
     * Analyze a food photo and return structured nutrition items.
     *
     * @return array<int, array{name: string, calories: float, protein_g: float, carbs_g: float, fat_g: float, serving_description: string}>|null
     */
    public function analyzePhoto(string $filePath): ?array;
}
