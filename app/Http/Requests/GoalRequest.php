<?php

namespace App\Http\Requests;

use App\Enums\ActivityLevel;
use App\Enums\GoalType;
use App\Enums\Sex;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class GoalRequest extends FormRequest
{
    /**
     * The goal always belongs to the authenticated user, so authentication
     * is sufficient authorization here.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('goal_type') === GoalType::Maintain->value) {
            $this->merge(['weekly_rate_kg' => 0]);
        }

        $this->merge(['include_band_calories' => $this->boolean('include_band_calories')]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'sex' => ['required', Rule::enum(Sex::class)],
            'birth_date' => [
                'required',
                'date_format:Y-m-d',
                'before_or_equal:'.today()->subYears(18)->toDateString(),
                'after:'.now()->subYears(100)->toDateString(),
            ],
            'height_cm' => ['required', 'integer', 'between:100,250'],
            'activity_level' => ['required', Rule::enum(ActivityLevel::class)],
            'goal_type' => ['required', Rule::enum(GoalType::class)],
            'weekly_rate_kg' => [
                Rule::when(
                    $this->input('goal_type') !== GoalType::Maintain->value,
                    ['required'],
                    ['nullable'],
                ),
                'numeric',
                Rule::when($this->input('goal_type') === GoalType::Maintain->value, ['in:0'], ['between:0.1,1.5']),
            ],
            'target_weight_kg' => ['nullable', 'numeric', 'between:30,300'],
            'water_goal_ml' => ['required', 'integer', 'between:500,10000'],
            'protein_pct' => ['required', 'integer', 'between:0,100'],
            'carbs_pct' => ['required', 'integer', 'between:0,100'],
            'fat_pct' => ['required', 'integer', 'between:0,100'],
            'include_band_calories' => ['boolean'],
            'initial_weight_kg' => [
                Rule::excludeIf(! $this->isMethod('post')),
                'required',
                'numeric',
                'between:30,300',
            ],
            'locale' => ['nullable', Rule::in(config('opencal.locales'))],
        ];
    }

    /**
     * @return array<callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->hasAny(['protein_pct', 'carbs_pct', 'fat_pct'])) {
                    return;
                }

                $sum = $this->integer('protein_pct') + $this->integer('carbs_pct') + $this->integer('fat_pct');

                if ($sum !== 100) {
                    $validator->errors()->add(
                        'protein_pct',
                        __('Macros must add up to 100% (currently :sum%).', ['sum' => $sum]),
                    );
                }
            },
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['goal_type', 'target_weight_kg', 'initial_weight_kg']) || $this->input('target_weight_kg') === null) {
                    return;
                }

                $weight = $this->isMethod('post')
                    ? $this->float('initial_weight_kg')
                    : $this->user()?->weighIns()->whereDate('date', '<=', today()->toDateString())->latest('date')->first()?->weight_kg;

                if ($weight === null) {
                    return;
                }

                $target = $this->float('target_weight_kg');

                if (($this->input('goal_type') === GoalType::Lose->value && $target >= (float) $weight)
                    || ($this->input('goal_type') === GoalType::Gain->value && $target <= (float) $weight)) {
                    $validator->errors()->add('target_weight_kg', __('Target weight must match your goal direction.'));
                }
            },
        ];
    }
}
