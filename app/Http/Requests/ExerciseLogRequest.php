<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ExerciseLogRequest extends FormRequest
{
    /**
     * Ownership of an existing log is enforced through the ExerciseLogPolicy;
     * storing only needs an authenticated user because the log is always
     * attached to the authenticated user.
     */
    public function authorize(): bool
    {
        return $this->route('exerciseLog') === null
            || $this->user()->can('update', $this->route('exerciseLog'));
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
            'name' => ['required', 'string', 'max:255'],
            'duration_min' => ['nullable', 'integer', 'between:1,1440'],
            'calories_kcal' => ['required', 'numeric', 'min:0', 'max:5000'],
        ];
    }
}
