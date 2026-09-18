import { router, useForm } from '@inertiajs/react';
import { Head } from '@inertiajs/react';
import {
    ArrowLeft,
    ArrowRight,
    Droplets,
    Dumbbell,
    Footprints,
    Pencil,
    Plus,
    Scale,
    Trash2,
    X,
} from 'lucide-react';
import { useEffect, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Skeleton } from '@/components/ui/skeleton';
import { useI18n } from '@/lib/i18n';
import { cn } from '@/lib/utils';
import { store as waterStore, destroy as waterDestroy } from '@/routes/water';
import { destroy as foodDestroy } from '@/routes/food';
import { destroy as exerciseDestroy } from '@/routes/exercise';
import type {
    ActivityDay,
    DailyTargets,
    ExerciseLog,
    FoodEntry,
    Goal,
    MealType,
    WaterLog,
    WeighIn,
} from '@/types/models';
import { FoodFormSheet } from '@/components/food/food-form-sheet';
import { ExerciseFormSheet } from '@/components/exercise/exercise-form-sheet';
import { WeightFormSheet } from '@/components/weight/weight-form-sheet';

type Summary = {
    eaten: number;
    protein: number;
    carbs: number;
    fat: number;
    water: number;
    exercise: number;
    remaining: number;
};

type Props = {
    date: string;
    goal: Goal;
    targets: DailyTargets;
    currentWeightKg: number | null;
    foodEntries: FoodEntry[];
    waterLogs: WaterLog[];
    exerciseLogs: ExerciseLog[];
    weightHistory: WeighIn[];
    activityDay: ActivityDay | null;
    summary: Summary;
    frequentFoods: Array<{
        name: string;
        calories_kcal: string;
        protein_g: string;
        carbs_g: string;
        fat_g: string;
        serving_description: string | null;
        times_logged: number;
    }>;
};

const MEALS: { type: MealType; labelKey: string }[] = [
    { type: 'breakfast', labelKey: 'dashboard.breakfast' },
    { type: 'lunch', labelKey: 'dashboard.lunch' },
    { type: 'dinner', labelKey: 'dashboard.dinner' },
    { type: 'snack', labelKey: 'dashboard.snack' },
];

const QUICK_WATER = [250, 500, 750];

function DashboardSkeleton() {
    return (
        <div className="mx-auto w-full max-w-xl px-4 pt-4 pb-6">
            <div className="mb-4 flex items-center justify-between">
                <Skeleton className="size-9 rounded-md" />
                <Skeleton className="h-5 w-32" />
                <Skeleton className="size-9 rounded-md" />
            </div>

            <div className="bg-card mb-4 rounded-2xl border p-4">
                <div className="flex items-center gap-4">
                    <Skeleton className="size-20 rounded-full" />
                    <div className="flex-1 space-y-2">
                        <div className="flex items-center justify-between">
                            <Skeleton className="h-4 w-16" />
                            <Skeleton className="h-4 w-20" />
                        </div>
                        <div className="flex items-center justify-between">
                            <Skeleton className="h-4 w-16" />
                            <Skeleton className="h-4 w-20" />
                        </div>
                        <div className="flex items-center justify-between">
                            <Skeleton className="h-4 w-24" />
                        </div>
                    </div>
                </div>
                <div className="mt-3 flex gap-2">
                    <div className="flex-1">
                        <Skeleton className="mb-1 h-3 w-12" />
                        <Skeleton className="h-1.5 rounded-full" />
                    </div>
                    <div className="flex-1">
                        <Skeleton className="mb-1 h-3 w-12" />
                        <Skeleton className="h-1.5 rounded-full" />
                    </div>
                    <div className="flex-1">
                        <Skeleton className="mb-1 h-3 w-12" />
                        <Skeleton className="h-1.5 rounded-full" />
                    </div>
                </div>
            </div>

            <div className="mb-4">
                <Skeleton className="mb-2 h-4 w-16" />
                <div className="space-y-3">
                    {[1, 2, 3, 4].map((i) => (
                        <div key={i} className="bg-card rounded-2xl border p-3">
                            <div className="mb-2 flex items-center justify-between">
                                <Skeleton className="h-4 w-20" />
                                <Skeleton className="h-7 w-20 rounded-md" />
                            </div>
                            <Skeleton className="h-4 w-full" />
                        </div>
                    ))}
                </div>
            </div>

            <div className="bg-card mb-4 rounded-2xl border p-4">
                <div className="mb-2 flex items-center justify-between">
                    <Skeleton className="h-4 w-20" />
                    <Skeleton className="h-3 w-24" />
                </div>
                <Skeleton className="mb-3 h-2 rounded-full" />
                <div className="flex gap-2">
                    <Skeleton className="h-8 flex-1 rounded-md" />
                    <Skeleton className="h-8 flex-1 rounded-md" />
                    <Skeleton className="h-8 flex-1 rounded-md" />
                </div>
            </div>

            <div className="bg-card mb-4 rounded-2xl border p-4">
                <div className="mb-2 flex items-center justify-between">
                    <Skeleton className="h-4 w-20" />
                    <Skeleton className="h-7 w-24 rounded-md" />
                </div>
                <Skeleton className="h-8 w-28" />
                <Skeleton className="mt-2 h-12 w-full" />
            </div>

            <div className="bg-card mb-4 rounded-2xl border p-4">
                <div className="mb-2 flex items-center justify-between">
                    <Skeleton className="h-4 w-24" />
                    <Skeleton className="h-7 w-28 rounded-md" />
                </div>
                <Skeleton className="h-4 w-full" />
                <Skeleton className="mt-1 h-4 w-3/4" />
            </div>
        </div>
    );
}

export default function Dashboard({
    date,
    goal: _goal,
    targets,
    currentWeightKg,
    foodEntries,
    waterLogs,
    exerciseLogs,
    weightHistory,
    activityDay,
    summary,
    frequentFoods,
}: Props) {
    const { t } = useI18n();
    const [isNavigating, setIsNavigating] = useState(false);

    useEffect(() => {
        const unsubscribeStart = router.on('start', () =>
            setIsNavigating(true),
        );
        const unsubscribeFinish = router.on('finish', () =>
            setIsNavigating(false),
        );
        return () => {
            unsubscribeStart();
            unsubscribeFinish();
        };
    }, []);
    const [foodSheet, setFoodSheet] = useState<{
        open: boolean;
        mealType: MealType;
        entry?: FoodEntry;
    }>({
        open: false,
        mealType: 'breakfast',
    });
    const [exerciseSheet, setExerciseSheet] = useState<{
        open: boolean;
        entry?: ExerciseLog;
    }>({ open: false });
    const [weightSheet, setWeightSheet] = useState(false);

    const waterForm = useForm({ date, amount_ml: 250 });
    const navigateDate = (offset: number) => {
        const d = new Date(date + 'T00:00:00');
        d.setDate(d.getDate() + offset);
        router.get(
            `/dashboard?date=${d.toISOString().split('T')[0]}`,
            {},
            { preserveScroll: true },
        );
    };

    const isToday = date === new Date().toISOString().split('T')[0];

    const caloriesPct =
        targets.calorie_target > 0
            ? Math.min((summary.eaten / targets.calorie_target) * 100, 100)
            : 0;
    const waterPct =
        targets.water_goal_ml > 0
            ? Math.min((summary.water / targets.water_goal_ml) * 100, 100)
            : 0;

    const groupedFood = MEALS.map((meal) => ({
        ...meal,
        entries: foodEntries.filter((e) => e.meal_type === meal.type),
    }));

    const addFood = (mealType: MealType) =>
        setFoodSheet({ open: true, mealType });
    const editFood = (entry: FoodEntry) =>
        setFoodSheet({ open: true, mealType: entry.meal_type, entry });

    const deleteFood = (id: number) =>
        router.delete(foodDestroy(id).url, { preserveScroll: true });

    const deleteWater = (id: number) =>
        router.delete(waterDestroy(id).url, { preserveScroll: true });

    const addExercise = () => setExerciseSheet({ open: true });
    const editExercise = (entry: ExerciseLog) =>
        setExerciseSheet({ open: true, entry });
    const deleteExercise = (id: number) =>
        router.delete(exerciseDestroy(id).url, { preserveScroll: true });

    const quickAddWater = (ml: number) => {
        waterForm.setData('amount_ml', ml);
        waterForm.post(waterStore().url, { preserveScroll: true });
    };

    return (
        <>
            <Head title={t('dashboard.title')} />
            {isNavigating ? (
                <DashboardSkeleton />
            ) : (
                <div className="mx-auto w-full max-w-xl px-4 pt-4 pb-6">
                    {/* Date nav */}
                    <div className="mb-4 flex items-center justify-between">
                        <Button
                            variant="ghost"
                            size="icon"
                            onClick={() => navigateDate(-1)}
                        >
                            <ArrowLeft className="size-4" />
                        </Button>
                        <h1 className="text-lg font-bold">
                            {isToday
                                ? t('common.today')
                                : new Date(
                                      date + 'T00:00:00',
                                  ).toLocaleDateString(undefined, {
                                      weekday: 'short',
                                      month: 'short',
                                      day: 'numeric',
                                  })}
                        </h1>
                        <Button
                            variant="ghost"
                            size="icon"
                            onClick={() => navigateDate(1)}
                            disabled={isToday}
                        >
                            <ArrowRight className="size-4" />
                        </Button>
                    </div>

                    {/* Calorie ring */}
                    <div className="bg-card mb-4 rounded-2xl border p-4">
                        <div className="flex items-center gap-4">
                            <div className="relative size-20">
                                <svg
                                    viewBox="0 0 36 36"
                                    className="size-full -rotate-90"
                                >
                                    <circle
                                        cx="18"
                                        cy="18"
                                        r="15.5"
                                        fill="none"
                                        className="stroke-muted"
                                        strokeWidth="3"
                                    />
                                    <circle
                                        cx="18"
                                        cy="18"
                                        r="15.5"
                                        fill="none"
                                        className="stroke-primary"
                                        strokeWidth="3"
                                        strokeDasharray={`${caloriesPct} 100`}
                                        strokeLinecap="round"
                                    />
                                </svg>
                                <div className="absolute inset-0 flex flex-col items-center justify-center">
                                    <span className="text-muted-foreground text-xs">
                                        {t('dashboard.remaining')}
                                    </span>
                                    <span className="text-sm font-bold">
                                        {summary.remaining}
                                    </span>
                                </div>
                            </div>
                            <div className="flex-1 space-y-1">
                                <div className="flex items-center justify-between text-sm">
                                    <span className="text-muted-foreground">
                                        {t('dashboard.eaten')}
                                    </span>
                                    <span className="font-medium">
                                        {summary.eaten}{' '}
                                        <span className="text-muted-foreground text-xs">
                                            {t('common.kcal')}
                                        </span>
                                    </span>
                                </div>
                                <div className="flex items-center justify-between text-sm">
                                    <span className="text-muted-foreground">
                                        {t('dashboard.burned')}
                                    </span>
                                    <span className="font-medium">
                                        {summary.exercise}{' '}
                                        <span className="text-muted-foreground text-xs">
                                            {t('common.kcal')}
                                        </span>
                                    </span>
                                </div>
                                <div className="flex items-center justify-between text-sm">
                                    <span className="text-muted-foreground">
                                        {t('dashboard.goalOf', {
                                            target: String(
                                                targets.calorie_target,
                                            ),
                                        })}
                                    </span>
                                </div>
                            </div>
                        </div>
                        {/* Macro bar */}
                        <div className="mt-3 flex gap-2">
                            {[
                                {
                                    label: t('goals.protein'),
                                    value: summary.protein,
                                    target: targets.protein_g,
                                    color: 'bg-blue-500',
                                },
                                {
                                    label: t('goals.carbs'),
                                    value: summary.carbs,
                                    target: targets.carbs_g,
                                    color: 'bg-amber-500',
                                },
                                {
                                    label: t('goals.fat'),
                                    value: summary.fat,
                                    target: targets.fat_g,
                                    color: 'bg-rose-500',
                                },
                            ].map((m) => (
                                <div key={m.label} className="flex-1">
                                    <div className="flex items-center justify-between text-xs">
                                        <span className="text-muted-foreground">
                                            {m.label}
                                        </span>
                                        <span>
                                            {m.value}/{m.target}
                                            {t('common.g')}
                                        </span>
                                    </div>
                                    <div className="bg-muted mt-1 h-1.5 rounded-full">
                                        <div
                                            className={cn(
                                                'h-full rounded-full',
                                                m.color,
                                            )}
                                            style={{
                                                width: `${m.target > 0 ? Math.min((m.value / m.target) * 100, 100) : 0}%`,
                                            }}
                                        />
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>

                    {/* Meals */}
                    <div className="mb-4">
                        <h2 className="mb-2 text-sm font-semibold">
                            {t('dashboard.meals')}
                        </h2>
                        <div className="space-y-3">
                            {groupedFood.map((meal) => (
                                <div
                                    key={meal.type}
                                    className="bg-card rounded-2xl border p-3"
                                >
                                    <div className="mb-2 flex items-center justify-between">
                                        <span className="text-sm font-medium">
                                            {t(meal.labelKey as any)}
                                        </span>
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            className="h-7 gap-1 text-xs"
                                            onClick={() => addFood(meal.type)}
                                        >
                                            <Plus className="size-3" />{' '}
                                            {t('dashboard.addFood')}
                                        </Button>
                                    </div>
                                    {meal.entries.length === 0 ? (
                                        <p className="text-muted-foreground py-1 text-xs">
                                            {t('dashboard.noFoodYet')}
                                        </p>
                                    ) : (
                                        <div className="space-y-1">
                                            {meal.entries.map((entry) => (
                                                <div
                                                    key={entry.id}
                                                    className="hover:bg-muted/50 flex cursor-pointer items-center justify-between rounded-lg px-2 py-1.5 transition-colors"
                                                    onClick={() =>
                                                        editFood(entry)
                                                    }
                                                >
                                                    <div className="flex min-w-0 flex-1 items-center gap-2">
                                                        {entry.photo && (
                                                            <img
                                                                src={
                                                                    entry.photo
                                                                        .url
                                                                }
                                                                alt=""
                                                                className="size-8 rounded object-cover"
                                                            />
                                                        )}
                                                        <span className="truncate text-sm">
                                                            {entry.name}
                                                        </span>
                                                    </div>
                                                    <span className="ml-2 flex shrink-0 items-center gap-1 text-xs tabular-nums">
                                                        <span>
                                                            {Math.round(
                                                                Number(
                                                                    entry.calories_kcal,
                                                                ),
                                                            )}{' '}
                                                            {t('common.kcal')}
                                                        </span>
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            className="size-6"
                                                            onClick={(e) => {
                                                                e.stopPropagation();
                                                                deleteFood(
                                                                    entry.id,
                                                                );
                                                            }}
                                                        >
                                                            <Trash2 className="text-muted-foreground hover:text-destructive size-3" />
                                                        </Button>
                                                    </span>
                                                </div>
                                            ))}
                                        </div>
                                    )}
                                </div>
                            ))}
                        </div>
                    </div>

                    {/* Water */}
                    <div className="bg-card mb-4 rounded-2xl border p-4">
                        <div className="mb-2 flex items-center justify-between">
                            <div className="flex items-center gap-2">
                                <Droplets className="size-4 text-blue-500" />
                                <span className="text-sm font-semibold">
                                    {t('dashboard.water')}
                                </span>
                            </div>
                            <span className="text-xs tabular-nums">
                                {summary.water} / {targets.water_goal_ml}{' '}
                                {t('common.ml')}
                            </span>
                        </div>
                        <div className="bg-muted mb-3 h-2 rounded-full">
                            <div
                                className="h-full rounded-full bg-blue-500 transition-all"
                                style={{ width: `${waterPct}%` }}
                            />
                        </div>
                        <div className="flex gap-2">
                            {QUICK_WATER.map((ml) => (
                                <Button
                                    key={ml}
                                    variant="outline"
                                    size="sm"
                                    className="flex-1"
                                    onClick={() => quickAddWater(ml)}
                                >
                                    +{ml} {t('common.ml')}
                                </Button>
                            ))}
                        </div>
                        {waterLogs.length > 0 && (
                            <div className="mt-2 flex flex-wrap gap-1">
                                {waterLogs.map((log) => (
                                    <span
                                        key={log.id}
                                        className="flex items-center gap-1 rounded-full bg-blue-50 px-2 py-0.5 text-[10px] text-blue-700"
                                    >
                                        {log.amount_ml} ml
                                        <button
                                            onClick={() => deleteWater(log.id)}
                                            className="hover:text-destructive ml-0.5 rounded-full p-0.5"
                                        >
                                            <X className="size-2.5" />
                                        </button>
                                    </span>
                                ))}
                            </div>
                        )}
                    </div>

                    {/* Weight */}
                    <div className="bg-card mb-4 rounded-2xl border p-4">
                        <div className="mb-2 flex items-center justify-between">
                            <div className="flex items-center gap-2">
                                <Scale className="size-4 text-violet-500" />
                                <span className="text-sm font-semibold">
                                    {t('dashboard.weight')}
                                </span>
                            </div>
                            <Button
                                variant="ghost"
                                size="sm"
                                className="h-7 gap-1 text-xs"
                                onClick={() => setWeightSheet(true)}
                            >
                                <Pencil className="size-3" />{' '}
                                {t('dashboard.logWeight')}
                            </Button>
                        </div>
                        {currentWeightKg ? (
                            <p className="text-2xl font-bold tabular-nums">
                                {currentWeightKg}{' '}
                                <span className="text-muted-foreground text-sm font-normal">
                                    {t('common.kg')}
                                </span>
                            </p>
                        ) : (
                            <p className="text-muted-foreground text-xs">
                                {t('goals.noWeighIn')}
                            </p>
                        )}
                        {weightHistory.length >= 2 &&
                            (() => {
                                const weights = weightHistory.map((w) =>
                                    Number(w.weight_kg),
                                );
                                const min = Math.min(...weights) - 1;
                                const max = Math.max(...weights) + 1;
                                const range = max - min || 1;
                                const width = 200;
                                const height = 48;
                                const points = weights.map((w, i) => {
                                    const x =
                                        (i / (weights.length - 1)) * width;
                                    const y =
                                        height - ((w - min) / range) * height;
                                    return `${x},${y}`;
                                });
                                const lastX = width;
                                const lastY =
                                    height -
                                    ((weights[weights.length - 1] - min) /
                                        range) *
                                        height;
                                return (
                                    <svg
                                        viewBox={`0 0 ${width} ${height}`}
                                        className="mt-2 w-full"
                                        style={{ height: 48 }}
                                        preserveAspectRatio="none"
                                    >
                                        <polyline
                                            points={points.join(' ')}
                                            fill="none"
                                            className="stroke-primary"
                                            strokeWidth="2"
                                        />
                                        <circle
                                            cx={lastX}
                                            cy={lastY}
                                            r="3"
                                            className="fill-primary"
                                        />
                                    </svg>
                                );
                            })()}
                        <p className="text-muted-foreground mt-1 text-xs">
                            {t('dashboard.updateWeightViaForm')}
                        </p>
                    </div>

                    {/* Exercise */}
                    <div className="bg-card mb-4 rounded-2xl border p-4">
                        <div className="mb-2 flex items-center justify-between">
                            <div className="flex items-center gap-2">
                                <Dumbbell className="size-4 text-orange-500" />
                                <span className="text-sm font-semibold">
                                    {t('dashboard.exercise')}
                                </span>
                            </div>
                            <Button
                                variant="ghost"
                                size="sm"
                                className="h-7 gap-1 text-xs"
                                onClick={() => addExercise()}
                            >
                                <Plus className="size-3" />{' '}
                                {t('dashboard.addExercise')}
                            </Button>
                        </div>
                        {exerciseLogs.length === 0 ? (
                            <p className="text-muted-foreground text-xs">
                                {t('dashboard.noExercise')}
                            </p>
                        ) : (
                            <div className="space-y-1">
                                {exerciseLogs.map((log) => (
                                    <div
                                        key={log.id}
                                        className="flex items-center justify-between rounded-lg px-2 py-1.5"
                                    >
                                        <span className="text-sm">
                                            {log.name}
                                        </span>
                                        <span className="flex items-center gap-1 text-xs tabular-nums">
                                            {Math.round(
                                                Number(log.calories_kcal),
                                            )}{' '}
                                            {t('common.kcal')}
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                className="size-6"
                                                onClick={() =>
                                                    editExercise(log)
                                                }
                                            >
                                                <Pencil className="text-muted-foreground hover:text-foreground size-3" />
                                            </Button>
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                className="size-6"
                                                onClick={() =>
                                                    deleteExercise(log.id)
                                                }
                                            >
                                                <Trash2 className="text-muted-foreground hover:text-destructive size-3" />
                                            </Button>
                                        </span>
                                    </div>
                                ))}
                            </div>
                        )}
                    </div>

                    {/* Band stats */}
                    {activityDay && (
                        <div className="bg-card mb-4 rounded-2xl border p-4">
                            <div className="mb-2 flex items-center gap-2">
                                <Footprints className="size-4 text-teal-500" />
                                <span className="text-sm font-semibold">
                                    {t('dashboard.bandStats')}
                                </span>
                            </div>
                            <div className="grid grid-cols-2 gap-3">
                                <div className="text-center">
                                    <p className="text-lg font-bold tabular-nums">
                                        {Number(
                                            activityDay.steps,
                                        ).toLocaleString()}
                                    </p>
                                    <p className="text-muted-foreground text-xs">
                                        {t('common.steps')}
                                    </p>
                                </div>
                                <div className="text-center">
                                    <p className="text-lg font-bold tabular-nums">
                                        {Math.round(
                                            Number(activityDay.active_kcal),
                                        )}
                                    </p>
                                    <p className="text-muted-foreground text-xs">
                                        {t('dashboard.burned')}{' '}
                                        {t('common.kcal')}
                                    </p>
                                </div>
                            </div>
                        </div>
                    )}

                    {/* FAB - Add actions */}
                    <div className="fixed right-4 bottom-24 z-30 flex flex-col gap-2">
                        <Button
                            size="icon"
                            className="size-12 rounded-full shadow-lg"
                            onClick={() => addFood('breakfast')}
                        >
                            <Plus className="size-5" />
                        </Button>
                    </div>
                </div>
            )}

            <FoodFormSheet
                open={foodSheet.open}
                onOpenChange={(open) => setFoodSheet((s) => ({ ...s, open }))}
                mealType={foodSheet.mealType}
                entry={foodSheet.entry}
                date={date}
                frequentFoods={frequentFoods}
            />
            <ExerciseFormSheet
                open={exerciseSheet.open}
                onOpenChange={(open) =>
                    setExerciseSheet((s) => ({ ...s, open }))
                }
                date={date}
                entry={exerciseSheet.entry}
            />
            <WeightFormSheet
                open={weightSheet}
                onOpenChange={setWeightSheet}
                date={date}
                currentWeightKg={currentWeightKg}
            />
        </>
    );
}
