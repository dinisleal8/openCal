import { useForm, Head } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import { Flame } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { useI18n } from '@/lib/i18n';
import { estimateTargets } from '@/lib/goal-math';
import { OptionCard } from '@/components/goal/option-card';
import { ChipSelect } from '@/components/goal/chip-select';
import { NumberField } from '@/components/goal/number-field';
import { TargetsPreview } from '@/components/goal/targets-preview';
import { update as goalUpdate } from '@/routes/goal';
import type { ActivityLevel, Goal, GoalFormData } from '@/types/models';

type Props = {
    goal: Goal;
    targets: {
        calorie_target: number;
        protein_g: number;
        carbs_g: number;
        fat_g: number;
        water_goal_ml: number;
        bmr: number;
        tdee: number;
    };
    currentWeightKg: number | null;
};

const MACRO_PRESETS: Record<
    string,
    { protein: number; carbs: number; fat: number }
> = {
    balanced: { protein: 30, carbs: 40, fat: 30 },
    protein: { protein: 40, carbs: 30, fat: 30 },
    lowcarb: { protein: 35, carbs: 25, fat: 40 },
    keto: { protein: 25, carbs: 5, fat: 70 },
};

const RATE_OPTIONS = ['0.25', '0.50', '0.75', '1.00'] as const;

type Section = 'body' | 'activity' | 'goal' | 'nutrition';

export default function GoalEdit({
    goal,
    targets: _targets,
    currentWeightKg,
}: Props) {
    const { t } = useI18n();
    const [activeSection, setActiveSection] = useState<Section>('body');

    const form = useForm<GoalFormData>({
        sex: goal.sex,
        birth_date: goal.birth_date.split('T')[0],
        height_cm: String(goal.height_cm),
        activity_level: goal.activity_level,
        goal_type: goal.goal_type,
        weekly_rate_kg: goal.weekly_rate_kg,
        target_weight_kg: goal.target_weight_kg ?? '',
        water_goal_ml: String(goal.water_goal_ml),
        protein_pct: String(goal.protein_pct),
        carbs_pct: String(goal.carbs_pct),
        fat_pct: String(goal.fat_pct),
        include_band_calories: goal.include_band_calories,
        initial_weight_kg: '',
    });

    const { data, setData, put, processing, errors } = form;

    const previewEstimate = useMemo(() => {
        const tempData: GoalFormData = {
            sex: data.sex,
            birth_date: data.birth_date,
            height_cm: data.height_cm,
            activity_level: data.activity_level,
            goal_type: data.goal_type,
            weekly_rate_kg: data.weekly_rate_kg,
            target_weight_kg: data.target_weight_kg,
            water_goal_ml: data.water_goal_ml,
            protein_pct: data.protein_pct,
            carbs_pct: data.carbs_pct,
            fat_pct: data.fat_pct,
            include_band_calories: data.include_band_calories,
            initial_weight_kg: currentWeightKg ? String(currentWeightKg) : '',
        };
        return estimateTargets(tempData);
    }, [data, currentWeightKg]);

    const applyPreset = (preset: string) => {
        const p = MACRO_PRESETS[preset];
        if (p) {
            setData('protein_pct', String(p.protein));
            setData('carbs_pct', String(p.carbs));
            setData('fat_pct', String(p.fat));
        }
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        put(goalUpdate().url);
    };

    const sections: { key: Section; label: string }[] = [
        { key: 'body', label: t('goals.sectionBody') },
        { key: 'activity', label: t('goals.sectionActivity') },
        { key: 'goal', label: t('goals.sectionGoal') },
        { key: 'nutrition', label: t('goals.sectionNutrition') },
    ];

    return (
        <>
            <Head title={t('goals.title')} />
            <div className="mx-auto w-full max-w-xl px-5 py-6">
                <div className="mb-6">
                    <div className="flex items-center gap-3">
                        <span className="bg-primary/10 text-primary flex size-10 items-center justify-center rounded-xl">
                            <Flame className="size-5" />
                        </span>
                        <div>
                            <h1 className="text-xl font-bold">
                                {t('goals.title')}
                            </h1>
                            <p className="text-muted-foreground text-sm">
                                {t('goals.description')}
                            </p>
                        </div>
                    </div>
                </div>

                {/* Section tabs */}
                <div className="bg-muted/50 mb-6 flex rounded-xl p-1">
                    {sections.map(({ key, label }) => (
                        <button
                            key={key}
                            type="button"
                            onClick={() => setActiveSection(key)}
                            className={`flex-1 rounded-lg px-3 py-2 text-sm font-medium transition-colors ${
                                activeSection === key
                                    ? 'bg-background text-foreground shadow-sm'
                                    : 'text-muted-foreground hover:text-foreground'
                            }`}
                        >
                            {label}
                        </button>
                    ))}
                </div>

                <form onSubmit={handleSubmit} className="space-y-6">
                    {activeSection === 'body' && (
                        <div className="space-y-4">
                            <NumberField
                                id="height_cm"
                                label={t('onboarding.height')}
                                value={data.height_cm}
                                onChange={(v) => setData('height_cm', v)}
                                suffix={t('common.cm')}
                                min={100}
                                max={250}
                                error={errors.height_cm}
                            />
                            <NumberField
                                id="target_weight_kg"
                                label={t('onboarding.currentWeight')}
                                value={
                                    currentWeightKg
                                        ? String(currentWeightKg)
                                        : ''
                                }
                                onChange={() => {}}
                                suffix={t('common.kg')}
                                placeholder={t('goals.noWeighIn')}
                            />
                            <NumberField
                                id="target_weight_kg_goal"
                                label={t('onboarding.targetWeight')}
                                value={data.target_weight_kg}
                                onChange={(v) => setData('target_weight_kg', v)}
                                suffix={t('common.kg')}
                                min={30}
                                max={300}
                                error={errors.target_weight_kg}
                            />
                            <NumberField
                                id="water_goal_ml"
                                label={t('goals.water')}
                                value={data.water_goal_ml}
                                onChange={(v) => setData('water_goal_ml', v)}
                                suffix={t('common.ml')}
                                min={500}
                                max={10000}
                                step="250"
                                error={errors.water_goal_ml}
                            />
                        </div>
                    )}

                    {activeSection === 'activity' && (
                        <div className="space-y-4">
                            <div className="grid gap-3">
                                {(
                                    [
                                        [
                                            'sedentary',
                                            t('onboarding.levelSedentary'),
                                            t('onboarding.levelSedentaryDesc'),
                                        ],
                                        [
                                            'light',
                                            t('onboarding.levelLight'),
                                            t('onboarding.levelLightDesc'),
                                        ],
                                        [
                                            'moderate',
                                            t('onboarding.levelModerate'),
                                            t('onboarding.levelModerateDesc'),
                                        ],
                                        [
                                            'active',
                                            t('onboarding.levelActive'),
                                            t('onboarding.levelActiveDesc'),
                                        ],
                                        [
                                            'very_active',
                                            t('onboarding.levelVeryActive'),
                                            t('onboarding.levelVeryActiveDesc'),
                                        ],
                                    ] as const
                                ).map(([value, title, desc]) => (
                                    <OptionCard
                                        key={value}
                                        selected={data.activity_level === value}
                                        onSelect={() =>
                                            setData(
                                                'activity_level',
                                                value as ActivityLevel,
                                            )
                                        }
                                        title={title}
                                        description={desc}
                                    />
                                ))}
                            </div>
                        </div>
                    )}

                    {activeSection === 'goal' && (
                        <div className="space-y-4">
                            <div className="grid gap-3">
                                <OptionCard
                                    selected={data.goal_type === 'lose'}
                                    onSelect={() =>
                                        setData('goal_type', 'lose')
                                    }
                                    title={t('onboarding.goalLose')}
                                    description={t('onboarding.goalLoseDesc')}
                                />
                                <OptionCard
                                    selected={data.goal_type === 'maintain'}
                                    onSelect={() =>
                                        setData('goal_type', 'maintain')
                                    }
                                    title={t('onboarding.goalMaintain')}
                                    description={t(
                                        'onboarding.goalMaintainDesc',
                                    )}
                                />
                                <OptionCard
                                    selected={data.goal_type === 'gain'}
                                    onSelect={() =>
                                        setData('goal_type', 'gain')
                                    }
                                    title={t('onboarding.goalGain')}
                                    description={t('onboarding.goalGainDesc')}
                                />
                            </div>

                            {data.goal_type &&
                                data.goal_type !== 'maintain' && (
                                    <div className="space-y-4">
                                        <div>
                                            <span className="text-sm font-medium">
                                                {t('onboarding.weeklyRate')}
                                            </span>
                                            <ChipSelect
                                                options={RATE_OPTIONS.map(
                                                    (r) => ({
                                                        value: r,
                                                        label: `${r} kg`,
                                                    }),
                                                )}
                                                value={data.weekly_rate_kg}
                                                onChange={(v) =>
                                                    setData('weekly_rate_kg', v)
                                                }
                                                className="mt-2"
                                            />
                                            {errors.weekly_rate_kg && (
                                                <p className="text-destructive mt-1 text-xs">
                                                    {errors.weekly_rate_kg}
                                                </p>
                                            )}
                                        </div>
                                    </div>
                                )}
                            <label className="flex items-start gap-3 rounded-xl border p-3.5">
                                <input
                                    type="checkbox"
                                    checked={data.include_band_calories}
                                    onChange={(e) =>
                                        setData(
                                            'include_band_calories',
                                            e.target.checked,
                                        )
                                    }
                                    className="border-primary mt-0.5"
                                />
                                <span>
                                    <span className="block text-sm font-medium">
                                        {t('onboarding.bandCalories')}
                                    </span>
                                    <span className="text-muted-foreground mt-0.5 block text-xs leading-snug">
                                        {t('onboarding.bandCaloriesDesc')}
                                    </span>
                                </span>
                            </label>
                        </div>
                    )}

                    {activeSection === 'nutrition' && (
                        <div className="space-y-4">
                            <div>
                                <span className="text-sm font-medium">
                                    {t('onboarding.macroSplit')}
                                </span>
                                <ChipSelect
                                    options={[
                                        {
                                            value: 'balanced',
                                            label: t(
                                                'onboarding.presetBalanced',
                                            ),
                                        },
                                        {
                                            value: 'protein',
                                            label: t(
                                                'onboarding.presetProtein',
                                            ),
                                        },
                                        {
                                            value: 'lowcarb',
                                            label: t(
                                                'onboarding.presetLowcarb',
                                            ),
                                        },
                                        {
                                            value: 'keto',
                                            label: t('onboarding.presetKeto'),
                                        },
                                    ]}
                                    value={
                                        Object.keys(MACRO_PRESETS).find(
                                            (k) =>
                                                MACRO_PRESETS[k].protein ===
                                                    Number(data.protein_pct) &&
                                                MACRO_PRESETS[k].carbs ===
                                                    Number(data.carbs_pct) &&
                                                MACRO_PRESETS[k].fat ===
                                                    Number(data.fat_pct),
                                        ) ?? ''
                                    }
                                    onChange={applyPreset}
                                    className="mt-2"
                                />
                                <div className="mt-3 grid grid-cols-3 gap-3">
                                    <NumberField
                                        id="protein_pct"
                                        label={t('goals.protein')}
                                        value={data.protein_pct}
                                        onChange={(v) =>
                                            setData('protein_pct', v)
                                        }
                                        suffix="%"
                                        min={0}
                                        max={100}
                                        error={errors.protein_pct}
                                    />
                                    <NumberField
                                        id="carbs_pct"
                                        label={t('goals.carbs')}
                                        value={data.carbs_pct}
                                        onChange={(v) =>
                                            setData('carbs_pct', v)
                                        }
                                        suffix="%"
                                        min={0}
                                        max={100}
                                        error={errors.carbs_pct}
                                    />
                                    <NumberField
                                        id="fat_pct"
                                        label={t('goals.fat')}
                                        value={data.fat_pct}
                                        onChange={(v) => setData('fat_pct', v)}
                                        suffix="%"
                                        min={0}
                                        max={100}
                                        error={errors.fat_pct}
                                    />
                                </div>
                            </div>
                        </div>
                    )}

                    {/* Live targets preview */}
                    <TargetsPreview
                        estimate={previewEstimate}
                        hint={t('onboarding.previewHint')}
                        compact
                    />

                    {Object.keys(errors).length > 0 && (
                        <div className="bg-destructive/10 text-destructive rounded-lg p-3 text-sm">
                            {Object.entries(errors).map(([key, err]) => (
                                <p key={key}>{err}</p>
                            ))}
                        </div>
                    )}

                    <Button
                        type="submit"
                        disabled={processing}
                        className="w-full"
                    >
                        {processing ? t('common.saving') : t('common.save')}
                    </Button>
                </form>
            </div>
        </>
    );
}
