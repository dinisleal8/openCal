<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class WaterLogRequest extends FormRequest
{
    /**
     * Ownership of an existing log is enforced through the WaterLogPolicy;
     * storing only needs an authenticated user because the log is always
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
            'amount_ml' => ['required', 'integer', 'between:1,2000'],
        ];
    }
}
