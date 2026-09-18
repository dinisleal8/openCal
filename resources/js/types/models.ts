export type Sex = 'male' | 'female';

export type ActivityLevel =
    | 'sedentary'
    | 'light'
    | 'moderate'
    | 'active'
    | 'very_active';

export type GoalType = 'lose' | 'maintain' | 'gain';

export type MealType = 'breakfast' | 'lunch' | 'dinner' | 'snack';

export type FoodSource = 'manual' | 'ai_photo' | 'barcode';

export type ExerciseSource = 'manual' | 'google_health';

export type Goal = {
    id: number;
    user_id: number;
    sex: Sex;
    birth_date: string;
    height_cm: number;
    activity_level: ActivityLevel;
    goal_type: GoalType;
    weekly_rate_kg: string;
    target_weight_kg: string | null;
    water_goal_ml: number;
    protein_pct: number;
    carbs_pct: number;
    fat_pct: number;
    include_band_calories: boolean;
    created_at: string;
    updated_at: string;
};

export type DailyTargets = {
    bmr: number;
    tdee: number;
    calorie_target: number;
    protein_g: number;
    carbs_g: number;
    fat_g: number;
    water_goal_ml: number;
};

export type GoalFormData = {
    sex: Sex | '';
    birth_date: string;
    height_cm: string;
    activity_level: ActivityLevel | '';
    goal_type: GoalType | '';
    weekly_rate_kg: string;
    target_weight_kg: string;
    water_goal_ml: string;
    protein_pct: string;
    carbs_pct: string;
    fat_pct: string;
    include_band_calories: boolean;
    initial_weight_kg: string;
    locale?: string;
};

export type Photo = {
    id: number;
    user_id: number;
    path: string;
    url: string;
    mime: string;
    created_at: string;
};

export type FoodEntry = {
    id: number;
    user_id: number;
    photo_id: number | null;
    photo?: Photo | null;
    date: string;
    meal_type: MealType;
    name: string;
    serving_description: string | null;
    calories_kcal: string;
    protein_g: string;
    carbs_g: string;
    fat_g: string;
    source: FoodSource;
    barcode: string | null;
    created_at: string;
};

export type WeighIn = {
    id: number;
    date: string;
    weight_kg: string;
};

export type WaterLog = {
    id: number;
    logged_at: string;
    amount_ml: number;
};

export type ExerciseLog = {
    id: number;
    date: string;
    name: string;
    duration_min: number | null;
    calories_kcal: string;
    source: ExerciseSource;
};

export type ActivityDay = {
    id: number;
    date: string;
    steps: number;
    active_kcal: string;
    bmr_kcal: string;
    distance_m: number;
    synced_at: string | null;
};
