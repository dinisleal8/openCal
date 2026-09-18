<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'barcode' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'brand' => ['nullable', 'string', 'max:255'],
            'calories_kcal_per_100g' => ['required', 'numeric', 'min:0'],
            'protein_g_per_100g' => ['required', 'numeric', 'min:0'],
            'carbs_g_per_100g' => ['required', 'numeric', 'min:0'],
            'fat_g_per_100g' => ['required', 'numeric', 'min:0'],
            'serving_description' => ['nullable', 'string', 'max:255'],
        ];
    }
}
