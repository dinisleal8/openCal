import type { ActivityLevel, GoalFormData, Sex } from '@/types/models';

export const ACTIVITY_MULTIPLIERS: Record<ActivityLevel, number> = {
    sedentary: 1.2,
    light: 1.375,
    moderate: 1.55,
    active: 1.725,
    very_active: 1.9,
};

export const KCAL_PER_KG = 7700;
export const MIN_CALORIES: Record<Sex, number> = {
    male: 1500,
    female: 1200,
};
export const MAX_SURPLUS_KCAL = 1000;

export type TargetEstimate = {
    bmr: number;
    tdee: number;
    calorieTarget: number;
    proteinG: number;
    carbsG: number;
    fatG: number;
};

function ageFrom(birthDate: string): number | null {
    const birth = new Date(birthDate);
    if (Number.isNaN(birth.getTime())) {
        return null;
    }

    const now = new Date();
    let age = now.getFullYear() - birth.getFullYear();
    const monthDiff = now.getMonth() - birth.getMonth();
    if (monthDiff < 0 || (monthDiff === 0 && now.getDate() < birth.getDate())) {
        age -= 1;
    }

    return age;
}

/**
 * Client-side mirror of App\Services\DailyTargetsService (Mifflin-St Jeor),
 * used to preview targets while the user fills the goal form.
 */
export function estimateTargets(form: GoalFormData): TargetEstimate | null {
    const height = Number(form.height_cm);
    const weight =
        Number(form.initial_weight_kg) || Number(form.target_weight_kg) || 0;
    const age = ageFrom(form.birth_date);

    if (
        !form.sex ||
        !form.activity_level ||
        !form.goal_type ||
        !height ||
        !weight ||
        age === null ||
        age <= 0
    ) {
        return null;
    }

    const base = 10 * weight + 6.25 * height - 5 * age;
    const bmr = form.sex === 'male' ? base + 5 : base - 161;
    const tdee = bmr * ACTIVITY_MULTIPLIERS[form.activity_level];

    const rate = Number(form.weekly_rate_kg) || 0;
    const delta =
        form.goal_type === 'lose'
            ? (-rate * KCAL_PER_KG) / 7
            : form.goal_type === 'gain'
              ? (rate * KCAL_PER_KG) / 7
              : 0;

    const calorieTarget = Math.round(
        Math.max(
            MIN_CALORIES[form.sex],
            Math.min(tdee + delta, tdee + MAX_SURPLUS_KCAL),
        ),
    );

    const proteinPct = Number(form.protein_pct) || 0;
    const carbsPct = Number(form.carbs_pct) || 0;
    const fatPct = Number(form.fat_pct) || 0;

    return {
        bmr: Math.round(bmr),
        tdee: Math.round(tdee),
        calorieTarget,
        proteinG: Math.round((calorieTarget * proteinPct) / 100 / 4),
        carbsG: Math.round((calorieTarget * carbsPct) / 100 / 4),
        fatG: Math.round((calorieTarget * fatPct) / 100 / 9),
    };
}
