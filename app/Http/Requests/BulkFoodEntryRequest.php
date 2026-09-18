<?php

namespace App\Http\Requests;

use App\Enums\MealType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkFoodEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'date' => ['required', 'date_format:Y-m-d'],
            'meal_type' => ['required', Rule::enum(MealType::class)],
            'photo_id' => ['nullable', 'integer', 'exists:photos,id'],
            'items' => ['required', 'array', 'min:1', 'max:30'],
            'items.*.name' => ['required', 'string', 'max:255'],
            'items.*.serving_description' => ['nullable', 'string', 'max:255'],
            'items.*.calories_kcal' => ['required', 'numeric', 'min:0', 'max:5000'],
            'items.*.protein_g' => ['required', 'numeric', 'min:0', 'max:1000'],
            'items.*.carbs_g' => ['required', 'numeric', 'min:0', 'max:1000'],
            'items.*.fat_g' => ['required', 'numeric', 'min:0', 'max:1000'],
        ];
    }
}
