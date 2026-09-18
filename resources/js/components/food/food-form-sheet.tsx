import { useForm } from '@inertiajs/react';
import { useRef, useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/input-error';
import { useI18n } from '@/lib/i18n';
import { store as foodStore, update as foodUpdate } from '@/routes/food';
import type { FoodEntry, MealType } from '@/types/models';

type Props = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    mealType: MealType;
    entry?: FoodEntry;
    date: string;
    frequentFoods?: Array<{
        name: string;
        calories_kcal: string;
        protein_g: string;
        carbs_g: string;
        fat_g: string;
        serving_description: string | null;
        times_logged: number;
    }>;
};

export function FoodFormSheet({
    open,
    onOpenChange,
    mealType,
    entry,
    date,
    frequentFoods = [],
}: Props) {
    const { t } = useI18n();
    const isEditing = !!entry;
    const fileRef = useRef<HTMLInputElement>(null);
    const [analyzing, setAnalyzing] = useState(false);
    const [aiSuggestion, setAiSuggestion] = useState<string | null>(null);

    const form = useForm({
        date,
        meal_type: mealType,
        name: entry?.name ?? '',
        serving_description: entry?.serving_description ?? '',
        calories_kcal: entry?.calories_kcal ?? '',
        protein_g: entry?.protein_g ?? '',
        carbs_g: entry?.carbs_g ?? '',
        fat_g: entry?.fat_g ?? '',
        photo_id: entry?.photo_id ? String(entry.photo_id) : '',
    });

    const { data, setData, post, put, processing, errors, reset } = form;

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        const opts = {
            onSuccess: () => {
                onOpenChange(false);
                reset();
            },
            preserveScroll: true,
        };

        if (isEditing) {
            put(foodUpdate(entry.id).url, opts);
        } else {
            post(foodStore().url, opts);
        }
    };

    const handlePhotoAnalysis = async (file: File) => {
        setAnalyzing(true);
        setAiSuggestion(null);
        try {
            const formData = new FormData();
            formData.append('photo', file);
            const response = await fetch('/api/photo/analyze', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-XSRF-TOKEN': decodeURIComponent(
                        document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] ?? '',
                    ),
                },
                body: formData,
            });
            const result = await response.json();
            if (result.photo_id) {
                setData('photo_id', String(result.photo_id));
            }
            if (result.analysis?.items?.length > 0) {
                const item = result.analysis.items[0];
                setData('name', item.name ?? data.name);
                setData(
                    'calories_kcal',
                    String(item.calories ?? data.calories_kcal),
                );
                setData('protein_g', String(item.protein_g ?? data.protein_g));
                setData('carbs_g', String(item.carbs_g ?? data.carbs_g));
                setData('fat_g', String(item.fat_g ?? data.fat_g));
                setData(
                    'serving_description',
                    item.serving_description ?? data.serving_description,
                );
                setAiSuggestion(t('food.aiSuggested'));
            }
        } catch {
            setAiSuggestion(t('food.aiFailed'));
        } finally {
            setAnalyzing(false);
        }
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>
                        {isEditing ? t('food.editTitle') : t('food.title')}
                    </DialogTitle>
                </DialogHeader>

                <form onSubmit={handleSubmit} className="space-y-4">
                    {/* Frequent foods */}
                    {frequentFoods.length > 0 && !isEditing && (
                        <div>
                            <p className="text-muted-foreground mb-1.5 text-xs font-medium">
                                {t('food.frequent')}
                            </p>
                            <div className="flex flex-wrap gap-1.5">
                                {frequentFoods.map((food, i) => (
                                    <button
                                        key={`${food.name}-${i}`}
                                        type="button"
                                        className="border-input bg-background hover:bg-accent hover:text-accent-foreground rounded-full border px-2.5 py-1 text-xs transition-colors"
                                        onClick={() => {
                                            setData('name', food.name);
                                            setData(
                                                'calories_kcal',
                                                food.calories_kcal,
                                            );
                                            setData(
                                                'protein_g',
                                                food.protein_g,
                                            );
                                            setData('carbs_g', food.carbs_g);
                                            setData('fat_g', food.fat_g);
                                            setData(
                                                'serving_description',
                                                food.serving_description ?? '',
                                            );
                                        }}
                                    >
                                        {food.name}
                                        <span className="text-muted-foreground ml-1">
                                            {Math.round(
                                                Number(food.calories_kcal),
                                            )}{' '}
                                            {t('common.kcal')}
                                        </span>
                                    </button>
                                ))}
                            </div>
                        </div>
                    )}

                    {/* Photo */}
                    <div>
                        <input
                            ref={fileRef}
                            type="file"
                            accept="image/*"
                            capture="environment"
                            className="hidden"
                            onChange={(e) => {
                                const file = e.target.files?.[0];
                                if (file) void handlePhotoAnalysis(file);
                            }}
                        />
                        <Button
                            type="button"
                            variant="outline"
                            className="w-full"
                            onClick={() => fileRef.current?.click()}
                            disabled={analyzing}
                        >
                            {analyzing
                                ? t('food.aiAnalyzing')
                                : t('food.addPhoto')}
                        </Button>
                        {aiSuggestion && (
                            <p className="text-primary mt-1 text-xs">
                                {aiSuggestion}
                            </p>
                        )}
                    </div>

                    <div>
                        <Label htmlFor="food-name">{t('food.name')}</Label>
                        <Input
                            id="food-name"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            placeholder={t('food.namePlaceholder')}
                            required
                        />
                        {errors.name && (
                            <p className="text-destructive text-xs">
                                {errors.name}
                            </p>
                        )}
                    </div>

                    <div>
                        <Label htmlFor="food-serving">
                            {t('food.serving')}
                        </Label>
                        <Input
                            id="food-serving"
                            value={data.serving_description}
                            onChange={(e) =>
                                setData('serving_description', e.target.value)
                            }
                            placeholder={t('food.servingPlaceholder')}
                        />
                        <InputError message={errors.serving_description} />
                    </div>

                    <div className="grid grid-cols-2 gap-3">
                        <div>
                            <Label htmlFor="food-cal">
                                {t('food.calories')}
                            </Label>
                            <Input
                                id="food-cal"
                                type="number"
                                inputMode="decimal"
                                value={data.calories_kcal}
                                onChange={(e) =>
                                    setData('calories_kcal', e.target.value)
                                }
                                min="0"
                                required
                            />
                            {errors.calories_kcal && (
                                <p className="text-destructive text-xs">
                                    {errors.calories_kcal}
                                </p>
                            )}
                        </div>
                        <div>
                            <Label htmlFor="food-protein">
                                {t('food.protein')}
                            </Label>
                            <Input
                                id="food-protein"
                                type="number"
                                inputMode="decimal"
                                value={data.protein_g}
                                onChange={(e) =>
                                    setData('protein_g', e.target.value)
                                }
                                min="0"
                                required
                            />
                            <InputError message={errors.protein_g} />
                        </div>
                    </div>

                    <div className="grid grid-cols-2 gap-3">
                        <div>
                            <Label htmlFor="food-carbs">
                                {t('food.carbs')}
                            </Label>
                            <Input
                                id="food-carbs"
                                type="number"
                                inputMode="decimal"
                                value={data.carbs_g}
                                onChange={(e) =>
                                    setData('carbs_g', e.target.value)
                                }
                                min="0"
                                required
                            />
                            <InputError message={errors.carbs_g} />
                        </div>
                        <div>
                            <Label htmlFor="food-fat">{t('food.fat')}</Label>
                            <Input
                                id="food-fat"
                                type="number"
                                inputMode="decimal"
                                value={data.fat_g}
                                onChange={(e) =>
                                    setData('fat_g', e.target.value)
                                }
                                min="0"
                                required
                            />
                            <InputError message={errors.fat_g} />
                        </div>
                    </div>

                    <Button
                        type="submit"
                        className="w-full"
                        disabled={processing}
                    >
                        {processing ? t('common.saving') : t('common.save')}
                    </Button>
                </form>
            </DialogContent>
        </Dialog>
    );
}
