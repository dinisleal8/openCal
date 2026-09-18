<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class WeighInRequest extends FormRequest
{
    /**
     * Ownership of an existing entry is enforced through the WeighInPolicy;
     * storing only needs an authenticated user because the entry is always
     * attached to the authenticated user.
     */
    public function authorize(): bool
    {
        return true;
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
            'weight_kg' => ['required', 'numeric', 'between:30,300'],
        ];
    }
}
