import { useForm, Head } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import { ArrowLeft, ArrowRight, Check, Flame } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';
import { estimateTargets } from '@/lib/goal-math';
import { useI18n } from '@/lib/i18n';
import { OptionCard } from '@/components/goal/option-card';
import { ChipSelect } from '@/components/goal/chip-select';
import { NumberField } from '@/components/goal/number-field';
import { TargetsPreview } from '@/components/goal/targets-preview';
import { store as goalStore } from '@/routes/goal';
import type { ActivityLevel, GoalFormData } from '@/types/models';

const STEPS = ['sex', 'body', 'activity', 'goal', 'nutrition'] as const;

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

export default function Onboarding() {
    const { t } = useI18n();
    const [step, setStep] = useState(0);
    const currentStep = STEPS[step];

    const form = useForm<GoalFormData>({
        sex: '',
        birth_date: '',
        height_cm: '',
        activity_level: '',
        goal_type: '',
        weekly_rate_kg: '0.50',
        target_weight_kg: '',
        water_goal_ml: '2500',
        protein_pct: '30',
        carbs_pct: '40',
        fat_pct: '30',
        include_band_calories: false,
        initial_weight_kg: '',
    });

    const { data, setData, post, processing, errors } = form;

    const estimate = useMemo(() => estimateTargets(data), [data]);

    const canNext = useMemo(() => {
        switch (currentStep) {
            case 'sex':
                return !!data.sex && !!data.birth_date;
            case 'body':
                return !!data.height_cm && !!data.initial_weight_kg;
            case 'activity':
                return !!data.activity_level;
            case 'goal':
                return (
                    !!data.goal_type &&
                    (!data.goal_type || data.goal_type !== 'maintain'
                        ? !!data.weekly_rate_kg
                        : true)
                );
            case 'nutrition':
                return true;
            default:
                return false;
        }
    }, [currentStep, data]);

    const handleNext = () => {
        if (step < STEPS.length - 1) {
            setStep(step + 1);
        } else {
            post(goalStore().url);
        }
    };

    const handleBack = () => {
        if (step > 0) {
            setStep(step - 1);
        }
    };

    const applyPreset = (preset: string) => {
        const p = MACRO_PRESETS[preset];
        if (p) {
            setData('protein_pct', String(p.protein));
            setData('carbs_pct', String(p.carbs));
            setData('fat_pct', String(p.fat));
        }
    };

    return (
        <>
            <Head title={t('onboarding.title')} />
            <div className="from-background to-muted/30 flex min-h-dvh flex-col bg-gradient-to-b">
                <div className="mx-auto flex w-full max-w-xl flex-1 flex-col px-5 pt-10 pb-6">
                    {/* Header */}
                    <div className="mb-8 text-center">
                        <div className="bg-primary/10 text-primary mx-auto mb-4 flex size-14 items-center justify-center rounded-2xl">
                            <Flame className="size-7" />
                        </div>
                        <h1 className="text-2xl font-bold tracking-tight">
                            {t('onboarding.title')}
                        </h1>
                        <p className="text-muted-foreground mt-1 text-sm">
                            {t('onboarding.subtitle')}
                        </p>
                    </div>

                    {/* Step indicator */}
                    <div className="mb-6 flex items-center justify-center gap-2">
                        {STEPS.map((_, i) => (
                            <div
                                key={i}
                                className={cn(
                                    'h-1.5 rounded-full transition-all',
                                    i <= step
                                        ? 'bg-primary w-8'
                                        : 'bg-border w-4',
                                )}
                            />
                        ))}
                    </div>
                    <p className="text-muted-foreground mb-6 text-center text-xs font-medium">
                        {t('onboarding.stepOf', {
                            current: String(step + 1),
                            total: String(STEPS.length),
                        })}
                    </p>

                    {/* Step content */}
                    <div className="flex-1">
                        {currentStep === 'sex' && (
                            <div className="space-y-6">
                                <div>
                                    <h2 className="mb-1 text-lg font-semibold">
                                        {t('onboarding.sex')}
                                    </h2>
                                    <p className="text-muted-foreground text-sm">
                                        {t('onboarding.aboutYouSubtitle')}
                                    </p>
                                </div>
                                <div className="grid gap-3">
                                    <OptionCard
                                        selected={data.sex === 'male'}
                                        onSelect={() => setData('sex', 'male')}
                                        title={t('onboarding.male')}
                                    />
                                    <OptionCard
                                        selected={data.sex === 'female'}
                                        onSelect={() =>
                                            setData('sex', 'female')
                                        }
                                        title={t('onboarding.female')}
                                    />
                                </div>
                                <NumberField
                                    id="birth_date"
                                    label={t('onboarding.birthDate')}
                                    value={data.birth_date}
                                    onChange={(v) => setData('birth_date', v)}
                                    type="date"
                                />
                            </div>
                        )}

                        {currentStep === 'body' && (
                            <div className="space-y-6">
                                <div>
                                    <h2 className="mb-1 text-lg font-semibold">
                                        {t('onboarding.bodyTitle')}
                                    </h2>
                                    <p className="text-muted-foreground text-sm">
                                        {t('onboarding.bodySubtitle')}
                                    </p>
                                </div>
                                <NumberField
                                    id="height_cm"
                                    label={t('onboarding.height')}
                                    value={data.height_cm}
                                    onChange={(v) => setData('height_cm', v)}
                                    suffix={t('common.cm')}
                                    min={100}
                                    max={250}
                                    placeholder="170"
                                />
                                <NumberField
                                    id="initial_weight_kg"
                                    label={t('onboarding.currentWeight')}
                                    value={data.initial_weight_kg}
                                    onChange={(v) =>
                                        setData('initial_weight_kg', v)
                                    }
                                    suffix={t('common.kg')}
                                    min={30}
                                    max={300}
                                    placeholder="70"
                                />
                            </div>
                        )}

                        {currentStep === 'activity' && (
                            <div className="space-y-6">
                                <div>
                                    <h2 className="mb-1 text-lg font-semibold">
                                        {t('onboarding.activityTitle')}
                                    </h2>
                                    <p className="text-muted-foreground text-sm">
                                        {t('onboarding.activitySubtitle')}
                                    </p>
                                </div>
                                <div className="grid gap-3">
                                    {(
                                        [
                                            [
                                                'sedentary',
                                                t('onboarding.levelSedentary'),
                                                t(
                                                    'onboarding.levelSedentaryDesc',
                                                ),
                                            ],
                                            [
                                                'light',
                                                t('onboarding.levelLight'),
                                                t('onboarding.levelLightDesc'),
                                            ],
                                            [
                                                'moderate',
                                                t('onboarding.levelModerate'),
                                                t(
                                                    'onboarding.levelModerateDesc',
                                                ),
                                            ],
                                            [
                                                'active',
                                                t('onboarding.levelActive'),
                                                t('onboarding.levelActiveDesc'),
                                            ],
                                            [
                                                'very_active',
                                                t('onboarding.levelVeryActive'),
                                                t(
                                                    'onboarding.levelVeryActiveDesc',
                                                ),
                                            ],
                                        ] as const
                                    ).map(([value, title, desc]) => (
                                        <OptionCard
                                            key={value}
                                            selected={
                                                data.activity_level === value
                                            }
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

                        {currentStep === 'goal' && (
                            <div className="space-y-6">
                                <div>
                                    <h2 className="mb-1 text-lg font-semibold">
                                        {t('onboarding.goalTitle')}
                                    </h2>
                                    <p className="text-muted-foreground text-sm">
                                        {t('onboarding.goalSubtitle')}
                                    </p>
                                </div>
                                <div className="grid gap-3">
                                    <OptionCard
                                        selected={data.goal_type === 'lose'}
                                        onSelect={() =>
                                            setData('goal_type', 'lose')
                                        }
                                        title={t('onboarding.goalLose')}
                                        description={t(
                                            'onboarding.goalLoseDesc',
                                        )}
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
                                        description={t(
                                            'onboarding.goalGainDesc',
                                        )}
                                    />
                                </div>

                                {data.goal_type &&
                                    data.goal_type !== 'maintain' && (
                                        <div className="space-y-4">
                                            <div>
                                                <Label>
                                                    {t('onboarding.weeklyRate')}
                                                </Label>
                                                <ChipSelect
                                                    options={RATE_OPTIONS.map(
                                                        (r) => ({
                                                            value: r,
                                                            label: `${r} kg`,
                                                        }),
                                                    )}
                                                    value={data.weekly_rate_kg}
                                                    onChange={(v) =>
                                                        setData(
                                                            'weekly_rate_kg',
                                                            v,
                                                        )
                                                    }
                                                />
                                            </div>
                                            <NumberField
                                                id="target_weight_kg"
                                                label={t(
                                                    'onboarding.targetWeight',
                                                )}
                                                value={data.target_weight_kg}
                                                onChange={(v) =>
                                                    setData(
                                                        'target_weight_kg',
                                                        v,
                                                    )
                                                }
                                                suffix={t('common.kg')}
                                                min={30}
                                                max={300}
                                            />
                                        </div>
                                    )}
                            </div>
                        )}

                        {currentStep === 'nutrition' && (
                            <div className="space-y-6">
                                <div>
                                    <h2 className="mb-1 text-lg font-semibold">
                                        {t('onboarding.nutritionTitle')}
                                    </h2>
                                    <p className="text-muted-foreground text-sm">
                                        {t('onboarding.nutritionSubtitle')}
                                    </p>
                                </div>

                                <NumberField
                                    id="water_goal_ml"
                                    label={t('onboarding.waterGoal')}
                                    value={data.water_goal_ml}
                                    onChange={(v) =>
                                        setData('water_goal_ml', v)
                                    }
                                    suffix={t('common.ml')}
                                    min={500}
                                    max={10000}
                                    step="250"
                                />

                                <div>
                                    <Label>{t('onboarding.macroSplit')}</Label>
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
                                                label: t(
                                                    'onboarding.presetKeto',
                                                ),
                                            },
                                        ]}
                                        value={
                                            Object.keys(MACRO_PRESETS).find(
                                                (k) =>
                                                    MACRO_PRESETS[k].protein ===
                                                        Number(
                                                            data.protein_pct,
                                                        ) &&
                                                    MACRO_PRESETS[k].carbs ===
                                                        Number(
                                                            data.carbs_pct,
                                                        ) &&
                                                    MACRO_PRESETS[k].fat ===
                                                        Number(data.fat_pct),
                                            ) ?? ''
                                        }
                                        onChange={applyPreset}
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
                                        />
                                        <NumberField
                                            id="fat_pct"
                                            label={t('goals.fat')}
                                            value={data.fat_pct}
                                            onChange={(v) =>
                                                setData('fat_pct', v)
                                            }
                                            suffix="%"
                                            min={0}
                                            max={100}
                                        />
                                    </div>
                                </div>

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

                                {/* Live preview */}
                                <div>
                                    <Label className="mb-2">
                                        {t('onboarding.previewTitle')}
                                    </Label>
                                    <TargetsPreview
                                        estimate={estimate}
                                        hint={t('onboarding.previewHint')}
                                    />
                                    <p className="text-muted-foreground mt-2 text-center text-[11px]">
                                        {t('onboarding.previewNote')}
                                    </p>
                                </div>
                            </div>
                        )}
                    </div>

                    {Object.keys(errors).length > 0 && (
                        <div className="bg-destructive/10 text-destructive mb-4 rounded-lg p-3 text-sm">
                            {Object.values(errors).map((err, i) => (
                                <p key={i}>{err}</p>
                            ))}
                        </div>
                    )}

                    {/* Navigation */}
                    <div className="flex gap-3 pt-4">
                        {step > 0 && (
                            <Button
                                type="button"
                                variant="outline"
                                onClick={handleBack}
                                className="flex-1"
                            >
                                <ArrowLeft className="mr-2 size-4" />
                                {t('common.back')}
                            </Button>
                        )}
                        <Button
                            type="button"
                            onClick={handleNext}
                            disabled={!canNext || processing}
                            className="flex-1"
                        >
                            {step === STEPS.length - 1 ? (
                                <>
                                    {t('onboarding.finish')}
                                    <Check className="ml-2 size-4" />
                                </>
                            ) : (
                                <>
                                    {t('common.next')}
                                    <ArrowRight className="ml-2 size-4" />
                                </>
                            )}
                        </Button>
                    </div>
                </div>
            </div>
        </>
    );
}
