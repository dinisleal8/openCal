<?php

namespace App\Http\Requests;

use App\Enums\MealType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FoodEntryRequest extends FormRequest
{
    /**
     * Ownership of an existing entry is enforced through the FoodEntryPolicy;
     * storing only needs an authenticated user because the entry is always
     * attached to the authenticated user.
     */
    public function authorize(): bool
    {
        return $this->route('foodEntry') === null
            || $this->user()->can('update', $this->route('foodEntry'));
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
            'name' => ['required', 'string', 'max:255'],
            'serving_description' => ['nullable', 'string', 'max:255'],
            'calories_kcal' => ['required', 'numeric', 'min:0', 'max:5000'],
            'protein_g' => ['required', 'numeric', 'min:0', 'max:1000'],
            'carbs_g' => ['required', 'numeric', 'min:0', 'max:1000'],
            'fat_g' => ['required', 'numeric', 'min:0', 'max:1000'],
            'photo_id' => ['nullable', 'integer', 'exists:photos,id'],
        ];
    }
}
